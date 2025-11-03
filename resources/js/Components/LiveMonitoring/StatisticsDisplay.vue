<script setup>
import { computed } from 'vue';

const props = defineProps({
    statistics: {
        type: Object,
        default: () => ({
            packets_per_second: 0,
            bytes_per_second: 0,
            protocols: {},
            threat_percentage: 0,
        })
    },
    packetCount: {
        type: Number,
        default: 0
    },
    isMonitoring: {
        type: Boolean,
        default: false
    }
});

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};

const formatNumber = (num) => {
    return num.toLocaleString();
};

const getThreatClass = computed(() => {
    const percentage = props.statistics.threat_percentage || 0;
    if (percentage >= 50) return 'text-red-600 dark:text-red-400';
    if (percentage >= 25) return 'text-orange-600 dark:text-orange-400';
    if (percentage >= 10) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-green-600 dark:text-green-400';
});

const topProtocols = computed(() => {
    const protocols = props.statistics.protocols || {};
    return Object.entries(protocols)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 5);
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Real-time Statistics
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Packets Per Second -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                Packets/sec
                            </p>
                            <p class="mt-1 text-2xl font-bold text-blue-900 dark:text-blue-100">
                                {{ formatNumber(statistics.packets_per_second || 0) }}
                            </p>
                        </div>
                        <div class="p-3 bg-blue-200 dark:bg-blue-800 rounded-full">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Bandwidth -->
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-purple-600 dark:text-purple-400">
                                Bandwidth
                            </p>
                            <p class="mt-1 text-2xl font-bold text-purple-900 dark:text-purple-100">
                                {{ formatBytes(statistics.bytes_per_second || 0) }}/s
                            </p>
                        </div>
                        <div class="p-3 bg-purple-200 dark:bg-purple-800 rounded-full">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Packets -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-green-600 dark:text-green-400">
                                Total Packets
                            </p>
                            <p class="mt-1 text-2xl font-bold text-green-900 dark:text-green-100">
                                {{ formatNumber(packetCount) }}
                            </p>
                        </div>
                        <div class="p-3 bg-green-200 dark:bg-green-800 rounded-full">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Threat Percentage -->
                <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-red-600 dark:text-red-400">
                                Threat Level
                            </p>
                            <p :class="getThreatClass" class="mt-1 text-2xl font-bold">
                                {{ (statistics.threat_percentage || 0).toFixed(1) }}%
                            </p>
                        </div>
                        <div class="p-3 bg-red-200 dark:bg-red-800 rounded-full">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Protocol Distribution -->
            <div v-if="topProtocols.length > 0" class="mt-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    Protocol Distribution
                </h4>
                <div class="space-y-2">
                    <div
                        v-for="[protocol, count] in topProtocols"
                        :key="protocol"
                        class="flex items-center"
                    >
                        <div class="w-24 text-sm font-medium text-gray-600 dark:text-gray-400">
                            {{ protocol }}
                        </div>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                            <div
                                class="bg-blue-500 h-full rounded-full transition-all duration-300"
                                :style="{ width: `${(count / packetCount) * 100}%` }"
                            ></div>
                        </div>
                        <div class="w-16 text-right text-sm text-gray-600 dark:text-gray-400">
                            {{ count }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
