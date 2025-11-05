<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { useToast } from '@/Composables/useToast';

const { success, error } = useToast();

const props = defineProps({
    rules: Object,
    statistics: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) }
});

const showImportModal = ref(false);
const searchQuery = ref(props.filters.search || '');
const filterCategory = ref(props.filters.category || '');
const filterSeverity = ref(props.filters.severity || '');
const filterStatus = ref(props.filters.status || '');
const deletingId = ref(null);

const importForm = useForm({ file: null });

const applyFilters = () => {
    router.get('/rules', {
        search: searchQuery.value || undefined,
        category: filterCategory.value || undefined,
        severity: filterSeverity.value || undefined,
        status: filterStatus.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
};

const toggleRule = (ruleId) => {
    router.post(`/rules/${ruleId}/toggle`, {}, {
        preserveScroll: true,
        onSuccess: () => success('Success', 'Rule status updated!'),
        onError: () => error('Error', 'Failed to update rule status')
    });
};

const deleteRule = (ruleId) => {
    if (confirm('Delete this rule?')) {
        deletingId.value = ruleId;
        router.delete(`/rules/${ruleId}`, {
            preserveScroll: true,
            onFinish: () => deletingId.value = null
        });
    }
};

const submitImport = () => {
    if (!importForm.file) return error('Error', 'Select a file');
    importForm.post('/rules/import', {
        onSuccess: () => { showImportModal.value = false; importForm.reset(); }
    });
};

const getSeverityClass = (s) => ({
    critical: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    high: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    low: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
}[s] || 'bg-yellow-100 text-yellow-800');

const getCategoryClass = (c) => ({
    malware: 'bg-purple-100 text-purple-800',
    exploit: 'bg-red-100 text-red-800',
    dos: 'bg-orange-100 text-orange-800',
    scan: 'bg-blue-100 text-blue-800',
    custom: 'bg-cyan-100 text-cyan-800'
}[c] || 'bg-gray-100 text-gray-800');
</script>

<template>
    <Head title="Rules Management" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-6">
                <!-- Header -->
                <div class="mb-6 flex justify-between">
                    <div>
                        <h1 class="text-2xl font-bold dark:text-white">Rules & Signatures</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Manage Snort detection rules</p>
                    </div>
                    <div class="flex gap-2">
                        <button @click="showImportModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
                            Import
                        </button>
                        <a href="/rules/export" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                            Export
                        </a>
                        <Link :href="route('rules.create')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                            Add Rule
                        </Link>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-5 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total</p>
                        <p class="text-2xl font-bold dark:text-white">{{ statistics.total_rules || 0 }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Enabled</p>
                        <p class="text-2xl font-bold text-green-600">{{ statistics.enabled_rules || 0 }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Disabled</p>
                        <p class="text-2xl font-bold text-gray-600">{{ statistics.disabled_rules || 0 }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Critical</p>
                        <p class="text-2xl font-bold text-red-600">{{ statistics.critical_rules || 0 }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Triggers</p>
                        <p class="text-2xl font-bold text-purple-600">{{ statistics.total_triggers || 0 }}</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex gap-4">
                        <input v-model="searchQuery" @input="handleSearch" placeholder="Search..." class="flex-1 px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
                        <select v-model="filterCategory" @change="applyFilters" class="px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <option value="">All Categories</option>
                            <option value="malware">Malware</option>
                            <option value="exploit">Exploit</option>
                            <option value="dos">DoS</option>
                            <option value="custom">Custom</option>
                        </select>
                        <select v-model="filterSeverity" @change="applyFilters" class="px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <option value="">All Severities</option>
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                        <select v-model="filterStatus" @change="applyFilters" class="px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            <option value="">All Status</option>
                            <option value="enabled">Enabled</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
                    <table v-if="rules.data && rules.data.length" class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">SID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Severity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Triggers</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <tr v-for="rule in rules.data" :key="rule.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 text-sm font-mono dark:text-white">{{ rule.sid }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium dark:text-white">{{ rule.name }}</div>
                                    <div class="text-xs text-gray-500 truncate max-w-xs">{{ rule.description || 'No description' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getCategoryClass(rule.category)" class="px-3 py-1 text-xs rounded-full capitalize">{{ rule.category }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getSeverityClass(rule.severity)" class="px-3 py-1 text-xs rounded-full capitalize">{{ rule.severity }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm dark:text-white">{{ rule.alert_count }}</td>
                                <td class="px-6 py-4">
                                    <button @click="toggleRule(rule.id)" :class="['relative inline-flex h-6 w-11 rounded-full transition-colors', rule.enabled ? 'bg-green-600' : 'bg-gray-200']">
                                        <span :class="['inline-block h-5 w-5 transform rounded-full bg-white shadow transition', rule.enabled ? 'translate-x-5' : 'translate-x-0']"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <Link :href="route('rules.show', rule.id)" class="text-blue-600 hover:text-blue-900">View</Link>
                                    <Link :href="route('rules.edit', rule.id)" class="text-indigo-600 hover:text-indigo-900">Edit</Link>
                                    <button @click="deleteRule(rule.id)" :disabled="deletingId === rule.id" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="p-8 text-center text-gray-500 dark:text-gray-400">No rules found</div>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <Modal :show="showImportModal" @close="showImportModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium dark:text-white mb-4">Import Snort Rules</h3>
                <form @submit.prevent="submitImport">
                    <input type="file" @change="e => importForm.file = e.target.files[0]" accept=".rules,.txt" class="block w-full mb-4" />
                    <div class="flex justify-end space-x-3">
                        <button type="button" @click="showImportModal = false" class="px-4 py-2 border rounded-lg">Cancel</button>
                        <button type="submit" :disabled="importForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Import</button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
