<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    mlModel: Object,
    modelTypes: Object,
    trainingSessions: Array,
});

// Training state
const selectedModelType = ref('random_forest');
const hyperparameters = ref({});
const isTraining = ref(false);
const currentSession = ref(null);
const progress = ref(0);
const statusMessage = ref('');
const error = ref(null);

// Status polling
let statusInterval = null;

// Computed
const modelTypeOptions = computed(() => {
    return Object.entries(props.modelTypes).map(([value, label]) => ({
        value,
        label
    }));
});

const canStartTraining = computed(() => {
    return !isTraining.value && props.mlModel.status !== 'training';
});

// Methods
const startTraining = async () => {
    if (!canStartTraining.value) return;

    error.value = null;
    isTraining.value = true;

    try {
        const response = await axios.post(
            `/api/ml-models/${props.mlModel.id}/start-training`,
            {
                model_type: selectedModelType.value,
                hyperparameters: hyperparameters.value,
            }
        );

        if (response.data.success) {
            currentSession.value = response.data.session;
            startStatusPolling();
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to start training';
        isTraining.value = false;
    }
};

const startStatusPolling = () => {
    if (!currentSession.value) return;

    statusInterval = setInterval(async () => {
        try {
            const response = await axios.get(
                `/api/ml-models/${props.mlModel.id}/training-status/${currentSession.value.session_id}`
            );

            if (response.data.success) {
                const session = response.data.session;
                progress.value = session.progress;
                statusMessage.value = session.status_message;

                if (session.status === 'completed') {
                    isTraining.value = false;
                    stopStatusPolling();
                    // Refresh page to show results
                    window.location.reload();
                } else if (session.status === 'failed') {
                    isTraining.value = false;
                    error.value = session.error_message;
                    stopStatusPolling();
                }
            }
        } catch (err) {
            console.error('Failed to fetch status:', err);
        }
    }, 3000); // Poll every 3 seconds
};

const stopStatusPolling = () => {
    if (statusInterval) {
        clearInterval(statusInterval);
        statusInterval = null;
    }
};

onUnmounted(() => {
    stopStatusPolling();
});
</script>

<template>
    <Head :title="`Train Model: ${mlModel.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Train Model: {{ mlModel.name }}
                </h2>
                <Link
                    :href="route('ml-models.show', mlModel.id)"
                    class="text-sm text-blue-600 hover:text-blue-800"
                >
                    ← Back to Model
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Training Form -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Training Configuration
                        </h3>

                        <div class="space-y-4">
                            <!-- Model Type Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Model Type
                                </label>
                                <select
                                    v-model="selectedModelType"
                                    :disabled="isTraining"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option
                                        v-for="option in modelTypeOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Start Training Button -->
                            <div class="flex items-center space-x-4">
                                <button
                                    @click="startTraining"
                                    :disabled="!canStartTraining"
                                    class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                >
                                    <svg v-if="isTraining" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ isTraining ? 'Training...' : 'Start Training' }}
                                </button>

                                <div v-if="isTraining" class="text-sm text-gray-600 dark:text-gray-400">
                                    Progress: {{ progress }}%
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div v-if="error" class="p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 rounded">
                                {{ error }}
                            </div>

                            <!-- Status Message -->
                            <div v-if="statusMessage && isTraining" class="p-4 bg-blue-100 dark:bg-blue-900 border border-blue-400 dark:border-blue-700 text-blue-700 dark:text-blue-200 rounded">
                                {{ statusMessage }}
                            </div>

                            <!-- Progress Bar -->
                            <div v-if="isTraining" class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                <div
                                    class="bg-indigo-600 h-4 rounded-full transition-all duration-300"
                                    :style="{ width: `${progress}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Training History -->
                <div v-if="trainingSessions.length > 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Training History
                        </h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                            Date
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                            Model Type
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                            Status
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                            Accuracy
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                            Duration
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="session in trainingSessions" :key="session.id">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                            {{ new Date(session.created_at).toLocaleString() }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                            {{ modelTypes[session.model_type] }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span
                                                :class="{
                                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': session.status === 'completed',
                                                    'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': session.status === 'running',
                                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': session.status === 'failed',
                                                    'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': session.status === 'pending',
                                                }"
                                                class="px-2 py-1 text-xs font-semibold rounded"
                                            >
                                                {{ session.status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                            {{ session.accuracy ? (session.accuracy * 100).toFixed(2) + '%' : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                            {{ session.duration_seconds ? session.duration_seconds + 's' : 'N/A' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Model Info -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Model Information
                        </h3>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ mlModel.status }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Best Accuracy</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ mlModel.best_accuracy ? (mlModel.best_accuracy * 100).toFixed(2) + '%' : 'Not trained' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Predictions</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ mlModel.total_predictions }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Active</dt>
                                <dd class="mt-1">
                                    <span
                                        :class="mlModel.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                        class="px-2 py-1 text-xs font-semibold rounded"
                                    >
                                        {{ mlModel.is_active ? 'Yes' : 'No' }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
