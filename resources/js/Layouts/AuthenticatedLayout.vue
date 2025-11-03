<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import SearchBox from '@/Components/SearchBox.vue';
import { useToast } from '@/Composables/useToast';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const setLocale = (newLocale) => {
    locale.value = newLocale;
    localStorage.setItem('locale', newLocale);
};

const showingNavigationDropdown = ref(false);
const sidebarOpen = ref(false);
const page = usePage();

// Theme management مع SSR safety
const isDark = ref(false);


// استخراج بيانات المستخدم مع fallback
const user = computed(() => {
    try {
        return page.props.auth?.user || null;
    } catch (error) {
        console.error('Error accessing user data:', error);
        return null;
    }
});

// Sidebar menu items
const sidebarMenuItems = ref([
    {
        name: 'Dashboard',
        href: '/dashboard',
        icon: 'dashboard',
        active: 'dashboard',
        description: 'Overview and statistics'
    },
    {
        name: 'Network Logs',
        href: '/network-logs',
        icon: 'logs',
        active: 'network-logs*',
        description: 'Upload and manage network logs'
    },
    {
        name: 'Live Monitoring',
        href: '/live-monitoring',
        icon: 'live',
        active: 'live-monitoring*',
        description: 'Real-time packet capture',
        badge: 'NEW'
    },
    {
        name: 'Alerts',
        href: '/alerts',
        icon: 'alerts',
        active: 'alerts*',
        description: 'Security alerts and threats'
    },
    {
        name: 'ML Models',
        href: '/ml-models',
        icon: 'models',
        active: 'ml-models*',
        description: 'Machine learning models'
    },
    {
        name: 'Analytics',
        href: '/analytics',
        icon: 'analytics',
        active: 'analytics*',
        description: 'Data analysis and reports'
    },
    {
        name: 'Notifications',
        href: '/notifications',
        icon: 'notifications',
        active: 'notifications*',
        description: 'View all notifications'
    },
    {
        name: 'Users',
        href: '/users',
        icon: 'users',
        active: 'users*',
        description: 'User management',
        adminOnly: true
    },
    {
        name: 'System Health',
        href: '/system-health',
        icon: 'settings',
        active: 'system-health*',
        description: 'System health monitoring',
        adminOnly: true
    },
    {
        name: 'Settings',
        href: '/settings',
        icon: 'settings',
        active: 'settings*',
        description: 'System configuration'
    }
]);

// Filter menu items based on user role
const filteredMenuItems = computed(() => {
    return sidebarMenuItems.value.filter(item => {
        // Admin can see everything
        if (user.value?.role === 'Admin') {
            return true;
        }
        
        // Analyst can see most items except user management
        if (user.value?.role === 'Analyst') {
            return !item.adminOnly;
        }
        
        // Viewer can only see dashboard, analytics, and profile
        if (user.value?.role === 'Viewer') {
            return ['Dashboard', 'Analytics', 'Settings'].includes(item.name);
        }
        
        // Default: hide admin-only items
        return !item.adminOnly;
    });
});

// التحقق من الصفحة النشطة
const isActive = (routeName) => {
    try {
        const currentRoute = page.url;
        if (routeName.includes('*')) {
            const baseRoute = routeName.replace('*', '');
            return currentRoute.startsWith(baseRoute);
        }
        return currentRoute === `/${routeName}` || currentRoute === routeName;
    } catch (error) {
        console.error('Error checking active route:', error);
        return false;
    }
};

// دالة للحصول على path الأيقونة
const getIconPath = (iconName) => {
    const iconPaths = {
        dashboard: "M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z",
        logs: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
        live: "M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z",
        alerts: "M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.864-.833-2.634 0L4.18 16.5c-.77.833.192 2.5 1.732 2.5z",
        models: "M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z",
        analytics: "M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z",
        notifications: "M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9",
        users: "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-.5a4 4 0 11-8 0 4 4 0 018 0z",
        settings: "M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"
    };
    return iconPaths[iconName] || iconPaths.dashboard;
};

// Initialize toast functions
const { success, error, warning, info } = useToast()

// Flash Messages Watcher
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;
        
        console.log('🔔 Flash message detected:', flash);
        
        try {
            // Show toast for each flash message type
            if (flash.success) {
                success('Success', flash.success);
            }
            if (flash.error) {
                error('Error', flash.error);
            }
            if (flash.warning) {
                warning('Warning', flash.warning);
            }
            if (flash.info) {
                info('Information', flash.info);
            }
        } catch (err) {
            console.error('Error processing flash message:', err);
        }
    },
    { immediate: true } // Removed deep: true to prevent infinite recursion
);


// Theme management functions مع error handling
const initTheme = () => {
    try {
        if (typeof window !== 'undefined') {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            isDark.value = savedTheme === 'dark' || (!savedTheme && prefersDark);
            updateTheme();
        }
    } catch (error) {
        console.error('Error initializing theme:', error);
        isDark.value = false;
    }
};

const toggleTheme = () => {
    try {
        isDark.value = !isDark.value;
        
        if (typeof window !== 'undefined') {
            localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
        }
        
        updateTheme();
        console.log('🎨 Theme toggled:', isDark.value ? 'dark' : 'light');
    } catch (error) {
        console.error('Error toggling theme:', error);
    }
};

const updateTheme = () => {
    try {
        if (typeof document !== 'undefined') {
            if (isDark.value) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    } catch (error) {
        console.error('Error updating theme:', error);
    }
};

// Navigation functions مع error handling
const goToNetworkLogs = () => {
    try {
        router.get('/network-logs');
    } catch (error) {
        console.error('Error navigating to network logs:', error);
    }
};

const goToAnalytics = () => {
    try {
        router.get('/analytics');
    } catch (error) {
        console.error('Error navigating to analytics:', error);
    }
};

const goToSettings = () => {
    try {
        router.get('/settings');
    } catch (error) {
        console.error('Error navigating to settings:', error);
    }
};

// 🔥 LOGOUT FUNCTION - الحل الأساسي للمشكلة
const logout = () => {
    try {
        console.log('🚪 Logging out user...');
        
        // استخدام /logout مباشرة بدلاً من route() helper
        router.post('/logout', {}, {
            onSuccess: () => {
                console.log('✅ Logout successful');
                // لا حاجة لـ redirect لأن Laravel سيتولى ذلك
            },
            onError: (errors) => {
                console.error('❌ Logout failed:', errors);
                error('Logout Error', 'Failed to logout. Please try again.');
            },
            onFinish: () => {
                console.log('🏁 Logout request finished');
            }
        });
    } catch (error) {
        console.error('Error during logout:', error);
        error('Logout Error', 'An unexpected error occurred during logout.');
        
        // Fallback - redirect to logout manually
        try {
            window.location.href = '/logout';
        } catch (fallbackError) {
            console.error('Fallback redirect failed:', fallbackError);
        }
    }
};

onMounted(() => {
    try {
        console.log('🚀 AuthenticatedLayout mounted');
        
        // Initialize theme
        initTheme();

        // Load saved locale
        const savedLocale = localStorage.getItem('locale');
        if (savedLocale) {
            locale.value = savedLocale;
        }
        
        // إغلاق sidebar عند النقر خارجه
        const handleClickOutside = (event) => {
            try {
                const sidebar = document.getElementById('sidebar');
                const sidebarButton = document.getElementById('sidebar-button');
                
                if (sidebar && !sidebar.contains(event.target) && !sidebarButton?.contains(event.target)) {
                    sidebarOpen.value = false;
                }
            } catch (error) {
                console.error('Error handling click outside:', error);
            }
        };
        
        if (typeof document !== 'undefined') {
            document.addEventListener('mousedown', handleClickOutside);
            
            // Cleanup function
            return () => {
                document.removeEventListener('mousedown', handleClickOutside);
            };
        }
    } catch (error) {
        console.error('Error during component mount:', error);
    }
});
</script>

<template>
    <!-- Toast Container -->
    <ToastContainer />
    
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex">
        <!-- Sidebar -->
        <div 
            id="sidebar"
            :class="[
                'fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <!-- Sidebar Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <!-- Logo مع صورة حقيقية -->
                <Link href="/dashboard" class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-lg">AI</span>
                    </div>
                    <div class="hidden lg:block">
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">AI-IDS</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Security Platform</p>
                    </div>
                </Link>
                
                <!-- Close button for mobile -->
                <button 
                    @click="sidebarOpen = false"
                    class="lg:hidden p-1 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- User Profile Section -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                        <span class="text-sm font-semibold text-white">
                            {{ user?.name?.charAt(0).toUpperCase() || 'U' }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ user?.name || 'Unknown User' }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ user?.role || 'User' }} • {{ user?.email || 'No email' }}
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
                <Link 
                    v-for="item in filteredMenuItems" 
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'group flex items-center px-4 py-3 rounded-lg text-sm font-medium transition-all duration-200',
                        isActive(item.active) 
                            ? 'bg-blue-50 dark:bg-blue-900/50 text-blue-700 dark:text-blue-200 border-r-2 border-blue-500' 
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'
                    ]"
                    @click="sidebarOpen = false"
                >
                    <svg 
                        class="mr-4 h-5 w-5 flex-shrink-0" 
                        :class="[
                            isActive(item.active) 
                                ? 'text-blue-500 dark:text-blue-400' 
                                : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300'
                        ]"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path 
                            stroke-linecap="round" 
                            stroke-linejoin="round" 
                            stroke-width="2" 
                            :d="getIconPath(item.icon)"
                        />
                    </svg>
                    <div class="flex-1 min-w-0">
                        <span class="truncate">{{ item.name }}</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ item.description }}
                        </p>
                    </div>
                </Link>
            </nav>
            
            <!-- Sidebar Footer -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>AI-IDS v1.0</span>
                    <div class="flex items-center space-x-1">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span>Online</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mobile sidebar overlay -->
        <div 
            v-if="sidebarOpen" 
            class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
            @click="sidebarOpen = false"
        ></div>
        
        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col lg:ml-0">
            <!-- Top Header -->
            <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between px-6 py-4">
                    <!-- Left side - Mobile menu button + Breadcrumb -->
                    <div class="flex items-center space-x-4">
                        <!-- Mobile sidebar toggle -->
                        <button
                            id="sidebar-button"
                            @click="sidebarOpen = !sidebarOpen"
                            class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        
                        <!-- Breadcrumb -->
                        <div class="hidden md:flex items-center space-x-2 text-sm">
                            <Link href="/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                                Dashboard
                            </Link>
                            <svg class="w-4 h-4 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-900 dark:text-white font-medium">
                                Overview
                            </span>
                        </div>
                    </div>
                    
                    <!-- Right side - Actions and user menu -->
                    <div class="flex items-center space-x-4">
                        <!-- Global Search -->
                        <div class="hidden md:block">
                            <SearchBox />
                        </div>
                        
                        <!-- Theme Toggle -->
                        <button 
                            @click="toggleTheme"
                            class="p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                        >
                            <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                        </button>
                        
                        <!-- Notifications -->
                        <Link 
                            href="/notifications" 
                            class="relative p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            title="View Notifications"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">3</span>
                        </Link>
                        
                        <!-- User dropdown -->
                        <div class="relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center space-x-2 px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-lg text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700">
                                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-semibold text-white">
                                                {{ user?.name?.charAt(0).toUpperCase() || 'U' }}
                                            </span>
                                        </div>
                                        <span class="hidden md:block">{{ user?.name || 'User' }}</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                </template>
                                
                                <template #content>
                                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-600">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ user?.name || 'Unknown User' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ user?.email || 'No email' }}</p>
                                        <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">{{ user?.role || 'User' }}</p>
                                    </div>
                                    
                                    <DropdownLink :href="'/profile'">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        Profile Settings
                                    </DropdownLink>
                                    
                                    <DropdownLink :href="'/settings'">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        System Settings
                                    </DropdownLink>

                                    <div class="border-t border-gray-100 dark:border-gray-600"></div>

                                    <div class="px-4 py-3">
                                        <p class="text-xs text-gray-400">Language</p>
                                        <div class="mt-2 space-y-1">
                                            <button @click="setLocale('en')" class="w-full text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md px-2 py-1">English</button>
                                            <button @click="setLocale('ar')" class="w-full text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md px-2 py-1">العربية</button>
                                        </div>
                                    </div>
                                    
                                    <div class="border-t border-gray-100 dark:border-gray-600"></div>
                                    
                                    <!-- 🔥 LOGOUT BUTTON - الحل النهائي -->
                                    <DropdownLink @click="logout" as="button">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        Sign Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 p-6 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
/* إضافة تحسينات CSS */
.sidebar-transition {
    transition: transform 0.3s ease-in-out;
}

.gradient-avatar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* تحسينات responsive */
@media (max-width: 768px) {
    .sidebar-menu-item {
        padding: 0.75rem 1rem;
    }
    
    .sidebar-menu-item .description {
        display: none;
    }
}

/* Dark mode improvements */
@media (prefers-color-scheme: dark) {
    .glass-effect {
        background: rgba(31, 41, 55, 0.8);
        backdrop-filter: blur(10px);
    }
}
</style>