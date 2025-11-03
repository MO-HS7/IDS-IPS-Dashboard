<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import DangerButton from '@/Components/DangerButton.vue'

const props = defineProps({
    mlModel: {
        type: Object,
        required: true
    }
})

const form = useForm({
    name: props.mlModel.name,
    description: props.mlModel.description || '',
    file_path: props.mlModel.file_path || '',
    model_file: null,
})

const submit = () => {
    form.put(`/ml-models/${props.mlModel.id}`, {
        forceFormData: !!form.model_file,
        onSuccess: () => {
            console.log('ML Model updated successfully')
        }
    })
}

const deleteModel = () => {
    if (confirm('Are you sure you want to delete this ML model? This action cannot be undone.')) {
        form.delete(`/ml-models/${props.mlModel.id}`)
    }
}
</script>

<template>
    <Head title="Edit ML Model" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Edit ML Model: {{ mlModel.name }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Update model configuration and settings</p>
                </div>
                <Link 
                    :href="`/ml-models/${mlModel.id}`"
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200"
                >
                    View Model
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Current Model Info -->
                        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Model ID: {{ mlModel.id }}</p>
                                    <p class="text-xs text-blue-600 dark:text-blue-400">Created: {{ new Date(mlModel.created_at).toLocaleString() }}</p>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="submit">
                            <!-- Model Name -->
                            <div class="mb-6">
                                <InputLabel for="name" value="Model Name" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="Enter model name"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
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
                                    Update the filename for the trained model
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

                            <!-- Model Status Information -->
                            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Model Status</h3>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Status:</span>
                                        <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full text-xs">Active</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Last Updated:</span>
                                        <span class="ml-2 text-gray-600 dark:text-gray-400">{{ new Date(mlModel.updated_at).toLocaleDateString() }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Training Date:</span>
                                        <span class="ml-2 text-gray-600 dark:text-gray-400">{{ mlModel.trained_at ? new Date(mlModel.trained_at).toLocaleDateString() : 'Not specified' }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">File Size:</span>
                                        <span class="ml-2 text-gray-600 dark:text-gray-400">~2.3 MB</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center justify-between">
                                <DangerButton
                                    type="button"
                                    @click="deleteModel"
                                    class="bg-red-600 hover:bg-red-700"
                                >
                                    Delete Model
                                </DangerButton>

                                <div class="flex items-center space-x-4">
                                    <Link
                                        :href="`/ml-models/${mlModel.id}`"
                                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded transition-colors duration-200"
                                    >
                                        Cancel
                                    </Link>
                                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Update Model
                                    </PrimaryButton>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Warning Card -->
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mt-6">
                    <div class="flex">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Important Notice</h3>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                Modifying this model may affect existing alerts and ongoing threat detection. 
                                Make sure you understand the implications before making changes.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
