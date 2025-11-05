<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({ investigation: Object });

const getStatusClass = (s) => ({
    open: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    resolved: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800'
}[s]);

const getPriorityClass = (p) => ({
    critical: 'bg-red-100 text-red-800',
    high: 'bg-orange-100 text-orange-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-green-100 text-green-800'
}[p]);
</script>

<template>
    <Head :title="investigation.title" />
    <AuthenticatedLayout>
        <div class="py-6 max-w-6xl mx-auto px-6">
            <div class="mb-6 flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold dark:text-white">{{ investigation.title }}</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Investigation #{{ investigation.id }}</p>
                </div>
                <Link :href="route('investigations.edit', investigation.id)" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    Edit
                </Link>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <div class="col-span-2 space-y-6">
                    <!-- Details -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
                        <h3 class="text-lg font-semibold dark:text-white mb-4">Investigation Details</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Status</p>
                                <span :class="getStatusClass(investigation.status)" class="px-3 py-1 text-xs rounded-full inline-block mt-1">{{ investigation.status }}</span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Priority</p>
                                <span :class="getPriorityClass(investigation.priority)" class="px-3 py-1 text-xs rounded-full inline-block mt-1">{{ investigation.priority }}</span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Assigned To</p>
                                <p class="font-medium dark:text-white">{{ investigation.assigned_to?.name || 'Unassigned' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Created By</p>
                                <p class="font-medium dark:text-white">{{ investigation.created_by }}</p>
                            </div>
                        </div>
                        <div v-if="investigation.description" class="mt-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Description</p>
                            <p class="mt-1 dark:text-white">{{ investigation.description }}</p>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
                        <h3 class="text-lg font-semibold dark:text-white mb-4">🕐 Timeline</h3>
                        <div v-if="investigation.timeline && investigation.timeline.length" class="space-y-4">
                            <div v-for="(event, index) in investigation.timeline" :key="index" class="flex gap-4">
                                <div class="flex flex-col items-center">
                                    <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                                    <div v-if="index < investigation.timeline.length - 1" class="w-0.5 h-full bg-gray-300 dark:bg-gray-600"></div>
                                </div>
                                <div class="flex-1 pb-4">
                                    <p class="font-medium dark:text-white">{{ event.event }}</p>
                                    <p v-if="event.description" class="text-sm text-gray-600 dark:text-gray-400">{{ event.description }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ event.timestamp }}</p>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 dark:text-gray-400 text-center py-4">لا توجد أحداث في المخطط الزمني</p>
                    </div>

                    <!-- Alerts -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
                        <h3 class="text-lg font-semibold dark:text-white mb-4">🚨 التنبيهات المرتبطة ({{ investigation.alerts.length }})</h3>
                        <div v-if="investigation.alerts.length" class="space-y-3">
                            <div v-for="alert in investigation.alerts" :key="alert.id" class="flex items-center justify-between p-3 border dark:border-gray-700 rounded">
                                <div>
                                    <p class="font-medium dark:text-white">{{ alert.attack_type }}</p>
                                    <p class="text-sm text-gray-500">{{ alert.source_ip }} - {{ alert.detected_at }}</p>
                                </div>
                                <span :class="getPriorityClass(alert.severity)" class="px-3 py-1 text-xs rounded-full">{{ alert.severity }}</span>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 dark:text-gray-400 text-center py-4">لا توجد تنبيهات مرتبطة</p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
                        <h3 class="text-lg font-semibold dark:text-white mb-4">معلومات</h3>
                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">تاريخ البدء</p>
                                <p class="dark:text-white">{{ investigation.started_at || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">تاريخ الحل</p>
                                <p class="dark:text-white">{{ investigation.resolved_at || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">تاريخ الإنشاء</p>
                                <p class="dark:text-white">{{ investigation.created_at }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="investigation.resolution_notes" class="bg-white dark:bg-gray-800 rounded-lg p-6">
                        <h3 class="text-lg font-semibold dark:text-white mb-4">ملاحظات الحل</h3>
                        <p class="text-sm dark:text-white">{{ investigation.resolution_notes }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <Link :href="route('investigations.index')" class="text-blue-600 hover:text-blue-700">← العودة إلى التحقيقات</Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
