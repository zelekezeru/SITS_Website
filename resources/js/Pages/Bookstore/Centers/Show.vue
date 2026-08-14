<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import StatCard from '@/Components/Bookstore/StatCard.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';

defineProps({ center: Object, outstanding: Array, totals: Object, requests: Array });

const money = (v) => Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
const date = (v) => (v ? new Date(v).toLocaleDateString() : '—');

const statusColors = {
    draft: 'gray', submitted: 'blue', awaiting_payment: 'amber', payment_verified: 'cyan',
    approved: 'indigo', partially_dispatched: 'orange', dispatched: 'teal',
    received: 'green', rejected: 'red', cancelled: 'rose',
};
</script>

<template>
    <Head :title="center.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="min-w-0">
                <h1 class="truncate text-base font-semibold text-slate-900 dark:text-white">{{ center.name }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ center.code }}
                    <template v-if="center.coordinator_name"> · {{ center.coordinator_name }}</template>
                    <template v-if="center.coordinator_phone"> · {{ center.coordinator_phone }}</template>
                </p>
            </div>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-6">

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Verified students" :value="center.student_count" icon="Users" tone="slate" />
                <StatCard label="Books issued" :value="totals.issued" icon="Truck" tone="indigo" />
                <StatCard label="Books returned" :value="totals.returned" icon="Undo2" tone="emerald" />
                <StatCard label="Outstanding" :value="totals.outstanding"
                          :sub="`${money(totals.value)} at selling price`" icon="AlertTriangle"
                          :tone="totals.outstanding > 0 ? 'amber' : 'slate'" />
            </div>

            <!-- Issued − returned = outstanding: the reconciliation the paper
                 issue-and-return form works out by hand, per title. -->
            <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <div class="px-5 pt-5">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Centre statement</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        What this centre has been issued, what came back, and what it still holds.
                    </p>
                </div>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-2 text-left font-medium">Code</th>
                                <th class="px-3 py-2 text-left font-medium">Book</th>
                                <th class="px-3 py-2 text-right font-medium">Issued</th>
                                <th class="px-3 py-2 text-right font-medium">Returned</th>
                                <th class="px-3 py-2 text-right font-medium">Outstanding</th>
                                <th class="px-5 py-2 text-right font-medium">Value outstanding</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="row in outstanding" :key="row.book_title_id">
                                <td class="px-5 py-2.5 font-mono text-xs text-slate-500 dark:text-slate-400">{{ row.code }}</td>
                                <td class="px-3 py-2.5">
                                    <Link :href="route('bookstore.titles.show', row.book_title_id)"
                                          class="text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ row.title }}
                                    </Link>
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ row.issued }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ row.returned }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums font-semibold"
                                    :class="row.outstanding > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400'">
                                    {{ row.outstanding }}
                                </td>
                                <td class="px-5 py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400">
                                    {{ money(row.outstanding * row.unit_price) }}
                                </td>
                            </tr>
                            <tr v-if="!outstanding.length">
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500 dark:text-slate-400">
                                    Nothing has been issued to this centre yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <h2 class="px-5 pt-5 text-sm font-semibold text-slate-900 dark:text-white">Recent requests</h2>
                <ul class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                    <li v-for="r in requests" :key="r.id" class="px-5 py-3 flex items-center justify-between gap-3">
                        <div>
                            <Link :href="route('bookstore.requests.show', r.id)"
                                  class="font-mono text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ r.request_number }}
                            </Link>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ r.total_quantity }} books · {{ money(r.total_amount) }} · {{ date(r.created_at) }}
                            </p>
                        </div>
                        <StatusBadge :label="r.status.replace(/_/g, ' ')" :color="statusColors[r.status]" />
                    </li>
                    <li v-if="!requests.length" class="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                        No requests from this centre yet.
                    </li>
                </ul>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
