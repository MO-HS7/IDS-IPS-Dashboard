<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    networkLogs: Object,
});

const statistics = computed(() => ({
    total: props.networkLogs?.total || 0,
    processed: props.networkLogs?.data?.filter(log => log.status === 'processed').length || 0,
    processing: props.networkLogs?.data?.filter(log => log.status === 'processing').length || 0,
    pending: props.networkLogs?.data?.filter(log => log.status === 'pending').length || 0,
    failed: props.networkLogs?.data?.filter(log => log.status === 'failed').length || 0,
}));

const deletingId = ref(null);

const deleteLog = (id) => {
    if (!id) {
        alert("Error: Missing log ID!");
        return;
    }

    if (!confirm('Are you sure you want to delete this network log?')) {
        return;
    }

    deletingId.value = id;

    // حاول نولد URL من route() (Ziggy) ثم fallback لعنوان يدوي
    let url = null;
    try {
        url = route('network-logs.destroy', { network_log: id });
    } catch (e) {
        // إذا route غير معرف أو رمى خطأ
        url = `/network-logs/${encodeURIComponent(id)}`;
    }

    // تأكد أن الـ id موجود داخل الـ url؛ إن لم يكن، استخدم fallback
    if (!String(url).includes(String(id))) {
        url = `/network-logs/${encodeURIComponent(id)}`;
    }

    // طبع للمساعدة في تتبع المشكلة (افتح Console في المتصفح)
    console.log('[deleteLog] id:', id, ' -> url:', url);

    router.delete(url, {
        preserveScroll: true,
        onSuccess: () => {
            deletingId.value = null;
            // حدث الصفحة / جلب البيانات من جديد لعرض القائمة المحدثة
            router.reload();
        },
        onError: (errors) => {
            deletingId.value = null;
            console.error('[deleteLog] error:', errors);
            alert('Failed to delete log. Check console for details.');
        }
    });
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'text-yellow-600 bg-yellow-100',
        processing: 'text-blue-600 bg-blue-100',
        processed: 'text-green-600 bg-green-100',
        failed: 'text-red-600 bg-red-100'
    };
    return colors[status] || 'text-gray-600 bg-gray-100';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString();
};

const formatFileSize = (bytes) => {
    if (!bytes) return 'N/A';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};

const getFileTypeBadge = (fileType) => {
    const badges = {
        csv: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        pcap: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        pcapng: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
    };
    return badges[fileType] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
};

const retryProcessing = (id) => {
    if (!confirm('Retry processing this file?')) {
        return;
    }

    router.post(`/api/pcap/retry/${id}`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload();
        }
    });
};
</script>

<template>
    <Head title="Network Logs" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-6">
                <!-- Header -->
                <div class="mb-6 flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold dark:text-white">📁 Network Logs</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Upload and manage network traffic captures</p>
                    </div>
                    <Link :href="route('network-logs.create')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Upload New Log
                    </Link>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-5 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Logs</p>
                        <p class="text-2xl font-bold dark:text-white">{{ statistics.total }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Processed</p>
                        <p class="text-2xl font-bold text-green-600">{{ statistics.processed }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Processing</p>
                        <p class="text-2xl font-bold text-blue-600">{{ statistics.processing }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Pending</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ statistics.pending }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Failed</p>
                        <p class="text-2xl font-bold text-red-600">{{ statistics.failed }}</p>
                    </div>
                </div>
                
                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">File Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type / Size</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Packets</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>

                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="log in networkLogs.data" :key="log.id">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                            <div>
                                                {{ log.file_name }}
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ formatDate(log.upload_date) }} • {{ log.user?.name || 'Unknown' }}
                                                </p>
                                                <!-- Progress bar for processing -->
                                                <div v-if="log.status === 'processing'" class="mt-2">
                                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                        <div 
                                                            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                            :style="{ width: `${log.processing_progress || 0}%` }"
                                                        ></div>
                                                    </div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        Processing... {{ log.processing_progress || 0 }}%
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <span :class="getFileTypeBadge(log.file_type)" class="px-2 py-1 text-xs font-semibold rounded uppercase">
                                                {{ log.file_type || 'csv' }}
                                            </span>
                                            <p class="text-xs mt-1">{{ formatFileSize(log.file_size) }}</p>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ log.packet_count ? log.packet_count.toLocaleString() : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getStatusColor(log.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                {{ log.status }}
                                            </span>
                                            <p v-if="log.processing_error" class="text-xs text-red-600 dark:text-red-400 mt-1">
                                                {{ log.processing_error.substring(0, 50) }}...
                                            </p>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2 items-center">
                                                <Link :href="route('network-logs.show', log.id)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">View</Link>
                                                <Link :href="route('network-logs.view', log.id)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">View Log</Link>
                                                
                                                <button
                                                    v-if="log.status === 'failed' && (log.file_type === 'pcap' || log.file_type === 'pcapng')"
                                                    @click="retryProcessing(log.id)"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                >
                                                    Retry
                                                </button>

                                                <button
                                                    @click="deleteLog(log.id)"
                                                    :disabled="deletingId === log.id"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 disabled:opacity-50"
                                                >
                                                    <span v-if="deletingId === log.id">Deleting...</span>
                                                    <span v-else>Delete</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="networkLogs.links && networkLogs.data.length" class="mt-6 flex justify-between items-center">
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                Showing {{ networkLogs.from }} to {{ networkLogs.to }} of {{ networkLogs.total }} results
                            </div>
                            <div class="flex space-x-2">
                                <Link v-if="networkLogs.prev_page_url" :href="networkLogs.prev_page_url" class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">Previous</Link>
                                <Link v-if="networkLogs.next_page_url" :href="networkLogs.next_page_url" class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">Next</Link>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
