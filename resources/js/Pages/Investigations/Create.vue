<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    analysts: { type: Array, default: () => [] },
    availableAlerts: { type: Array, default: () => [] }
});

const form = useForm({
    title: '',
    description: '',
    priority: 'medium',
    assigned_to: '',
    alert_ids: []
});

const submit = () => {
    form.post(route('investigations.store'));
};
</script>

<template>
    <Head title="Create Investigation" />
    <AuthenticatedLayout>
        <div class="py-6 max-w-4xl mx-auto px-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold dark:text-white">Create New Investigation</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Create a new security investigation case</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
                <form @submit.prevent="submit">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Investigation Title *</label>
                            <input v-model="form.title" type="text" required class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Description</label>
                            <textarea v-model="form.description" rows="4" class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium mb-2 dark:text-white">Priority *</label>
                                <select v-model="form.priority" required class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                    <option value="critical">Critical</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 dark:text-white">Assign to Analyst</label>
                                <select v-model="form.assigned_to" class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                    <option value="">Unassigned</option>
                                    <option v-for="analyst in analysts" :key="analyst.id" :value="analyst.id">{{ analyst.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="availableAlerts.length">
                            <label class="block text-sm font-medium mb-2 dark:text-white">Link Alerts</label>
                            <div class="border dark:border-gray-600 rounded-lg p-4 max-h-60 overflow-y-auto">
                                <label v-for="alert in availableAlerts" :key="alert.id" class="flex items-center py-2 hover:bg-gray-50 dark:hover:bg-gray-700 px-2 rounded">
                                    <input type="checkbox" :value="alert.id" v-model="form.alert_ids" class="rounded mr-3" />
                                    <div class="flex-1">
                                        <div class="text-sm font-medium dark:text-white">{{ alert.attack_type }}</div>
                                        <div class="text-xs text-gray-500">{{ alert.detected_at }} - {{ alert.severity }}</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <Link :href="route('investigations.index')" class="px-4 py-2 border dark:border-gray-600 rounded-lg dark:text-white ml-3">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg disabled:opacity-50">
                            {{ form.processing ? 'Creating...' : 'Create Investigation' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
