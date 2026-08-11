<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import StatCard from '@/Components/Bookstore/StatCard.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';
import QrPanel from '@/Components/Bookstore/QrPanel.vue';
import Pagination from '@/Components/Library/Pagination.vue';
import Icon from '@/Components/Icon.vue';

defineProps({
    title: Object,
    movements: Object,
    printRuns: Array,
    stats: Object,
});

const money = (v) => Number(v ?? 0).toFixed(2);
const date = (v) => (v ? new Date(v).toLocaleDateString() : '—');
</script>

<template>
    <Head :title="title.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="truncate text-base font-semibold text-slate-900 dark:text-white">{{ title.title }}</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ title.code }}<span v-if="title.author"> · {{ title.author }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Link :href="route('bookstore.stock.bin-card', title.id)"
                          class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        <Icon name="ScrollText" :size="15" /> Bin card
                    </Link>
                    <Link :href="route('bookstore.titles.edit', title.id)"
                          class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                        <Icon name="Settings" :size="15" /> Edit
                    </Link>
                </div>
            </div>
        </template>

        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <div v-if="stats.low_stock"
                 class="flex items-center gap-3 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 px-4 py-3">
                <Icon name="AlertTriangle" :size="18" class="text-amber-600 dark:text-amber-400 shrink-0" />
                <p class="text-sm text-amber-900 dark:text-amber-200">
                    At or below the reorder level of {{ title.reorder_level }}.
                    <template v-if="title.reorder_quantity">Suggested reprint: {{ title.reorder_quantity }}.</template>
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="On hand" :value="stats.on_hand" icon="Boxes" tone="indigo" />
                <StatCard label="Available" :value="stats.available" :sub="`${stats.reserved} reserved`"
                          icon="Package" tone="emerald" />
                <StatCard label="Selling price" :value="money(title.unit_price)"
                          :sub="`Cost ${money(title.unit_cost)}`" icon="Banknote" tone="slate" />
                <StatCard label="Weeks of cover"
                          :value="stats.weeks_of_cover ?? '—'"
                          sub="At the last 90 days' issue rate" icon="Clock"
                          :tone="stats.weeks_of_cover !== null && stats.weeks_of_cover < 4 ? 'rose' : 'slate'" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-6">

                    <!-- Where the copies physically are -->
                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                        <h2 class="px-5 pt-5 text-sm font-semibold text-slate-900 dark:text-white">Locations</h2>
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                    <tr>
                                        <th class="px-5 py-2 text-left font-medium">Store › Shelf › Section</th>
                                        <th class="px-3 py-2 text-right font-medium">On hand</th>
                                        <th class="px-3 py-2 text-right font-medium">Reserved</th>
                                        <th class="px-5 py-2 text-right font-medium">Counted</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="stock in title.stocks" :key="stock.id">
                                        <td class="px-5 py-2.5">
                                            <Link :href="route('bookstore.sections.show', stock.shelf_section_id)"
                                                  class="text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400">
                                                {{ stock.shelf_section?.shelf?.store_room?.name }} ›
                                                {{ stock.shelf_section?.shelf?.label || stock.shelf_section?.shelf?.code }} ›
                                                {{ stock.shelf_section?.code }}
                                            </Link>
                                        </td>
                                        <td class="px-3 py-2.5 text-right tabular-nums font-medium text-slate-900 dark:text-white">{{ stock.quantity }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400">{{ stock.reserved_quantity }}</td>
                                        <td class="px-5 py-2.5 text-right text-xs text-slate-400">{{ date(stock.last_counted_at) }}</td>
                                    </tr>
                                    <tr v-if="!title.stocks.length">
                                        <td colspan="4" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">
                                            Not yet racked anywhere. Record a print run to bring stock in.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Recent ledger -->
                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                        <h2 class="px-5 pt-5 text-sm font-semibold text-slate-900 dark:text-white">Stock movement</h2>
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                    <tr>
                                        <th class="px-5 py-2 text-left font-medium">Date</th>
                                        <th class="px-3 py-2 text-left font-medium">Type</th>
                                        <th class="px-3 py-2 text-left font-medium">Section</th>
                                        <th class="px-3 py-2 text-right font-medium">Qty</th>
                                        <th class="px-3 py-2 text-right font-medium">Balance</th>
                                        <th class="px-5 py-2 text-left font-medium">Ref</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="m in movements.data" :key="m.id">
                                        <td class="px-5 py-2 text-slate-500 dark:text-slate-400">{{ date(m.occurred_at) }}</td>
                                        <td class="px-3 py-2 text-slate-700 dark:text-slate-300">{{ m.type }}</td>
                                        <td class="px-3 py-2 text-slate-500 dark:text-slate-400">{{ m.shelf_section?.code }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums">{{ m.quantity }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums font-medium text-slate-900 dark:text-white">{{ m.balance_after }}</td>
                                        <td class="px-5 py-2 text-xs text-slate-400">{{ m.reference_number || '—' }}</td>
                                    </tr>
                                    <tr v-if="!movements.data.length">
                                        <td colspan="6" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">No movement recorded.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4"><Pagination :links="movements.links" /></div>
                    </section>
                </div>

                <div class="space-y-6">
                    <QrPanel type="title" :id="title.id" :name="title.title" :sub="title.code" />

                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Classification</h2>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500 dark:text-slate-400">Programme</dt>
                                <dd class="text-right text-slate-800 dark:text-slate-200">{{ title.program?.title || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500 dark:text-slate-400">Study mode</dt>
                                <dd class="text-right text-slate-800 dark:text-slate-200">{{ title.study_mode?.name || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500 dark:text-slate-400">Language</dt>
                                <dd class="text-right text-slate-800 dark:text-slate-200">{{ title.language }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500 dark:text-slate-400">Course</dt>
                                <dd class="text-right text-slate-800 dark:text-slate-200">{{ title.course_name || title.course?.title || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                                <dd><StatusBadge :label="title.is_active ? 'Active' : 'Inactive'" :color="title.is_active ? 'green' : 'slate'" /></dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Recent print runs</h2>
                        <ul v-if="printRuns.length" class="mt-3 divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                            <li v-for="run in printRuns" :key="run.id" class="py-2">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ run.batch_number }}</span>
                                    <span class="tabular-nums font-medium text-slate-900 dark:text-white">+{{ run.quantity }}</span>
                                </div>
                                <p class="text-xs text-slate-400">
                                    {{ date(run.received_on) }} · {{ run.printer_name || 'printer not recorded' }}
                                </p>
                            </li>
                        </ul>
                        <p v-else class="mt-3 text-sm text-slate-500 dark:text-slate-400">No print runs recorded.</p>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
