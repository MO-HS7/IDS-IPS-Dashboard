<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    status: String,
});

const form = useForm({});
const isResending = ref(false);

const submit = () => {
    isResending.value = true;
    form.post('/email/verification-notification', {
        onFinish: () => {
            isResending.value = false;
        },
        onError: (errors) => {
            console.error('Resend failed:', errors);
            isResending.value = false;
        }
    });
};

const statusMessage = computed(() => {
    if (props.status === 'verification-link-sent') {
        return 'A new verification link has been sent to your email address.';
    }
    return null;
});
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />

        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <!-- Custom Logo -->
            <div class="flex justify-center">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            
            <h2 class="text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                Verify Your Email
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                We've sent a verification link to your email address
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow-xl rounded-lg sm:px-10 border border-gray-200 dark:border-gray-700">
                <!-- Email Icon -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full mb-4">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m0 0V9a2 2 0 012-2h8a2 2 0 012 2v4M6 13h8"/>
                        </svg>
                    </div>
                </div>

                <!-- Verification Message -->
                <div class="text-center mb-6">
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Thanks for signing up with <strong>AI-IDS</strong>! Before getting started, could you verify your email address by 
                        clicking on the link we just emailed to you?
                    </p>
                    
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        If you didn't receive the email, we will gladly send you another.
                    </p>
                </div>

                <!-- Success Message -->
                <div v-if="statusMessage" class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">
                            {{ statusMessage }}
                        </p>
                    </div>
                </div>

                <!-- Error Message (if any) -->
                <div v-if="form.hasErrors" class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">
                            Failed to send verification email. Please try again.
                        </p>
                    </div>
                </div>

                <!-- Resend Form -->
                <form @submit.prevent="submit" class="space-y-4">
                    <PrimaryButton 
                        class="w-full justify-center py-3 text-lg font-semibold bg-blue-600 hover:bg-blue-700"
                        :class="{ 'opacity-50': isResending || form.processing }" 
                        :disabled="isResending || form.processing"
                    >
                        <svg v-if="isResending || form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ (isResending || form.processing) ? 'Sending...' : 'Resend Verification Email' }}
                    </PrimaryButton>
                </form>

                <!-- Back to Login -->
                <div class="mt-6 text-center">
                    <div class="flex items-center justify-between">
                        <Link href="/login" 
                              class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                            ← Back to Login
                        </Link>
                        
                        <Link href="/logout" 
                              method="post" 
                              as="button"
                              class="text-sm font-medium text-gray-600 hover:text-gray-500 dark:text-gray-400">
                            Sign Out
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
