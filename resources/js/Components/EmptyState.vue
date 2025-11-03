<template>
    <div class="text-center py-8">
        <div class="text-4xl mb-2">{{ icon }}</div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ title }}</h3>
        <p class="text-gray-500 dark:text-gray-400">{{ message }}</p>
    </div>
</template>

<script setup>
defineProps({
    icon: {
        type: String,
        default: '🤷‍',
    },
    title: {
        type: String,
        default: 'No data available',
    },
    message: {
        type: String,
        default: 'There is no data to display at the moment.',
    },
});
</script>
