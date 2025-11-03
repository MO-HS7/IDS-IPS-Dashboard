<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    networkLogs: Object,
});

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
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Network Logs
                </h2>
                <Link
                    :href="route('network-logs.create')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    Upload New Log
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
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
                        <div class="mt-6 flex justify-between items-center">
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
