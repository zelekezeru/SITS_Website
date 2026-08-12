<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Pagination from '@/Components/Library/Pagination.vue';
import StatCard from '@/Components/Bookstore/StatCard.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';

/**
 * The deferral register — every time books were released before the money came
 * in, with the reason, the authorisation, and whether the promise has come due.
 */
defineProps({
    bypasses: Object,
    filters: Object,
    statuses: Object,
    totals: Object,
});

const deciding = ref(null);
const approveForm = useForm({ justification: '' });
const rejectForm = useForm({ reason: '' });

const money = (v) => Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const date = (v) => (v ? new Date(v).toLocaleDateString() : '—');

const colors = { pending: 'amber', approved: 'purple', rejected: 'red', settled: 'green' };

const filterBy = (status) =>
    router.get(route('bookstore.bypasses.index'), status ? { status } : {}, { preserveState: true, replace: true });

const field = 'mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500';
</script>

<template>
    <Head title="Pay-later deferrals" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-base font-semibold text-slate-900 dark:text-white">Pay-later deferrals</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Books released before payment. Finance asks, a separate authoriser accepts the debt.
                </p>
            </div>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-5">

            <div class="grid gap-4 sm:grid-cols-3">
                <StatCard label="Awaiting authorisation" :value="totals.pending" icon="Clock"
                          :tone="totals.pending ? 'amber' : 'slate'" />
                <StatCard label="Outstanding debt" :value="money(totals.outstanding)"
                          sub="Released but not yet settled" icon="HandCoins"
                          :tone="totals.outstanding > 0 ? 'rose' : 'slate'" />
                <StatCard label="Past promised date" :value="totals.overdue" icon="AlertTriangle"
                          :tone="totals.overdue ? 'rose' : 'emerald'" />
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" @click="filterBy('')"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                        :class="!filters.status ? 'bg-indigo-600 text-white' : 'border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'">
                    All
                </button>
                <button v-for="(label, value) in statuses" :key="value" type="button" @click="filterBy(value)"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                        :class="filters.status === value ? 'bg-indigo-600 text-white' : 'border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'">
                    {{ label }}
                </button>
            </div>

            <div class="space-y-3">
                <div v-for="b in bypasses.data" :key="b.id"
                     class="rounded-xl border bg-white dark:bg-slate-900 p-4"
                     :class="b.is_overdue
                         ? 'border-rose-300 dark:border-rose-800'
                         : 'border-slate-200 dark:border-slate-800'">

                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-medium text-slate-600 dark:text-slate-400">{{ b.reference }}</span>
                                <StatusBadge :label="b.status" :color="colors[b.status]" />
                                <StatusBadge v-if="b.is_overdue" label="overdue" color="red" />
                                <Link :href="route('bookstore.requests.show', b.book_request_id)"
                                      class="font-mono text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ b.book_request?.request_number }}
                                </Link>
                            </div>
                            <p class="mt-1 text-lg font-semibold tabular-nums text-slate-900 dark:text-white">
                                {{ money(b.amount) }} deferred
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ b.book_request?.center?.name || b.book_request?.campus?.name || '—' }}
                                · asked by {{ b.requested_by?.name }} on {{ date(b.requested_at) }}
                                <template v-if="b.promised_on"> · promised {{ date(b.promised_on) }}</template>
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button v-if="b.status === 'pending'" type="button"
                                    @click="deciding = deciding === b.id ? null : b.id"
                                    class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 transition">
                                Decide
                            </button>
                            <button v-if="b.status === 'approved'" type="button"
                                    @click="router.post(route('bookstore.bypasses.settle', b.id), {}, { preserveScroll: true })"
                                    class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 transition">
                                Mark settled
                            </button>
                        </div>
                    </div>

                    <!-- The two written statements, side by side: that pairing is the whole control. -->
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 border-t border-slate-100 dark:border-slate-800 pt-3">
                        <div>
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Reason for deferring</p>
                            <p class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ b.reason }}</p>
                        </div>
                        <div v-if="b.justification">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                Authorised by {{ b.decided_by?.name }} on {{ date(b.decided_at) }}
                            </p>
                            <p class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ b.justification }}</p>
                        </div>
                        <div v-else-if="b.rejection_reason">
                            <p class="text-xs font-medium text-rose-600 dark:text-rose-400">
                                Declined by {{ b.decided_by?.name }} on {{ date(b.decided_at) }}
                            </p>
                            <p class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ b.rejection_reason }}</p>
                        </div>
                    </div>

                    <div v-if="deciding === b.id" class="mt-3 grid gap-3 border-t border-slate-100 dark:border-slate-800 pt-3 sm:grid-cols-2">
                        <form @submit.prevent="approveForm.post(route('bookstore.bypasses.approve', b.id), { preserveScroll: true, onSuccess: () => { deciding = null; approveForm.reset(); } })"
                              class="rounded-lg border border-emerald-200 dark:border-emerald-900 p-3">
                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                                Justification <span class="text-rose-500">*</span>
                            </label>
                            <textarea v-model="approveForm.justification" rows="2" :class="field" required
                                      placeholder="Why is it acceptable to release these books unpaid?"></textarea>
                            <p class="mt-1 text-[11px] text-slate-400">This accepts a debt. It is recorded against your name.</p>
                            <button type="submit" :disabled="approveForm.processing"
                                    class="mt-2 w-full rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">
                                Authorise deferral
                            </button>
                        </form>

                        <form @submit.prevent="rejectForm.post(route('bookstore.bypasses.reject', b.id), { preserveScroll: true, onSuccess: () => { deciding = null; rejectForm.reset(); } })"
                              class="rounded-lg border border-rose-200 dark:border-rose-900 p-3">
                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                                Reason for declining <span class="text-rose-500">*</span>
                            </label>
                            <textarea v-model="rejectForm.reason" rows="2" :class="field" required></textarea>
                            <p class="mt-1 text-[11px] text-slate-400">The request stays at the payment gate.</p>
                            <button type="submit" :disabled="rejectForm.processing"
                                    class="mt-2 w-full rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-50">
                                Decline
                            </button>
                        </form>
                    </div>
                </div>

                <p v-if="!bypasses.data.length"
                   class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                    <Icon name="CheckCircle" :size="26" class="mx-auto mb-2 text-emerald-500" />
                    No deferrals recorded. Every release so far was paid for first.
                </p>
            </div>

            <Pagination :links="bypasses.links" />
        </div>
    </AuthenticatedLayout>
</template>
