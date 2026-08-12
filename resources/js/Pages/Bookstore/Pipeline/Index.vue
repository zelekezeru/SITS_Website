<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';

/**
 * The shared board. Deliberately open to every bookstore viewer: layered
 * approval only works if the people waiting can see where their request is and
 * who is holding it.
 */
const props = defineProps({
    stages: Array,
    requests: Array,
    lag: Array,
    alerts: Object,
    stalledAfterDays: Number,
});

const focus = ref('');

const visible = computed(() =>
    focus.value ? props.requests.filter((r) => r.status === focus.value) : props.requests);

const money = (v) => Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// "4h" / "3d" — the unit somebody chasing a delay actually thinks in.
const age = (row) => (row.stage_age_days >= 1 ? `${row.stage_age_days}d` : `${row.stage_age_hours}h`);

const isStalled = (row) => row.stage_age_days >= props.stalledAfterDays;
</script>

<template>
    <Head title="Request pipeline" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-base font-semibold text-slate-900 dark:text-white">Request pipeline</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Every open request, the layer it is waiting on, and how long it has waited.
                    </p>
                </div>
                <Link :href="route('bookstore.requests.create')"
                      class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                    <Icon name="Plus" :size="15" /> New request
                </Link>
            </div>
        </template>

        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- What needs a human right now -->
            <div v-if="alerts.stalled || alerts.pending_bypasses || alerts.overdue_bypasses"
                 class="grid gap-3 sm:grid-cols-3">
                <div v-if="alerts.stalled"
                     class="flex items-center gap-3 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 px-4 py-3">
                    <Icon name="Clock" :size="18" class="text-amber-600 dark:text-amber-400 shrink-0" />
                    <p class="text-sm text-amber-900 dark:text-amber-200">
                        <strong>{{ alerts.stalled }}</strong> sitting more than {{ stalledAfterDays }} days at one stage
                    </p>
                </div>
                <Link v-if="alerts.pending_bypasses" :href="route('bookstore.bypasses.index', { status: 'pending' })"
                      class="flex items-center gap-3 rounded-xl border border-purple-300 dark:border-purple-800 bg-purple-50 dark:bg-purple-950/30 px-4 py-3 hover:bg-purple-100 dark:hover:bg-purple-950/50 transition">
                    <Icon name="HandCoins" :size="18" class="text-purple-600 dark:text-purple-400 shrink-0" />
                    <p class="text-sm text-purple-900 dark:text-purple-200">
                        <strong>{{ alerts.pending_bypasses }}</strong> pay-later deferral(s) awaiting authorisation
                    </p>
                </Link>
                <Link v-if="alerts.overdue_bypasses" :href="route('bookstore.bypasses.index', { status: 'approved' })"
                      class="flex items-center gap-3 rounded-xl border border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30 px-4 py-3 hover:bg-rose-100 dark:hover:bg-rose-950/50 transition">
                    <Icon name="AlertTriangle" :size="18" class="text-rose-600 dark:text-rose-400 shrink-0" />
                    <p class="text-sm text-rose-900 dark:text-rose-200">
                        <strong>{{ alerts.overdue_bypasses }}</strong> deferred payment(s) past their promised date
                    </p>
                </Link>
            </div>

            <!-- The layers, as clickable columns -->
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <button v-for="stage in stages" :key="stage.status" type="button"
                        @click="focus = focus === stage.status ? '' : stage.status"
                        class="rounded-xl border bg-white dark:bg-slate-900 p-3 text-left transition"
                        :class="focus === stage.status
                            ? 'border-indigo-500 ring-1 ring-indigo-500'
                            : 'border-slate-200 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-600'">
                    <StatusBadge :label="stage.label" :color="stage.color" />
                    <p class="mt-2 text-2xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ stage.count }}</p>
                    <p class="mt-0.5 text-[11px] leading-tight text-slate-500 dark:text-slate-400">{{ stage.waiting_on }}</p>
                    <p v-if="stage.count" class="mt-1 text-[11px] text-slate-400">
                        oldest {{ stage.oldest_days }}d · {{ money(stage.value) }}
                    </p>
                </button>
            </div>

            <!-- Who is holding what -->
            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <div class="flex items-center justify-between px-5 pt-5">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                        Open requests<span v-if="focus" class="font-normal text-slate-500 dark:text-slate-400"> — filtered</span>
                    </h2>
                    <button v-if="focus" type="button" @click="focus = ''"
                            class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                        Show all
                    </button>
                </div>

                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-2.5 text-left font-medium">Request</th>
                                <th class="px-3 py-2.5 text-left font-medium">Destination</th>
                                <th class="px-3 py-2.5 text-left font-medium">Stage</th>
                                <th class="px-3 py-2.5 text-left font-medium">Waiting on</th>
                                <th class="px-3 py-2.5 text-right font-medium">At this stage</th>
                                <th class="px-3 py-2.5 text-right font-medium">Total age</th>
                                <th class="px-5 py-2.5 text-right font-medium">Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="row in visible" :key="row.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                :class="isStalled(row) ? 'bg-amber-50/50 dark:bg-amber-950/10' : ''">
                                <td class="px-5 py-2.5">
                                    <Link :href="route('bookstore.requests.show', row.id)"
                                          class="font-mono text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ row.request_number }}
                                    </Link>
                                    <p class="text-xs text-slate-400">{{ row.requester }}</p>
                                </td>
                                <td class="px-3 py-2.5 text-slate-800 dark:text-slate-200">
                                    {{ row.destination }}
                                    <span v-if="row.deferred"
                                          class="ml-1 rounded bg-purple-100 px-1.5 py-0.5 text-[10px] font-medium text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                                        pay later
                                    </span>
                                    <span v-else-if="row.bypass_pending"
                                          class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                        deferral pending
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <StatusBadge :label="row.status_label" :color="row.color" />
                                </td>
                                <td class="px-3 py-2.5 text-xs text-slate-600 dark:text-slate-400">
                                    {{ row.waiting_on }}
                                    <p v-if="row.owners.length" class="text-slate-400">{{ row.owners.join(', ') }}</p>
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums font-medium"
                                    :class="isStalled(row) ? 'text-amber-700 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300'">
                                    {{ age(row) }}
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400">
                                    {{ row.total_age_days }}d
                                </td>
                                <td class="px-5 py-2.5 text-right tabular-nums">{{ money(row.amount) }}</td>
                            </tr>
                            <tr v-if="!visible.length">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500 dark:text-slate-400">
                                    Nothing open{{ focus ? ' at this stage' : '' }}.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Which layer is actually the bottleneck -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Average lag by layer</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    Measured across completed steps — how long each layer typically leaves a request waiting.
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="entry in lag" :key="entry.stage"
                         class="rounded-lg border border-slate-200 dark:border-slate-800 p-3">
                        <p class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ entry.label }}</p>
                        <p class="mt-1 text-lg font-semibold tabular-nums text-slate-900 dark:text-white">
                            <template v-if="entry.avg_hours !== null">{{ entry.avg_hours }}h</template>
                            <template v-else>—</template>
                        </p>
                        <p class="text-[11px] text-slate-400">
                            <template v-if="entry.samples">
                                {{ entry.samples }} step(s) · worst {{ entry.worst_hours }}h
                            </template>
                            <template v-else>No completed steps yet</template>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
