<script setup>
import { Head, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
</script>

<template>
  <Head title="Access Denied - 403" />

  <GuestLayout>
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
      <div class="max-w-md w-full text-center">
        <!-- Error Icon -->
        <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-red-100 dark:bg-red-900/20 mb-6">
          <svg class="h-12 w-12 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-1.924-1.333-2.694 0L4.12 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
        </div>

        <!-- Error Code -->
        <h1 class="text-6xl font-bold text-gray-900 dark:text-white mb-4">403</h1>
        
        <!-- Error Title -->
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-4">
          Access Denied
        </h2>
        
        <!-- Error Message -->
        <p class="text-gray-600 dark:text-gray-400 mb-8">
          Sorry, you don't have permission to access this page. This area is restricted to administrators only.
        </p>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <Link
            :href="route('dashboard')"
            class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Go to Dashboard
          </Link>
          
          <button
            @click="$inertia.visit($inertia.page.props.previous || route('dashboard'))"
            class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Go Back
          </button>
        </div>

        <!-- Additional Help -->
        <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
          <p class="text-sm text-blue-800 dark:text-blue-300">
            <strong>Need access?</strong> Contact your system administrator to request the appropriate permissions.
          </p>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>
