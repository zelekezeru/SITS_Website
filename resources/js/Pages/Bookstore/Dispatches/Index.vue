<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Pagination from '@/Components/Library/Pagination.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';

defineProps({ dispatches: Object, filters: Object, pending: Number });

const money = (v) => Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
const date = (v) => (v ? new Date(v).toLocaleDateString() : '—');
const colors = { prepared: 'gray', in_transit: 'blue', received: 'green', returned: 'orange' };
</script>

<template>
    <Head title="Dispatches" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-slate-900 dark:text-white">Dispatches</h1>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-5">

            <Link v-if="pending > 0" :href="route('bookstore.requests.index', { status: 'approved' })"
                  class="flex items-center justify-between gap-3 rounded-xl border border-indigo-300 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/30 px-4 py-3 hover:bg-indigo-100 dark:hover:bg-indigo-950/50 transition">
                <p class="text-sm text-indigo-900 dark:text-indigo-200">
                    <strong>{{ pending }}</strong> approved request(s) waiting to be dispatched.
                </p>
                <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">Open the queue →</span>
            </Link>

            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Waybill</th>
                                <th class="px-4 py-2.5 text-left font-medium">Request</th>
                                <th class="px-4 py-2.5 text-left font-medium">Destination</th>
                                <th class="px-4 py-2.5 text-right font-medium">Books</th>
                                <th class="px-4 py-2.5 text-right font-medium">Value</th>
                                <th class="px-4 py-2.5 text-left font-medium">Dispatched</th>
                                <th class="px-4 py-2.5 text-left font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="d in dispatches.data" :key="d.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-2.5">
                                    <Link :href="route('bookstore.dispatches.show', d.id)"
                                          class="font-mono text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ d.dispatch_number }}
                                    </Link>
                                </td>
                                <td class="px-4 py-2.5 font-mono text-xs text-slate-500 dark:text-slate-400">
                                    {{ d.book_request?.request_number }}
                                </td>
                                <td class="px-4 py-2.5 text-slate-800 dark:text-slate-200">
                                    {{ d.book_request?.center?.name || d.book_request?.campus?.name || d.book_request?.campus?.name_en || '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ d.total_quantity }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ money(d.total_amount) }}</td>
                                <td class="px-4 py-2.5 text-xs text-slate-400">
                                    {{ date(d.dispatched_at) }}<span v-if="d.dispatched_by"> · {{ d.dispatched_by.name }}</span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <StatusBadge :label="d.status.replace(/_/g, ' ')" :color="colors[d.status]" />
                                </td>
                            </tr>
                            <tr v-if="!dispatches.data.length">
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">Nothing dispatched yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :links="dispatches.links" />
        </div>
    </AuthenticatedLayout>
</template>
