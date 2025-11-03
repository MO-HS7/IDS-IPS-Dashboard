<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { useToast } from '@/Composables/useToast'

const { success, error } = useToast()

const form = useForm({
    name: '',
    description: '',
    file_path: '',
    model_file: null,
})

const submit = () => {
    // Client-side validation
    if (!form.name || form.name.trim() === '') {
        error('Validation Error', 'Model name is required')
        return
    }
    
    if (!form.description || form.description.trim() === '') {
        error('Validation Error', 'Model description is required')
        return
    }
    
    // Either file path OR model file upload must be provided
    if ((!form.file_path || form.file_path.trim() === '') && !form.model_file) {
        error('Validation Error', 'Provide a model file or a file path')
        return
    }
    
    // Validate file extension if using path
    if (form.file_path) {
        const validExtensions = ['.pkl', '.joblib', '.h5']
        const hasValidExtension = validExtensions.some(ext => form.file_path.toLowerCase().endsWith(ext))
        if (!hasValidExtension) {
            error('Validation Error', 'File must have a valid extension (.pkl, .joblib, or .h5)')
            return
        }
    }
    
    form.post('/ml-models', {
        forceFormData: !!form.model_file,
        onSuccess: () => {
            console.log('ML Model created successfully')
            success('Success', 'ML Model created successfully!')
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0]
            if (firstError) {
                error('Validation Error', firstError)
            }
        }
    })
}

const modelTypes = [
    { name: 'Random Forest IDS', description: 'Random Forest classifier for network intrusion detection' },
    { name: 'Neural Network IDS', description: 'Deep learning neural network for advanced threat detection' },
    { name: 'SVM Classifier', description: 'Support Vector Machine for pattern recognition' },
    { name: 'Decision Tree IDS', description: 'Decision tree algorithm for rule-based detection' },
    { name: 'Naive Bayes IDS', description: 'Probabilistic classifier for anomaly detection' },
    { name: 'Ensemble IDS', description: 'Combined multiple algorithms for better accuracy' }
]
</script>

<template>
    <Head title="Create ML Model" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Create Machine Learning Model
                </h2>
                <Link 
                    href="/ml-models" 
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200"
                >
                    Back to Models
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header Info -->
                        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    Configure a new machine learning model for intrusion detection. The model will be used to analyze network traffic patterns and detect security threats.
                                </p>
                            </div>
                        </div>

                        <form @submit.prevent="submit">
                            <!-- Model Name -->
                            <div class="mb-6">
                                <InputLabel for="name" value="Model Name" />
                                <select
                                    id="name"
                                    v-model="form.name"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="form.description = modelTypes.find(t => t.name === form.name)?.description || ''"
                                >
                                    <option value="">Select a model type</option>
                                    <option v-for="type in modelTypes" :key="type.name" :value="type.name">
                                        {{ type.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.name" />
                                
                                <!-- Custom Name Option -->
                                <div class="mt-3">
                                    <label class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            @change="if ($event.target.checked) { form.name = ''; form.description = '' }"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Use custom name</span>
                                    </label>
                                </div>
                                
                                <TextInput
                                    v-if="!modelTypes.find(t => t.name === form.name)"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-2 block w-full"
                                    placeholder="Enter custom model name"
                                />
                            </div>

                            <!-- Description -->
                            <div class="mb-6">
                                <InputLabel for="description" value="Description" />
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Describe the model's purpose and functionality..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <!-- File Path -->
                            <div class="mb-6">
                                <InputLabel for="filepath" value="Model File Path" />
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm">
                                        models/
                                    </span>
                                    <TextInput
                                        v-model="form.file_path"
                                        type="text"
                                        class="flex-1 rounded-none rounded-r-md"
                                        placeholder="model_name.pkl"
                                    />
                                </div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Enter the filename for the trained model (e.g., random_forest_ids.pkl)
                                </p>
                                <InputError class="mt-2" :message="form.errors.file_path" />
                            </div>

                            <!-- Or Upload Model File -->
                            <div class="mb-6">
                                <InputLabel for="model_file" value="Or Upload Model File" />
                                <input
                                    id="model_file"
                                    type="file"
                                    accept=".pkl,.joblib,.h5"
                                    class="mt-1 block w-full text-sm text-gray-900 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-200"
                                    @change="(e) => { const f = e.target.files?.[0]; form.model_file = f || null }"
                                />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Supported formats: .pkl, .joblib, .h5</p>
                                <InputError class="mt-2" :message="form.errors.model_file" />
                            </div>

                            <!-- Training Information -->
                            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Training Information</h3>
                                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                    <p><strong>Training Date:</strong> Will be set automatically when the model is created</p>
                                    <p><strong>Model Storage:</strong> Files will be stored in the <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">storage/app/models/</code> directory</p>
                                    <p><strong>Supported Formats:</strong> .pkl (Python pickle), .joblib, .h5 (for neural networks)</p>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-end space-x-4">
                                <Link
                                    href="/ml-models"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded transition-colors duration-200"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Create Model
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
