<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({ rule: Object });

const form = useForm({
    name: props.rule.name,
    signature: props.rule.signature,
    category: props.rule.category,
    severity: props.rule.severity,
    description: props.rule.description,
    enabled: props.rule.enabled,
    sid: props.rule.sid,
    rev: props.rule.rev,
    protocol: props.rule.protocol,
    source_ip: props.rule.source_ip,
    source_port: props.rule.source_port,
    destination_ip: props.rule.destination_ip,
    destination_port: props.rule.destination_port,
    action: props.rule.action
});

const submit = () => {
    form.put(route('rules.update', props.rule.id));
};
</script>

<template>
    <Head title="Edit Rule" />
    <AuthenticatedLayout>
        <div class="py-6 max-w-4xl mx-auto px-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold dark:text-white">Edit Rule</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Modify rule: {{ rule.name }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium mb-2 dark:text-white">Rule Name *</label>
                            <input v-model="form.name" type="text" required class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">SID *</label>
                            <input v-model="form.sid" type="number" required class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Revision</label>
                            <input v-model="form.rev" type="number" min="1" class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Category *</label>
                            <select v-model="form.category" required class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="malware">Malware</option>
                                <option value="exploit">Exploit</option>
                                <option value="dos">DoS</option>
                                <option value="scan">Scan</option>
                                <option value="policy">Policy</option>
                                <option value="trojan">Trojan</option>
                                <option value="web-attack">Web Attack</option>
                                <option value="misc">Misc</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Severity *</label>
                            <select v-model="form.severity" required class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Action *</label>
                            <select v-model="form.action" required class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="alert">Alert</option>
                                <option value="log">Log</option>
                                <option value="pass">Pass</option>
                                <option value="drop">Drop</option>
                                <option value="reject">Reject</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Protocol</label>
                            <select v-model="form.protocol" class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="tcp">TCP</option>
                                <option value="udp">UDP</option>
                                <option value="icmp">ICMP</option>
                                <option value="ip">IP</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Source IP</label>
                            <input v-model="form.source_ip" type="text" class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Source Port</label>
                            <input v-model="form.source_port" type="text" class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Destination IP</label>
                            <input v-model="form.destination_ip" type="text" class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 dark:text-white">Destination Port</label>
                            <input v-model="form.destination_port" type="text" class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium mb-2 dark:text-white">Description</label>
                            <textarea v-model="form.description" rows="3" class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium mb-2 dark:text-white">Full Signature *</label>
                            <textarea v-model="form.signature" rows="4" required class="w-full px-4 py-2 border dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white font-mono text-sm"></textarea>
                        </div>

                        <div class="col-span-2">
                            <label class="flex items-center">
                                <input v-model="form.enabled" type="checkbox" class="rounded" />
                                <span class="ml-2 text-sm dark:text-white">Enabled</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <Link :href="route('rules.index')" class="px-4 py-2 border dark:border-gray-600 rounded-lg dark:text-white">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg disabled:opacity-50">
                            {{ form.processing ? 'Updating...' : 'Update Rule' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
