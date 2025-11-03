<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const emit = defineEmits(['file-selected', 'preview-ready', 'upload-complete']);

const props = defineProps({
    acceptedTypes: {
        type: String,
        default: '.csv,.txt,.pcap,.pcapng,.cap'
    },
    maxSize: {
        type: Number,
        default: 500 * 1024 * 1024 // 500MB
    },
    showPreview: {
        type: Boolean,
        default: true
    }
});

const isDragging = ref(false);
const selectedFile = ref(null);
const previewLoading = ref(false);
const previewData = ref(null);
const previewError = ref(null);

const fileInfo = computed(() => {
    if (!selectedFile.value) return null;
    
    return {
        name: selectedFile.value.name,
        size: formatFileSize(selectedFile.value.size),
        type: selectedFile.value.type || 'Unknown',
        extension: selectedFile.value.name.split('.').pop().toUpperCase()
    };
});

const isPcapFile = computed(() => {
    if (!selectedFile.value) return false;
    const ext = selectedFile.value.name.split('.').pop().toLowerCase();
    return ['pcap', 'pcapng', 'cap'].includes(ext);
});

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};

const handleDragOver = (e) => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const handleDrop = (e) => {
    e.preventDefault();
    isDragging.value = false;
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFileSelect(files[0]);
    }
};

const handleFileInput = (e) => {
    const files = e.target.files;
    if (files.length > 0) {
        handleFileSelect(files[0]);
    }
};

const handleFileSelect = async (file) => {
    // Validate file size
    if (file.size > props.maxSize) {
        alert(`File size exceeds maximum allowed size of ${formatFileSize(props.maxSize)}`);
        return;
    }
    
    // Validate file type
    const ext = file.name.split('.').pop().toLowerCase();
    const allowedExts = props.acceptedTypes.split(',').map(t => t.replace('.', ''));
    if (!allowedExts.includes(ext)) {
        alert(`File type .${ext} is not allowed. Allowed types: ${allowedExts.join(', ')}`);
        return;
    }
    
    selectedFile.value = file;
    emit('file-selected', file);
    
    // Get preview for PCAP files if enabled
    if (props.showPreview && isPcapFile.value) {
        await getFilePreview();
    }
};

const getFilePreview = async () => {
    previewLoading.value = true;
    previewError.value = null;
    previewData.value = null;
    
    try {
        const formData = new FormData();
        formData.append('file', selectedFile.value);
        
        const response = await axios.post('/api/pcap/preview', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        
        if (response.data.success) {
            previewData.value = response.data.summary;
            emit('preview-ready', response.data.summary);
        } else {
            previewError.value = response.data.message || 'Failed to generate preview';
        }
    } catch (error) {
        previewError.value = error.response?.data?.message || 'Failed to generate preview';
        console.error('Preview error:', error);
    } finally {
        previewLoading.value = false;
    }
};

const clearFile = () => {
    selectedFile.value = null;
    previewData.value = null;
    previewError.value = null;
};

defineExpose({
    selectedFile,
    clearFile
});
</script>

<template>
    <div class="space-y-4">
        <!-- Drag and Drop Area -->
        <div
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
            :class="[
                'border-2 border-dashed rounded-lg p-8 text-center transition-all cursor-pointer',
                isDragging
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : 'border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500'
            ]"
            @click="$refs.fileInput.click()"
        >
            <input
                ref="fileInput"
                type="file"
                :accept="acceptedTypes"
                @change="handleFileInput"
                class="hidden"
            />
            
            <div v-if="!selectedFile" class="space-y-3">
                <!-- Upload Icon -->
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                
                <div>
                    <p class="text-lg font-medium text-gray-700 dark:text-gray-300">
                        Drop your file here or click to browse
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Supports CSV, TXT, PCAP, and PCAPNG files (up to {{ formatFileSize(maxSize) }})
                    </p>
                </div>
            </div>
            
            <div v-else class="space-y-3">
                <!-- File Icon -->
                <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                
                <div>
                    <p class="text-lg font-medium text-gray-700 dark:text-gray-300">
                        {{ fileInfo.name }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ fileInfo.size }} • {{ fileInfo.extension }} File
                    </p>
                </div>
                
                <button
                    @click.stop="clearFile"
                    type="button"
                    class="mt-2 px-4 py-2 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200 dark:bg-red-900 dark:text-red-200"
                >
                    Remove File
                </button>
            </div>
        </div>
        
        <!-- Preview Area for PCAP Files -->
        <div v-if="isPcapFile && showPreview" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                File Preview
            </h3>
            
            <!-- Loading State -->
            <div v-if="previewLoading" class="flex items-center justify-center py-8">
                <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-600 dark:text-gray-400">Analyzing file...</span>
            </div>
            
            <!-- Error State -->
            <div v-else-if="previewError" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-red-700 dark:text-red-300">
                {{ previewError }}
            </div>
            
            <!-- Preview Data -->
            <div v-else-if="previewData" class="space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Packets</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ previewData.packet_count?.toLocaleString() || 'N/A' }}
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Duration</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ previewData.duration ? `${previewData.duration}s` : 'N/A' }}
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                        <p class="text-xs text-gray-500 dark:text-gray-400">File Size</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ formatFileSize(previewData.file_size || 0) }}
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Est. Time</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ previewData.estimated_processing_time || 'N/A' }}
                        </p>
                    </div>
                </div>
                
                <!-- Protocol Distribution -->
                <div v-if="previewData.protocols && Object.keys(previewData.protocols).length > 0">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Protocol Distribution
                    </h4>
                    <div class="space-y-2">
                        <div
                            v-for="(count, protocol) in previewData.protocols"
                            :key="protocol"
                            class="flex items-center"
                        >
                            <span class="w-16 text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ protocol }}
                            </span>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                <div
                                    class="bg-blue-500 h-full rounded-full"
                                    :style="{ width: `${(count / previewData.packet_count) * 100}%` }"
                                ></div>
                            </div>
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ count }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Capture Time Range -->
                <div v-if="previewData.start_time && previewData.end_time" class="text-sm text-gray-600 dark:text-gray-400">
                    <p>
                        <strong>Capture Period:</strong>
                        {{ new Date(previewData.start_time).toLocaleString() }} -
                        {{ new Date(previewData.end_time).toLocaleString() }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
