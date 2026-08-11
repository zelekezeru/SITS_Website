<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    request: Object,
    availability: Object,
    actions: Object,
    stages: Array,
    financials: Object,
});

const statusColors = {
    draft: 'gray', submitted: 'blue', awaiting_payment: 'amber', payment_verified: 'cyan',
    approved: 'indigo', partially_dispatched: 'orange', dispatched: 'teal',
    received: 'green', rejected: 'red', cancelled: 'rose',
};

const steps = [
    { key: 'draft', label: 'Raised' },
    { key: 'submitted', label: 'Verified' },
    { key: 'awaiting_payment', label: 'Paid' },
    { key: 'payment_verified', label: 'Approved' },
    { key: 'approved', label: 'Dispatched' },
    { key: 'dispatched', label: 'Received' },
];

const stepIndex = computed(() => ({
    draft: 0, submitted: 1, awaiting_payment: 2, payment_verified: 3,
    approved: 4, partially_dispatched: 4, dispatched: 5, received: 6,
    rejected: -1, cancelled: -1,
}[props.request.status] ?? 0));

const money = (v) => Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const date = (v) => (v ? new Date(v).toLocaleString() : '—');

// ── Verification: the approver may trim each line ───────────────────────────
const verifyForm = useForm({
    quantities: Object.fromEntries(props.request.items.map((i) => [i.id, i.quantity_requested])),
    note: '',
});

const noteForm = useForm({ note: '' });
const rejectForm = useForm({ stage: 'verification', reason: '' });
const cancelForm = useForm({ reason: '' });

const paymentForm = useForm({
    amount: props.financials.outstanding,
    method: 'bank_transfer',
    bank_name: '',
    transaction_reference: '',
    crv_number: '',
    receipt_number: '',
    paid_on: new Date().toISOString().slice(0, 10),
    receipt_image: null,
    notes: '',
});

const showVerify = ref(false);
const showReject = ref(false);
const showPayment = ref(false);

const post = (name, form = noteForm) => form.post(route(name, props.request.id), { preserveScroll: true });

const needsReference = computed(() =>
    ['bank_transfer', 'cbe_birr', 'telebirr', 'cheque'].includes(paymentForm.method));

const field = 'mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-xs font-medium text-slate-600 dark:text-slate-400';
</script>

<template>
    <Head :title="request.request_number" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="truncate text-base font-semibold text-slate-900 dark:text-white">
                        {{ request.request_number }}
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ request.center?.name || request.campus?.name || request.campus?.name_en }} ·
                        {{ request.student_count }} students
                    </p>
                </div>
                <StatusBadge :label="request.status.replace(/_/g, ' ')" :color="statusColors[request.status]" />
            </div>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-6">

            <!-- Progress: one predictable journey, always visible. -->
            <div v-if="stepIndex >= 0" class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                <ol class="flex flex-wrap items-center gap-y-3">
                    <li v-for="(step, i) in steps" :key="step.key" class="flex items-center">
                        <div class="flex items-center gap-2">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-semibold"
                                  :class="i < stepIndex
                                      ? 'bg-emerald-500 text-white'
                                      : i === stepIndex
                                          ? 'bg-indigo-600 text-white'
                                          : 'bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'">
                                <Icon v-if="i < stepIndex" name="Check" :size="13" />
                                <template v-else>{{ i + 1 }}</template>
                            </span>
                            <span class="text-xs font-medium"
                                  :class="i <= stepIndex ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-600'">
                                {{ step.label }}
                            </span>
                        </div>
                        <span v-if="i < steps.length - 1" class="mx-3 h-px w-6 sm:w-10"
                              :class="i < stepIndex ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-800'"></span>
                    </li>
                </ol>
            </div>

            <div v-else class="rounded-xl border border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30 px-4 py-3">
                <p class="text-sm font-medium text-rose-900 dark:text-rose-200">
                    This request was {{ request.status }}.
                </p>
                <p v-if="request.rejection_reason" class="mt-1 text-sm text-rose-800 dark:text-rose-300">
                    {{ request.rejection_reason }}
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-6">

                    <!-- Lines -->
                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                        <h2 class="px-5 pt-5 text-sm font-semibold text-slate-900 dark:text-white">Books requested</h2>
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                    <tr>
                                        <th class="px-5 py-2 text-left font-medium">Book</th>
                                        <th class="px-3 py-2 text-right font-medium">Requested</th>
                                        <th class="px-3 py-2 text-right font-medium">Approved</th>
                                        <th class="px-3 py-2 text-right font-medium">Dispatched</th>
                                        <th class="px-3 py-2 text-right font-medium">Available</th>
                                        <th class="px-5 py-2 text-right font-medium">Line total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="item in request.items" :key="item.id">
                                        <td class="px-5 py-2.5">
                                            <Link :href="route('bookstore.titles.show', item.book_title_id)"
                                                  class="text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400">
                                                {{ item.book_title?.title }}
                                            </Link>
                                            <p class="text-xs text-slate-400">{{ item.book_title?.code }}</p>
                                        </td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ item.quantity_requested }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums font-medium text-slate-900 dark:text-white">{{ item.quantity_approved }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ item.quantity_dispatched }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400">{{ availability[item.id] }}</td>
                                        <td class="px-5 py-2.5 text-right tabular-nums">{{ money(item.line_total) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="border-t border-slate-200 dark:border-slate-800 text-sm font-semibold">
                                    <tr>
                                        <td class="px-5 py-2.5">Total</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ request.total_quantity }}</td>
                                        <td colspan="3"></td>
                                        <td class="px-5 py-2.5 text-right tabular-nums">{{ money(request.total_amount) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </section>

                    <!-- Payments -->
                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Payments</h2>
                            <button type="button" @click="showPayment = !showPayment"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-2.5 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                <Icon name="Plus" :size="14" /> Record payment
                            </button>
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-3 text-sm">
                            <div><p class="text-xs text-slate-500 dark:text-slate-400">Invoiced</p><p class="font-semibold tabular-nums">{{ money(financials.total) }}</p></div>
                            <div><p class="text-xs text-slate-500 dark:text-slate-400">Verified</p><p class="font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ money(financials.paid) }}</p></div>
                            <div><p class="text-xs text-slate-500 dark:text-slate-400">Outstanding</p><p class="font-semibold tabular-nums" :class="financials.outstanding > 0 ? 'text-amber-600 dark:text-amber-400' : ''">{{ money(financials.outstanding) }}</p></div>
                        </div>

                        <!-- Record payment -->
                        <form v-if="showPayment"
                              @submit.prevent="paymentForm.post(route('bookstore.payments.store', request.id), { forceFormData: true, preserveScroll: true, onSuccess: () => { showPayment = false; paymentForm.reset(); } })"
                              class="mt-4 grid gap-3 sm:grid-cols-2 rounded-lg border border-indigo-200 dark:border-indigo-900 p-4">
                            <div>
                                <label :class="label">Amount</label>
                                <input v-model="paymentForm.amount" type="number" step="0.01" min="0" :class="field" required />
                            </div>
                            <div>
                                <label :class="label">Method</label>
                                <select v-model="paymentForm.method" :class="field">
                                    <option value="bank_transfer">Bank transfer</option>
                                    <option value="cbe_birr">CBE Birr</option>
                                    <option value="telebirr">Telebirr</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="cash">Cash</option>
                                    <option value="waiver">Waiver / free issue</option>
                                </select>
                            </div>
                            <div v-if="needsReference">
                                <label :class="label">Bank</label>
                                <input v-model="paymentForm.bank_name" type="text" :class="field" />
                            </div>
                            <div v-if="needsReference">
                                <label :class="label">Transaction reference <span class="text-rose-500">*</span></label>
                                <input v-model="paymentForm.transaction_reference" type="text" :class="field" required />
                                <p v-if="paymentForm.errors.transaction_reference" class="mt-1 text-xs text-rose-500">{{ paymentForm.errors.transaction_reference }}</p>
                            </div>
                            <div>
                                <label :class="label">CRV number</label>
                                <input v-model="paymentForm.crv_number" type="text" :class="field" placeholder="Manual receipt book" />
                            </div>
                            <div>
                                <label :class="label">Paid on</label>
                                <input v-model="paymentForm.paid_on" type="date" :class="field" required />
                            </div>
                            <div class="sm:col-span-2">
                                <label :class="label">Receipt image</label>
                                <input type="file" accept="image/*" :class="field"
                                       @input="paymentForm.receipt_image = $event.target.files[0]" />
                                <p class="mt-1 text-xs text-slate-400">Stored privately and only viewable inside the system.</p>
                            </div>
                            <div class="sm:col-span-2 flex justify-end gap-2">
                                <button type="button" @click="showPayment = false"
                                        class="rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-sm">Cancel</button>
                                <button type="submit" :disabled="paymentForm.processing"
                                        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                                    Record payment
                                </button>
                            </div>
                        </form>

                        <ul v-if="request.payments.length" class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                            <li v-for="p in request.payments" :key="p.id" class="py-3 flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-200">
                                        {{ money(p.amount) }} · {{ p.method.replace(/_/g, ' ') }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        <template v-if="p.transaction_reference">Txn {{ p.transaction_reference }} · </template>
                                        <template v-if="p.crv_number">CRV {{ p.crv_number }} · </template>
                                        {{ p.paid_on }}
                                    </p>
                                    <a v-if="p.has_receipt" :href="route('bookstore.payments.receipt', p.id)" target="_blank"
                                       class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View receipt</a>
                                </div>
                                <StatusBadge :label="p.status"
                                             :color="{ pending: 'amber', verified: 'green', rejected: 'red' }[p.status]" />
                            </li>
                        </ul>
                        <p v-else class="mt-4 text-sm text-slate-500 dark:text-slate-400">No payment recorded yet.</p>
                    </section>

                    <!-- Dispatches -->
                    <section v-if="request.dispatches.length" class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Consignments</h2>
                        <ul class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                            <li v-for="d in request.dispatches" :key="d.id" class="py-3 flex items-center justify-between gap-3">
                                <div>
                                    <Link :href="route('bookstore.dispatches.show', d.id)"
                                          class="font-mono text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ d.dispatch_number }}
                                    </Link>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ d.total_quantity }} books · {{ date(d.dispatched_at) }}
                                    </p>
                                </div>
                                <StatusBadge :label="d.status.replace(/_/g, ' ')"
                                             :color="{ prepared: 'gray', in_transit: 'blue', received: 'green', returned: 'orange' }[d.status]" />
                            </li>
                        </ul>
                    </section>
                </div>

                <!-- Actions + trail -->
                <div class="space-y-6">
                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">What happens next</h2>

                        <div class="mt-3 space-y-2">
                            <Link v-if="actions.edit" :href="route('bookstore.requests.edit', request.id)"
                                  class="block w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 text-center text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                Edit draft
                            </Link>

                            <button v-if="actions.submit" type="button" @click="post('bookstore.requests.submit')"
                                    class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition">
                                Submit for verification
                            </button>

                            <button v-if="actions.verify" type="button" @click="showVerify = !showVerify"
                                    class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition">
                                Verify availability
                            </button>

                            <button v-if="actions.verify_payment" type="button" @click="post('bookstore.requests.verify-payment')"
                                    class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition">
                                Confirm payment settled
                            </button>

                            <button v-if="actions.approve" type="button" @click="post('bookstore.requests.approve')"
                                    class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition">
                                Give final approval
                            </button>

                            <Link v-if="actions.dispatch" :href="route('bookstore.dispatches.create', request.id)"
                                  class="block w-full rounded-lg bg-teal-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-teal-700 transition">
                                Dispatch from store
                            </Link>

                            <button v-if="actions.confirm_receipt" type="button" @click="post('bookstore.requests.confirm')"
                                    class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition">
                                Confirm receipt
                            </button>

                            <button v-if="actions.reject" type="button" @click="showReject = !showReject"
                                    class="w-full rounded-lg border border-rose-300 dark:border-rose-800 px-3 py-2 text-sm font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">
                                Reject
                            </button>

                            <button v-if="actions.cancel" type="button"
                                    @click="cancelForm.post(route('bookstore.requests.cancel', request.id), { preserveScroll: true })"
                                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                Cancel request
                            </button>

                            <p v-if="!Object.values(actions).some(Boolean)" class="text-sm text-slate-500 dark:text-slate-400">
                                Nothing for you to do on this request right now.
                            </p>
                        </div>

                        <!-- Verification: trim lines to what the shelves can cover. -->
                        <form v-if="showVerify"
                              @submit.prevent="verifyForm.post(route('bookstore.requests.verify', request.id), { preserveScroll: true, onSuccess: () => (showVerify = false) })"
                              class="mt-4 space-y-3 rounded-lg border border-indigo-200 dark:border-indigo-900 p-3">
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Approve up to the requested quantity per line. Approving reserves the stock.
                            </p>
                            <div v-for="item in request.items" :key="item.id">
                                <label :class="label">
                                    {{ item.book_title?.title }}
                                    <span class="text-slate-400">(max {{ item.quantity_requested }}, {{ availability[item.id] }} available)</span>
                                </label>
                                <input v-model="verifyForm.quantities[item.id]" type="number" min="0"
                                       :max="item.quantity_requested" :class="field" />
                            </div>
                            <div>
                                <label :class="label">Note</label>
                                <input v-model="verifyForm.note" type="text" :class="field" />
                            </div>
                            <button type="submit" :disabled="verifyForm.processing"
                                    class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                                Verify and reserve
                            </button>
                        </form>

                        <form v-if="showReject"
                              @submit.prevent="rejectForm.post(route('bookstore.requests.reject', request.id), { preserveScroll: true, onSuccess: () => (showReject = false) })"
                              class="mt-4 space-y-3 rounded-lg border border-rose-200 dark:border-rose-900 p-3">
                            <div>
                                <label :class="label">Stage</label>
                                <select v-model="rejectForm.stage" :class="field">
                                    <option v-for="s in stages" :key="s.value" :value="s.value">{{ s.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label :class="label">Reason <span class="text-rose-500">*</span></label>
                                <textarea v-model="rejectForm.reason" rows="2" :class="field" required></textarea>
                            </div>
                            <button type="submit" :disabled="rejectForm.processing"
                                    class="w-full rounded-lg bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-50">
                                Reject and release stock
                            </button>
                        </form>
                    </section>

                    <!-- The four signature blocks, with names and timestamps behind them. -->
                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Approval trail</h2>
                        <ol v-if="request.approvals.length" class="mt-3 space-y-3">
                            <li v-for="a in request.approvals" :key="a.id" class="flex gap-3">
                                <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full"
                                      :class="a.decision === 'approved'
                                          ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400'
                                          : 'bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400'">
                                    <Icon :name="a.decision === 'approved' ? 'Check' : 'X'" :size="13" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-200">
                                        {{ a.stage.replace(/_/g, ' ') }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ a.actor?.name }} · {{ date(a.acted_at) }}
                                    </p>
                                    <p v-if="a.note" class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">{{ a.note }}</p>
                                </div>
                            </li>
                        </ol>
                        <p v-else class="mt-3 text-sm text-slate-500 dark:text-slate-400">Nothing signed yet.</p>
                    </section>

                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Details</h2>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500 dark:text-slate-400">Requester</dt>
                                <dd class="text-right text-slate-800 dark:text-slate-200">{{ request.requester?.name }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500 dark:text-slate-400">Contact</dt>
                                <dd class="text-right text-slate-800 dark:text-slate-200">{{ request.contact_name || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500 dark:text-slate-400">Mobile</dt>
                                <dd class="text-right text-slate-800 dark:text-slate-200">{{ request.contact_phone || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500 dark:text-slate-400">Needed by</dt>
                                <dd class="text-right text-slate-800 dark:text-slate-200">{{ request.needed_by || '—' }}</dd>
                            </div>
                        </dl>
                        <p v-if="request.notes" class="mt-3 border-t border-slate-100 dark:border-slate-800 pt-3 text-sm text-slate-600 dark:text-slate-400">
                            {{ request.notes }}
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
