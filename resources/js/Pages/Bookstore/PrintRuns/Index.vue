<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Pagination from '@/Components/Library/Pagination.vue';
import Icon from '@/Components/Icon.vue';

defineProps({ printRuns: Object, filters: Object, options: Object });

const money = (v) => Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
</script>

<template>
    <Head title="Print runs" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-base font-semibold text-slate-900 dark:text-white">Print runs</h1>
                <Link :href="route('bookstore.print-runs.create')"
                      class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                    <Icon name="Plus" :size="15" /> Receive a print run
                </Link>
            </div>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                The only way new stock enters the store. Each run posts a receipt to the ledger and rolls the title's
                average print cost.
            </p>

            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Batch</th>
                                <th class="px-4 py-2.5 text-left font-medium">Book</th>
                                <th class="px-4 py-2.5 text-right font-medium">Quantity</th>
                                <th class="px-4 py-2.5 text-right font-medium">Unit cost</th>
                                <th class="px-4 py-2.5 text-right font-medium">Total</th>
                                <th class="px-4 py-2.5 text-left font-medium">Racked at</th>
                                <th class="px-4 py-2.5 text-left font-medium">CRV / invoice</th>
                                <th class="px-4 py-2.5 text-left font-medium">Received</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="run in printRuns.data" :key="run.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-2.5 font-mono text-xs text-slate-600 dark:text-slate-400">{{ run.batch_number }}</td>
                                <td class="px-4 py-2.5">
                                    <Link :href="route('bookstore.titles.show', run.book_title_id)"
                                          class="text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ run.book_title?.title }}
                                    </Link>
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums font-medium text-emerald-600 dark:text-emerald-400">+{{ run.quantity }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ money(run.unit_cost) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ money(run.total_cost) }}</td>
                                <td class="px-4 py-2.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ run.shelf_section?.shelf?.store_room?.name }} › {{ run.shelf_section?.code }}
                                </td>
                                <td class="px-4 py-2.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ [run.crv_number, run.invoice_number].filter(Boolean).join(' / ') || '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-xs text-slate-400">{{ run.received_on }}</td>
                            </tr>
                            <tr v-if="!printRuns.data.length">
                                <td colspan="8" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">No print runs recorded yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :links="printRuns.links" />
        </div>
    </AuthenticatedLayout>
</template>
