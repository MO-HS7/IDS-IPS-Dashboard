<template>
    <Head title="Alerts" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Security Alerts
                </h2>
                <Link :href="route('alerts.create')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Create New Alert
                </Link>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Success Message -->
                        <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                            {{ $page.props.flash.success }}
                        </div>
                        
                        <!-- Alerts Table -->
                        <div v-if="alerts.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Attack Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Severity
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Detected At
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            ML Model
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Description
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="alert in alerts.data" :key="alert.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <!-- Attack Type -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ alert.attack_type }}
                                        </td>
                                        
                                        <!-- Severity Badge -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getSeverityBadgeClass(alert.severity)" class="px-2 py-1 text-xs font-semibold rounded-full">
                                                {{ getSeverityText(alert.severity) }}
                                            </span>
                                        </td>
                                        
                                        <!-- Detected At -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDate(alert.detected_at) }}
                                        </td>
                                        
                                        <!-- ML Model -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ alert.ml_model ? alert.ml_model.name : 'Default IDS Model' }}
                                        </td>
                                        
                                        <!-- Description -->
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                            {{ alert.description || 'No description available' }}
                                        </td>
                                        
                                        <!-- Actions -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <Link :href="route('alerts.show', alert.id)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400">
                                                View
                                            </Link>
                                            <Link :href="route('alerts.edit', alert.id)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                                                Edit
                                            </Link>
                                            <button 
                                                @click="deleteAlert(alert.id)"
                                                :disabled="deletingId === alert.id"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 disabled:opacity-50"
                                            >
                                                {{ deletingId === alert.id ? 'Deleting...' : 'Delete' }}
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Empty State -->
                        <div v-else class="text-center py-8">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <p class="text-lg">No alerts detected yet</p>
                                <p class="text-sm mt-2">When threats are detected, they will appear here.</p>
                                <Link :href="route('alerts.create')" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-700 text-white font-bold rounded">
                                    Create Test Alert
                                </Link>
                            </div>
                        </div>
                        
                        <!-- Pagination - مُصحح بالكامل -->
                        <div v-if="alerts.links && alerts.links.length > 3" class="mt-6">
                            <nav class="flex items-center justify-between border-t border-gray-200 dark:border-gray-600 pt-6">
                                <!-- Mobile Pagination -->
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <template v-if="alerts.prev_page_url">
                                        <Link 
                                            :href="alerts.prev_page_url"
                                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                                        >
                                            Previous
                                        </Link>
                                    </template>
                                    <template v-if="alerts.next_page_url">
                                        <Link 
                                            :href="alerts.next_page_url"
                                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                                        >
                                            Next
                                        </Link>
                                    </template>
                                </div>
                                
                                <!-- Desktop Pagination -->
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                            Showing {{ alerts.from || 0 }} to {{ alerts.to || 0 }} of {{ alerts.total || 0 }} results
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                            <!-- Pagination Links - مُصحح -->
                                            <template v-for="link in alerts.links" :key="link.label">
                                                <!-- روابط نشطة -->
                                                <Link 
                                                    v-if="link.url"
                                                    :href="link.url"
                                                    v-html="link.label"
                                                    :class="[
                                                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                        link.active 
                                                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600 dark:bg-indigo-900 dark:border-indigo-400 dark:text-indigo-200' 
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'
                                                    ]"
                                                />
                                                <!-- روابط غير نشطة -->
                                                <span 
                                                    v-else
                                                    v-html="link.label"
                                                    :class="[
                                                        'relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-50 text-sm font-medium',
                                                        'cursor-not-allowed opacity-50 text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-500'
                                                    ]"
                                                />
                                            </template>
                                        </nav>
                                    </div>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    alerts: {
        type: Object,
        required: true
    }
})

const deletingId = ref(null)

// Badge Colors
const getSeverityBadgeClass = (severity) => {
    const classes = {
        'critical': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'high': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        'medium': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'low': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    }
    return classes[String(severity).toLowerCase()] || classes['medium']
}

// Badge Text
const getSeverityText = (severity) => {
    const texts = {
        'critical': 'Critical',
        'high': 'High',
        'medium': 'Medium',
        'low': 'Low'
    }
    return texts[String(severity).toLowerCase()] || 'Medium'
}

// Date formatter
const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    try {
        const date = new Date(dateString)
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    } catch (error) {
        return 'Invalid Date'
    }
}

const deleteAlert = (id) => {
    if (!id) {
        alert("Error: Missing alert ID!")
        return
    }

    if (!confirm('Are you sure you want to delete this alert?')) {
        return
    }

    deletingId.value = id

    const deleteUrl = `/alerts/${encodeURIComponent(id)}`
    
    console.log('[deleteAlert] Deleting alert ID:', id)
    console.log('[deleteAlert] DELETE URL:', deleteUrl)

    router.delete(deleteUrl, {
        preserveScroll: true,
        onStart: () => {
            console.log('[deleteAlert] Request started')
        },
        onSuccess: () => {
            console.log('[deleteAlert] Delete successful')
            deletingId.value = null
        },
        onError: (errors) => {
            console.error('[deleteAlert] Delete failed:', errors)
            deletingId.value = null
            
            let errorMessage = 'Failed to delete alert.'
            if (errors.message) {
                errorMessage += ' Error: ' + errors.message
            }
            if (errors.errors && Object.keys(errors.errors).length > 0) {
                errorMessage += ' Details: ' + JSON.stringify(errors.errors)
            }
            
            alert(errorMessage)
        },
        onFinish: () => {
            deletingId.value = null
        }
    })
}
</script>