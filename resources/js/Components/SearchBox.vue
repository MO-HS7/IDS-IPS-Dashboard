<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useSearch } from '@/Composables/useSearch'
import { MagnifyingGlassIcon, XMarkIcon, ClockIcon, DocumentTextIcon, ShieldExclamationIcon, CpuChipIcon, ChartBarIcon, BellIcon, UserIcon } from '@heroicons/vue/24/outline'

const {
    searchQuery,
    searchResults,
    isSearching,
    showResults,
    currentModule,
    searchPlaceholder,
    handleSearch,
    navigateToResult,
    clearSearch
} = useSearch()

const searchInput = ref(null)
const searchContainer = ref(null)

// Detect OS for keyboard shortcut display
const isMac = computed(() => {
    if (typeof navigator !== 'undefined') {
        return /Mac|iPhone|iPod|iPad/i.test(navigator.platform) || /Mac|iPhone|iPod|iPad/i.test(navigator.userAgent)
    }
    return false
})

const keyboardShortcut = computed(() => {
    return isMac.value ? '⌘K' : 'Ctrl+K'
})

// Get icon based on result type
const getResultIcon = (type) => {
    const icons = {
        'network-log': DocumentTextIcon,
        'alert': ShieldExclamationIcon,
        'ml-model': CpuChipIcon,
        'analytics': ChartBarIcon,
        'notification': BellIcon,
        'user': UserIcon
    }
    return icons[type] || DocumentTextIcon
}

// Get result type label
const getResultTypeLabel = (type) => {
    const labels = {
        'network-log': 'Network Log',
        'alert': 'Alert',
        'ml-model': 'ML Model',
        'analytics': 'Analytics',
        'notification': 'Notification',
        'user': 'User'
    }
    return labels[type] || 'Result'
}

// Get severity badge color
const getSeverityColor = (severity) => {
    const colors = {
        'critical': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        'high': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        'medium': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        'low': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'info': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    }
    return colors[severity?.toLowerCase()] || colors.info
}

// Handle click outside
const handleClickOutside = (event) => {
    if (searchContainer.value && !searchContainer.value.contains(event.target)) {
        showResults.value = false
    }
}

// Keyboard shortcuts
const handleKeyDown = (event) => {
    // Ctrl + K on Windows/Linux, Cmd + K on Mac (NOT Windows Key!)
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        // Prevent default browser behavior and Windows shortcuts
        event.preventDefault()
        event.stopPropagation()
        searchInput.value?.focus()
    }
    
    // Escape to clear search
    if (event.key === 'Escape') {
        event.preventDefault()
        clearSearch()
        searchInput.value?.blur()
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    document.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    document.removeEventListener('keydown', handleKeyDown)
})
</script>

<template>
    <div ref="searchContainer" class="relative w-full md:w-96">
        <!-- Search Input -->
        <div class="relative">
            <input
                ref="searchInput"
                v-model="searchQuery"
                @input="handleSearch(searchQuery)"
                @focus="showResults = searchResults.length > 0"
                type="text"
                :placeholder="searchPlaceholder"
                class="w-full pl-10 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            >
            
            <!-- Search Icon -->
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
            
            <!-- Clear Button -->
            <button
                v-if="searchQuery"
                @click="clearSearch"
                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
                <XMarkIcon class="w-4 h-4" />
            </button>
            
            <!-- Loading Spinner -->
            <div v-if="isSearching" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
        
        <!-- Search Results Dropdown -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="showResults && searchQuery"
                class="absolute z-50 mt-2 w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 max-h-96 overflow-y-auto"
            >
                <!-- Results Header -->
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <span v-if="isSearching">Searching...</span>
                            <span v-else-if="searchResults.length > 0">
                                Found {{ searchResults.length }} result{{ searchResults.length !== 1 ? 's' : '' }}
                            </span>
                            <span v-else>No results found</span>
                        </p>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                            {{ currentModule }}
                        </span>
                    </div>
                </div>
                
                <!-- Results List -->
                <div v-if="searchResults.length > 0" class="py-2">
                    <button
                        v-for="(result, index) in searchResults"
                        :key="index"
                        @click="navigateToResult(result)"
                        class="w-full px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors flex items-start space-x-3 text-left"
                    >
                        <!-- Result Icon -->
                        <div class="flex-shrink-0 mt-1">
                            <component 
                                :is="getResultIcon(result.type)" 
                                class="w-5 h-5 text-gray-400"
                            />
                        </div>
                        
                        <!-- Result Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                    {{ getResultTypeLabel(result.type) }}
                                </span>
                                <span v-if="result.severity" :class="['text-xs px-2 py-0.5 rounded-full font-medium', getSeverityColor(result.severity)]">
                                    {{ result.severity }}
                                </span>
                            </div>
                            
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ result.title || result.name || 'Untitled' }}
                            </p>
                            
                            <p v-if="result.description" class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1">
                                {{ result.description }}
                            </p>
                            
                            <div v-if="result.timestamp" class="flex items-center mt-1 text-xs text-gray-400">
                                <ClockIcon class="w-3 h-3 mr-1" />
                                {{ result.timestamp }}
                            </div>
                        </div>
                    </button>
                </div>
                
                <!-- No Results -->
                <div v-else-if="!isSearching" class="px-6 py-10 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                        <MagnifyingGlassIcon class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">
                        No results found
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 max-w-xs mx-auto">
                        Try adjusting your search query or search in a different module
                    </p>
                    <div class="flex flex-wrap justify-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                        <span class="px-2 py-1 bg-gray-50 dark:bg-gray-700/50 rounded">Try different keywords</span>
                        <span class="px-2 py-1 bg-gray-50 dark:bg-gray-700/50 rounded">Check spelling</span>
                        <span class="px-2 py-1 bg-gray-50 dark:bg-gray-700/50 rounded">Use fewer filters</span>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <div class="flex items-center space-x-1">
                            <span>Press</span>
                            <kbd class="px-1.5 py-0.5 bg-white dark:bg-gray-800 rounded border border-gray-300 dark:border-gray-600 font-mono text-xs">ESC</kbd>
                            <span>to close</span>
                        </div>
                        <div class="hidden md:flex items-center space-x-1">
                            <kbd class="px-1.5 py-0.5 bg-white dark:bg-gray-800 rounded border border-gray-300 dark:border-gray-600 font-mono text-xs">{{ keyboardShortcut }}</kbd>
                            <span>to search</span>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
        
        <!-- Keyboard Shortcut Hint -->
        <div v-if="!searchQuery && !showResults" class="absolute right-3 top-1/2 transform -translate-y-1/2 hidden lg:flex items-center space-x-1 text-xs text-gray-400">
            <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600 font-mono text-xs">{{ keyboardShortcut }}</kbd>
        </div>
    </div>
</template>
