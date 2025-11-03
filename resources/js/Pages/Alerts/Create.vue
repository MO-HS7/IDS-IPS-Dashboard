<script setup>
import { computed, ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'

const { success, error } = useToast()

const props = defineProps({
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
    network_log_id: '',
    ml_model_id: '',
    attack_type: '',
    severity: '',
    source_ip: '',
    destination_ip: '',
    confidence_score: '',
    description: ''
})

// Track validation errors
const validationErrors = ref({})

// Check if form is valid
const isFormValid = computed(() => {
    return form.network_log_id && 
           form.ml_model_id && 
           form.attack_type && 
           form.severity &&
           form.source_ip &&
           form.destination_ip &&
           form.confidence_score &&
           form.description &&
           Object.keys(validationErrors.value).length === 0
})

// Validate individual fields
const validateField = (field, value) => {
    const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/
    
    // Clear previous error
    delete validationErrors.value[field]
    
    // Validate based on field
    switch(field) {
        case 'network_log_id':
            if (!value) validationErrors.value[field] = 'Please select a network log'
            break
        case 'ml_model_id':
            if (!value) validationErrors.value[field] = 'Please select an ML model'
            break
        case 'attack_type':
            if (!value) validationErrors.value[field] = 'Please select an attack type'
            break
        case 'severity':
            if (!value) validationErrors.value[field] = 'Please select a severity level'
            break
        case 'source_ip':
            if (!value) {
                validationErrors.value[field] = 'Source IP address is required'
            } else if (!ipRegex.test(value)) {
                validationErrors.value[field] = 'Invalid IP address format'
            }
            break
        case 'destination_ip':
            if (!value) {
                validationErrors.value[field] = 'Destination IP address is required'
            } else if (!ipRegex.test(value)) {
                validationErrors.value[field] = 'Invalid IP address format'
            }
            break
        case 'confidence_score':
            if (!value && value !== 0) {
                validationErrors.value[field] = 'Confidence score is required'
            } else {
                const score = parseFloat(value)
                if (isNaN(score) || score < 0 || score > 1) {
                    validationErrors.value[field] = 'Must be between 0.0 and 1.0'
                }
            }
            break
        case 'description':
            if (!value || value.trim() === '') {
                validationErrors.value[field] = 'Description is required'
            }
            break
    }
}

const submit = () => {
    // Clear all validation errors
    validationErrors.value = {}
    
    // Validate all required fields
    validateField('network_log_id', form.network_log_id)
    validateField('ml_model_id', form.ml_model_id)
    validateField('attack_type', form.attack_type)
    validateField('severity', form.severity)
    
    // Validate all remaining required fields
    validateField('source_ip', form.source_ip)
    validateField('destination_ip', form.destination_ip)
    validateField('confidence_score', form.confidence_score)
    validateField('description', form.description)
    
    // Check if form is valid
    if (!isFormValid.value) {
        error('Validation Error', 'Please fill in all required fields correctly')
        return
    }
    
    form.post('/alerts', {
        onSuccess: () => {
            success('Success', 'Alert created successfully!')
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0]
            if (firstError) {
                error('Validation Error', firstError)
            }
        }
    })
}
</script>

<template>
    <Head title="Create Alert" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Create New Alert
                </h2>
                <Link 
                    href="/alerts" 
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                    Back to Alerts
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Success Message -->
                        <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                            {{ $page.props.flash.success }}
                        </div>

                        <!-- Error Messages -->
                        <div v-if="Object.keys(errors).length > 0" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            <h4 class="font-bold mb-2">Please fix the following errors:</h4>
                            <ul class="list-disc list-inside">
                                <li v-for="(error, field) in errors" :key="field">{{ error }}</li>
                            </ul>
                        </div>

                        <form @submit.prevent="submit">
                            <!-- Network Log Selection -->
                            <div class="mb-6">
                                <label for="network_log_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Network Log <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="network_log_id"
                                    v-model="form.network_log_id"
                                    @change="validateField('network_log_id', form.network_log_id)"
                                    :class="[
                                        'mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:text-gray-300',
                                        (validationErrors.network_log_id || errors.network_log_id) ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'
                                    ]"
                                    required
                                >
                                    <option value="">Select a network log</option>
                                    <option v-for="log in networkLogs" :key="log.id" :value="log.id">
                                        {{ log.filename }} - {{ log.upload_date }}
                                    </option>
                                </select>
                                <div v-if="validationErrors.network_log_id || errors.network_log_id" class="text-red-600 text-sm mt-1">
                                    {{ validationErrors.network_log_id || errors.network_log_id }}
                                </div>
                            </div>

                            <!-- ML Model Selection -->
                            <div class="mb-6">
                                <label for="ml_model_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ML Model <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="ml_model_id"
                                    v-model="form.ml_model_id"
                                    @change="validateField('ml_model_id', form.ml_model_id)"
                                    :class="[
                                        'mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:text-gray-300',
                                        (validationErrors.ml_model_id || errors.ml_model_id) ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'
                                    ]"
                                    required
                                >
                                    <option value="">Select an ML model</option>
                                    <option v-for="model in mlModels" :key="model.id" :value="model.id">
                                        {{ model.name }}
                                    </option>
                                </select>
                                <div v-if="validationErrors.ml_model_id || errors.ml_model_id" class="text-red-600 text-sm mt-1">
                                    {{ validationErrors.ml_model_id || errors.ml_model_id }}
                                </div>
                            </div>

                            <!-- Attack Type -->
                            <div class="mb-6">
                                <label for="attack_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Attack Type <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="attack_type"
                                    v-model="form.attack_type"
                                    @change="validateField('attack_type', form.attack_type)"
                                    :class="[
                                        'mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:text-gray-300',
                                        (validationErrors.attack_type || errors.attack_type) ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'
                                    ]"
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
                                <div v-if="validationErrors.attack_type || errors.attack_type" class="text-red-600 text-sm mt-1">
                                    {{ validationErrors.attack_type || errors.attack_type }}
                                </div>
                            </div>

                            <!-- Severity -->
                            <div class="mb-6">
                                <label for="severity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Severity Level <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="severity"
                                    v-model="form.severity"
                                    @change="validateField('severity', form.severity)"
                                    :class="[
                                        'mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:text-gray-300',
                                        (validationErrors.severity || errors.severity) ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'
                                    ]"
                                    required
                                >
                                    <option value="">Select severity</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                                <div v-if="validationErrors.severity || errors.severity" class="text-red-600 text-sm mt-1">
                                    {{ validationErrors.severity || errors.severity }}
                                </div>
                            </div>

                            <!-- Source IP -->
                            <div class="mb-6">
                                <label for="source_ip" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Source IP Address <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="source_ip"
                                    v-model="form.source_ip"
                                    @blur="validateField('source_ip', form.source_ip)"
                                    placeholder="e.g., 192.168.1.100"
                                    :class="[
                                        'mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:text-gray-300',
                                        (validationErrors.source_ip || errors.source_ip) ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'
                                    ]"
                                />
                                <div v-if="validationErrors.source_ip || errors.source_ip" class="text-red-600 text-sm mt-1">
                                    {{ validationErrors.source_ip || errors.source_ip }}
                                </div>
                            </div>

                            <!-- Destination IP -->
                            <div class="mb-6">
                                <label for="destination_ip" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Destination IP Address <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="destination_ip"
                                    v-model="form.destination_ip"
                                    @blur="validateField('destination_ip', form.destination_ip)"
                                    placeholder="e.g., 10.0.0.1"
                                    :class="[
                                        'mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:text-gray-300',
                                        (validationErrors.destination_ip || errors.destination_ip) ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'
                                    ]"
                                />
                                <div v-if="validationErrors.destination_ip || errors.destination_ip" class="text-red-600 text-sm mt-1">
                                    {{ validationErrors.destination_ip || errors.destination_ip }}
                                </div>
                            </div>

                            <!-- Confidence Score -->
                            <div class="mb-6">
                                <label for="confidence_score" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Confidence Score (0.0 - 1.0) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="confidence_score"
                                    v-model="form.confidence_score"
                                    @blur="validateField('confidence_score', form.confidence_score)"
                                    step="0.01"
                                    min="0"
                                    max="1"
                                    placeholder="e.g., 0.95"
                                    :class="[
                                        'mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:text-gray-300',
                                        (validationErrors.confidence_score || errors.confidence_score) ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'
                                    ]"
                                />
                                <div v-if="validationErrors.confidence_score || errors.confidence_score" class="text-red-600 text-sm mt-1">
                                    {{ validationErrors.confidence_score || errors.confidence_score }}
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-6">
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Description <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    @blur="validateField('description', form.description)"
                                    rows="4"
                                    placeholder="Detailed description of the detected threat..."
                                    :class="[
                                        'mt-1 block w-full rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:text-gray-300',
                                        (validationErrors.description || errors.description) ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-700'
                                    ]"
                                    required
                                ></textarea>
                                <div v-if="validationErrors.description || errors.description" class="text-red-600 text-sm mt-1">
                                    {{ validationErrors.description || errors.description }}
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-end">
                                <Link 
                                    href="/alerts" 
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-4"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing || !isFormValid"
                                    :class="[
                                        'bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-opacity',
                                        (form.processing || !isFormValid) ? 'opacity-50 cursor-not-allowed' : 'opacity-100'
                                    ]"
                                >
                                    <span v-if="form.processing">Creating...</span>
                                    <span v-else>Create Alert</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
