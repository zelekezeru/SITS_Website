<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';

const props = defineProps({ options: Object });

const form = useForm({
    book_title_id: '',
    batch_number: '',
    quantity: 1,
    unit_cost: 0,
    printer_name: '',
    invoice_number: '',
    crv_number: '',
    printed_on: '',
    received_on: new Date().toISOString().slice(0, 10),
    shelf_section_id: '',
    notes: '',
});

const totalCost = computed(() => (Number(form.quantity) || 0) * (Number(form.unit_cost) || 0));

const field = 'mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-xs font-medium text-slate-600 dark:text-slate-400';
</script>

<template>
    <Head title="Receive a print run" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-slate-900 dark:text-white">Receive a print run</h1>
        </template>

        <form @submit.prevent="form.post(route('bookstore.print-runs.store'))" class="p-6 max-w-3xl mx-auto space-y-6">

            <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">What arrived</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label :class="label">Book <span class="text-rose-500">*</span></label>
                        <select v-model="form.book_title_id" :class="field" required>
                            <option value="">Choose a title…</option>
                            <option v-for="t in options.titles" :key="t.id" :value="t.id">{{ t.code }} — {{ t.title }}</option>
                        </select>
                    </div>

                    <div>
                        <label :class="label">Quantity <span class="text-rose-500">*</span></label>
                        <input v-model="form.quantity" type="number" min="1" :class="field" required />
                    </div>

                    <div>
                        <label :class="label">Unit print cost <span class="text-rose-500">*</span></label>
                        <input v-model="form.unit_cost" type="number" step="0.01" min="0" :class="field" required />
                        <p class="mt-1 text-xs text-slate-400">Total {{ totalCost.toFixed(2) }} — rolled into the title's average cost.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label :class="label">Rack into <span class="text-rose-500">*</span></label>
                        <select v-model="form.shelf_section_id" :class="field" required>
                            <option value="">Choose a shelf section…</option>
                            <option v-for="s in options.sections" :key="s.id" :value="s.id">{{ s.label }}</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Paperwork</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label :class="label">Batch number</label>
                        <input v-model="form.batch_number" type="text" :class="field" placeholder="Auto-generated" />
                        <p v-if="form.errors.batch_number" class="mt-1 text-xs text-rose-500">{{ form.errors.batch_number }}</p>
                    </div>
                    <div>
                        <label :class="label">Printer</label>
                        <input v-model="form.printer_name" type="text" :class="field" />
                    </div>
                    <div>
                        <label :class="label">Invoice number</label>
                        <input v-model="form.invoice_number" type="text" :class="field" />
                    </div>
                    <div>
                        <label :class="label">CRV number</label>
                        <input v-model="form.crv_number" type="text" :class="field" placeholder="Manual receipt book" />
                    </div>
                    <div>
                        <label :class="label">Printed on</label>
                        <input v-model="form.printed_on" type="date" :class="field" />
                    </div>
                    <div>
                        <label :class="label">Received on <span class="text-rose-500">*</span></label>
                        <input v-model="form.received_on" type="date" :class="field" required />
                    </div>
                    <div class="sm:col-span-2">
                        <label :class="label">Notes</label>
                        <textarea v-model="form.notes" rows="2" :class="field"></textarea>
                    </div>
                </div>
            </section>

            <div class="flex items-center justify-end gap-3">
                <Link :href="route('bookstore.print-runs.index')"
                      class="rounded-lg border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancel
                </Link>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition">
                    Receive into store
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
