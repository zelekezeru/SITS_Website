<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    report: String,
    reports: Object,
    filters: Object,
    rows: Array,
    options: Object,
});

const form = ref({
    report: props.report,
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    store_room_id: props.filters.store_room_id ?? '',
    program_id: props.filters.program_id ?? '',
    study_mode_id: props.filters.study_mode_id ?? '',
});

watch(form, (value) => {
    router.get(route('bookstore.reports.index'), value, { preserveState: true, replace: true });
}, { deep: true });

// Column headings come from the first row, so a new report needs no UI change.
const columns = computed(() => (props.rows.length ? Object.keys(props.rows[0]) : []));

const heading = (key) => key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

const isNumeric = (value) => typeof value === 'number'
    || (typeof value === 'string' && value !== '' && !Number.isNaN(Number(value)));

const exportUrl = computed(() => route('bookstore.reports.export', form.value));
</script>

<template>
    <Head title="Bookstore reports" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-base font-semibold text-slate-900 dark:text-white">Bookstore reports</h1>
                <a :href="exportUrl"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                    <Icon name="Download" :size="15" /> Export CSV
                </a>
            </div>
        </template>

        <div class="p-6 max-w-7xl mx-auto space-y-5">

            <div class="flex flex-wrap gap-2">
                <button v-for="(label, key) in reports" :key="key" type="button" @click="form.report = key"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                        :class="form.report === key
                            ? 'bg-indigo-600 text-white'
                            : 'border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'">
                    {{ label }}
                </button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <input v-model="form.from" type="date" placeholder="From"
                       class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <input v-model="form.to" type="date" placeholder="To"
                       class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <select v-model="form.store_room_id" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All store rooms</option>
                    <option v-for="s in options.stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <select v-model="form.program_id" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All programmes</option>
                    <option v-for="p in options.programs" :key="p.id" :value="p.id">{{ p.title }}</option>
                </select>
                <select v-model="form.study_mode_id" class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All study modes</option>
                    <option v-for="m in options.studyModes" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th v-for="col in columns" :key="col" class="px-4 py-2.5 whitespace-nowrap font-medium"
                                    :class="isNumeric(rows[0][col]) ? 'text-right' : 'text-left'">
                                    {{ heading(col) }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="(row, index) in rows" :key="index" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td v-for="col in columns" :key="col" class="px-4 py-2 text-slate-700 dark:text-slate-300"
                                    :class="isNumeric(row[col]) ? 'text-right tabular-nums' : 'text-left'">
                                    {{ row[col] }}
                                </td>
                            </tr>
                            <tr v-if="!rows.length">
                                <td class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
                                    Nothing to report for these filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ rows.length }} row(s). The export contains exactly what is on screen — same query, same numbers.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
