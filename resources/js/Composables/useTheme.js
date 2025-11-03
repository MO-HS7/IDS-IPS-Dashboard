import { ref } from 'vue'

const isDark = ref(false)
const currentTheme = ref('light')
const isLoading = ref(false)

export function useTheme() {
  // Check system preference
  const getSystemTheme = () => {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }

  // Apply theme to document
  const applyTheme = (theme) => {
    const root = document.documentElement
    
    if (theme === 'dark') {
      root.classList.add('dark')
      isDark.value = true
    } else {
      root.classList.remove('dark')
      isDark.value = false
    }
    
    // Add smooth transition
    root.style.transition = 'background-color 0.3s ease, color 0.3s ease'
  }

  // Set theme
  const setTheme = (theme) => {
    currentTheme.value = theme
    localStorage.setItem('theme', theme)
    
    const actualTheme = theme === 'system' ? getSystemTheme() : theme
    applyTheme(actualTheme)
  }

  // Toggle between light and dark
  const toggleTheme = () => {
    const newTheme = isDark.value ? 'light' : 'dark'
    setTheme(newTheme)
  }

  // Initialize theme
  const initTheme = () => {
    isLoading.value = true
    
    // Get saved theme or default to system
    const savedTheme = localStorage.getItem('theme') || 'light'
    currentTheme.value = savedTheme
    
    const actualTheme = savedTheme === 'system' ? getSystemTheme() : savedTheme
    applyTheme(actualTheme)
    
    // Listen for system theme changes
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    mediaQuery.addEventListener('change', (e) => {
      if (currentTheme.value === 'system') {
        applyTheme(e.matches ? 'dark' : 'light')
      }
    })
    
    isLoading.value = false
  }

  return {
    isDark,
    isLoading,
    currentTheme,
    setTheme,
    toggleTheme,
    initTheme,
    getSystemTheme
  }
}
