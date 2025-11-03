<template>
    <Head title="Edit Alert" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Edit Alert #{{ alert.id }}
                </h2>
                <div class="flex space-x-2">
                    <Link :href="route('alerts.show', alert.id)" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        View Alert
                    </Link>
                    <Link :href="route('alerts.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Back to Alerts
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <!-- Network Log Selection -->
                            <div class="mb-6">
                                <label for="network_log_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Network Log
                                </label>
                                <select 
                                    id="network_log_id"
                                    v-model="form.network_log_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select a network log</option>
                                    <option v-for="log in networkLogs" :key="log.id" :value="log.id">
                                        {{ log.filename }} ({{ log.upload_date }})
                                    </option>
                                </select>
                                <div v-if="errors.network_log_id" class="text-red-600 text-sm mt-1">
                                    {{ errors.network_log_id }}
                                </div>
                            </div>

                            <!-- ML Model Selection -->
                            <div class="mb-6">
                                <label for="ml_model_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ML Model
                                </label>
                                <select 
                                    id="ml_model_id"
                                    v-model="form.ml_model_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select an ML model</option>
                                    <option v-for="model in mlModels" :key="model.id" :value="model.id">
                                        {{ model.name }}
                                    </option>
                                </select>
                                <div v-if="errors.ml_model_id" class="text-red-600 text-sm mt-1">
                                    {{ errors.ml_model_id }}
                                </div>
                            </div>

                            <!-- Attack Type -->
                            <div class="mb-6">
                                <label for="attack_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Attack Type
                                </label>
                                <select 
                                    id="attack_type"
                                    v-model="form.attack_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select attack type</option>
                                    <option value="DDoS">DDoS</option>
                                    <option value="SQL Injection">SQL Injection</option>
                                    <option value="XSS">XSS (Cross-Site Scripting)</option>
                                    <option value="Port Scan">Port Scan</option>
                                    <option value="Brute Force">Brute Force</option>
                                    <option value="Malware">Malware</option>
                                    <option value="Phishing">Phishing</option>
                                    <option value="Man-in-the-Middle">Man-in-the-Middle</option>
                                    <option value="Buffer Overflow">Buffer Overflow</option>
                                    <option value="Privilege Escalation">Privilege Escalation</option>
                                </select>
                                <div v-if="errors.attack_type" class="text-red-600 text-sm mt-1">
                                    {{ errors.attack_type }}
                                </div>
                            </div>

                            <!-- Severity -->
                            <div class="mb-6">
                                <label for="severity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Severity Level
                                </label>
                                <select 
                                    id="severity"
                                    v-model="form.severity"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select severity</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                                <div v-if="errors.severity" class="text-red-600 text-sm mt-1">
                                    {{ errors.severity }}
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="mb-6">
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Status
                                </label>
                                <select 
                                    id="status"
                                    v-model="form.status"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="new">New</option>
                                    <option value="investigating">Investigating</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="false_positive">False Positive</option>
                                </select>
                                <div v-if="errors.status" class="text-red-600 text-sm mt-1">
                                    {{ errors.status }}
                                </div>
                            </div>

                            <!-- Source IP -->
                            <div class="mb-6">
                                <label for="source_ip" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Source IP Address (Optional)
                                </label>
                                <input 
                                    type="text"
                                    id="source_ip"
                                    v-model="form.source_ip"
                                    placeholder="e.g., 192.168.1.100"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <div v-if="errors.source_ip" class="text-red-600 text-sm mt-1">
                                    {{ errors.source_ip }}
                                </div>
                            </div>

                            <!-- Destination IP -->
                            <div class="mb-6">
                                <label for="destination_ip" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Destination IP Address (Optional)
                                </label>
                                <input 
                                    type="text"
                                    id="destination_ip"
                                    v-model="form.destination_ip"
                                    placeholder="e.g., 10.0.0.1"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <div v-if="errors.destination_ip" class="text-red-600 text-sm mt-1">
                                    {{ errors.destination_ip }}
                                </div>
                            </div>

                            <!-- Confidence Score -->
                            <div class="mb-6">
                                <label for="confidence_score" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Confidence Score (0.0 - 1.0)
                                </label>
                                <input 
                                    type="number"
                                    id="confidence_score"
                                    v-model="form.confidence_score"
                                    step="0.01"
                                    min="0"
                                    max="1"
                                    placeholder="e.g., 0.95"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <div v-if="errors.confidence_score" class="text-red-600 text-sm mt-1">
                                    {{ errors.confidence_score }}
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-6">
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Description
                                </label>
                                <textarea 
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    placeholder="Detailed description of the detected threat..."
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                                <div v-if="errors.description" class="text-red-600 text-sm mt-1">
                                    {{ errors.description }}
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-end">
                                <Link :href="route('alerts.index')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-4">
                                    Cancel
                                </Link>
                                <button 
                                    type="submit" 
                                    :disabled="processing"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                                >
                                    <span v-if="processing">Updating...</span>
                                    <span v-else>Update Alert</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    alert: {
        type: Object,
        required: true
    },
    networkLogs: {
        type: Array,
        default: () => []
    },
    mlModels: {
        type: Array,
        default: () => []
    },
    errors: {
        type: Object,
        default: () => ({})
    }
})

const form = useForm({
    network_log_id: props.alert.network_log_id,
    ml_model_id: props.alert.ml_model_id,
    attack_type: props.alert.attack_type,
    severity: props.alert.severity,
    status: props.alert.status,
    source_ip: props.alert.source_ip || '',
    destination_ip: props.alert.destination_ip || '',
    confidence_score: props.alert.confidence_score || '',
    description: props.alert.description || ''
})

const submit = () => {
    form.put(route('alerts.update', props.alert.id), {
        onSuccess: () => {
            // Form will be redirected automatically by the controller
        },
        onError: (errors) => {
            console.log('Validation errors:', errors)
        }
    })
}

const { processing } = form
</script>
