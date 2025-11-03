<template>
  <Head title="View Network Log" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Network Log Details
      </h2>
    </template>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">File Name</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ networkLog.file_name }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Uploaded By</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ networkLog.user?.name || 'Unknown' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Upload Date</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ new Date(networkLog.upload_date).toLocaleString() }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                <dd class="mt-1">
                  <span :class="statusColor(networkLog.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                    {{ networkLog.status }}
                  </span>
                </dd>
              </div>
              <div v-if="networkLog.analysis_result">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Analysis Result</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                  <pre>{{ networkLog.analysis_result }}</pre>
                </dd>
              </div>
            </dl>
            <div class="mt-6 flex space-x-2">
              <Link :href="route('network-logs.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back
              </Link>
              <Link :href="route('network-logs.edit', networkLog.id)" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                Edit
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
  networkLog: Object
})

const statusColor = (status) => {
  const map = {
    pending: 'text-yellow-600 bg-yellow-100',
    processing: 'text-blue-600 bg-blue-100',
    processed: 'text-green-600 bg-green-100',
    failed: 'text-red-600 bg-red-100'
  }
  return map[status] || 'text-gray-600 bg-gray-100'
}
</script>
