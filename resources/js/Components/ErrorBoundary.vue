<template>
  <div v-if="hasError" class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-6">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <ExclamationTriangleIcon class="h-8 w-8 text-red-400" aria-hidden="true" />
        </div>
        <div class="ml-3">
          <h3 class="text-lg font-medium text-gray-900">
            Something went wrong
          </h3>
          <div class="mt-2 text-sm text-gray-500">
            <p>{{ errorMessage }}</p>
          </div>
          <div class="mt-4">
            <button
              @click="retry"
              class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <ArrowPathIcon class="h-4 w-4 mr-2" aria-hidden="true" />
              Try again
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <slot v-else />
</template>

<script setup>
import { ref, onErrorCaptured } from 'vue'
import { ExclamationTriangleIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const hasError = ref(false)
const errorMessage = ref('')

const retry = () => {
  hasError.value = false
  errorMessage.value = ''
  // Reload the page to reset the component state
  window.location.reload()
}

onErrorCaptured((error, instance, info) => {
  console.error('Error caught by boundary:', error)
  console.error('Component instance:', instance)
  console.error('Error info:', info)
  
  hasError.value = true
  errorMessage.value = error.message || 'An unexpected error occurred'
  
  // Log to external service in production
  if (import.meta.env.PROD) {
    // You can integrate with error tracking services like Sentry here
    console.error('Production error:', {
      error: error.message,
      stack: error.stack,
      component: instance?.$options.name || 'Unknown',
      info
    })
  }
  
  return false // Prevent the error from propagating further
})
</script>
