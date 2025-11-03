<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    packets: {
        type: Array,
        default: () => []
    },
    isMonitoring: {
        type: Boolean,
        default: false
    }
});

const filterProtocol = ref('all');
const filterThreats = ref(false);
const searchQuery = ref('');
const currentPage = ref(1);
const perPage = 50;

// Computed
const protocols = computed(() => {
    const prots = new Set(props.packets.map(p => p.protocol));
    return ['all', ...Array.from(prots)];
});

const filteredPackets = computed(() => {
    let filtered = [...props.packets];

    // Filter by protocol
    if (filterProtocol.value !== 'all') {
        filtered = filtered.filter(p => p.protocol === filterProtocol.value);
    }

    // Filter threats only
    if (filterThreats.value) {
        filtered = filtered.filter(p => p.is_threat);
    }

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(p =>
            p.source?.toLowerCase().includes(query) ||
            p.destination?.toLowerCase().includes(query) ||
            p.protocol?.toLowerCase().includes(query)
        );
    }

    return filtered;
});

const paginatedPackets = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    const end = start + perPage;
    return filteredPackets.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(filteredPackets.value.length / perPage);
});

const getThreatClass = (packet) => {
    if (!packet.is_threat) return '';
    
    if (packet.threat_score >= 0.8) {
        return 'bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500';
    } else if (packet.threat_score >= 0.5) {
        return 'bg-orange-50 dark:bg-orange-900/20 border-l-4 border-orange-500';
    } else {
        return 'bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500';
    }
};

const getThreatBadgeClass = (score) => {
    if (score >= 0.8) {
        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    } else if (score >= 0.5) {
        return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
    } else if (score > 0) {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    } else {
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    }
};

const formatThreatScore = (score) => {
    return (score * 100).toFixed(0) + '%';
};

// Reset page when filters change
watch([filterProtocol, filterThreats, searchQuery], () => {
    currentPage.value = 1;
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Captured Packets
                    <span class="ml-2 text-sm font-normal text-gray-500">
                        ({{ filteredPackets.length }} packets)
                    </span>
                </h3>

                <div class="flex items-center space-x-3">
                    <!-- Search -->
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search IP or protocol..."
                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                    />

                    <!-- Protocol Filter -->
                    <select
                        v-model="filterProtocol"
                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100"
                    >
                        <option v-for="protocol in protocols" :key="protocol" :value="protocol">
                            {{ protocol === 'all' ? 'All Protocols' : protocol }}
                        </option>
                    </select>

                    <!-- Threats Only Toggle -->
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input
                            v-model="filterThreats"
                            type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-700 text-red-600 focus:ring-red-500"
                        />
                        <span class="text-sm text-gray-700 dark:text-gray-300">Threats Only</span>
                    </label>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Time
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Source
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Destination
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Protocol
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Length
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Threat
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Type
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-if="paginatedPackets.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-sm">
                                        {{ isMonitoring ? 'Waiting for packets...' : 'No packets captured yet. Click "Go Live" to start monitoring.' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="packet in paginatedPackets"
                            :key="packet.id"
                            :class="getThreatClass(packet)"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-900 dark:text-gray-100 font-mono">
                                {{ packet.time }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700 dark:text-gray-300 font-mono">
                                {{ packet.source }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700 dark:text-gray-300 font-mono">
                                {{ packet.destination }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ packet.protocol }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-700 dark:text-gray-300">
                                {{ packet.length }} bytes
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    v-if="packet.threat_score > 0"
                                    :class="getThreatBadgeClass(packet.threat_score)"
                                    class="px-2 py-1 text-xs font-semibold rounded"
                                >
                                    {{ formatThreatScore(packet.threat_score) }}
                                </span>
                                <span v-else class="text-xs text-gray-400">-</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                {{ packet.threat_type || '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="mt-4 flex items-center justify-between">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Page {{ currentPage }} of {{ totalPages }}
                </div>
                <div class="flex space-x-2">
                    <button
                        @click="currentPage = Math.max(1, currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="px-3 py-1 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Previous
                    </button>
                    <button
                        @click="currentPage = Math.min(totalPages, currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        class="px-3 py-1 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
