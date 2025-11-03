<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 relative">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 25px 25px, rgba(59, 130, 246, 0.5) 2px, transparent 0), radial-gradient(circle at 75px 75px, rgba(59, 130, 246, 0.3) 1px, transparent 0); background-size: 100px 100px;"></div>
        </div>
        
        <!-- Back to Home -->
        <div class="w-full sm:max-w-md px-6 py-4 z-20">
            <Link href="/" 
                  class="flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Home
            </Link>
        </div>
        
        <!-- Page Content -->
        <div class="relative z-20 w-full sm:max-w-md px-6">
            <slot />
        </div>
    </div>
</template>
