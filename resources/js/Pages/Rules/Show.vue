<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({ rule: Object });

const getSeverityClass = (s) => ({
    critical: 'bg-red-100 text-red-800',
    high: 'bg-orange-100 text-orange-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-green-100 text-green-800'
}[s]);
</script>

<template>
    <Head :title="rule.name" />
    <AuthenticatedLayout>
        <div class="py-6 max-w-4xl mx-auto px-6">
            <div class="mb-6 flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold dark:text-white">{{ rule.name }}</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">SID: {{ rule.sid }} | Rev: {{ rule.rev }}</p>
                </div>
                <Link :href="route('rules.edit', rule.id)" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    Edit Rule
                </Link>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 space-y-6">
                <div>
                    <h3 class="text-lg font-semibold dark:text-white mb-4">Rule Details</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Category</p>
                            <p class="font-medium dark:text-white capitalize">{{ rule.category }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Severity</p>
                            <span :class="getSeverityClass(rule.severity)" class="px-3 py-1 text-xs rounded-full capitalize">{{ rule.severity }}</span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Action</p>
                            <p class="font-medium dark:text-white capitalize">{{ rule.action }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Protocol</p>
                            <p class="font-medium dark:text-white uppercase">{{ rule.protocol }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Status</p>
                            <span :class="rule.enabled ? 'text-green-600' : 'text-gray-600'" class="font-medium">{{ rule.enabled ? 'Enabled' : 'Disabled' }}</span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Alert Count</p>
                            <p class="font-medium dark:text-white">{{ rule.alert_count }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="rule.description">
                    <h3 class="text-lg font-semibold dark:text-white mb-2">Description</h3>
                    <p class="text-gray-700 dark:text-gray-300">{{ rule.description }}</p>
                </div>

                <div>
                    <h3 class="text-lg font-semibold dark:text-white mb-2">Network Pattern</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Source</p>
                            <p class="font-mono text-sm dark:text-white">{{ rule.source_ip }}:{{ rule.source_port }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Destination</p>
                            <p class="font-mono text-sm dark:text-white">{{ rule.destination_ip }}:{{ rule.destination_port }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold dark:text-white mb-2">Full Signature</h3>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded p-4">
                        <code class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ rule.signature }}</code>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold dark:text-white mb-2">Metadata</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Created By</p>
                            <p class="dark:text-white">{{ rule.created_by }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Created At</p>
                            <p class="dark:text-white">{{ rule.created_at }}</p>
                        </div>
                        <div v-if="rule.last_triggered_at">
                            <p class="text-gray-600 dark:text-gray-400">Last Triggered</p>
                            <p class="dark:text-white">{{ rule.last_triggered_at }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <Link :href="route('rules.index')" class="text-blue-600 hover:text-blue-700">← Back to Rules</Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
