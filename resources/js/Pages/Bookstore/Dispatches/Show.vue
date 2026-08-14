<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';
import QrPanel from '@/Components/Bookstore/QrPanel.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({ dispatch: Object, qr: Object });

const confirmForm = useForm({
    received_by_name: props.dispatch.received_by_name ?? '',
    received_by_phone: props.dispatch.received_by_phone ?? '',
});

const money = (v) => Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
const date = (v) => (v ? new Date(v).toLocaleString() : '—');
const colors = { prepared: 'gray', in_transit: 'blue', received: 'green', returned: 'orange' };

const field = 'mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-xs font-medium text-slate-600 dark:text-slate-400';
</script>

<template>
    <Head :title="dispatch.dispatch_number" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="truncate text-base font-semibold text-slate-900 dark:text-white">{{ dispatch.dispatch_number }}</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ dispatch.book_request?.center?.name || dispatch.book_request?.campus?.name || dispatch.book_request?.campus?.name_en }}
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <StatusBadge :label="dispatch.status.replace(/_/g, ' ')" :color="colors[dispatch.status]" />
                    <a :href="route('bookstore.dispatches.print', dispatch.id)" target="_blank"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                        <Icon name="Printer" :size="15" /> Print waybill
                    </a>
                </div>
            </div>
        </template>

        <div class="p-6 max-w-5xl mx-auto space-y-6">

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-6">
                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                        <h2 class="px-5 pt-5 text-sm font-semibold text-slate-900 dark:text-white">Consignment</h2>
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                    <tr>
                                        <th class="px-5 py-2 text-left font-medium">Code</th>
                                        <th class="px-3 py-2 text-left font-medium">Book</th>
                                        <th class="px-3 py-2 text-left font-medium">Picked from</th>
                                        <th class="px-3 py-2 text-right font-medium">Qty</th>
                                        <th class="px-5 py-2 text-right font-medium">Line total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="item in dispatch.items" :key="item.id">
                                        <td class="px-5 py-2.5 font-mono text-xs text-slate-500 dark:text-slate-400">{{ item.book_title?.code }}</td>
                                        <td class="px-3 py-2.5 text-slate-800 dark:text-slate-200">{{ item.book_title?.title }}</td>
                                        <td class="px-3 py-2.5 text-xs text-slate-500 dark:text-slate-400">
                                            {{ item.shelf_section?.shelf?.store_room?.name }} › {{ item.shelf_section?.code }}
                                        </td>
                                        <td class="px-3 py-2.5 text-right tabular-nums font-medium">{{ item.quantity }}</td>
                                        <td class="px-5 py-2.5 text-right tabular-nums">{{ money(item.line_total) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="border-t border-slate-200 dark:border-slate-800 text-sm font-semibold">
                                    <tr>
                                        <td colspan="3" class="px-5 py-2.5">Total</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ dispatch.total_quantity }}</td>
                                        <td class="px-5 py-2.5 text-right tabular-nums">{{ money(dispatch.total_amount) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Handover</h2>
                        <dl class="mt-3 grid gap-3 sm:grid-cols-2 text-sm">
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Handed over by</dt>
                                <dd class="text-slate-800 dark:text-slate-200">{{ dispatch.dispatched_by?.name }}</dd>
                                <dd class="text-xs text-slate-400">{{ date(dispatch.dispatched_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500 dark:text-slate-400">Received by</dt>
                                <dd class="text-slate-800 dark:text-slate-200">{{ dispatch.received_by_name || '—' }}</dd>
                                <dd class="text-xs text-slate-400">{{ dispatch.received_at ? date(dispatch.received_at) : 'Not yet confirmed' }}</dd>
                            </div>
                        </dl>

                        <form v-if="dispatch.status !== 'received'"
                              @submit.prevent="confirmForm.post(route('bookstore.dispatches.confirm', dispatch.id), { preserveScroll: true })"
                              class="mt-4 grid gap-3 sm:grid-cols-3 border-t border-slate-100 dark:border-slate-800 pt-4">
                            <div>
                                <label :class="label">Receiver name</label>
                                <input v-model="confirmForm.received_by_name" type="text" :class="field" />
                            </div>
                            <div>
                                <label :class="label">Mobile</label>
                                <input v-model="confirmForm.received_by_phone" type="text" :class="field" />
                            </div>
                            <div class="flex items-end">
                                <button type="submit" :disabled="confirmForm.processing"
                                        class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">
                                    Confirm delivery
                                </button>
                            </div>
                        </form>

                        <p v-if="dispatch.notes" class="mt-3 text-sm text-slate-600 dark:text-slate-400">{{ dispatch.notes }}</p>
                    </section>
                </div>

                <div class="space-y-6">
                    <QrPanel type="waybill" :id="dispatch.id" :name="dispatch.dispatch_number"
                             :sub="'Scan to confirm receipt'" :size="180" />

                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Request</h2>
                        <Link :href="route('bookstore.requests.show', dispatch.book_request_id)"
                              class="mt-2 block font-mono text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                            {{ dispatch.book_request?.request_number }}
                        </Link>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Raised by {{ dispatch.book_request?.requester?.name }}
                        </p>
                        <Link :href="route('bookstore.returns.create', { dispatch: dispatch.id })"
                              class="mt-4 block w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 text-center text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            Record a return against this
                        </Link>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
