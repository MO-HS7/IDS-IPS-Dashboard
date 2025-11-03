<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';
import PacketTable from '@/Components/LiveMonitoring/PacketTable.vue';
import TrafficChart from '@/Components/LiveMonitoring/TrafficChart.vue';
import StatisticsDisplay from '@/Components/LiveMonitoring/StatisticsDisplay.vue';

// Get current page props
const page = usePage();

const props = defineProps({
    activeSessions: Array,
    interfaces: Array,
});

// State
const isMonitoring = ref(false);
const selectedInterface = ref('');
const currentSession = ref(null);
const packets = ref([]);
const packetIds = ref(new Set()); // Track unique packet IDs to prevent duplicates
const statistics = ref({
    packets_per_second: 0,
    bytes_per_second: 0,
    protocols: {},
    threat_percentage: 0,
});
const loading = ref(false);
const error = ref(null);
const pollingInterval = ref(null);
const maxPackets = 1000; // Maximum packets to keep in memory
const pollingDelay = 2000; // Poll every 2 seconds
const filterProtocol = ref('all'); // Filter by protocol
const filterThreatOnly = ref(false); // Show only threats

// Computed
const hasActiveSession = computed(() => {
    return currentSession.value && currentSession.value.status === 'active';
});

const interfaceOptions = computed(() => {
    return props.interfaces.map(iface => ({
        value: iface.name,
        label: `${iface.name} - ${iface.description}`
    }));
});

// Methods
const startMonitoring = async () => {
    if (!selectedInterface.value) {
        error.value = 'Please select a network interface';
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        const response = await axios.post('/api/live-monitoring/start', {
            interface: selectedInterface.value
        });

        if (response.data.success) {
            currentSession.value = response.data.session;
            isMonitoring.value = true;
            
            // Start long polling for packets
            startPacketPolling();
            
            // Start polling for statistics
            startStatisticsPolling();
        } else {
            error.value = response.data.message || 'Failed to start monitoring';
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to start monitoring';
        console.error('Start monitoring error:', err);
    } finally {
        loading.value = false;
    }
};

const stopMonitoring = async () => {
    if (!currentSession.value) return;

    loading.value = true;

    try {
        const response = await axios.post('/api/live-monitoring/stop', {
            session_id: currentSession.value.session_id
        });

        if (response.data.success) {
            isMonitoring.value = false;
            
            // Stop long polling
            stopPacketPolling();
            
            // Stop polling
            stopStatisticsPolling();
            
            // Show final statistics
            statistics.value = response.data.statistics.statistics || statistics.value;
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to stop monitoring';
        console.error('Stop monitoring error:', err);
    } finally {
        loading.value = false;
    }
};

// Long Polling Implementation (replaces WebSocket)
const startPacketPolling = () => {
    if (!currentSession.value) return;
    
    const pollPackets = async () => {
        if (!isMonitoring.value || !currentSession.value) {
            stopPacketPolling();
            return;
        }
        
        try {
            const response = await axios.get('/api/live-monitoring/poll', {
                params: {
                    session_id: currentSession.value.session_id,
                    batch_size: 20 // Get 20 packets per poll
                },
                timeout: 10000 // 10 second timeout
            });
            
            if (response.data.success && response.data.packets) {
                // Process received packets
                response.data.packets.forEach(packet => {
                    handlePacketReceived({ packet });
                });
            }
        } catch (err) {
            if (err.response?.status === 404) {
                // Session not found or inactive
                console.warn('Session inactive, stopping polling');
                stopPacketPolling();
                isMonitoring.value = false;
                error.value = 'Monitoring session ended';
            } else if (!err.message?.includes('timeout')) {
                console.error('Polling error:', err);
            }
        }
        
        // Schedule next poll
        if (isMonitoring.value) {
            pollingInterval.value = setTimeout(pollPackets, pollingDelay);
        }
    };
    
    // Start polling
    pollPackets();
};

const stopPacketPolling = () => {
    if (pollingInterval.value) {
        clearTimeout(pollingInterval.value);
        pollingInterval.value = null;
    }
};

const handlePacketReceived = (data) => {
    if (!data.packet) return;
    
    const packet = data.packet;
    
    // Generate unique ID for packet
    const packetId = `${packet.captured_at}_${packet.source_ip}_${packet.destination_ip}_${packet.protocol}_${packet.source_port || 0}`;
    
    // Skip if we've already seen this packet (deduplication)
    if (packetIds.value.has(packetId)) {
        return;
    }
    
    // Apply filters
    if (filterProtocol.value !== 'all' && packet.protocol !== filterProtocol.value) {
        return;
    }
    
    if (filterThreatOnly.value && (!packet.threat_score || packet.threat_score < 0.3)) {
        return;
    }
    
    // Add to tracking set
    packetIds.value.add(packetId);
    
    // Add new packet to the beginning of the array
    packets.value.unshift(packet);
    
    // Check for threats and notify
    if (packet.threat_score && packet.threat_score >= 0.5) {
        handleThreatDetected({ threat: packet });
    }
    
    // Keep only last maxPackets packets in memory
    if (packets.value.length > maxPackets) {
        const removedPackets = packets.value.slice(maxPackets);
        packets.value = packets.value.slice(0, maxPackets);
        
        // Clean up tracking set for removed packets
        removedPackets.forEach(p => {
            const oldId = `${p.captured_at}_${p.source_ip}_${p.destination_ip}_${p.protocol}_${p.source_port || 0}`;
            packetIds.value.delete(oldId);
        });
    }
    
    // Update statistics
    if (data.statistics) {
        statistics.value = data.statistics;
    }
};

const handleStatusUpdate = (data) => {
    console.log('Status update:', data);
    
    if (data.status === 'stopped' || data.status === 'error') {
        isMonitoring.value = false;
        stopPacketPolling();
        stopStatisticsPolling();
        
        if (data.status === 'error') {
            error.value = data.message;
        }
    }
};

const handleThreatDetected = (data) => {
    const threat = data.threat;
    
    // Show notification for threat
    if (window.Notification && Notification.permission === 'granted') {
        new Notification('Threat Detected!', {
            body: `${threat.threat_type || 'Suspicious Activity'} from ${threat.source_ip}`,
            icon: '/favicon.ico'
        });
    }
    
    // Log threat for debugging
    console.warn('Threat detected:', threat);
};

let statisticsInterval = null;
let isPolling = ref(false); // Prevent overlapping polls

const startStatisticsPolling = () => {
    // Poll statistics every 5 seconds as backup to WebSocket (with debouncing)
    statisticsInterval = setInterval(async () => {
        if (currentSession.value && isMonitoring.value && !isPolling.value) {
            isPolling.value = true;
            try {
                const response = await axios.get(
                    `/api/live-monitoring/status/${currentSession.value.session_id}`,
                    { timeout: 4000 } // 4 second timeout
                );
                
                if (response.data.success) {
                    statistics.value = response.data.statistics.statistics || statistics.value;
                    
                    // Update recent packets if needed (only if we have very few)
                    if (packets.value.length < 10 && response.data.recent_packets) {
                        packets.value = response.data.recent_packets;
                    }
                }
            } catch (err) {
                if (!err.message?.includes('timeout')) {
                    console.error('Failed to fetch statistics:', err);
                }
            } finally {
                isPolling.value = false;
            }
        }
    }, 5000);
};

const stopStatisticsPolling = () => {
    if (statisticsInterval) {
        clearInterval(statisticsInterval);
        statisticsInterval = null;
    }
};

const clearPackets = () => {
    packets.value = [];
    packetIds.value.clear();
};

const toggleFilter = (protocol) => {
    filterProtocol.value = protocol;
};

const toggleThreatFilter = () => {
    filterThreatOnly.value = !filterThreatOnly.value;
    // Re-apply filters to current packets
    if (filterThreatOnly.value) {
        packets.value = packets.value.filter(p => p.threat_score && p.threat_score >= 0.3);
    }
};

// Computed property for unique protocols in captured packets
const availableProtocols = computed(() => {
    const protocols = new Set(packets.value.map(p => p.protocol));
    return ['all', ...Array.from(protocols)];
});

const exportPackets = () => {
    if (packets.value.length === 0) {
        alert('No packets to export');
        return;
    }

    const dataStr = JSON.stringify(packets.value, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `captured_packets_${Date.now()}.json`;
    link.click();
    URL.revokeObjectURL(url);
};

// Request notification permission
const requestNotificationPermission = () => {
    if (window.Notification && Notification.permission === 'default') {
        Notification.requestPermission();
    }
};

// Lifecycle
onMounted(() => {
    requestNotificationPermission();
    
    // Check if there's an active session from props
    if (props.activeSessions && props.activeSessions.length > 0) {
        currentSession.value = props.activeSessions[0];
        isMonitoring.value = true;
        selectedInterface.value = currentSession.value.interface;
        startPacketPolling();
        startStatisticsPolling();
    }
    
    // Set default interface
    if (!selectedInterface.value && props.interfaces.length > 0) {
        selectedInterface.value = props.interfaces[0].name;
    }
});

onUnmounted(() => {
    stopPacketPolling();
    stopStatisticsPolling();
});
</script>

<template>
    <Head title="Live Network Monitoring" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Live Network Monitoring
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Control Panel -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <!-- Interface Selection -->
                                <div class="flex items-center space-x-2">
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Interface:
                                    </label>
                                    <select
                                        v-model="selectedInterface"
                                        :disabled="isMonitoring || loading"
                                        class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">Select Interface</option>
                                        <option
                                            v-for="option in interfaceOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Start/Stop Button -->
                                <button
                                    v-if="!isMonitoring"
                                    @click="startMonitoring"
                                    :disabled="loading || !selectedInterface"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                >
                                    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ loading ? 'Starting...' : '▶ Go Live' }}
                                </button>

                                <button
                                    v-else
                                    @click="stopMonitoring"
                                    :disabled="loading"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                >
                                    <span class="inline-block w-2 h-2 bg-white rounded-full mr-2 animate-pulse"></span>
                                    {{ loading ? 'Stopping...' : '⏹ Stop Monitoring' }}
                                </button>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center space-x-2">
                                <button
                                    @click="clearPackets"
                                    :disabled="packets.length === 0"
                                    class="px-3 py-2 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50"
                                >
                                    Clear
                                </button>
                                <button
                                    @click="exportPackets"
                                    :disabled="packets.length === 0"
                                    class="px-3 py-2 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
                                >
                                    Export
                                </button>
                            </div>
                        </div>

                        <!-- Error Display -->
                        <div v-if="error" class="mt-4 p-3 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 rounded">
                            {{ error }}
                        </div>

                        <!-- Status Indicator -->
                        <div v-if="isMonitoring" class="mt-4 flex items-center space-x-2 text-green-600 dark:text-green-400">
                            <div class="w-3 h-3 bg-green-600 rounded-full animate-pulse"></div>
                            <span class="text-sm font-medium">
                                Live monitoring active on {{ selectedInterface }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Statistics Display -->
                <StatisticsDisplay
                    :statistics="statistics"
                    :packet-count="packets.length"
                    :is-monitoring="isMonitoring"
                />

                <!-- Traffic Chart -->
                <TrafficChart
                    :packets="packets"
                    :is-monitoring="isMonitoring"
                />

                <!-- Packet Table -->
                <PacketTable
                    :packets="packets"
                    :is-monitoring="isMonitoring"
                />

            </div>
        </div>
    </AuthenticatedLayout>
</template>
