<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password')
        },
        onError: () => {
            // Form errors are automatically displayed by Inertia
            form.reset('password')
        }
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Sign In" />

        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <!-- Custom Logo -->
            <div class="flex justify-center">
                <img src="/images/logo.png" alt="AI-IDS Logo" class="w-16 h-16 mx-auto mb-6" />
            </div>
            
            <h2 class="text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                Welcome back
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Sign in to your AI-IDS account
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow-xl rounded-lg sm:px-10 border border-gray-200 dark:border-gray-700">
                <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Email -->
                    <div>
                        <InputLabel for="email" value="Email address" class="text-gray-700 dark:text-gray-300" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div>
                        <InputLabel for="password" value="Password" class="text-gray-700 dark:text-gray-300" />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <!-- Submit Button -->
                    <PrimaryButton 
                        class="w-full justify-center py-3 text-lg font-semibold bg-primary-600 hover:bg-primary-700"
                        :class="{ 'opacity-25': form.processing }" 
                        :disabled="form.processing"
                    >
                        Sign In
                    </PrimaryButton>
                </form>

                <!-- Register Link -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Don't have an account?
                        <Link :href="route('register')" 
                              class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                            Create one now
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
