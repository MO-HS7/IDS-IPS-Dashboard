<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import PasswordInput from '@/Components/PasswordInput.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { nextTick, ref } from 'vue'

const passwordInput = ref(null)

const form = useForm({
    password: '',
})

const submit = () => {
    form.post('/confirm-password', {
        onFinish: () => {
            form.reset()
            nextTick(() => passwordInput.value?.focus())
        }
    })
}
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            This is a secure area of the application. Please confirm your password before continuing.
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Password" />

                <PasswordInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    required
                    autofocus
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex justify-end mt-4">
                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Confirm
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
