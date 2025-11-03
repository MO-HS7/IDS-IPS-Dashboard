<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    networkLog: {
        type: Object,
    },
    logContent: {
        type: String,
    },
});
</script>

<template>
    <Head :title="`View Log: ${networkLog.file_name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                View Log: {{ networkLog.file_name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <pre class="text-sm text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-900 p-4 rounded-md">{{ logContent }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
