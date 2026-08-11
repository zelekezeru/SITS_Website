<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({ request: Object, picking: Object });

/**
 * Picking sheet. One row per outstanding line, defaulting to the whole balance
 * off the richest section — the store keeper only has to change what differs
 * from the obvious pick.
 */
const lines = reactive(props.request.items.map((item) => {
    const options = props.picking[item.id] ?? [];
    const outstanding = Math.max(0, item.quantity_approved - item.quantity_dispatched);
    const best = options[0];

    return {
        book_request_item_id: item.id,
        title: item.book_title?.title,
        code: item.book_title?.code,
        outstanding,
        options,
        shelf_section_id: best?.shelf_section_id ?? '',
        quantity: best ? Math.min(outstanding, best.quantity) : 0,
    };
}));

const form = useForm({
    lines: [],
    received_by_name: props.request.contact_name ?? '',
    received_by_phone: props.request.contact_phone ?? '',
    notes: '',
});

const totalPicked = computed(() => lines.reduce((sum, l) => sum + (Number(l.quantity) || 0), 0));

const sectionFor = (line) => line.options.find((o) => o.shelf_section_id === Number(line.shelf_section_id));

const overPick = (line) => {
    const section = sectionFor(line);
    return Number(line.quantity) > line.outstanding
        || (section && Number(line.quantity) > section.quantity);
};

const hasProblem = computed(() => lines.some(overPick) || totalPicked.value === 0);

function submit() {
    form.lines = lines
        .filter((l) => Number(l.quantity) > 0)
        .map((l) => ({
            book_request_item_id: l.book_request_item_id,
            shelf_section_id: l.shelf_section_id,
            quantity: Number(l.quantity),
        }));

    form.post(route('bookstore.dispatches.store', props.request.id));
}

const field = 'mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-xs font-medium text-slate-600 dark:text-slate-400';
</script>

<template>
    <Head :title="`Dispatch ${request.request_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="min-w-0">
                <h1 class="truncate text-base font-semibold text-slate-900 dark:text-white">
                    Dispatch {{ request.request_number }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    To {{ request.center?.name || request.campus?.name || request.campus?.name_en }}
                </p>
            </div>
        </template>

        <form @submit.prevent="submit" class="p-6 max-w-5xl mx-auto space-y-6">

            <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Pick from the shelves</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    Dispatch what is physically there — a short pick leaves the request open for the balance.
                </p>

                <div class="mt-4 space-y-3">
                    <div v-for="line in lines" :key="line.book_request_item_id"
                         class="grid gap-3 sm:grid-cols-12 items-start rounded-lg border border-slate-200 dark:border-slate-800 p-3">
                        <div class="sm:col-span-4">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ line.title }}</p>
                            <p class="text-xs text-slate-400">{{ line.code }} · {{ line.outstanding }} outstanding</p>
                        </div>

                        <div class="sm:col-span-5">
                            <label :class="label">Pick from</label>
                            <select v-model="line.shelf_section_id" :class="field">
                                <option value="">Choose a section…</option>
                                <option v-for="o in line.options" :key="o.shelf_section_id" :value="o.shelf_section_id">
                                    {{ o.label }} — {{ o.quantity }} on hand
                                </option>
                            </select>
                            <p v-if="!line.options.length" class="mt-1 text-xs text-rose-500">
                                Nothing on any shelf for this title.
                            </p>
                        </div>

                        <div class="sm:col-span-3">
                            <label :class="label">Quantity</label>
                            <input v-model="line.quantity" type="number" min="0" :max="line.outstanding" :class="field" />
                            <p v-if="overPick(line)" class="mt-1 text-xs text-rose-500">
                                More than the outstanding balance or the section holds.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-end border-t border-slate-200 dark:border-slate-800 pt-4">
                    <div class="text-right">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Books on this consignment</p>
                        <p class="text-lg font-semibold tabular-nums text-slate-900 dark:text-white">{{ totalPicked }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Who is collecting</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label :class="label">Receiver</label>
                        <input v-model="form.received_by_name" type="text" :class="field" />
                    </div>
                    <div>
                        <label :class="label">Mobile</label>
                        <input v-model="form.received_by_phone" type="text" :class="field" />
                    </div>
                    <div class="sm:col-span-2">
                        <label :class="label">Notes on the waybill</label>
                        <textarea v-model="form.notes" rows="2" :class="field"></textarea>
                    </div>
                </div>
            </section>

            <div class="flex items-center justify-between gap-3">
                <p class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <Icon name="AlertTriangle" :size="14" />
                    This deducts stock immediately and prints a waybill for signature.
                </p>
                <div class="flex items-center gap-3">
                    <Link :href="route('bookstore.requests.show', request.id)"
                          class="rounded-lg border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing || hasProblem"
                            class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50 transition">
                        Dispatch &amp; create waybill
                    </button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
