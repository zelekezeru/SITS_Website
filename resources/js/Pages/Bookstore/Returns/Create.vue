<script setup>
import { reactive, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({ options: Object, dispatch: Object });

// Arriving from a waybill pre-fills the lines with what went out, so the clerk
// only types what actually came back.
const lines = reactive(
    props.dispatch
        ? props.dispatch.items.map((i) => ({
            book_title_id: i.book_title_id,
            quantity_returned: 0,
            quantity_damaged: 0,
            remark: '',
        }))
        : [{ book_title_id: '', quantity_returned: 1, quantity_damaged: 0, remark: '' }]
);

const form = useForm({
    book_dispatch_id: props.dispatch?.id ?? null,
    center_id: props.dispatch?.book_request?.center_id ?? '',
    campus_id: '',
    shelf_section_id: '',
    returned_on: new Date().toISOString().slice(0, 10),
    returned_by_name: '',
    condition_note: '',
    lines: [],
});

const total = computed(() => lines.reduce((sum, l) => sum + (Number(l.quantity_returned) || 0), 0));
const invalid = computed(() => lines.some((l) => Number(l.quantity_damaged) > Number(l.quantity_returned)));

function addLine() {
    lines.push({ book_title_id: '', quantity_returned: 1, quantity_damaged: 0, remark: '' });
}

function submit() {
    form.lines = lines.filter((l) => Number(l.quantity_returned) > 0);
    form.post(route('bookstore.returns.store'));
}

const field = 'mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-xs font-medium text-slate-600 dark:text-slate-400';
</script>

<template>
    <Head title="Record a return" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-slate-900 dark:text-white">Record a return</h1>
        </template>

        <form @submit.prevent="submit" class="p-6 max-w-4xl mx-auto space-y-6">

            <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Where the books came back from</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label :class="label">Centre</label>
                        <select v-model="form.center_id" :class="field">
                            <option value="">—</option>
                            <option v-for="c in options.centers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <p v-if="form.errors.center_id" class="mt-1 text-xs text-rose-500">{{ form.errors.center_id }}</p>
                    </div>
                    <div>
                        <label :class="label">…or campus</label>
                        <select v-model="form.campus_id" :class="field">
                            <option value="">—</option>
                            <option v-for="c in options.campuses" :key="c.id" :value="c.id">{{ c.name || c.name_en }}</option>
                        </select>
                    </div>
                    <div>
                        <label :class="label">Re-shelve into <span class="text-rose-500">*</span></label>
                        <select v-model="form.shelf_section_id" :class="field" required>
                            <option value="">Choose a section…</option>
                            <option v-for="s in options.sections" :key="s.id" :value="s.id">{{ s.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label :class="label">Returned on <span class="text-rose-500">*</span></label>
                        <input v-model="form.returned_on" type="date" :class="field" required />
                    </div>
                    <div>
                        <label :class="label">Brought back by</label>
                        <input v-model="form.returned_by_name" type="text" :class="field" />
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">What came back</h2>
                    <button type="button" @click="addLine"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-2.5 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        <Icon name="Plus" :size="14" /> Add book
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <div v-for="(line, index) in lines" :key="index"
                         class="grid gap-3 sm:grid-cols-12 rounded-lg border border-slate-200 dark:border-slate-800 p-3">
                        <div class="sm:col-span-5">
                            <label :class="label">Book</label>
                            <select v-model="line.book_title_id" :class="field" required>
                                <option value="">Choose…</option>
                                <option v-for="t in options.titles" :key="t.id" :value="t.id">{{ t.code }} — {{ t.title }}</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label :class="label">Returned</label>
                            <input v-model="line.quantity_returned" type="number" min="0" :class="field" />
                        </div>
                        <div class="sm:col-span-2">
                            <label :class="label">Damaged</label>
                            <input v-model="line.quantity_damaged" type="number" min="0" :class="field" />
                        </div>
                        <div class="sm:col-span-3">
                            <label :class="label">Remark</label>
                            <input v-model="line.remark" type="text" :class="field" />
                        </div>
                        <p v-if="Number(line.quantity_damaged) > Number(line.quantity_returned)"
                           class="sm:col-span-12 text-xs text-rose-500">
                            Damaged cannot exceed the quantity returned.
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-slate-200 dark:border-slate-800 pt-4">
                    <div class="flex-1 pr-6">
                        <label :class="label">Condition note</label>
                        <input v-model="form.condition_note" type="text" :class="field" />
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Total returned</p>
                        <p class="text-lg font-semibold tabular-nums text-slate-900 dark:text-white">{{ total }}</p>
                    </div>
                </div>
            </section>

            <div class="flex items-center justify-end gap-3">
                <Link :href="route('bookstore.returns.index')"
                      class="rounded-lg border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancel
                </Link>
                <button type="submit" :disabled="form.processing || invalid || total === 0"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition">
                    Record return
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
