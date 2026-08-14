<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Pagination from '@/Components/Library/Pagination.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    requests: Object,
    filters: Object,
    statuses: Object,
    queue: Object,
});

const form = ref({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    mine: props.filters.mine ?? '',
});

let debounce;
watch(form, (value) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('bookstore.requests.index'), value, { preserveState: true, replace: true });
    }, 300);
}, { deep: true });

const colors = {
    draft: 'gray', submitted: 'blue', awaiting_payment: 'amber', payment_verified: 'cyan',
    approved: 'indigo', partially_dispatched: 'orange', dispatched: 'teal',
    received: 'green', rejected: 'red', cancelled: 'rose',
};

const stages = [
    { key: 'submitted', label: 'Awaiting verification', status: 'submitted' },
    { key: 'awaiting_payment', label: 'Awaiting payment', status: 'awaiting_payment' },
    { key: 'payment_verified', label: 'Awaiting approval', status: 'payment_verified' },
    { key: 'approved', label: 'Awaiting dispatch', status: 'approved' },
];

const date = (v) => (v ? new Date(v).toLocaleDateString() : '—');
const money = (v) => Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
</script>

<template>
    <Head title="Book requests" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-base font-semibold text-slate-900 dark:text-white">Book requests</h1>
                <Link :href="route('bookstore.requests.create')"
                      class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                    <Icon name="Plus" :size="15" /> New request
                </Link>
            </div>
        </template>

        <div class="p-6 max-w-7xl mx-auto space-y-5">

            <!-- The four gates, as counters you can click into. -->
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <button v-for="stage in stages" :key="stage.key" type="button"
                        @click="form.status = form.status === stage.status ? '' : stage.status"
                        class="rounded-xl border bg-white dark:bg-slate-900 p-3 text-left transition"
                        :class="form.status === stage.status
                            ? 'border-indigo-500 ring-1 ring-indigo-500'
                            : 'border-slate-200 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-600'">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ stage.label }}</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ queue[stage.key] }}</p>
                </button>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <input v-model="form.search" type="search" placeholder="Request number or contact…"
                       class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <select v-model="form.status" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Any status</option>
                    <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                </select>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input v-model="form.mine" type="checkbox" true-value="1" false-value=""
                           class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500" />
                    Only my requests
                </label>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Request</th>
                                <th class="px-4 py-2.5 text-left font-medium">Destination</th>
                                <th class="px-4 py-2.5 text-left font-medium">Requester</th>
                                <th class="px-4 py-2.5 text-right font-medium">Books</th>
                                <th class="px-4 py-2.5 text-right font-medium">Amount</th>
                                <th class="px-4 py-2.5 text-left font-medium">Status</th>
                                <th class="px-4 py-2.5 text-left font-medium">Raised</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="req in requests.data" :key="req.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-2.5">
                                    <Link :href="route('bookstore.requests.show', req.id)"
                                          class="font-mono text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ req.request_number }}
                                    </Link>
                                </td>
                                <td class="px-4 py-2.5 text-slate-800 dark:text-slate-200">
                                    {{ req.center?.name || req.campus?.name || req.campus?.name_en || '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-slate-500 dark:text-slate-400">{{ req.requester?.name }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ req.total_quantity }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ money(req.total_amount) }}</td>
                                <td class="px-4 py-2.5">
                                    <StatusBadge :label="statuses[req.status]" :color="colors[req.status]" />
                                </td>
                                <td class="px-4 py-2.5 text-xs text-slate-400">{{ date(req.created_at) }}</td>
                            </tr>
                            <tr v-if="!requests.data.length">
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                    No requests match these filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :links="requests.links" />
        </div>
    </AuthenticatedLayout>
</template>
