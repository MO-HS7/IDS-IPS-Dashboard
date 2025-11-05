<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    totalAlerts: { type: Number, default: 0 },
    totalLogs: { type: Number, default: 0 },
    totalModels: { type: Number, default: 0 },
    criticalAlerts: { type: Number, default: 0 },
    recentAlerts: { type: Array, default: () => [] }
});

// Statistics with sample data for display
const statistics = computed(() => ([
    {
        title: 'Total Alerts',
        value: props.totalAlerts || 156,
        icon: '🚨',
        color: 'text-red-600',
        bgColor: 'bg-red-50 dark:bg-red-900/20',
        change: '+12.5%',
        changeType: 'increase',
        link: '/alerts'
    },
    {
        title: 'Network Logs',
        value: props.totalLogs || 2847,
        icon: '📁',
        color: 'text-blue-600',
        bgColor: 'bg-blue-50 dark:bg-blue-900/20',
        change: '+8.3%',
        changeType: 'increase',
        link: '/network-logs'
    },
    {
        title: 'Active Models',
        value: props.totalModels || 12,
        icon: '🤖',
        color: 'text-purple-600',
        bgColor: 'bg-purple-50 dark:bg-purple-900/20',
        change: '+2',
        changeType: 'increase',
        link: '/ml-models'
    },
    {
        title: 'Critical Threats',
        value: props.criticalAlerts || 23,
        icon: '⚠️',
        color: 'text-orange-600',
        bgColor: 'bg-orange-50 dark:bg-orange-900/20',
        change: '-5.2%',
        changeType: 'decrease',
        link: '/alerts'
    }
]));

// Threat types statistics
const threatTypes = ref([
    { name: 'SQL Injection', count: 45, percentage: 28.8, color: 'bg-red-500' },
    { name: 'XSS Attack', count: 38, percentage: 24.4, color: 'bg-orange-500' },
    { name: 'Brute Force', count: 32, percentage: 20.5, color: 'bg-yellow-500' },
    { name: 'DDoS', count: 25, percentage: 16.0, color: 'bg-blue-500' },
    { name: 'Port Scan', count: 16, percentage: 10.3, color: 'bg-green-500' }
]);

// System health status
const systemStatus = ref([
    { name: 'Database', status: 'Operational', icon: '🗄️', color: 'text-green-600' },
    { name: 'ML Engine', status: 'Operational', icon: '🤖', color: 'text-green-600' },
    { name: 'Network Monitor', status: 'Operational', icon: '📡', color: 'text-green-600' },
    { name: 'Alert System', status: 'Operational', icon: '🔔', color: 'text-green-600' }
]);

// Quick actions
const quickActions = ref([
    { title: 'View Alerts', icon: '🚨', link: '/alerts', description: 'Monitor security alerts' },
    { title: 'Upload Logs', icon: '📤', link: '/network-logs/create', description: 'Upload network traffic' },
    { title: 'Live Monitor', icon: '📹', link: '/live-monitoring', description: 'Real-time monitoring' },
    { title: 'Analytics', icon: '📊', link: '/analytics', description: 'View reports' }
]);

// Recent alerts display
const recentAlertsList = computed(() => {
    if (props.recentAlerts && props.recentAlerts.length > 0) {
        return props.recentAlerts.slice(0, 5);
    }
    // Sample data if no alerts
    return [
        { id: 1, attack_type: 'SQL Injection Attempt', severity: 'critical', detected_at: '2 minutes ago', source_ip: '192.168.1.100' },
        { id: 2, attack_type: 'XSS Attack Detected', severity: 'high', detected_at: '15 minutes ago', source_ip: '10.0.0.45' },
        { id: 3, attack_type: 'Brute Force Login', severity: 'medium', detected_at: '1 hour ago', source_ip: '172.16.0.23' },
        { id: 4, attack_type: 'Port Scan Activity', severity: 'low', detected_at: '2 hours ago', source_ip: '192.168.0.88' },
        { id: 5, attack_type: 'DDoS Attempt', severity: 'critical', detected_at: '3 hours ago', source_ip: '203.0.113.45' }
    ];
});

const getSeverityColor = (severity) => {
    const colors = {
        critical: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        high: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        low: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    };
    return colors[severity] || colors.low;
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-6 bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-6">
                <!-- Stunning Header with Gradient and Animation -->
                <div class="mb-8 animate-fade-in">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="relative">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl blur-xl opacity-50 animate-pulse-slow"></div>
                            <div class="relative p-4 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl shadow-2xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent dark:from-blue-400 dark:via-purple-400 dark:to-pink-400">
                                Security Dashboard
                            </h1>
                            <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2 mt-1">
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                </span>
                                Real-time threat monitoring & AI-powered analysis
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Stunning Statistics Cards -->
                <div class="grid grid-cols-4 gap-6 mb-8">
                    <Link v-for="(stat, index) in statistics" :key="stat.title" :href="stat.link"
                        :style="`animation-delay: ${index * 100}ms`"
                        class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 hover:border-transparent transition-all duration-500 hover:scale-110 hover:-translate-y-3 cursor-pointer animate-slide-up hover:shadow-2xl hover:shadow-blue-500/20">
                        <!-- Gradient Glow on Hover -->
                        <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-10 transition-opacity duration-500" :class="stat.bgColor"></div>
                        <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl blur opacity-0 group-hover:opacity-30 transition duration-500"></div>
                        
                        <div class="relative z-10">
                            <div class="flex items-start justify-between mb-4">
                                <div :class="['p-3 rounded-xl transition-all duration-300 group-hover:scale-125 group-hover:rotate-6', stat.bgColor]">
                                    <span class="text-3xl">{{ stat.icon }}</span>
                                </div>
                                <div :class="['flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-bold',
                                      stat.changeType === 'increase' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path v-if="stat.changeType === 'increase'" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z"/>
                                        <path v-else d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 112 0v11.586l2.293-2.293a1 1 0 011.414 0z"/>
                                    </svg>
                                    {{ stat.change }}
                                </div>
                            </div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">{{ stat.title }}</p>
                            <p :class="['text-4xl font-black mb-1 group-hover:scale-105 transition-transform duration-300', stat.color]">
                                {{ stat.value.toLocaleString() }}
                            </p>
                            <p class="text-xs text-gray-500">vs last period</p>
                        </div>
                        
                        <!-- Shine Effect -->
                        <div class="absolute top-0 -left-full h-full w-1/2 bg-gradient-to-r from-transparent via-white/20 to-transparent skew-x-12 group-hover:left-full transition-all duration-1000"></div>
                    </Link>
                </div>

                <!-- Stunning Quick Actions -->
                <div class="grid grid-cols-4 gap-6 mb-8">
                    <Link v-for="(action, index) in quickActions" :key="action.title" :href="action.link"
                        :style="`animation-delay: ${index * 100}ms`"
                        class="group relative bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-5 border border-gray-200 dark:border-gray-700 hover:border-blue-500/50 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-blue-500/30 cursor-pointer overflow-hidden animate-fade-in">
                        <div class="flex items-center gap-4 relative z-10">
                            <div class="p-4 rounded-xl bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 shadow-lg group-hover:shadow-2xl group-hover:shadow-purple-500/50 transition-all duration-300 group-hover:rotate-12 group-hover:scale-110">
                                <span class="text-3xl">{{ action.icon }}</span>
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-gray-900 dark:text-white text-sm group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ action.title }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ action.description }}</p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-500 group-hover:translate-x-2 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/0 via-purple-500/5 to-pink-500/0 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    </Link>
                </div>

                <!-- Stunning Main Content Grid -->
                <div class="grid grid-cols-3 gap-6 mb-6">
                    <!-- Beautiful Recent Alerts Section -->
                    <div class="col-span-2 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-xl animate-slide-up">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-gradient-to-br from-red-500 to-orange-500 rounded-xl shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold dark:text-white">Recent Security Alerts</h3>
                            </div>
                            <Link href="/alerts" class="group flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl text-sm font-bold transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-blue-500/50">
                                View All
                                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </Link>
                        </div>
                        <div class="space-y-3">
                            <Link v-for="alert in recentAlertsList" :key="alert.id" :href="`/alerts/${alert.id}`"
                                class="block p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3 flex-1">
                                        <div :class="['w-2 h-2 rounded-full', 
                                            alert.severity === 'critical' ? 'bg-red-500' : 
                                            alert.severity === 'high' ? 'bg-orange-500' : 
                                            alert.severity === 'medium' ? 'bg-yellow-500' : 'bg-green-500']"></div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium dark:text-white">{{ alert.attack_type }}</p>
                                            <p class="text-xs text-gray-500">{{ alert.source_ip }} • {{ alert.detected_at }}</p>
                                        </div>
                                    </div>
                                    <span :class="['px-2 py-1 rounded text-xs font-medium', getSeverityColor(alert.severity)]">
                                        {{ alert.severity }}
                                    </span>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <!-- Threat Types -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border dark:border-gray-700">
                        <h3 class="text-lg font-semibold dark:text-white mb-4">Top Threat Types</h3>
                        <div class="space-y-3">
                            <div v-for="threat in threatTypes" :key="threat.name">
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span class="dark:text-white">{{ threat.name }}</span>
                                    <span class="text-gray-500">{{ threat.count }}</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div :class="['h-2 rounded-full', threat.color]" :style="`width: ${threat.percentage}%`"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border dark:border-gray-700">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">System Status</h3>
                    <div class="grid grid-cols-4 gap-4">
                        <div v-for="service in systemStatus" :key="service.name" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <span class="text-2xl">{{ service.icon }}</span>
                            <div class="flex-1">
                                <p class="text-sm font-medium dark:text-white">{{ service.name }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span :class="['text-xs', service.color]">{{ service.status }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Stunning Animations */
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slide-up {
    from {
        opacity: 0;
        transform: translateY(40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

@keyframes pulse-slow {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

@keyframes glow {
    0%, 100% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.5); }
    50% { box-shadow: 0 0 40px rgba(59, 130, 246, 0.8); }
}

.animate-fade-in {
    animation: fade-in 0.6s ease-out both;
}

.animate-slide-up {
    animation: slide-up 0.8s ease-out both;
}

.animate-shimmer {
    animation: shimmer 2s infinite;
}

.animate-pulse-slow {
    animation: pulse-slow 3s ease-in-out infinite;
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

.animate-glow {
    animation: glow 2s ease-in-out infinite;
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #3b82f6, #8b5cf6);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #2563eb, #7c3aed);
}

/* Dark mode scrollbar */
.dark .custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

/* Glassmorphism Effect */
.glass {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Gradient Text */
.gradient-text {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Shine Effect */
.shine {
    position: relative;
    overflow: hidden;
}

.shine::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.shine:hover::after {
    left: 100%;
}
</style>