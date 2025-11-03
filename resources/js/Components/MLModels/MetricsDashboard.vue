<script setup>
import { computed } from 'vue';

const props = defineProps({
    metrics: {
        type: Object,
        default: null
    },
    model: {
        type: Object,
        required: true
    }
});

// Computed properties for display
const hasMetrics = computed(() => props.metrics && props.metrics.accuracy);

const metricsData = computed(() => {
    if (!hasMetrics.value) return null;
    
    return {
        accuracy: (props.metrics.accuracy * 100).toFixed(2),
        precision: props.metrics.precision ? (props.metrics.precision * 100).toFixed(2) : 'N/A',
        recall: props.metrics.recall ? (props.metrics.recall * 100).toFixed(2) : 'N/A',
        f1Score: props.metrics.f1_score ? (props.metrics.f1_score * 100).toFixed(2) : 'N/A',
    };
});

const trainingInfo = computed(() => {
    if (!hasMetrics.value) return null;
    
    return {
        trainingSamples: props.metrics.training_samples || 'N/A',
        testingSamples: props.metrics.testing_samples || 'N/A',
        epochs: props.metrics.epochs || 'N/A',
    };
});

const modelStatus = computed(() => {
    const status = props.model.status;
    const colors = {
        'trained': { bg: 'bg-green-100 dark:bg-green-900', text: 'text-green-800 dark:text-green-200', icon: '✓' },
        'training': { bg: 'bg-blue-100 dark:bg-blue-900', text: 'text-blue-800 dark:text-blue-200', icon: '⏳' },
        'failed': { bg: 'bg-red-100 dark:bg-red-900', text: 'text-red-800 dark:text-red-200', icon: '✗' },
        'pending': { bg: 'bg-gray-100 dark:bg-gray-900', text: 'text-gray-800 dark:text-gray-200', icon: '○' },
    };
    return colors[status] || colors.pending;
});
</script>

<template>
    <div class="space-y-6">
        <!-- Model Status Card -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Model Status
                    </h3>
                    <span 
                        :class="[modelStatus.bg, modelStatus.text]"
                        class="px-3 py-1 text-sm font-semibold rounded-full"
                    >
                        {{ modelStatus.icon }} {{ model.status }}
                    </span>
                </div>
                
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Model Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ model.model_type || 'Not specified' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Predictions</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ model.total_predictions || 0 }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Active</dt>
                        <dd class="mt-1">
                            <span 
                                :class="model.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'"
                                class="px-2 py-1 text-xs font-semibold rounded"
                            >
                                {{ model.is_active ? 'Yes' : 'No' }}
                            </span>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div v-if="hasMetrics" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Performance Metrics
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Accuracy -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800 p-4 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-600 dark:text-blue-300">Accuracy</p>
                                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100 mt-1">
                                    {{ metricsData.accuracy }}%
                                </p>
                            </div>
                            <div class="h-12 w-12 bg-blue-200 dark:bg-blue-700 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Precision -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900 dark:to-green-800 p-4 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-600 dark:text-green-300">Precision</p>
                                <p class="text-2xl font-bold text-green-900 dark:text-green-100 mt-1">
                                    {{ metricsData.precision }}{{ metricsData.precision !== 'N/A' ? '%' : '' }}
                                </p>
                            </div>
                            <div class="h-12 w-12 bg-green-200 dark:bg-green-700 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Recall -->
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900 dark:to-yellow-800 p-4 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-yellow-600 dark:text-yellow-300">Recall</p>
                                <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100 mt-1">
                                    {{ metricsData.recall }}{{ metricsData.recall !== 'N/A' ? '%' : '' }}
                                </p>
                            </div>
                            <div class="h-12 w-12 bg-yellow-200 dark:bg-yellow-700 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- F1-Score -->
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800 p-4 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-purple-600 dark:text-purple-300">F1-Score</p>
                                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100 mt-1">
                                    {{ metricsData.f1Score }}{{ metricsData.f1Score !== 'N/A' ? '%' : '' }}
                                </p>
                            </div>
                            <div class="h-12 w-12 bg-purple-200 dark:bg-purple-700 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Training Information -->
        <div v-if="hasMetrics" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Training Information
                </h3>
                
                <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 rounded-lg">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Training Samples</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ trainingInfo.trainingSamples }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 rounded-lg">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Testing Samples</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ trainingInfo.testingSamples }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 rounded-lg">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Epochs</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ trainingInfo.epochs }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- No Metrics Message -->
        <div v-else class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        No performance metrics available
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <p>Train this model to see performance metrics.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
