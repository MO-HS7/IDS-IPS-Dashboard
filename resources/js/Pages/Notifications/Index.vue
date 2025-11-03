<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    notifications: {
        type: Object,
        default: () => ({ data: [] })
    }
});

// Mark notification as read
const markAsRead = (id) => {
    router.post(`/notifications/${id}/read`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Notification marked
        }
    });
};

// Mark all as read
const markAllAsRead = () => {
    router.post('/notifications/read-all', {}, {
        preserveScroll: true,
    });
};

// Delete notification
const deleteNotification = (id) => {
    if (confirm('Are you sure you want to delete this notification?')) {
        router.delete(`/notifications/${id}`, {
            preserveScroll: true,
        });
    }
};

// Clear all notifications
const clearAll = () => {
    if (confirm('Are you sure you want to clear all notifications?')) {
        router.delete('/notifications/clear-all', {
            preserveScroll: true,
        });
    }
};

// Get icon based on type
const getIcon = (type) => {
    const icons = {
        'alert': '🚨',
        'info': 'ℹ️',
        'success': '✅',
        'warning': '⚠️',
        'threat': '🛡️',
        'system': '⚙️'
    };
    return icons[type] || '🔔';
};

// Get color based on type
const getColor = (type, isRead) => {
    if (isRead) return 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700';
    
    const colors = {
        'alert': 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
        'threat': 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
        'warning': 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
        'success': 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
        'info': 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
        'system': 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700'
    };
    return colors[type] || 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700';
};

// Format date
const formatDate = (date) => {
    const d = new Date(date);
    const now = new Date();
    const diff = now - d;
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (seconds < 60) return 'just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    return d.toLocaleDateString();
};
</script>

<template>
    <Head title="Notifications" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Notifications
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Stay updated with system alerts and activities
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        @click="markAllAsRead"
                        class="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                    >
                        Mark all as read
                    </button>
                    <button
                        @click="clearAll"
                        class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                    >
                        Clear all
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Success Message -->
                <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded dark:bg-green-900 dark:border-green-700 dark:text-green-200">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Notifications List -->
                <div v-if="notifications.data && notifications.data.length > 0" class="space-y-3">
                    <div
                        v-for="notification in notifications.data"
                        :key="notification.id"
                        :class="[
                            'border rounded-lg transition-all duration-200 hover:shadow-md',
                            getColor(notification.type, notification.read_at)
                        ]"
                    >
                        <div class="p-4 sm:p-6">
                            <div class="flex items-start">
                                <!-- Icon -->
                                <div class="flex-shrink-0">
                                    <div class="text-2xl">
                                        {{ getIcon(notification.type) }}
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="ml-4 flex-1">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ notification.data?.title || 'Notification' }}
                                            </h3>
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                                {{ notification.data?.message || 'No message' }}
                                            </p>
                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatDate(notification.created_at) }}
                                            </p>
                                        </div>

                                        <!-- Actions -->
                                        <div class="ml-4 flex items-center space-x-2">
                                            <button
                                                v-if="!notification.read_at"
                                                @click="markAsRead(notification.id)"
                                                class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                                title="Mark as read"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button
                                                @click="deleteNotification(notification.id)"
                                                class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                                title="Delete"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Additional Info -->
                                    <div v-if="notification.data?.link" class="mt-3">
                                        <Link
                                            :href="notification.data.link"
                                            class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                        >
                                            View details
                                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No notifications</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            You're all caught up! Check back later for new updates.
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="notifications.links && notifications.data.length > 0" class="mt-6">
                    <nav class="flex justify-center items-center space-x-2">
                        <template v-for="link in notifications.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-2 rounded-md text-sm transition-colors duration-200',
                                    link.active
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600'
                                ]"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="px-3 py-2 rounded-md text-sm bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500 border border-gray-300 dark:border-gray-600"
                            />
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
