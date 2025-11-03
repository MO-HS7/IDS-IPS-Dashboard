<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useToast } from '@/Composables/useToast'
import PcapUploader from '@/Components/FileUpload/PcapUploader.vue'

const uploaderRef = ref(null)
const previewData = ref(null)
const { success, error, warning } = useToast()

const form = useForm({
    file: null,
});

const handleFileSelected = (file) => {
    form.file = file;
};

const handlePreviewReady = (summary) => {
    previewData.value = summary;
};

const submit = () => {
    // Client-side validation
    if (!form.file) {
        error('Validation Error', 'Please select a file to upload')
        return
    }
    
    // Validate file size (max 500MB for PCAP, 10MB for CSV)
    const ext = form.file.name.split('.').pop().toLowerCase();
    const isPcap = ['pcap', 'pcapng', 'cap'].includes(ext);
    const maxSize = isPcap ? 500 * 1024 * 1024 : 10 * 1024 * 1024;
    
    if (form.file.size > maxSize) {
        error('File Too Large', `File size must not exceed ${isPcap ? '500MB' : '10MB'}`)
        return
    }
    
    // Validate file type
    const allowedExtensions = ['csv', 'txt', 'pcap', 'pcapng', 'cap']
    const fileExtension = form.file.name.split('.').pop().toLowerCase()
    if (!allowedExtensions.includes(fileExtension)) {
        error('Invalid File Type', 'Only CSV, TXT, PCAP, and PCAPNG files are allowed')
        return
    }
    
    form.post('/network-logs', {
        onSuccess: () => {
            success('Success', 'File uploaded successfully! Processing will begin shortly.')
            form.reset()
            if (uploaderRef.value) {
                uploaderRef.value.clearFile()
            }
            previewData.value = null
        },
        onError: (errors) => {
            if (errors.file) {
                error('Upload Failed', errors.file)
            } else {
                error('Upload Failed', 'An error occurred while uploading the file')
            }
        },
    });
};
</script>


<template>
    <Head title="Upload Network Log" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Upload Network Log
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                                    Network Log File
                                </label>
                                
                                <!-- PCAP Uploader Component -->
                                <PcapUploader
                                    ref="uploaderRef"
                                    accept-types=".csv,.txt,.pcap,.pcapng,.cap"
                                    :max-size="500 * 1024 * 1024"
                                    :show-preview="true"
                                    @file-selected="handleFileSelected"
                                    @preview-ready="handlePreviewReady"
                                />
                                
                                <div v-if="form.errors.file" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.file }}
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-4">
                                <Link
                                    :href="route('network-logs.index')"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing || !form.file"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                                >
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span v-if="form.processing">Uploading...</span>
                                    <span v-else>Upload & Analyze</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

