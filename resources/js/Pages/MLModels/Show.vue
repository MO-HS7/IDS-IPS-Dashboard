<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import MetricsDashboard from '@/Components/MLModels/MetricsDashboard.vue'
import { Head, Link } from '@inertiajs/vue3'

defineProps({
    mlModel: {
        type: Object,
        required: true
    }
})

const getModelTypeIcon = (name) => {
    if (name.toLowerCase().includes('neural')) return '🧠'
    if (name.toLowerCase().includes('random forest')) return '🌳'
    if (name.toLowerCase().includes('svm')) return '📊'
    if (name.toLowerCase().includes('decision tree')) return '🌲'
    if (name.toLowerCase().includes('naive bayes')) return '📈'
    return '🤖'
}
</script>

<template>
    <Head :title="`ML Model: ${mlModel.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ mlModel.name }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Machine Learning Model Details</p>
                </div>
                <div class="flex space-x-3">
                    <Link 
                        href="/ml-models"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200"
                    >
                        Back to Models
                    </Link>
                    <Link 
                        v-if="$page.props.auth.user.role === 'Admin'"
                        :href="`/ml-models/${mlModel.id}/train`"
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200"
                    >
                        Train Model
                    </Link>
                    <Link 
                        v-if="$page.props.auth.user.role === 'Admin'"
                        :href="`/ml-models/${mlModel.id}/edit`"
                        class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200"
                    >
                        Edit Model
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Model Overview Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center mb-6">
                            <div class="flex-shrink-0 h-20 w-20">
                                <div class="h-20 w-20 rounded-full bg-gradient-to-br from-indigo-400 to-purple-600 flex items-center justify-center">
                                    <span class="text-3xl">{{ getModelTypeIcon(mlModel.name) }}</span>
                                </div>
                            </div>
                            <div class="ml-6">
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ mlModel.name }}</h1>
                                <p class="text-lg text-gray-600 dark:text-gray-400 mt-2">{{ mlModel.description || 'No description available' }}</p>
                                <div class="flex items-center mt-3">
                                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Active
                                    </span>
                                    <span class="ml-3 text-sm text-gray-500 dark:text-gray-400">
                                        Model ID: {{ mlModel.id }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metrics Dashboard -->
                <MetricsDashboard 
                    :model="mlModel" 
                    :metrics="mlModel.latest_metric" 
                />

                <!-- Model Information Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Model Information
                        </h3>
                        
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="py-2 border-b border-gray-200 dark:border-gray-700">
                                <dt class="font-medium text-gray-700 dark:text-gray-300">Name</dt>
                                <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ mlModel.name }}</dd>
                            </div>
                            
                            <div class="py-2 border-b border-gray-200 dark:border-gray-700">
                                <dt class="font-medium text-gray-700 dark:text-gray-300">File Path</dt>
                                <dd class="mt-1 text-gray-900 dark:text-gray-100 font-mono text-sm">{{ mlModel.file_path || 'Not specified' }}</dd>
                            </div>
                            
                            <div class="py-2 border-b border-gray-200 dark:border-gray-700">
                                <dt class="font-medium text-gray-700 dark:text-gray-300">Created</dt>
                                <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ new Date(mlModel.created_at).toLocaleString() }}</dd>
                            </div>
                            
                            <div class="py-2 border-b border-gray-200 dark:border-gray-700">
                                <dt class="font-medium text-gray-700 dark:text-gray-300">Last Updated</dt>
                                <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ new Date(mlModel.updated_at).toLocaleString() }}</dd>
                            </div>
                            
                            <div class="py-2 border-b border-gray-200 dark:border-gray-700">
                                <dt class="font-medium text-gray-700 dark:text-gray-300">Training Date</dt>
                                <dd class="mt-1 text-gray-900 dark:text-gray-100">
                                    {{ mlModel.trained_at ? new Date(mlModel.trained_at).toLocaleString() : 'Not trained yet' }}
                                </dd>
                            </div>

                            <div class="py-2 border-b border-gray-200 dark:border-gray-700">
                                <dt class="font-medium text-gray-700 dark:text-gray-300">Version</dt>
                                <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ mlModel.version || '1.0' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Model Description -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6" v-if="mlModel.description">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Description</h3>
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ mlModel.description }}</p>
                    </div>
                </div>

                <!-- Usage Instructions -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            Usage Instructions
                        </h3>
                        <div class="prose dark:prose-invert max-w-none">
                            <p class="text-gray-700 dark:text-gray-300 mb-4">This model can be used for:</p>
                            <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-2">
                                <li>Analyzing network traffic patterns for anomalies</li>
                                <li>Detecting various types of cyber attacks</li>
                                <li>Processing uploaded network log files</li>
                                <li>Generating security alerts based on threat confidence levels</li>
                            </ul>
                            
                            <p class="text-gray-700 dark:text-gray-300 mt-4">
                                To use this model, upload network log files through the Network Logs section. 
                                The system will automatically process the logs using this model and generate security alerts.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
