<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import BarChart from '@/Components/Charts/BarChart.vue';
import LineChart from '@/Components/Charts/LineChart.vue';
import PieChart from '@/Components/Charts/PieChart.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    statistics: {
        type: Object,
        default: () => ({
            total_alerts: 0,
            total_logs: 0,
            critical_alerts: 0,
            pending_logs: 0,
            active_models: 0,
            total_users: 0
        })
    },
    attackTypeDistribution: {
        type: Array,
        default: () => []
    },
    alertsOverTime: {
        type: Array,
        default: () => []
    },
    severityDistribution: {
        type: Array,
        default: () => []
    },
    systemHealth: {
        type: Object,
        default: () => ({})
    },
    recentAlerts: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isLoading = ref(true);

// Local reactive state (async-loaded to avoid heavy initial payload)
const stats = ref(props.statistics || {});
const charts = ref({
    attackTypeDistribution: props.attackTypeDistribution || [],
    alertsOverTime: props.alertsOverTime || [],
    severityDistribution: props.severityDistribution || []
});
const recentAlertsLocal = ref((props.recentAlerts || []).slice(0, 5));

const alertsOverTimeData = computed(() => ({
    labels: (charts.value.alertsOverTime || []).map(item => item.day).slice(-30),
    datasets: [
        {
            label: 'Alerts',
            backgroundColor: '#3B82F6',
            borderColor: '#2563EB',
            borderWidth: 1,
            borderRadius: 6,
            data: (charts.value.alertsOverTime || []).map(item => item.count).slice(-30)
        }
    ]
}));

const attackTypeDistributionData = computed(() => ({
    labels: (charts.value.attackTypeDistribution || []).slice(0, 10).map(item => item.name),
    datasets: [
        {
            label: 'Attack Types',
            backgroundColor: ['#EF4444', '#F97316', '#EAB308', '#22C55E', '#6366F1', '#EC4899', '#8B5CF6', '#14B8A6'],
            borderWidth: 2,
            borderColor: '#fff',
            data: (charts.value.attackTypeDistribution || []).slice(0, 10).map(item => item.value)
        }
    ]
}));

const severityDistributionData = computed(() => ({
    labels: (charts.value.severityDistribution || []).map(item => item.severity),
    datasets: [
        {
            label: 'Severity',
            backgroundColor: (charts.value.severityDistribution || []).map(item => item.color),
            borderWidth: 2,
            borderColor: '#fff',
            data: (charts.value.severityDistribution || []).map(item => item.count)
        }
    ]
}));

const displayedAlerts = computed(() => {
    if (recentAlertsLocal.value && recentAlertsLocal.value.length > 0) {
        return recentAlertsLocal.value.slice(0, 5).map(alert => ({
            ...alert,
            detected_at: alert.detected_at || 'Unknown time'
        }));
    }
    return [];
});

const systemStatus = computed(() => [
    { 
        name: 'ML Models', 
        status: 'active', 
        count: stats.value?.active_models || 0, 
        icon: '🤖' 
    },
    { 
        name: 'Network Logs', 
        status: (stats.value?.total_logs || 0) > 0 ? 'active' : 'inactive', 
        count: stats.value?.total_logs || 0, 
        icon: '📁' 
    },
    { 
        name: 'Active Alerts', 
        status: (stats.value?.total_alerts || 0) > 0 ? 'active' : 'inactive', 
        count: stats.value?.total_alerts || 0, 
        icon: '🚨' 
    },
    { 
        name: 'System Users', 
        status: 'active', 
        count: stats.value?.total_users || 0, 
        icon: '👥' 
    }
]);

const navigateTo = (url) => {
    try {
        router.get(url);
    } catch (error) {
        console.error('Navigation error:', error);
        window.location.href = url;
    }
};

const getSeverityColor = (severity) => {
    const colors = {
        critical: 'text-red-600 bg-red-100 dark:bg-red-900/20',
        high: 'text-orange-600 bg-orange-100 dark:bg-orange-900/20',
        medium: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/20',
        low: 'text-green-600 bg-green-100 dark:bg-green-900/20'
    };
    return colors[severity] || colors.low;
};

onMounted(async () => {
    try {
        const [statsRes, chartsRes, alertsRes] = await Promise.all([
            axios.get('/api/dashboard/stats'),
            axios.get('/api/dashboard/charts'),
            axios.get('/api/dashboard/recent-alerts?limit=5'),
        ]);
        stats.value = statsRes?.data?.data || stats.value;
        const chartsData = chartsRes?.data?.data || {};
        charts.value.attackTypeDistribution = (chartsData.attackTypeDistribution || []).slice(0, 10);
        charts.value.alertsOverTime = (chartsData.alertsOverTime || []).slice(-30);
        charts.value.severityDistribution = chartsData.severityDistribution || [];
        recentAlertsLocal.value = (alertsRes?.data?.data || []).slice(0, 5);
    } catch (e) {
        console.warn('[Dashboard] Async load failed, using initial props:', e?.message || e);
    } finally {
        isLoading.value = false;
    }
    if (window.Echo && user.value?.id) {
        try {
            window.Echo.private(`network.live.${user.value.id}`)
                .listen('AlertCreated', (e) => {
                    if (e.alert) {
                        // Prevent duplicates and limit to 5 alerts maximum
                        const alertExists = recentAlertsLocal.value.some(a => a.id === e.alert.id);
                        if (!alertExists) {
                            // Create a new array with the new alert at the beginning
                            const updatedAlerts = [e.alert, ...recentAlertsLocal.value.slice(0, 4)];
                            recentAlertsLocal.value = updatedAlerts;
                            
                            // Update statistics
                            if (stats.value) {
                                stats.value.total_alerts = (stats.value.total_alerts || 0) + 1;
                                if (e.alert.severity === 'critical') {
                                    stats.value.critical_alerts = (stats.value.critical_alerts || 0) + 1;
                                }
                            }
                        }
                    }
                })
                .error((error) => {
                    console.warn('[Dashboard] WebSocket error:', error);
                });
        } catch (error) {
            console.warn('[Dashboard] Failed to subscribe to alerts channel:', error);
        }
    }
});
</script>

<template>
    <Head :title="$t('dashboard.title')" />

    <AuthenticatedLayout>
        <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <SkeletonLoader v-for="n in 4" :key="n" />
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $t('dashboard.total_alerts') }}</p>
                    <span class="text-2xl">🚨</span>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats?.value?.total_alerts || 0 }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Total detected alerts</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $t('dashboard.network_logs') }}</p>
                    <span class="text-2xl">📊</span>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats?.value?.total_logs || 0 }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Network traffic logs</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $t('dashboard.active_models') }}</p>
                    <span class="text-2xl">🤖</span>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats?.value?.active_models || 0 }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">ML models running</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow duration-200">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $t('dashboard.critical_threats') }}</p>
                    <span class="text-2xl">⚠️</span>
                </div>
                <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ stats?.value?.critical_alerts || 0 }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Critical severity alerts</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $t('dashboard.alerts_over_time') }}</h3>
                <BarChart :chart-data="alertsOverTimeData" :height="320" />
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $t('dashboard.severity_distribution') }}</h3>
                <PieChart :chart-data="severityDistributionData" :height="320" />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $t('dashboard.recent_security_alerts') }}</h3>
                <div v-if="isLoading">
                    <SkeletonLoader v-for="n in 5" :key="n" />
                </div>
                <EmptyState v-else-if="displayedAlerts.length === 0" :title="$t('dashboard.no_recent_alerts')" message="There are no recent alerts to display at the moment." />
                <div v-else class="space-y-3 max-h-96 overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-gray-100 dark:scrollbar-track-gray-800">
                    <div v-for="alert in displayedAlerts" :key="alert.id" @click="navigateTo(`/alerts/${alert.id}`)" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 cursor-pointer border border-gray-200 dark:border-gray-600 hover:shadow-md">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center space-x-3 flex-1">
                                <div :class="['w-2.5 h-2.5 rounded-full flex-shrink-0 mt-1', 
                                    alert.severity === 'critical' ? 'bg-red-500' : 
                                    alert.severity === 'high' ? 'bg-orange-500' : 
                                    alert.severity === 'medium' ? 'bg-yellow-500' : 'bg-green-500']"></div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ alert.attack_type }}</h4>
                                </div>
                            </div>
                            <span :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize flex-shrink-0 ml-2', getSeverityColor(alert.severity)]">
                                {{ alert.severity }}
                            </span>
                        </div>
                        <div class="ml-5 space-y-1">
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Source IP:</span> {{ alert.source_ip }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">
                                <span class="font-medium">Time:</span> {{ alert.detected_at }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $t('dashboard.system_status') }}</h3>
                <div v-if="isLoading">
                    <SkeletonLoader v-for="n in 4" :key="n" />
                </div>
                <div v-else class="space-y-4">
                    <div v-for="status in systemStatus" :key="status.name" class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="text-lg">{{ status.icon }}</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ status.name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ status.count }} {{ status.count === 1 ? 'item' : 'items' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div :class="['w-2 h-2 rounded-full', status.status === 'active' ? 'bg-green-500' : 'bg-red-500']"></div>
                            <span :class="['text-xs font-medium capitalize', status.status === 'active' ? 'text-green-600' : 'text-red-600']">{{ status.status }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Custom scrollbar for Recent Alerts */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.3);
}

/* Dark mode scrollbar */
.dark .overflow-y-auto::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}
</style>