<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Create Account" />

        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <!-- Custom Logo -->
            <div class="flex justify-center">
                <img src="/images/logo.png" alt="AI-IDS Logo" class="w-16 h-16 mx-auto mb-6" />
            </div>
            
            <h2 class="text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                Create your account
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Get started with AI-IDS security platform
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow-xl rounded-lg sm:px-10 border border-gray-200 dark:border-gray-700">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Name -->
                    <div>
                        <InputLabel for="name" value="Full Name" class="text-gray-700 dark:text-gray-300" />
                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <!-- Email -->
                    <div>
                        <InputLabel for="email" value="Email Address" class="text-gray-700 dark:text-gray-300" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
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
                            autocomplete="new-password"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <InputLabel for="password_confirmation" value="Confirm Password" class="text-gray-700 dark:text-gray-300" />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>

                    <!-- Terms Agreement -->
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        By creating an account, you agree to our 
                        <a href="#" class="text-primary-600 hover:text-primary-500">Terms of Service</a> 
                        and 
                        <a href="#" class="text-primary-600 hover:text-primary-500">Privacy Policy</a>.
                    </div>

                    <!-- Submit Button -->
                    <PrimaryButton 
                        class="w-full justify-center py-3 text-lg font-semibold bg-primary-600 hover:bg-primary-700"
                        :class="{ 'opacity-25': form.processing }" 
                        :disabled="form.processing"
                    >
                        Create Account
                    </PrimaryButton>
                </form>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Already have an account?
                        <Link :href="route('login')" 
                              class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                            Sign in here
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
