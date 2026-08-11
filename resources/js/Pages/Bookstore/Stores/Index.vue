<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Icon from '@/Components/Icon.vue';

defineProps({ stores: Array, options: Object });

const showForm = ref(false);
const form = useForm({
    name: '', code: '', campus_id: '', location_note: '', manager_id: '', is_active: true, notes: '',
});

const field = 'mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-xs font-medium text-slate-600 dark:text-slate-400';
</script>

<template>
    <Head title="Store rooms" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-base font-semibold text-slate-900 dark:text-white">Store rooms</h1>
                <button type="button" @click="showForm = !showForm"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                    <Icon name="Plus" :size="15" /> New store room
                </button>
            </div>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-5">

            <form v-if="showForm"
                  @submit.prevent="form.post(route('bookstore.stores.store'), { onSuccess: () => { showForm = false; form.reset(); } })"
                  class="grid gap-4 sm:grid-cols-2 rounded-xl border border-indigo-200 dark:border-indigo-900 bg-white dark:bg-slate-900 p-5">
                <div>
                    <label :class="label">Name <span class="text-rose-500">*</span></label>
                    <input v-model="form.name" type="text" :class="field" required />
                </div>
                <div>
                    <label :class="label">Code <span class="text-rose-500">*</span></label>
                    <input v-model="form.code" type="text" :class="field" required placeholder="MS" />
                    <p v-if="form.errors.code" class="mt-1 text-xs text-rose-500">{{ form.errors.code }}</p>
                </div>
                <div>
                    <label :class="label">Campus</label>
                    <select v-model="form.campus_id" :class="field">
                        <option value="">—</option>
                        <option v-for="c in options.campuses" :key="c.id" :value="c.id">{{ c.name || c.name_en }}</option>
                    </select>
                </div>
                <div>
                    <label :class="label">Store manager</label>
                    <select v-model="form.manager_id" :class="field">
                        <option value="">—</option>
                        <option v-for="u in options.managers" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label :class="label">Where it is</label>
                    <input v-model="form.location_note" type="text" :class="field" placeholder="Ground floor, behind the registry" />
                </div>
                <div class="sm:col-span-2 flex justify-end gap-2">
                    <button type="button" @click="showForm = false"
                            class="rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-sm">Cancel</button>
                    <button type="submit" :disabled="form.processing"
                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                        Create
                    </button>
                </div>
            </form>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link v-for="store in stores" :key="store.id" :href="route('bookstore.stores.show', store.id)"
                      class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 hover:border-indigo-400 dark:hover:border-indigo-600 transition">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-900 dark:text-white">{{ store.name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ store.code }}</p>
                        </div>
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-300">
                            <Icon name="Warehouse" :size="18" />
                        </span>
                    </div>
                    <dl class="mt-4 flex items-center gap-6 text-sm">
                        <div>
                            <dt class="text-xs text-slate-500 dark:text-slate-400">Shelves</dt>
                            <dd class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ store.shelves_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500 dark:text-slate-400">Books</dt>
                            <dd class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ store.total_on_hand }}</dd>
                        </div>
                    </dl>
                    <p v-if="store.manager" class="mt-3 text-xs text-slate-400">Managed by {{ store.manager.name }}</p>
                </Link>

                <p v-if="!stores.length" class="sm:col-span-2 lg:col-span-3 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                    No store rooms yet. Create one, then add shelves and sections — each gets a QR label.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
