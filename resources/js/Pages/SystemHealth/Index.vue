<script setup>
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const systemInfo = ref({
    php_version: '8.2.12',
    laravel_version: '12.30.1',
    database: 'MySQL 8.0',
    server_time: new Date().toLocaleString('en-US'),
});

const services = ref([
    { name: 'Database', status: 'operational', icon: '🗄️', description: 'MySQL Connected' },
    { name: 'Laravel Queue', status: 'operational', icon: '⚙️', description: 'Running normally' },
    { name: 'WebSocket Server', status: 'checking', icon: '🔌', description: 'Checking...' },
    { name: 'Python ML Service', status: 'operational', icon: '🤖', description: 'Active' },
    { name: 'Cache System', status: 'operational', icon: '💾', description: 'Redis/File Available' },
]);

const metrics = ref([
    { label: 'Requests Today', value: '1,234', change: '+12%', icon: '📊', color: 'text-blue-600' },
    { label: 'Avg Response Time', value: '145ms', change: '-8%', icon: '⚡', color: 'text-green-600' },
    { label: 'Active Sessions', value: '48', change: '+5%', icon: '👥', color: 'text-purple-600' },
    { label: 'Error Rate', value: '0.2%', change: '-15%', icon: '⚠️', color: 'text-orange-600' },
]);

const getStatusClass = (status) => {
    return {
        operational: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        error: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        checking: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
    }[status] || 'bg-gray-100 text-gray-800';
};

const getStatusText = (status) => {
    return {
        operational: 'Operational',
        warning: 'Warning',
        error: 'Error',
        checking: 'Checking'
    }[status] || 'Unknown';
};
</script>

<template>
    <Head title="System Health" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-6">
                <!-- Header -->
                <div class="mb-6 flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold dark:text-white">❤️ System Health</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Monitor system performance and status</p>
                    </div>
                </div>

                <!-- System Status Overview -->
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 mb-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold mb-2">✅ All Systems Operational</h3>
                            <p class="text-green-100">System is running at full capacity without issues</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-green-100">Last Updated</p>
                            <p class="text-lg font-semibold">{{ systemInfo.server_time }}</p>
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <div v-for="metric in metrics" :key="metric.label" class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-2xl">{{ metric.icon }}</span>
                            <span :class="metric.color" class="text-sm font-semibold">{{ metric.change }}</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ metric.label }}</p>
                        <p class="text-2xl font-bold dark:text-white">{{ metric.value }}</p>
                    </div>
                </div>

                <!-- Services Status -->
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">🔧 Services Status</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="service in services" :key="service.name" class="flex items-center justify-between p-4 border dark:border-gray-700 rounded-lg">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl">{{ service.icon }}</span>
                                <div>
                                    <p class="font-medium dark:text-white">{{ service.name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ service.description }}</p>
                                </div>
                            </div>
                            <span :class="getStatusClass(service.status)" class="px-3 py-1 text-xs rounded-full font-semibold">
                                {{ getStatusText(service.status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
                        <h3 class="text-lg font-semibold dark:text-white mb-4">📋 System Information</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">PHP Version</span>
                                <span class="font-medium dark:text-white">{{ systemInfo.php_version }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Laravel Version</span>
                                <span class="font-medium dark:text-white">{{ systemInfo.laravel_version }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Database</span>
                                <span class="font-medium dark:text-white">{{ systemInfo.database }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Server Time</span>
                                <span class="font-medium dark:text-white">{{ systemInfo.server_time }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
                        <h3 class="text-lg font-semibold dark:text-white mb-4">💾 Resource Usage</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">CPU Usage</span>
                                    <span class="font-medium dark:text-white">35%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: 35%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">Memory Usage</span>
                                    <span class="font-medium dark:text-white">62%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: 62%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">Disk Space</span>
                                    <span class="font-medium dark:text-white">48%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-purple-600 h-2 rounded-full" style="width: 48%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
