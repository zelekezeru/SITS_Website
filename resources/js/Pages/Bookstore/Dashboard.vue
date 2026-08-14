<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import StatCard from '@/Components/Bookstore/StatCard.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    stats: Object,
    queue: Array,
    lowStock: Array,
    movement: Array,
    byStore: Array,
    recent: Array,
});

const money = (v) => Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const num = (v) => Number(v ?? 0).toLocaleString();

// Scale the trend bars against the busiest week so the shape is readable
// whatever the absolute volumes happen to be.
const peak = computed(() =>
    Math.max(1, ...props.movement.flatMap((w) => [w.received, w.issued])));

const barHeight = (value) => `${Math.round((value / peak.value) * 100)}%`;
</script>

<template>
    <Head title="Bookstore" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-base font-semibold text-slate-900 dark:text-white">Bookstore</h1>
                <div class="flex items-center gap-2">
                    <Link :href="route('bookstore.pipeline')"
                          class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        <Icon name="GitPullRequest" :size="15" /> Pipeline
                    </Link>
                    <Link :href="route('bookstore.scan.index')"
                          class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        <Icon name="QrCode" :size="15" /> Scan
                    </Link>
                    <Link :href="route('bookstore.requests.create')"
                          class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                        <Icon name="Plus" :size="15" /> New request
                    </Link>
                </div>
            </div>
        </template>

        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Out of stock first: it is the only thing that stops distribution. -->
            <Link v-if="stats.low_stock > 0" :href="route('bookstore.stock.low')"
                  class="flex items-center gap-3 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 px-4 py-3 hover:bg-amber-100 dark:hover:bg-amber-950/50 transition">
                <Icon name="AlertTriangle" :size="18" class="text-amber-600 dark:text-amber-400 shrink-0" />
                <p class="text-sm text-amber-900 dark:text-amber-200">
                    <strong>{{ stats.low_stock }}</strong>
                    {{ stats.low_stock === 1 ? 'title is' : 'titles are' }} at or below the reorder level.
                </p>
                <Icon name="ArrowRight" :size="16" class="ml-auto text-amber-600 dark:text-amber-400" />
            </Link>

            <!-- Money released on credit needs a person, not a report. -->
            <Link v-if="stats.pending_bypasses || stats.overdue_bypasses"
                  :href="route('bookstore.bypasses.index')"
                  class="flex flex-wrap items-center gap-3 rounded-xl border px-4 py-3 transition"
                  :class="stats.overdue_bypasses
                      ? 'border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-100 dark:hover:bg-rose-950/50'
                      : 'border-purple-300 dark:border-purple-800 bg-purple-50 dark:bg-purple-950/30 hover:bg-purple-100 dark:hover:bg-purple-950/50'">
                <Icon :name="stats.overdue_bypasses ? 'AlertTriangle' : 'HandCoins'" :size="18"
                      class="shrink-0"
                      :class="stats.overdue_bypasses ? 'text-rose-600 dark:text-rose-400' : 'text-purple-600 dark:text-purple-400'" />
                <p class="text-sm"
                   :class="stats.overdue_bypasses ? 'text-rose-900 dark:text-rose-200' : 'text-purple-900 dark:text-purple-200'">
                    <template v-if="stats.pending_bypasses">
                        <strong>{{ stats.pending_bypasses }}</strong> pay-later deferral(s) awaiting authorisation.
                    </template>
                    <template v-if="stats.overdue_bypasses">
                        <strong>{{ stats.overdue_bypasses }}</strong> past their promised date.
                    </template>
                    <template v-if="stats.deferred_debt > 0">
                        {{ money(stats.deferred_debt) }} released on credit and still owed.
                    </template>
                </p>
                <Icon name="ArrowRight" :size="16" class="ml-auto shrink-0"
                      :class="stats.overdue_bypasses ? 'text-rose-600 dark:text-rose-400' : 'text-purple-600 dark:text-purple-400'" />
            </Link>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Books on hand" :value="num(stats.on_hand)"
                          :sub="`${num(stats.available)} available · ${num(stats.reserved)} reserved`"
                          icon="Boxes" tone="indigo" />
                <StatCard label="Active titles" :value="num(stats.titles)"
                          :sub="`${stats.low_stock} at reorder level`" icon="BookCopy" tone="slate" />
                <StatCard label="Stock value (cost)" :value="money(stats.value_at_cost)"
                          :sub="`${money(stats.value_at_price)} at selling price`" icon="Banknote" tone="emerald" />
                <StatCard label="Open requests" :value="num(stats.open_requests)"
                          :sub="`${stats.pending_payments} payment(s) awaiting finance`"
                          icon="ClipboardList" tone="amber" />
            </div>

            <!-- The pipeline: how much work sits at each stage and for how long. -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Request pipeline</h2>
                    <Link :href="route('bookstore.pipeline')"
                          class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                        Full board
                    </Link>
                </div>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    Where requests are waiting. The oldest figure is the one to act on.
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Link v-for="stage in queue" :key="stage.status"
                          :href="route('bookstore.requests.index', { status: stage.status })"
                          class="rounded-lg border border-slate-200 dark:border-slate-800 p-3 hover:border-indigo-400 dark:hover:border-indigo-600 transition">
                        <div class="flex items-center justify-between gap-2">
                            <StatusBadge :label="stage.label" :color="stage.color" />
                            <span class="text-lg font-semibold tabular-nums text-slate-900 dark:text-white">{{ stage.count }}</span>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                            <template v-if="stage.count">Oldest waiting {{ stage.oldest_days }} day(s)</template>
                            <template v-else>Nothing waiting</template>
                        </p>
                    </Link>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Twelve-week movement trend -->
                <div class="lg:col-span-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Received vs issued</h2>
                        <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                            <span class="flex items-center gap-1.5"><i class="h-2 w-2 rounded-sm bg-emerald-500"></i> Received</span>
                            <span class="flex items-center gap-1.5"><i class="h-2 w-2 rounded-sm bg-indigo-500"></i> Issued</span>
                        </div>
                    </div>

                    <div v-if="movement.length" class="mt-5 flex h-40 items-end gap-2">
                        <div v-for="week in movement" :key="week.week" class="flex-1 flex flex-col items-center gap-1">
                            <div class="flex w-full items-end justify-center gap-0.5" style="height: 128px">
                                <div class="w-1/2 rounded-t bg-emerald-500/80" :style="{ height: barHeight(week.received) }"
                                     :title="`Received ${week.received}`"></div>
                                <div class="w-1/2 rounded-t bg-indigo-500/80" :style="{ height: barHeight(week.issued) }"
                                     :title="`Issued ${week.issued}`"></div>
                            </div>
                            <span class="text-[10px] text-slate-400">{{ week.week.slice(5) }}</span>
                        </div>
                    </div>
                    <p v-else class="mt-6 text-sm text-slate-500 dark:text-slate-400">No stock has moved in the last twelve weeks.</p>
                </div>

                <!-- Low stock watchlist -->
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Low stock</h2>
                        <Link :href="route('bookstore.stock.low')" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">All</Link>
                    </div>

                    <ul v-if="lowStock.length" class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                        <li v-for="title in lowStock" :key="title.id" class="py-2.5">
                            <Link :href="route('bookstore.titles.show', title.id)" class="flex items-center justify-between gap-3 group">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ title.title }}</p>
                                    <p class="text-xs text-slate-400">{{ title.code }}</p>
                                </div>
                                <StatusBadge
                                    :label="title.out_of_stock ? 'Out' : `${title.on_hand} / ${title.reorder_level}`"
                                    :color="title.out_of_stock ? 'red' : 'amber'" />
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-slate-500 dark:text-slate-400">Every title is above its reorder level.</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Recent ledger lines -->
                <div class="lg:col-span-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                    <h2 class="px-5 pt-5 text-sm font-semibold text-slate-900 dark:text-white">Recent stock movement</h2>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="px-5 py-2 text-left font-medium">Book</th>
                                    <th class="px-3 py-2 text-left font-medium">Type</th>
                                    <th class="px-3 py-2 text-left font-medium">Section</th>
                                    <th class="px-3 py-2 text-right font-medium">Qty</th>
                                    <th class="px-5 py-2 text-left font-medium">By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="row in recent" :key="row.id">
                                    <td class="px-5 py-2 text-slate-800 dark:text-slate-200">{{ row.title }}</td>
                                    <td class="px-3 py-2"><StatusBadge :label="row.type_label" :color="row.color" /></td>
                                    <td class="px-3 py-2 text-slate-500 dark:text-slate-400">{{ row.section }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums font-medium"
                                        :class="row.signed > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                        {{ row.signed > 0 ? '+' : '−' }}{{ row.quantity }}
                                    </td>
                                    <td class="px-5 py-2 text-slate-500 dark:text-slate-400">{{ row.by }}</td>
                                </tr>
                                <tr v-if="!recent.length">
                                    <td colspan="5" class="px-5 py-6 text-center text-slate-500 dark:text-slate-400">Nothing has moved yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Per-store totals -->
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">By store room</h2>
                    <ul class="mt-3 space-y-2">
                        <li v-for="store in byStore" :key="store.id">
                            <Link :href="route('bookstore.stores.show', store.id)"
                                  class="flex items-center justify-between gap-3 rounded-lg px-2 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ store.name }}</span>
                                <span class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">{{ num(store.on_hand) }}</span>
                            </Link>
                        </li>
                        <li v-if="!byStore.length" class="text-sm text-slate-500 dark:text-slate-400">No store rooms yet.</li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
