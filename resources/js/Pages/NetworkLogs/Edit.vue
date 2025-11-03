<template>
  <Head title="Edit Network Log" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Edit Network Log
      </h2>
    </template>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <form @submit.prevent="submit">
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select v-model="form.status" class="block w-full border-gray-300 rounded-md">
                  <option value="pending">Pending</option>
                  <option value="processing">Processing</option>
                  <option value="processed">Processed</option>
                  <option value="failed">Failed</option>
                </select>
                <div v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</div>
              </div>
              <div class="flex space-x-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                  Update
                </button>
                <Link :href="route('network-logs.show', networkLog.id)" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                  Cancel
                </Link>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  networkLog: Object
})

const form = useForm({
  status: props.networkLog.status
})

function submit() {
  form.put(route('network-logs.update', props.networkLog.id), {
    onSuccess: () => {}
  })
}
</script>
