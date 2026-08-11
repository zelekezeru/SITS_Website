<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Pagination from '@/Components/Library/Pagination.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    titles: Object,
    filters: Object,
    options: Object,
});

const form = ref({
    search: props.filters.search ?? '',
    program_id: props.filters.program_id ?? '',
    study_mode_id: props.filters.study_mode_id ?? '',
    language: props.filters.language ?? '',
    stock: props.filters.stock ?? '',
});

let debounce;
watch(form, (value) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('bookstore.titles.index'), value, { preserveState: true, replace: true });
    }, 300);
}, { deep: true });

const onHand = (title) => (title.stocks ?? []).reduce((sum, s) => sum + s.quantity, 0);
const reserved = (title) => (title.stocks ?? []).reduce((sum, s) => sum + s.reserved_quantity, 0);
</script>

<template>
    <Head title="Book titles" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-base font-semibold text-slate-900 dark:text-white">Book titles</h1>
                <Link :href="route('bookstore.titles.create')"
                      class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                    <Icon name="Plus" :size="15" /> New title
                </Link>
            </div>
        </template>

        <div class="p-6 max-w-7xl mx-auto space-y-5">

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <input v-model="form.search" type="search" placeholder="Title, code, author, course…"
                       class="lg:col-span-2 rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <select v-model="form.program_id" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All programmes</option>
                    <option v-for="p in options.programs" :key="p.id" :value="p.id">{{ p.title }}</option>
                </select>
                <select v-model="form.study_mode_id" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All study modes</option>
                    <option v-for="m in options.studyModes" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
                <select v-model="form.stock" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Any stock level</option>
                    <option value="low">At or below reorder level</option>
                </select>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Code</th>
                                <th class="px-4 py-2.5 text-left font-medium">Title</th>
                                <th class="px-4 py-2.5 text-left font-medium">Category</th>
                                <th class="px-4 py-2.5 text-right font-medium">On hand</th>
                                <th class="px-4 py-2.5 text-right font-medium">Available</th>
                                <th class="px-4 py-2.5 text-right font-medium">Price</th>
                                <th class="px-4 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="title in titles.data" :key="title.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-2.5 font-mono text-xs text-slate-500 dark:text-slate-400">{{ title.code }}</td>
                                <td class="px-4 py-2.5">
                                    <Link :href="route('bookstore.titles.show', title.id)"
                                          class="font-medium text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ title.title }}
                                    </Link>
                                    <p v-if="title.author" class="text-xs text-slate-400">{{ title.author }}</p>
                                </td>
                                <td class="px-4 py-2.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ [title.program?.title, title.study_mode?.name].filter(Boolean).join(' · ') || '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-slate-800 dark:text-slate-200">{{ onHand(title) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400">
                                    {{ Math.max(0, onHand(title) - reserved(title)) }}
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400">
                                    {{ Number(title.unit_price).toFixed(2) }}
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <StatusBadge v-if="onHand(title) === 0" label="Out of stock" color="red" />
                                    <StatusBadge v-else-if="onHand(title) <= title.reorder_level" label="Low" color="amber" />
                                </td>
                            </tr>
                            <tr v-if="!titles.data.length">
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                    No titles match these filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :links="titles.links" />
        </div>
    </AuthenticatedLayout>
</template>
