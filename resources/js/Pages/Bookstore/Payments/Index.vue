<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Pagination from '@/Components/Library/Pagination.vue';
import StatCard from '@/Components/Bookstore/StatCard.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({ payments: Object, filters: Object, statuses: Object, totals: Object });

const filterForm = ref({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

let debounce;
watch(filterForm, (value) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('bookstore.payments.index'), value, { preserveState: true, replace: true });
    }, 300);
}, { deep: true });

const rejecting = ref(null);
const rejectForm = useForm({ reason: '' });
const verifyForm = useForm({ note: '' });

const money = (v) => Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
const colors = { pending: 'amber', verified: 'green', rejected: 'red' };
</script>

<template>
    <Head title="Book payments" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-slate-900 dark:text-white">Book payments</h1>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-5">

            <div class="grid gap-4 sm:grid-cols-2">
                <StatCard label="Awaiting verification" :value="money(totals.pending)" icon="Clock" tone="amber" />
                <StatCard label="Verified to date" :value="money(totals.verified)" icon="CheckCircle" tone="emerald" />
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="filterForm.search" type="search" placeholder="Transaction reference, CRV or receipt number…"
                       class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <select v-model="filterForm.status" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Any status</option>
                    <option v-for="(l, v) in statuses" :key="v" :value="v">{{ l }}</option>
                </select>
            </div>

            <div class="space-y-3">
                <div v-for="p in payments.data" :key="p.id"
                     class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <Link :href="route('bookstore.requests.show', p.book_request_id)"
                                      class="font-mono text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ p.book_request?.request_number }}
                                </Link>
                                <StatusBadge :label="p.status" :color="colors[p.status]" />
                            </div>
                            <p class="mt-1 text-lg font-semibold tabular-nums text-slate-900 dark:text-white">{{ money(p.amount) }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ p.method.replace(/_/g, ' ') }}
                                <template v-if="p.bank_name"> · {{ p.bank_name }}</template>
                                <template v-if="p.transaction_reference"> · Txn {{ p.transaction_reference }}</template>
                                <template v-if="p.crv_number"> · CRV {{ p.crv_number }}</template>
                                · {{ p.paid_on }}
                            </p>
                            <p class="text-xs text-slate-400">
                                Recorded by {{ p.recorded_by?.name }}
                                <template v-if="p.verified_by"> · decided by {{ p.verified_by.name }}</template>
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <a v-if="p.has_receipt" :href="route('bookstore.payments.receipt', p.id)" target="_blank"
                               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-2.5 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                <Icon name="Image" :size="14" /> Receipt
                            </a>
                            <template v-if="p.status === 'pending'">
                                <button type="button"
                                        @click="verifyForm.post(route('bookstore.payments.verify', p.id), { preserveScroll: true })"
                                        class="rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 transition">
                                    Verify
                                </button>
                                <button type="button" @click="rejecting = rejecting === p.id ? null : p.id"
                                        class="rounded-lg border border-rose-300 dark:border-rose-800 px-2.5 py-1.5 text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">
                                    Reject
                                </button>
                            </template>
                        </div>
                    </div>

                    <form v-if="rejecting === p.id"
                          @submit.prevent="rejectForm.post(route('bookstore.payments.reject', p.id), { preserveScroll: true, onSuccess: () => (rejecting = null) })"
                          class="mt-3 flex gap-2 border-t border-slate-100 dark:border-slate-800 pt-3">
                        <input v-model="rejectForm.reason" type="text" required placeholder="Why is this being rejected?"
                               class="flex-1 rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-rose-500 focus:ring-rose-500" />
                        <button type="submit" :disabled="rejectForm.processing"
                                class="rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-50">
                            Reject
                        </button>
                    </form>

                    <p v-if="p.rejection_reason" class="mt-2 text-xs text-rose-600 dark:text-rose-400">{{ p.rejection_reason }}</p>
                </div>

                <p v-if="!payments.data.length"
                   class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                    No payments match these filters.
                </p>
            </div>

            <Pagination :links="payments.links" />
        </div>
    </AuthenticatedLayout>
</template>
