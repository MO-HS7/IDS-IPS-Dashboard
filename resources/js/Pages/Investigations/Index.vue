<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from '@/Composables/useToast';

const { success } = useToast();

const props = defineProps({
    investigations: Object,
    statistics: { type: Object, default: () => ({}) },
    analysts: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) }
});

const searchQuery = ref(props.filters.search || '');
const filterStatus = ref(props.filters.status || '');
const filterPriority = ref(props.filters.priority || '');

const applyFilters = () => {
    router.get('/investigations', {
        search: searchQuery.value || undefined,
        status: filterStatus.value || undefined,
        priority: filterPriority.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
};

const getStatusClass = (s) => ({
    open: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    in_progress: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    resolved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    closed: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
}[s] || 'bg-gray-100 text-gray-800');

const getPriorityClass = (p) => ({
    critical: 'bg-red-100 text-red-800',
    high: 'bg-orange-100 text-orange-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-green-100 text-green-800'
}[p] || 'bg-yellow-100 text-yellow-800');
</script>

<template>
    <Head title="Investigations" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-6">
                <!-- Header -->
                <div class="mb-6 flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold dark:text-white">🔍 Investigations</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Manage security investigation cases</p>
                    </div>
                    <Link :href="route('investigations.create')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New Investigation
                    </Link>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total</p>
                        <p class="text-2xl font-bold dark:text-white">{{ statistics.total || 0 }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Open</p>
                        <p class="text-2xl font-bold text-blue-600">{{ statistics.open || 0 }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">In Progress</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ statistics.in_progress || 0 }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Resolved</p>
                        <p class="text-2xl font-bold text-green-600">{{ statistics.resolved || 0 }}</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex gap-4">
                        <input v-model="searchQuery" @input="handleSearch" placeholder="Search investigations..." class="flex-1 px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
                        <select v-model="filterStatus" @change="applyFilters" class="px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <option value="">All Status</option>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <select v-model="filterPriority" @change="applyFilters" class="px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <option value="">All Priorities</option>
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow">
                    <table v-if="investigations.data && investigations.data.length" class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Alerts</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Assigned To</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <tr v-for="inv in investigations.data" :key="inv.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium dark:text-white">{{ inv.title }}</div>
                                    <div class="text-xs text-gray-500 truncate max-w-xs">{{ inv.description || 'No description' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getStatusClass(inv.status)" class="px-3 py-1 text-xs rounded-full">{{ inv.status }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getPriorityClass(inv.priority)" class="px-3 py-1 text-xs rounded-full">{{ inv.priority }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm dark:text-white">{{ inv.alert_count }}</td>
                                <td class="px-6 py-4 text-sm dark:text-white">{{ inv.assigned_to }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ inv.created_at }}</td>
                                <td class="px-6 py-4 text-left space-x-2">
                                    <Link :href="route('investigations.show', inv.id)" class="text-blue-600 hover:text-blue-900 text-sm">View</Link>
                                    <Link :href="route('investigations.edit', inv.id)" class="text-indigo-600 hover:text-indigo-900 text-sm">Edit</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="p-12 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Investigations Found</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">
                            {{ searchQuery ? 'No results found' : 'Get started by creating a new security investigation' }}
                        </p>
                        <Link v-if="!searchQuery" :href="route('investigations.create')" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                            Create First Investigation
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
