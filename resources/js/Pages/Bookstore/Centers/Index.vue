<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Pagination from '@/Components/Library/Pagination.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({ centers: Object, filters: Object, options: Object });

const search = ref(props.filters.search ?? '');
let debounce;
watch(search, (value) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('bookstore.centers.index'), { search: value }, { preserveState: true, replace: true });
    }, 300);
});

const showForm = ref(false);
const form = useForm({
    name: '', code: '', city: '', region: '',
    coordinator_name: '', coordinator_phone: '', coordinator_user_id: '',
    student_count: 0, campus_id: '', is_active: true, notes: '',
});

const field = 'mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-xs font-medium text-slate-600 dark:text-slate-400';
</script>

<template>
    <Head title="Distribution centres" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-base font-semibold text-slate-900 dark:text-white">Distribution centres</h1>
                <button type="button" @click="showForm = !showForm"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                    <Icon name="Plus" :size="15" /> New centre
                </button>
            </div>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-5">

            <form v-if="showForm"
                  @submit.prevent="form.post(route('bookstore.centers.store'), { onSuccess: () => { showForm = false; form.reset(); } })"
                  class="grid gap-4 sm:grid-cols-3 rounded-xl border border-indigo-200 dark:border-indigo-900 bg-white dark:bg-slate-900 p-5">
                <div>
                    <label :class="label">Name <span class="text-rose-500">*</span></label>
                    <input v-model="form.name" type="text" :class="field" required />
                </div>
                <div>
                    <label :class="label">Code <span class="text-rose-500">*</span></label>
                    <input v-model="form.code" type="text" :class="field" required />
                    <p v-if="form.errors.code" class="mt-1 text-xs text-rose-500">{{ form.errors.code }}</p>
                </div>
                <div>
                    <label :class="label">Verified students</label>
                    <input v-model="form.student_count" type="number" min="0" :class="field" />
                </div>
                <div>
                    <label :class="label">Coordinator</label>
                    <input v-model="form.coordinator_name" type="text" :class="field" />
                </div>
                <div>
                    <label :class="label">Mobile</label>
                    <input v-model="form.coordinator_phone" type="text" :class="field" />
                </div>
                <div>
                    <label :class="label">City</label>
                    <input v-model="form.city" type="text" :class="field" />
                </div>
                <div class="sm:col-span-3 flex justify-end gap-2">
                    <button type="button" @click="showForm = false" class="rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-sm">Cancel</button>
                    <button type="submit" :disabled="form.processing"
                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">Create</button>
                </div>
            </form>

            <input v-model="search" type="search" placeholder="Centre name, code or coordinator…"
                   class="w-full sm:max-w-sm rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500" />

            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Centre</th>
                                <th class="px-4 py-2.5 text-left font-medium">Coordinator</th>
                                <th class="px-4 py-2.5 text-left font-medium">Mobile</th>
                                <th class="px-4 py-2.5 text-right font-medium">Students</th>
                                <th class="px-4 py-2.5 text-right font-medium">Requests</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="c in centers.data" :key="c.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-2.5">
                                    <Link :href="route('bookstore.centers.show', c.id)"
                                          class="font-medium text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ c.name }}
                                    </Link>
                                    <p class="text-xs text-slate-400">{{ c.code }}<span v-if="c.city"> · {{ c.city }}</span></p>
                                </td>
                                <td class="px-4 py-2.5 text-slate-600 dark:text-slate-400">{{ c.coordinator_name || '—' }}</td>
                                <td class="px-4 py-2.5 text-slate-600 dark:text-slate-400">{{ c.coordinator_phone || '—' }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ c.student_count }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400">{{ c.book_requests_count }}</td>
                            </tr>
                            <tr v-if="!centers.data.length">
                                <td colspan="5" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">No centres yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :links="centers.links" />
        </div>
    </AuthenticatedLayout>
</template>
