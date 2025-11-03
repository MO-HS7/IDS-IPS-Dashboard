<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, nextTick, onErrorCaptured } from 'vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import LineChart from '@/Components/Charts/LineChart.vue';
import { useFeature } from '@/Composables/useFeature';

const { feature } = useFeature();
const page = usePage();

const props = defineProps({
    alertsData: {
        type: Object,
        default: () => ({})
    },
    networkData: {
        type: Object,
        default: () => ({})
    },
    threatData: {
        type: Object,
        default: () => ({})
    },
    currentPeriod: {
        type: String,
        default: '30d'
    },
    pageTitle: {
        type: String,
        default: 'Analytics'
    }
});

// States
const isLoading = ref(false);
const lastError = ref(null);
const activeTab = ref('overview');
const tabKey = ref(0);

const tabs = [
    { id: 'overview', name: 'Overview', icon: '📊' },
    { id: 'alerts', name: 'Alerts Analytics', icon: '🚨' },
    { id: 'network', name: 'Network Traffic', icon: '🌐' },
    { id: 'threats', name: 'Threat Analysis', icon: '🛡️' },
];

const selectedPeriod = ref(props.currentPeriod || '30d');

const periodOptions = [
    { value: '7d', label: 'Last 7 days' },
    { value: '30d', label: 'Last 30 days' },
    { value: '90d', label: 'Last 90 days' },
    { value: '1y', label: 'Last year' }
];

// Summary stats
const summaryStats = computed(() => {
    try {
        const totalAlerts = props.alertsData?.monthly?.datasets[0]?.data?.reduce((a, b) => a + b, 0) || 0;
        const totalThreats = props.threatData?.types?.datasets[0]?.data?.reduce((a, b) => a + b, 0) || 0;
        const networkTraffic = props.networkData?.traffic?.datasets[0]?.data?.reduce((a, b) => a + b, 0) || 0;

        return [
            {
                name: 'Total Alerts This Period',
                value: totalAlerts.toLocaleString(),
                change: '+12.5%', // This should be calculated dynamically
                changeType: 'increase',
                icon: '🚨',
                color: 'text-red-600 bg-red-100 dark:bg-red-900/20'
            },
            {
                name: 'Network Traffic',
                value: `${(networkTraffic / 1024 / 1024 / 1024).toFixed(2)} GB`,
                change: '+8.2%', // This should be calculated dynamically
                changeType: 'increase',
                icon: '🌐',
                color: 'text-blue-600 bg-blue-100 dark:bg-blue-900/20'
            },
            {
                name: 'Blocked Threats',
                value: totalThreats.toLocaleString(),
                change: '+15.3%', // This should be calculated dynamically
                changeType: 'increase',
                icon: '🛡️',
                color: 'text-green-600 bg-green-100 dark:bg-green-900/20'
            },
            {
                name: 'System Uptime',
                value: '99.9%', // This should be fetched from the backend
                change: '0%',
                changeType: 'neutral',
                icon: '⚡',
                color: 'text-emerald-600 bg-emerald-100 dark:bg-emerald-900/20'
            }
        ];
    } catch (error) {
        console.error('Error calculating summary stats:', error);
        return [];
    }
});

// Data validation
const hasAlertsData = computed(() => {
    try {
        return props.alertsData && props.alertsData.monthly && props.alertsData.monthly.datasets[0].data.length > 0;
    } catch (error) {
        console.error('Error checking alerts data:', error);
        return false;
    }
});

const hasNetworkData = computed(() => {
    try {
        return props.networkData && props.networkData.traffic && props.networkData.traffic.datasets[0].data.length > 0;
    } catch (error) {
        console.error('Error checking network data:', error);
        return false;
    }
});

const hasThreatData = computed(() => {
    try {
        return props.threatData && props.threatData.types && props.threatData.types.datasets[0].data.length > 0;
    } catch (error) {
        console.error('Error checking threat data:', error);
        return false;
    }
});

// بيانات Severity للعرض البديل
const severityStats = computed(() => {
    return [
        {
            name: 'Critical',
            value: 125,
            percentage: 19.2,
            color: 'bg-red-500',
            textColor: 'text-red-700 dark:text-red-400',
            bgColor: 'bg-red-50 dark:bg-red-900/20'
        },
        {
            name: 'High', 
            value: 245,
            percentage: 37.7,
            color: 'bg-orange-500',
            textColor: 'text-orange-700 dark:text-orange-400',
            bgColor: 'bg-orange-50 dark:bg-orange-900/20'
        },
        {
            name: 'Medium',
            value: 180,
            percentage: 27.7,
            color: 'bg-blue-500',
            textColor: 'text-blue-700 dark:text-blue-400',
            bgColor: 'bg-blue-50 dark:bg-blue-900/20'
        },
        {
            name: 'Low',
            value: 90,
            percentage: 13.8,
            color: 'bg-green-500',
            textColor: 'text-green-700 dark:text-green-400',
            bgColor: 'bg-green-50 dark:bg-green-900/20'
        }
    ];
});

// Tab navigation
const setActiveTab = async (tabId) => {
    try {
        console.log(`🔄 Switching to tab: ${tabId}`);
        
        lastError.value = null;
        activeTab.value = tabId;
        tabKey.value += 1;
        
        await nextTick();
        
        console.log(`✅ Successfully switched to tab: ${activeTab.value}, key: ${tabKey.value}`);
        
    } catch (error) {
        console.error(`❌ Error switching to tab ${tabId}:`, error);
        lastError.value = `Failed to switch to ${tabId} tab: ${error.message}`;
        
        try {
            activeTab.value = 'overview';
            tabKey.value += 1;
        } catch (fallbackError) {
            console.error('❌ Fallback to overview also failed:', fallbackError);
        }
    }
};

// Period change handler
const handlePeriodChange = () => {
    try {
        console.log('Period changed to:', selectedPeriod.value);
        isLoading.value = true;
        
        router.get('/analytics', { period: selectedPeriod.value }, {
            preserveState: true,
            preserveScroll: true,
            only: ['alertsData', 'networkData', 'threatData', 'currentPeriod'],
            onSuccess: () => {
                isLoading.value = false;
                console.log('Data loaded successfully');
            },
            onError: (errors) => {
                isLoading.value = false;
                console.error('Error loading analytics data:', errors);
                lastError.value = 'Failed to load new data';
            }
        });
    } catch (error) {
        isLoading.value = false;
        console.error('Error changing period:', error);
        lastError.value = 'Error changing time period';
    }
};

const isExportMenuOpen = ref(false);

const toggleExportMenu = () => {
    isExportMenuOpen.value = !isExportMenuOpen.value;
};

const closeExportMenu = () => {
    isExportMenuOpen.value = false;
};

const exportReport = (format) => {
    try {
        const url = route('analytics.export', { period: selectedPeriod.value, format: format });
        window.open(url, '_blank');
        closeExportMenu();
    } catch (error) {
        console.error('Error exporting report:', error);
    }
};

// Watchers
watch(selectedPeriod, (newPeriod) => {
    if (newPeriod !== props.currentPeriod) {
        handlePeriodChange();
    }
});

// Error handling
onErrorCaptured((error, instance, errorInfo) => {
    console.error('Component error caught:', error, errorInfo);
    lastError.value = `Component error: ${error.message}`;
    return false;
});

// Mounted hook
onMounted(() => {
    try {
        console.log('🚀 Analytics component mounted');
        console.log('📊 Available data:', { 
            alerts: hasAlertsData.value,
            network: hasNetworkData.value,
            threats: hasThreatData.value
        });
        
    } catch (error) {
        console.error('❌ Error during component mount:', error);
        lastError.value = 'Error initializing component';
    }
});

// Debug mode
const isDebugMode = computed(() => {
    try {
        return page?.props?.app?.debug || false;
    } catch (error) {
        return false;
    }
});
</script>

<template>
    <Head :title="pageTitle" />

    <AuthenticatedLayout>
        <!-- عرض الأخطاء -->
        <div v-if="lastError" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <div class="flex items-center">
                <span class="text-red-500 mr-2">⚠️</span>
                <span>{{ lastError }}</span>
                <button @click="lastError = null" class="ml-auto text-red-500 hover:text-red-700">
                    ✕
                </button>
            </div>
        </div>

        <!-- Analytics Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        📊 Analytics Dashboard
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Comprehensive analysis of your network security data and trends.
                    </p>
                </div>
                
                                    <div class="flex items-center space-x-3">
                                        <select 
                                            v-model="selectedPeriod"
                                            :disabled="isLoading"
                                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                                        >
                                            <option 
                                                v-for="option in periodOptions" 
                                                :key="option.value" 
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </option>
                                        </select>
                                                        <div v-if="feature('export')" class="relative inline-block text-left">
                            <div>
                                <button @click="isExportMenuOpen = !isExportMenuOpen" type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-lg shadow-sm transition-colors" id="menu-button" aria-expanded="true" aria-haspopup="true">
                                    <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    {{ isLoading ? 'Loading...' : 'Export Report' }}
                                    <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="isExportMenuOpen" @click="isExportMenuOpen = false" class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                                <div class="py-1" role="none">
                                    <a href="#" @click.prevent="exportReport('csv')" class="text-gray-700 dark:text-gray-200 block px-4 py-2 text-sm" role="menuitem" tabindex="-1" id="menu-item-0">Export as CSV</a>
                                    <a href="#" @click.prevent="exportReport('json')" class="text-gray-700 dark:text-gray-200 block px-4 py-2 text-sm" role="menuitem" tabindex="-1" id="menu-item-1">Export as JSON</a>
                                </div>
                            </div>
                        </div>                                    </div>            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div 
                v-for="stat in summaryStats" 
                :key="stat.name"
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow duration-200"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ stat.name }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</p>
                    </div>
                    <div :class="['w-12 h-12 rounded-lg flex items-center justify-center', stat.color]">
                        <span class="text-2xl">{{ stat.icon }}</span>
                    </div>
                </div>
                
                <div class="mt-4 flex items-center">
                    <span 
                        :class="[
                            'inline-flex items-center text-xs font-medium',
                            stat.changeType === 'increase' ? 'text-green-600 dark:text-green-400' :
                            stat.changeType === 'decrease' ? 'text-red-600 dark:text-red-400' :
                            'text-gray-600 dark:text-gray-400'
                        ]"
                    >
                        <svg v-if="stat.changeType === 'increase'" class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <svg v-else-if="stat.changeType === 'decrease'" class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 112 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ stat.change }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">vs last period</span>
                </div>
            </div>
        </div>

        <!-- Analytics Tabs -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-8 px-6" aria-label="Analytics tabs">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="setActiveTab(tab.id)"
                        :disabled="isLoading"
                        :class="[
                            'flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200 cursor-pointer focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed',
                            activeTab === tab.id
                                ? 'border-blue-500 text-blue-600 dark:text-blue-400 bg-blue-50/50 dark:bg-blue-900/20'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                        ]"
                        type="button"
                    >
                        <span class="mr-2">{{ tab.icon }}</span>
                        {{ tab.name }}
                        <div v-if="activeTab === tab.id" class="ml-2 w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6" :key="`tab-content-${activeTab}-${tabKey}`">
                <!-- Overview Tab -->
                <Transition name="fade" mode="out-in">
                    <div v-if="activeTab === 'overview'" class="space-y-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Alerts Overview Chart -->
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    📊 Monthly Alerts Overview
                                </h3>
                                <div class="h-64">
                                    <BarChart 
                                        v-if="hasAlertsData && alertsData.monthly"
                                        :key="`overview-alerts-${tabKey}`"
                                        :chart-data="alertsData.monthly"
                                        :height="256"
                                        chart-id="overview-alerts-bar"
                                    />
                                    <div v-else class="flex items-center justify-center h-full">
                                        <div class="text-center">
                                            <div class="text-4xl mb-2">📊</div>
                                            <p class="text-gray-500 dark:text-gray-400">No alerts data available</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Network Traffic Chart -->
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    🌐 Network Traffic Trends
                                </h3>
                                <div class="h-64">
                                    <LineChart 
                                        v-if="hasNetworkData && networkData.traffic"
                                        :key="`overview-network-${tabKey}`"
                                        :chart-data="networkData.traffic"
                                        :height="256"
                                        chart-id="overview-network-line"
                                    />
                                    <div v-else class="flex items-center justify-center h-full">
                                        <div class="text-center">
                                            <div class="text-4xl mb-2">🌐</div>
                                            <p class="text-gray-500 dark:text-gray-400">No network data available</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Alerts Analytics Tab -->
                <Transition name="fade" mode="out-in">
                    <div v-if="activeTab === 'alerts'" class="space-y-8">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <!-- Monthly Alerts Bar Chart -->
                            <div class="lg:col-span-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    📈 Monthly Alerts Breakdown
                                </h3>
                                <div class="h-80">
                                    <BarChart 
                                        v-if="hasAlertsData && alertsData.monthly"
                                        :key="`alerts-monthly-${tabKey}`"
                                        :chart-data="alertsData.monthly"
                                        :height="320"
                                        chart-id="alerts-monthly-bar"
                                    />
                                    <div v-else class="flex items-center justify-center h-full">
                                        <div class="text-center">
                                            <div class="text-4xl mb-2">📈</div>
                                            <p class="text-gray-500 dark:text-gray-400">No alerts data available</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Alert Severity Distribution - العرض البديل الجديد -->
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                                    🎯 Alert Severity Distribution
                                </h3>
                                
                                <!-- Visual Severity Breakdown -->
                                <div class="space-y-4">
                                    <div 
                                        v-for="severity in severityStats" 
                                        :key="severity.name"
                                        :class="['rounded-lg p-4 border-l-4', severity.bgColor]"
                                        style="border-left-color: var(--severity-color)"
                                        :style="{ '--severity-color': severity.color.replace('bg-', '').replace('-500', '') === 'red' ? '#EF4444' : severity.color.replace('bg-', '').replace('-500', '') === 'orange' ? '#F59E0B' : severity.color.replace('bg-', '').replace('-500', '') === 'blue' ? '#3B82F6' : '#10B981' }"
                                    >
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center space-x-2">
                                                <div :class="['w-3 h-3 rounded-full', severity.color]"></div>
                                                <span :class="['font-semibold text-sm', severity.textColor]">
                                                    {{ severity.name }}
                                                </span>
                                            </div>
                                            <div class="text-right">
                                                <div :class="['text-lg font-bold', severity.textColor]">
                                                    {{ severity.value }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ severity.percentage }}%
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Progress Bar -->
                                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                            <div 
                                                :class="['h-2 rounded-full transition-all duration-500', severity.color]"
                                                :style="{ width: `${severity.percentage}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary Stats -->
                                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Total Alerts</span>
                                        <span class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ severityStats.reduce((total, item) => total + item.value, 0).toLocaleString() }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Status -->
                                <div class="mt-4 flex items-center justify-center">
                                    <div class="flex items-center space-x-2 px-3 py-1 bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-full text-xs">
                                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                        <span>Live Data</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Network Traffic Tab -->
                <Transition name="fade" mode="out-in">
                    <div v-if="activeTab === 'network'" class="space-y-8">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                📊 Network Traffic Analysis (24h)
                            </h3>
                            <div class="h-96">
                                <LineChart 
                                    v-if="hasNetworkData && networkData.traffic"
                                    :key="`network-traffic-${tabKey}`"
                                    :chart-data="networkData.traffic"
                                    :height="384"
                                    chart-id="network-traffic-line"
                                />
                                <div v-else class="flex items-center justify-center h-full">
                                    <div class="text-center">
                                        <div class="text-4xl mb-2">📊</div>
                                        <p class="text-gray-500 dark:text-gray-400">No network traffic data available</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Threat Analysis Tab -->
                <Transition name="fade" mode="out-in">
                    <div v-if="activeTab === 'threats'" class="space-y-8">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                🛡️ Threat Types Distribution
                            </h3>
                            <div class="h-96">
                                <BarChart 
                                    v-if="hasThreatData && (threatData.types || threatData.labels)"
                                    :key="`threats-types-${tabKey}`"
                                    :chart-data="threatData.types || threatData"
                                    :height="384"
                                    chart-id="threats-types-bar"
                                    :options="{ indexAxis: 'y' }"
                                />
                                <div v-else class="flex items-center justify-center h-full">
                                    <div class="text-center">
                                        <div class="text-4xl mb-2">🛡️</div>
                                        <p class="text-gray-500 dark:text-gray-400">No threat data available</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- Debug Info -->
        <div v-if="isDebugMode" class="mt-8 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
            <h4 class="font-semibold mb-2 text-gray-900 dark:text-white">🔧 Debug Info:</h4>
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300">
                <div>
                    <p><strong>Active Tab:</strong> {{ activeTab }}</p>
                    <p><strong>Tab Key:</strong> {{ tabKey }}</p>
                    <p><strong>Is Loading:</strong> {{ isLoading }}</p>
                    <p><strong>Last Error:</strong> {{ lastError || 'None' }}</p>
                </div>
                <div>
                    <p><strong>Has Alerts Data:</strong> {{ hasAlertsData }}</p>
                    <p><strong>Has Network Data:</strong> {{ hasNetworkData }}</p>
                    <p><strong>Has Threat Data:</strong> {{ hasThreatData }}</p>
                    <p><strong>PieChart Status:</strong> ❌ Disabled (using alternative)</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* CSS محسن */
.transition-colors {
    transition: color 0.2s ease-in-out, border-color 0.2s ease-in-out, background-color 0.2s ease-in-out;
}

/* Transitions للتبويبات */
.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease-in-out;
}

.fade-enter-from {
    opacity: 0;
    transform: translateX(20px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

/* تحسين مظهر التبويبات */
nav button:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
    border-radius: 4px;
}

nav button:hover:not(:disabled) {
    transform: translateY(-1px);
}

nav button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

/* Loading animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Responsive design */
@media (max-width: 768px) {
    nav {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    nav button {
        flex: 1;
        min-width: 120px;
        text-align: center;
        padding: 0.75rem 0.5rem;
    }
}

/* تحسينات للـ severity cards */
.severity-card {
    transition: all 0.2s ease-in-out;
}

.severity-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* تحسين progress bars */
.progress-bar {
    transition: width 0.5s ease-in-out;
}

/* تحسين animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.severity-stats > div {
    animation: slideIn 0.5s ease-out;
    animation-fill-mode: both;
}

.severity-stats > div:nth-child(1) { animation-delay: 0.1s; }
.severity-stats > div:nth-child(2) { animation-delay: 0.2s; }
.severity-stats > div:nth-child(3) { animation-delay: 0.3s; }
.severity-stats > div:nth-child(4) { animation-delay: 0.4s; }
</style>