<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import StatCard from '@/Components/Bookstore/StatCard.vue';
import QrPanel from '@/Components/Bookstore/QrPanel.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({ store: Object, options: Object, totals: Object });

const shelfForm = useForm({ code: '', label: '', capacity: null, sort_order: 0 });
const sectionForms = ref({});
const openShelf = ref(null);

function sectionForm(shelfId) {
    if (!sectionForms.value[shelfId]) {
        sectionForms.value[shelfId] = { code: '', name: '', capacity: null };
    }
    return sectionForms.value[shelfId];
}

function addSection(shelfId) {
    router.post(route('bookstore.sections.store', shelfId), sectionForm(shelfId), {
        preserveScroll: true,
        onSuccess: () => { sectionForms.value[shelfId] = { code: '', name: '', capacity: null }; },
    });
}

const sectionTotal = (section) => (section.stocks ?? []).reduce((sum, s) => sum + s.quantity, 0);

const field = 'mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-xs font-medium text-slate-600 dark:text-slate-400';
</script>

<template>
    <Head :title="store.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="truncate text-base font-semibold text-slate-900 dark:text-white">{{ store.name }}</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ store.code }}<span v-if="store.location_note"> · {{ store.location_note }}</span>
                    </p>
                </div>
                <a :href="route('bookstore.labels.sheet', { type: 'section', all: 1 })"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    <Icon name="Printer" :size="15" /> All section labels
                </a>
            </div>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-6">

            <div class="grid gap-4 sm:grid-cols-3">
                <StatCard label="Books on hand" :value="totals.on_hand" icon="Boxes" tone="indigo" />
                <StatCard label="Shelf sections" :value="totals.sections" icon="Layers" tone="slate" />
                <StatCard label="Store manager" :value="store.manager?.name ?? '—'" icon="User" tone="slate" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-4">

                    <div v-for="shelf in store.shelves" :key="shelf.id"
                         class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                        <div class="flex items-center justify-between gap-3 p-4">
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900 dark:text-white">{{ shelf.label || shelf.code }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ shelf.code }} · {{ shelf.sections.length }} section(s)
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a :href="route('bookstore.labels.png', { type: 'shelf', id: shelf.id })" target="_blank"
                                   class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 transition"
                                   title="Shelf QR"><Icon name="QrCode" :size="16" /></a>
                                <button type="button" @click="openShelf = openShelf === shelf.id ? null : shelf.id"
                                        class="rounded-lg border border-slate-300 dark:border-slate-700 px-2.5 py-1 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                    Add section
                                </button>
                            </div>
                        </div>

                        <form v-if="openShelf === shelf.id" @submit.prevent="addSection(shelf.id)"
                              class="grid gap-3 sm:grid-cols-4 border-t border-slate-100 dark:border-slate-800 p-4">
                            <div>
                                <label :class="label">Code <span class="text-rose-500">*</span></label>
                                <input v-model="sectionForm(shelf.id).code" type="text" :class="field" required placeholder="SM-02" />
                            </div>
                            <div class="sm:col-span-2">
                                <label :class="label">Name</label>
                                <input v-model="sectionForm(shelf.id).name" type="text" :class="field" placeholder="Sine-Mahiberesb" />
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                    Add
                                </button>
                            </div>
                        </form>

                        <ul v-if="shelf.sections.length" class="divide-y divide-slate-100 dark:divide-slate-800 border-t border-slate-100 dark:border-slate-800">
                            <li v-for="section in shelf.sections" :key="section.id">
                                <Link :href="route('bookstore.sections.show', section.id)"
                                      class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200">
                                            {{ section.code }}<span v-if="section.name" class="font-normal text-slate-500 dark:text-slate-400"> — {{ section.name }}</span>
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            {{ (section.stocks ?? []).length }} title(s)
                                        </p>
                                    </div>
                                    <span class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">{{ sectionTotal(section) }}</span>
                                </Link>
                            </li>
                        </ul>
                        <p v-else class="border-t border-slate-100 dark:border-slate-800 px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                            No sections on this shelf yet.
                        </p>
                    </div>

                    <p v-if="!store.shelves.length" class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                        No shelves yet. Add the first one on the right.
                    </p>
                </div>

                <div class="space-y-6">
                    <QrPanel type="store" :id="store.id" :name="store.name" :sub="store.code" :size="180" />

                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Add a shelf</h2>
                        <form @submit.prevent="shelfForm.post(route('bookstore.shelves.store', store.id), { preserveScroll: true, onSuccess: () => shelfForm.reset() })"
                              class="mt-3 space-y-3">
                            <div>
                                <label :class="label">Code <span class="text-rose-500">*</span></label>
                                <input v-model="shelfForm.code" type="text" :class="field" required placeholder="A" />
                                <p v-if="shelfForm.errors.code" class="mt-1 text-xs text-rose-500">{{ shelfForm.errors.code }}</p>
                            </div>
                            <div>
                                <label :class="label">Label</label>
                                <input v-model="shelfForm.label" type="text" :class="field" placeholder="Shelf A" />
                            </div>
                            <div>
                                <label :class="label">Capacity</label>
                                <input v-model="shelfForm.capacity" type="number" min="1" :class="field" />
                            </div>
                            <button type="submit" :disabled="shelfForm.processing"
                                    class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                                Add shelf
                            </button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
