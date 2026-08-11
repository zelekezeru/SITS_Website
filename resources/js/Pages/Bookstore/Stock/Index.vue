<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Pagination from '@/Components/Library/Pagination.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({ stocks: Object, filters: Object, options: Object });

const filterForm = ref({
    search: props.filters.search ?? '',
    store_room_id: props.filters.store_room_id ?? '',
    shelf_section_id: props.filters.shelf_section_id ?? '',
    stock: props.filters.stock ?? '',
});

let debounce;
watch(filterForm, (value) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('bookstore.stock.index'), value, { preserveState: true, replace: true });
    }, 300);
}, { deep: true });

const panel = ref(null);

const transferForm = useForm({ book_title_id: '', from_section: '', to_section: '', quantity: 1, note: '' });
const adjustForm = useForm({ book_title_id: '', shelf_section_id: '', type: 'adjustment_decrease', quantity: 1, reason: '' });

function openTransfer(stock) {
    transferForm.book_title_id = stock.book_title_id;
    transferForm.from_section = stock.shelf_section_id;
    panel.value = 'transfer';
}

function openAdjust(stock) {
    adjustForm.book_title_id = stock.book_title_id;
    adjustForm.shelf_section_id = stock.shelf_section_id;
    panel.value = 'adjust';
}

const field = 'mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-xs font-medium text-slate-600 dark:text-slate-400';
</script>

<template>
    <Head title="Stock" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-base font-semibold text-slate-900 dark:text-white">Stock on hand</h1>
                <div class="flex items-center gap-2">
                    <Link :href="route('bookstore.stock.low')"
                          class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        <Icon name="AlertTriangle" :size="15" /> Low stock
                    </Link>
                    <button type="button" @click="panel = panel === 'transfer' ? null : 'transfer'"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                        <Icon name="ArrowLeftRight" :size="15" /> Transfer
                    </button>
                </div>
            </div>
        </template>

        <div class="p-6 max-w-7xl mx-auto space-y-5">

            <!-- Transfer -->
            <form v-if="panel === 'transfer'"
                  @submit.prevent="transferForm.post(route('bookstore.stock.transfer'), { preserveScroll: true, onSuccess: () => { panel = null; transferForm.reset(); } })"
                  class="grid gap-4 sm:grid-cols-5 rounded-xl border border-indigo-200 dark:border-indigo-900 bg-white dark:bg-slate-900 p-5">
                <div class="sm:col-span-2">
                    <label :class="label">Book</label>
                    <select v-model="transferForm.book_title_id" :class="field" required>
                        <option value="">Choose…</option>
                        <option v-for="s in stocks.data" :key="s.id" :value="s.book_title_id">
                            {{ s.book_title?.code }} — {{ s.book_title?.title }}
                        </option>
                    </select>
                </div>
                <div>
                    <label :class="label">From</label>
                    <select v-model="transferForm.from_section" :class="field" required>
                        <option value="">Choose…</option>
                        <option v-for="s in options.sections" :key="s.id" :value="s.id">{{ s.label }}</option>
                    </select>
                </div>
                <div>
                    <label :class="label">To</label>
                    <select v-model="transferForm.to_section" :class="field" required>
                        <option value="">Choose…</option>
                        <option v-for="s in options.sections" :key="s.id" :value="s.id">{{ s.label }}</option>
                    </select>
                </div>
                <div>
                    <label :class="label">Quantity</label>
                    <input v-model="transferForm.quantity" type="number" min="1" :class="field" required />
                </div>
                <div class="sm:col-span-5 flex justify-end gap-2">
                    <button type="button" @click="panel = null" class="rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-sm">Cancel</button>
                    <button type="submit" :disabled="transferForm.processing"
                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">Transfer</button>
                </div>
            </form>

            <!-- Adjustment -->
            <form v-if="panel === 'adjust'"
                  @submit.prevent="adjustForm.post(route('bookstore.stock.adjust'), { preserveScroll: true, onSuccess: () => { panel = null; adjustForm.reset(); } })"
                  class="grid gap-4 sm:grid-cols-5 rounded-xl border border-amber-200 dark:border-amber-900 bg-white dark:bg-slate-900 p-5">
                <div class="sm:col-span-5">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Only corrections are posted by hand — receipts, issues and returns come from print runs, waybills and return notes so the ledger always traces back to a document.
                    </p>
                </div>
                <div>
                    <label :class="label">Type</label>
                    <select v-model="adjustForm.type" :class="field">
                        <option value="adjustment_increase">Adjustment (+)</option>
                        <option value="adjustment_decrease">Adjustment (−)</option>
                        <option value="damage">Damaged</option>
                        <option value="loss">Lost</option>
                    </select>
                </div>
                <div>
                    <label :class="label">Quantity</label>
                    <input v-model="adjustForm.quantity" type="number" min="1" :class="field" required />
                </div>
                <div class="sm:col-span-3">
                    <label :class="label">Reason <span class="text-rose-500">*</span></label>
                    <input v-model="adjustForm.reason" type="text" :class="field" required />
                </div>
                <div class="sm:col-span-5 flex justify-end gap-2">
                    <button type="button" @click="panel = null" class="rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-sm">Cancel</button>
                    <button type="submit" :disabled="adjustForm.processing"
                            class="rounded-lg bg-amber-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50">Post adjustment</button>
                </div>
            </form>

            <div class="grid gap-3 sm:grid-cols-4">
                <input v-model="filterForm.search" type="search" placeholder="Book title or code…"
                       class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <select v-model="filterForm.store_room_id" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All store rooms</option>
                    <option v-for="s in options.stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <select v-model="filterForm.shelf_section_id" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All sections</option>
                    <option v-for="s in options.sections" :key="s.id" :value="s.id">{{ s.label }}</option>
                </select>
                <select v-model="filterForm.stock" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Every row</option>
                    <option value="with">Only where stock is held</option>
                </select>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Book</th>
                                <th class="px-4 py-2.5 text-left font-medium">Location</th>
                                <th class="px-4 py-2.5 text-right font-medium">On hand</th>
                                <th class="px-4 py-2.5 text-right font-medium">Reserved</th>
                                <th class="px-4 py-2.5 text-right font-medium">Available</th>
                                <th class="px-4 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="stock in stocks.data" :key="stock.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-2.5">
                                    <Link :href="route('bookstore.titles.show', stock.book_title_id)"
                                          class="text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ stock.book_title?.title }}
                                    </Link>
                                    <p class="text-xs text-slate-400">{{ stock.book_title?.code }}</p>
                                </td>
                                <td class="px-4 py-2.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ stock.shelf_section?.shelf?.store_room?.name }} ›
                                    {{ stock.shelf_section?.shelf?.label || stock.shelf_section?.shelf?.code }} ›
                                    {{ stock.shelf_section?.code }}
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums font-medium text-slate-900 dark:text-white">{{ stock.quantity }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400">{{ stock.reserved_quantity }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ stock.available }}</td>
                                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                    <button type="button" @click="openTransfer(stock)"
                                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-slate-800 transition" title="Transfer">
                                        <Icon name="ArrowLeftRight" :size="15" />
                                    </button>
                                    <button type="button" @click="openAdjust(stock)"
                                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-amber-600 dark:hover:bg-slate-800 transition" title="Adjust">
                                        <Icon name="SlidersHorizontal" :size="15" />
                                    </button>
                                    <Link :href="route('bookstore.stock.bin-card', { title: stock.book_title_id, shelf_section_id: stock.shelf_section_id })"
                                          class="inline-block rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 transition" title="Bin card">
                                        <Icon name="ScrollText" :size="15" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!stocks.data.length">
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">No stock matches these filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :links="stocks.links" />
        </div>
    </AuthenticatedLayout>
</template>
