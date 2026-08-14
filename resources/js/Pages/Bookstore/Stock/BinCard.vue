<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Pagination from '@/Components/Library/Pagination.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    title: Object,
    movements: Object,
    filters: Object,
    sections: Array,
    reconciliation: Object,
});

const form = ref({
    shelf_section_id: props.filters.shelf_section_id ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

watch(form, (value) => {
    router.get(route('bookstore.stock.bin-card', props.title.id), value, { preserveState: true, replace: true });
}, { deep: true });

const printPage = () => window.print();

const money = (v) => (v === null || v === undefined ? '' : Number(v).toFixed(2));
const date = (v) => (v ? new Date(v).toLocaleDateString() : '');
</script>

<template>
    <Head :title="`Bin card — ${title.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="truncate text-base font-semibold text-slate-900 dark:text-white">Bin card — {{ title.title }}</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ title.code }}</p>
                </div>
                <button type="button" @click="printPage"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition print:hidden">
                    <Icon name="Printer" :size="15" /> Print
                </button>
            </div>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-5">

            <div class="grid gap-3 sm:grid-cols-3 print:hidden">
                <select v-model="form.shelf_section_id" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All sections (combined)</option>
                    <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.label }} — {{ s.quantity }}</option>
                </select>
                <input v-model="form.from" type="date" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <input v-model="form.to" type="date" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </div>

            <!--
                The cached balance should always match a replay of the ledger.
                Showing both makes a divergence — a bug, or a direct database
                edit — impossible to miss during an audit.
            -->
            <div v-if="reconciliation"
                 class="flex items-center gap-3 rounded-xl border px-4 py-3"
                 :class="reconciliation.cached === reconciliation.from_ledger
                     ? 'border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/30'
                     : 'border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30'">
                <Icon :name="reconciliation.cached === reconciliation.from_ledger ? 'CheckCircle' : 'AlertTriangle'" :size="18"
                      :class="reconciliation.cached === reconciliation.from_ledger ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'" />
                <p class="text-sm"
                   :class="reconciliation.cached === reconciliation.from_ledger ? 'text-emerald-900 dark:text-emerald-200' : 'text-rose-900 dark:text-rose-200'">
                    <template v-if="reconciliation.cached === reconciliation.from_ledger">
                        Balance agrees with the ledger: {{ reconciliation.cached }}.
                    </template>
                    <template v-else>
                        Recorded balance is {{ reconciliation.cached }} but the ledger replays to {{ reconciliation.from_ledger }}. Investigate before the next audit.
                    </template>
                </p>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <!-- Column-for-column the paper SBCE store log. -->
                    <table class="w-full text-sm">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Date</th>
                                <th class="px-3 py-2.5 text-left font-medium">Description</th>
                                <th class="px-3 py-2.5 text-left font-medium">Section</th>
                                <th class="px-3 py-2.5 text-right font-medium">Unit price</th>
                                <th class="px-3 py-2.5 text-right font-medium">Total price</th>
                                <th class="px-3 py-2.5 text-right font-medium">Received</th>
                                <th class="px-3 py-2.5 text-right font-medium">Issued</th>
                                <th class="px-3 py-2.5 text-right font-medium">Balance</th>
                                <th class="px-4 py-2.5 text-left font-medium">Remark</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="m in movements.data" :key="m.id">
                                <td class="px-4 py-2 whitespace-nowrap text-slate-600 dark:text-slate-400">{{ date(m.occurred_at) }}</td>
                                <td class="px-3 py-2 text-slate-800 dark:text-slate-200">{{ m.description || m.type }}</td>
                                <td class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">{{ m.shelf_section?.code }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-slate-500 dark:text-slate-400">{{ money(m.unit_price) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-slate-500 dark:text-slate-400">{{ money(m.total_price) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-emerald-600 dark:text-emerald-400">{{ m.quantity_received ?? '' }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-rose-600 dark:text-rose-400">{{ m.quantity_issued ?? '' }}</td>
                                <td class="px-3 py-2 text-right tabular-nums font-semibold text-slate-900 dark:text-white">{{ m.balance_after }}</td>
                                <td class="px-4 py-2 text-xs text-slate-500 dark:text-slate-400">{{ m.remark || m.reference_number || '' }}</td>
                            </tr>
                            <tr v-if="!movements.data.length">
                                <td colspan="9" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                    No movement in this range.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <p v-if="!filters.shelf_section_id" class="text-xs text-slate-500 dark:text-slate-400 print:hidden">
                Balances are per section. With sections combined, the balance column reads correctly only within each
                section — choose one above for a running total you can tick down the page.
            </p>

            <div class="print:hidden"><Pagination :links="movements.links" /></div>
        </div>
    </AuthenticatedLayout>
</template>
