<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    investigation: Object,
    analysts: { type: Array, default: () => [] }
});

const form = useForm({
    title: props.investigation.title,
    description: props.investigation.description,
    status: props.investigation.status,
    priority: props.investigation.priority,
    assigned_to: props.investigation.assigned_to?.id || '',
    resolution_notes: props.investigation.resolution_notes || ''
});

const submit = () => {
    form.put(route('investigations.update', props.investigation.id));
};
</script>

<template>
    <Head title="Edit Investigation" />
    <AuthenticatedLayout>
        <div class="py-6 max-w-4xl mx-auto px-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold dark:text-white">Edit Investigation</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ investigation.title }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
                <form @submit.prevent="submit">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Investigation Title *</label>
                            <input v-model="form.title" type="text" required class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Description</label>
                            <textarea v-model="form.description" rows="4" class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium mb-2 dark:text-white">Status *</label>
                                <select v-model="form.status" required class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 dark:text-white">Priority *</label>
                                <select v-model="form.priority" required class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                    <option value="critical">Critical</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Assign to Analyst</label>
                            <select v-model="form.assigned_to" class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="">Unassigned</option>
                                <option v-for="analyst in analysts" :key="analyst.id" :value="analyst.id">{{ analyst.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Resolution Notes</label>
                            <textarea v-model="form.resolution_notes" rows="4" class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" placeholder="Add notes about the resolution of this investigation..."></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <Link :href="route('investigations.show', investigation.id)" class="px-4 py-2 border dark:border-gray-600 rounded-lg dark:text-white ml-3">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg disabled:opacity-50">
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
