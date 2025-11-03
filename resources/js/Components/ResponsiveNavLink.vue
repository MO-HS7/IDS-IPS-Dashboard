<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

// Props: href, active status, and allowed roles
const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    roles: {
        type: [String, Array], // single role or array of roles
        default: null, // if null, visible to all
    },
});

const page = usePage();

// Compute dynamic classes depending on whether the link is active or not
const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-indigo-400 text-start text-base font-medium text-indigo-700 bg-indigo-50 focus:outline-none focus:text-indigo-800 focus:bg-indigo-100 focus:border-indigo-700 transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out'
);

// RoleGuard logic: check if the current user has an allowed role
const hasRole = computed(() => {
    if (!props.roles) return true; // no role restriction
    const userRole = page.props.auth.user?.role;
    if (!userRole) return false;
    const allowedRoles = Array.isArray(props.roles) ? props.roles : [props.roles];
    return allowedRoles.includes(userRole);
});
</script>

<template>
    <!-- Only render the link if user has the role -->
    <div v-if="hasRole">
        <Link :href="href" :class="classes">
            <slot />
        </Link>
    </div>
</template>
