<template>
    <Head :title="`Alert #${alert.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Alert Details #{{ alert.id }}
                </h2>
                <div class="flex space-x-2">
                    <Link :href="route('alerts.edit', alert.id)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Edit Alert
                    </Link>
                    <Link :href="route('alerts.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Back to Alerts
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Alert Information -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Alert Information
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Attack Type</label>
                                    <p class="text-sm text-gray-900 dark:text-white font-semibold">{{ alert.attack_type }}</p>
                                </div>
                                
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Severity</label>
                                    <div class="mt-1">
                                        <span :class="getSeverityBadgeClass(alert.severity)" class="px-3 py-1 text-sm font-semibold rounded-full">
                                            {{ getSeverityText(alert.severity) }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                                    <p class="text-sm text-gray-900 dark:text-white capitalize">{{ alert.status }}</p>
                                </div>
                                
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Detected At</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ formatDate(alert.detected_at) }}</p>
                                </div>
                                
                                <div v-if="alert.confidence_score">
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Confidence Score</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ (alert.confidence_score * 100).toFixed(1) }}%</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Network Information -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Network Information
                            </h3>
                            
                            <div class="space-y-4">
                                <div v-if="alert.source_ip">
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Source IP</label>
                                    <p class="text-sm text-gray-900 dark:text-white font-mono">{{ alert.source_ip }}</p>
                                </div>
                                
                                <div v-if="alert.destination_ip">
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Destination IP</label>
                                    <p class="text-sm text-gray-900 dark:text-white font-mono">{{ alert.destination_ip }}</p>
                                </div>
                                
                                <div v-if="alert.network_log">
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Network Log File</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ alert.network_log.filename }}</p>
                                </div>
                                
                                <div v-if="alert.ml_model">
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">ML Model</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ alert.ml_model.name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div v-if="alert.description" class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Description
                        </h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ alert.description }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    alert: {
        type: Object,
        required: true
    }
})

// Function to get severity badge styling
const getSeverityBadgeClass = (severity) => {
    const classes = {
        'critical': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'high': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        'medium': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'low': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    }
    return classes[severity?.toLowerCase()] || classes['medium']
}

// Function to get severity text
const getSeverityText = (severity) => {
    const texts = {
        'critical': 'Critical',
        'high': 'High',
        'medium': 'Medium',
        'low': 'Low'
    }
    return texts[severity?.toLowerCase()] || severity || 'Medium'
}

// Function to format date
const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    const date = new Date(dateString)
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString()
}
</script>
