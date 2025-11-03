<template>
    <div class="relative">
        <!-- Notification Bell Button -->
        <button 
            @click="toggleDropdown"
            class="relative p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-full transition-colors duration-200"
        >
            <!-- Bell Icon -->
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            
            <!-- Notification Badge -->
            <span 
                v-if="unreadCount > 0" 
                class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full min-w-[18px] h-[18px]"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Menu -->
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div 
                v-show="showDropdown" 
                class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg overflow-hidden z-50 border border-gray-200"
                @click.stop
            >
                <!-- Header -->
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
                        <span v-if="unreadCount > 0" class="text-xs text-red-500 font-medium">{{ unreadCount }} unread</span>
                        <span v-else class="text-xs text-gray-400">All caught up!</span>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="px-4 py-6 text-center">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600 mx-auto"></div>
                    <p class="mt-2 text-xs text-gray-500">Loading...</p>
                </div>

                <!-- Notifications List -->
                <div v-else class="max-h-64 overflow-y-auto">
                    <div v-if="notifications.length === 0" class="px-4 py-6 text-center text-gray-500">
                        <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <p class="text-sm">No notifications</p>
                    </div>
                    <div v-else>
                        <div 
                            v-for="notification in notifications.slice(0, 5)" 
                            :key="notification.id"
                            class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100 cursor-pointer transition-colors duration-150"
                            :class="{ 'bg-blue-50': !notification.is_read }"
                            @click="handleNotificationClick(notification)"
                        >
                            <div class="flex items-start space-x-3">
                                <span class="text-lg flex-shrink-0">{{ notification.icon }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ notification.title }}</p>
                                        <div class="flex items-center space-x-1">
                                            <div v-if="!notification.is_read" class="w-2 h-2 bg-blue-600 rounded-full"></div>
                                            <span class="text-xs text-gray-500">{{ notification.created_at }}</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ notification.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                    <button 
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="text-xs text-blue-600 hover:text-blue-500 font-medium"
                    >
                        Mark all read
                    </button>
                    <Link 
                        href="/notifications"
                        @click="closeDropdown"
                        class="text-xs text-gray-600 hover:text-gray-800 font-medium"
                    >
                        View all →
                    </Link>
                </div>
            </div>
        </transition>

        <!-- Background Overlay -->
        <div 
            v-show="showDropdown" 
            @click="closeDropdown"
            class="fixed inset-0 z-40"
        ></div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'

const { success, error } = useToast()

const showDropdown = ref(false)
const notifications = ref([])
const unreadCount = ref(0)
const loading = ref(false)

// Auto refresh interval
let refreshInterval = null

const toggleDropdown = () => {
    showDropdown.value = !showDropdown.value
    if (showDropdown.value) {
        fetchNotifications()
    }
}

const closeDropdown = () => {
    showDropdown.value = false
}

const fetchNotifications = async () => {
    loading.value = true
    try {
        const response = await fetch('/api/notifications')
        if (response.ok) {
            const data = await response.json()
            notifications.value = data.notifications
            unreadCount.value = data.unread_count
            console.log('Notifications fetched:', data)
        } else {
            console.error('Failed to fetch notifications:', response.status)
            // Don't show error toast for background refresh
        }
    } catch (err) {
        console.error('Error fetching notifications:', err)
        // Don't show error toast for background refresh
    } finally {
        loading.value = false
    }
}

const handleNotificationClick = async (notification) => {
    // Mark as read if unread
    if (!notification.is_read) {
        // Use router.post for CSRF protection
        router.post(`/notifications/${notification.id}/read`, {}, {
            preserveState: true,
            preserveScroll: true,
            only: [],
            onSuccess: () => {
                // Update local state immediately
                notification.is_read = true
                unreadCount.value = Math.max(0, unreadCount.value - 1)
            }
        })
    }

    // Navigate if action URL exists
    if (notification.action_url) {
        closeDropdown()
        router.visit(notification.action_url)
    }
}

const markAllAsRead = async () => {
    if (unreadCount.value === 0) return

    const count = unreadCount.value
    
    router.post('/notifications/read-all', {}, {
        preserveState: true,
        preserveScroll: true,
        only: [],
        onSuccess: () => {
            // Update local state immediately
            notifications.value.forEach(n => n.is_read = true)
            unreadCount.value = 0
            success('Success', `${count} notifications marked as read`)
        },
        onError: (errors) => {
            console.error('Error marking all as read:', errors)
            error('Error', 'Failed to mark all notifications as read')
        }
    })
}

// Auto refresh every 30 seconds
onMounted(() => {
    fetchNotifications()
    refreshInterval = setInterval(fetchNotifications, 30000)
})

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval)
    }
})
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
