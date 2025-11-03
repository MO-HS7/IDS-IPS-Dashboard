import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

/**
 * Global Search Composable
 * Provides context-aware search functionality across all modules
 */
export function useSearch() {
    const page = usePage()
    const searchQuery = ref('')
    const searchResults = ref([])
    const isSearching = ref(false)
    const showResults = ref(false)

    // Get current module from URL
    const currentModule = computed(() => {
        const url = page.url
        if (url.startsWith('/network-logs')) return 'network-logs'
        if (url.startsWith('/alerts')) return 'alerts'
        if (url.startsWith('/ml-models')) return 'ml-models'
        if (url.startsWith('/analytics')) return 'analytics'
        if (url.startsWith('/notifications')) return 'notifications'
        if (url.startsWith('/users')) return 'users'
        return 'dashboard'
    })

    // Get search placeholder based on current module
    const searchPlaceholder = computed(() => {
        const placeholders = {
            'network-logs': 'Search network logs...',
            'alerts': 'Search alerts and threats...',
            'ml-models': 'Search ML models...',
            'analytics': 'Search reports and analytics...',
            'notifications': 'Search notifications...',
            'users': 'Search users...',
            'dashboard': 'Search alerts, logs...'
        }
        return placeholders[currentModule.value] || 'Search...'
    })

    // Get search API endpoint based on current module
    const searchEndpoint = computed(() => {
        const endpoints = {
            'network-logs': '/api/network-logs/search',
            'alerts': '/api/alerts/search',
            'ml-models': '/api/ml-models/search',
            'analytics': '/api/analytics/search',
            'notifications': '/api/notifications/search',
            'users': '/api/users/search',
            'dashboard': '/api/search/global'
        }
        return endpoints[currentModule.value] || '/api/search/global'
    })

    // Perform search
    const performSearch = async (query) => {
        if (!query || query.trim().length < 2) {
            searchResults.value = []
            showResults.value = false
            return
        }

        isSearching.value = true
        showResults.value = true

        try {
            const response = await fetch(`${searchEndpoint.value}?q=${encodeURIComponent(query)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })

            if (response.ok) {
                const data = await response.json()
                searchResults.value = data.results || data.data || []
            } else {
                searchResults.value = []
            }
        } catch (error) {
            console.error('Search error:', error)
            searchResults.value = []
        } finally {
            isSearching.value = false
        }
    }

    // Debounced search
    let searchTimeout = null
    const debouncedSearch = (query) => {
        clearTimeout(searchTimeout)
        searchTimeout = setTimeout(() => {
            performSearch(query)
        }, 300)
    }

    // Handle search input
    const handleSearch = (query) => {
        searchQuery.value = query
        debouncedSearch(query)
    }

    // Navigate to result
    const navigateToResult = (result) => {
        if (result.url) {
            router.visit(result.url)
            searchQuery.value = ''
            searchResults.value = []
            showResults.value = false
        }
    }

    // Clear search
    const clearSearch = () => {
        searchQuery.value = ''
        searchResults.value = []
        showResults.value = false
        isSearching.value = false
    }

    // Watch for route changes and clear search
    watch(() => page.url, () => {
        clearSearch()
    })

    return {
        searchQuery,
        searchResults,
        isSearching,
        showResults,
        currentModule,
        searchPlaceholder,
        handleSearch,
        navigateToResult,
        clearSearch,
        performSearch
    }
}
