<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Modal from '@/Components/Library/Modal.vue';
import Pagination from '@/Components/Library/Pagination.vue';

const props = defineProps({
    fines: Object,
    filters: Object,
    totals: Object,
    currency: String,
});

const money = (v) => Number(v ?? 0).toFixed(2);
const search = ref(props.filters.q ?? '');

const statusTabs = ['open', 'paid', 'waived', 'all'];
function setStatus(status) {
    router.get(route('library.fines.index'), { status, q: search.value }, { preserveState: true, replace: true });
}
function doSearch() {
    router.get(route('library.fines.index'), { status: props.filters.status, q: search.value }, { preserveState: true, replace: true });
}

// Collect payment modal
const collecting = ref(null);
const collectForm = useForm({ amount: '', method: 'cash' });
function openCollect(fine) {
    collecting.value = fine;
    collectForm.amount = (Number(fine.amount) - Number(fine.paid_amount)).toFixed(2);
    collectForm.method = 'cash';
}
function submitCollect() {
    collectForm.post(route('library.fines.collect', collecting.value.id), {
        preserveScroll: true,
        onSuccess: () => { collecting.value = null; collectForm.reset(); },
    });
}

// Waive
function waive(fine) {
    if (!confirm('Waive this fine? This cannot be undone.')) return;
    router.post(route('library.fines.waive', fine.id), {}, { preserveScroll: true });
}

// Issue fine modal
const issuing = ref(false);
const issueForm = useForm({ user_id: '', reason: 'lost', amount: '', note: '' });
function submitIssue() {
    issueForm.post(route('library.fines.store'), {
        preserveScroll: true,
        onSuccess: () => { issuing.value = false; issueForm.reset(); },
    });
}

const statusClass = (s) => ({
    open:   'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    paid:   'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
    waived: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
}[s] || 'bg-blue-100 text-blue-800');
</script>

<template>
    <Head title="Fines" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-gray-900 dark:text-white">Fines &amp; Payments</h1>
        </template>

        <div class="mx-auto max-w-6xl p-6 space-y-5">
            <!-- Totals -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Outstanding</p>
                    <p class="mt-1 text-2xl font-bold text-rose-600 dark:text-rose-400">{{ currency }} {{ money(totals.outstanding) }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Collected (all time)</p>
                    <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ currency }} {{ money(totals.collected) }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Open fines</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ totals.open_count }}</p>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex gap-1 rounded-lg bg-gray-100 dark:bg-gray-800 p-1">
                    <button
                        v-for="tab in statusTabs"
                        :key="tab"
                        @click="setStatus(tab)"
                        class="rounded-md px-3 py-1 text-sm font-medium capitalize transition"
                        :class="filters.status === tab
                            ? 'bg-white dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 shadow-sm'
                            : 'text-gray-600 dark:text-gray-400'"
                    >
                        {{ tab }}
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="search"
                        @keyup.enter="doSearch"
                        type="search"
                        placeholder="Search patron…"
                        class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm"
                    />
                    <button @click="issuing = true" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Issue fine
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <th class="px-4 py-3">Patron</th>
                            <th class="px-4 py-3">Reason</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-right">Balance</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-if="!fines.data.length">
                            <td colspan="6" class="px-4 py-12 text-center text-gray-400">No fines found.</td>
                        </tr>
                        <tr v-for="fine in fines.data" :key="fine.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900 dark:text-white">{{ fine.user?.name }}</p>
                                <p class="text-xs text-gray-500">{{ fine.user?.email }}</p>
                            </td>
                            <td class="px-4 py-3 capitalize text-gray-600 dark:text-gray-400">
                                {{ fine.reason }}
                                <span v-if="fine.loan?.copy?.book" class="block text-xs text-gray-400">{{ fine.loan.copy.book.title }}</span>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-200">{{ currency }} {{ money(fine.amount) }}</td>
                            <td class="px-4 py-3 text-right font-medium" :class="fine.balance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400'">
                                {{ currency }} {{ money(fine.balance) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize" :class="statusClass(fine.status)">{{ fine.status }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div v-if="fine.status === 'open'" class="flex justify-end gap-2">
                                    <button @click="openCollect(fine)" class="rounded-md bg-green-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-green-700">Collect</button>
                                    <button @click="waive(fine)" class="rounded-md border border-gray-300 dark:border-gray-700 px-2.5 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">Waive</button>
                                </div>
                                <span v-else class="text-xs text-gray-400">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="fines.links" />
        </div>

        <!-- Collect modal -->
        <Modal :show="!!collecting" @close="collecting = null">
            <div v-if="collecting" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Record payment</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ collecting.user?.name }} — balance {{ currency }} {{ money(Number(collecting.amount) - Number(collecting.paid_amount)) }}
                </p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount ({{ currency }})</label>
                    <input v-model="collectForm.amount" type="number" step="0.01" min="0.01" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm" />
                    <p v-if="collectForm.errors.amount" class="mt-1 text-xs text-red-500">{{ collectForm.errors.amount }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Method</label>
                    <select v-model="collectForm.method" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm">
                        <option value="cash">Cash</option>
                        <option value="telebirr">Telebirr</option>
                        <option value="chapa">Chapa</option>
                        <option value="card">Card</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button @click="collecting = null" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm">Cancel</button>
                    <button @click="submitCollect" :disabled="collectForm.processing" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">Record payment</button>
                </div>
            </div>
        </Modal>

        <!-- Issue fine modal -->
        <Modal :show="issuing" @close="issuing = false">
            <div class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Issue a fine</h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Patron user ID</label>
                    <input v-model="issueForm.user_id" type="number" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm" />
                    <p v-if="issueForm.errors.user_id" class="mt-1 text-xs text-red-500">{{ issueForm.errors.user_id }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason</label>
                        <select v-model="issueForm.reason" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm">
                            <option value="lost">Lost</option>
                            <option value="damaged">Damaged</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount ({{ currency }})</label>
                        <input v-model="issueForm.amount" type="number" step="0.01" min="0.01" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm" />
                        <p v-if="issueForm.errors.amount" class="mt-1 text-xs text-red-500">{{ issueForm.errors.amount }}</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Note (optional)</label>
                    <textarea v-model="issueForm.note" rows="2" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button @click="issuing = false" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm">Cancel</button>
                    <button @click="submitIssue" :disabled="issueForm.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">Issue fine</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
