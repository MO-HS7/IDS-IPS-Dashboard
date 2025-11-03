<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: false,
        default: null,
    },
    as: {
        type: String,
        default: 'link',
        validator: (value) => ['link', 'button'].includes(value)
    },
});

const isButton = props.as === 'button' || !props.href;
</script>

<template>
    <!-- Render as Link -->
    <Link
        v-if="!isButton"
        :href="href"
        class="flex items-center w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 transition duration-150 ease-in-out"
    >
        <slot />
    </Link>

    <!-- Render as Button -->
    <button
        v-else
        type="button"
        class="flex items-center w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 transition duration-150 ease-in-out"
    >
        <slot />
    </button>
</template>
