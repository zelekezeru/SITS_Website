<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';

defineProps({ studyModes: Array });

const showForm = ref(false);
const form = useForm({ name: '', code: '', description: '', is_active: true, sort_order: 0 });

const field = 'mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-xs font-medium text-slate-600 dark:text-slate-400';

function remove(mode) {
    if (confirm(`Remove the "${mode.name}" study mode?`)) {
        router.delete(route('bookstore.study-modes.destroy', mode.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Study modes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-base font-semibold text-slate-900 dark:text-white">Study modes</h1>
                <button type="button" @click="showForm = !showForm"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                    <Icon name="Plus" :size="15" /> New study mode
                </button>
            </div>
        </template>

        <div class="p-6 max-w-3xl mx-auto space-y-5">

            <p class="text-sm text-slate-500 dark:text-slate-400">
                One of the three axes every book is categorised on, alongside programme of study and language. Add a new
                mode here whenever the seminary starts one — no deploy needed.
            </p>

            <form v-if="showForm"
                  @submit.prevent="form.post(route('bookstore.study-modes.store'), { onSuccess: () => { showForm = false; form.reset(); } })"
                  class="grid gap-4 sm:grid-cols-3 rounded-xl border border-indigo-200 dark:border-indigo-900 bg-white dark:bg-slate-900 p-5">
                <div>
                    <label :class="label">Name <span class="text-rose-500">*</span></label>
                    <input v-model="form.name" type="text" :class="field" required />
                </div>
                <div>
                    <label :class="label">Code <span class="text-rose-500">*</span></label>
                    <input v-model="form.code" type="text" :class="field" required placeholder="DST" />
                    <p v-if="form.errors.code" class="mt-1 text-xs text-rose-500">{{ form.errors.code }}</p>
                </div>
                <div>
                    <label :class="label">Sort order</label>
                    <input v-model="form.sort_order" type="number" min="0" :class="field" />
                </div>
                <div class="sm:col-span-3 flex justify-end gap-2">
                    <button type="button" @click="showForm = false" class="rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-sm">Cancel</button>
                    <button type="submit" :disabled="form.processing"
                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">Add</button>
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <table class="w-full text-sm">
                    <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-medium">Name</th>
                            <th class="px-4 py-2.5 text-left font-medium">Code</th>
                            <th class="px-4 py-2.5 text-right font-medium">Books</th>
                            <th class="px-4 py-2.5 text-left font-medium">Status</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="mode in studyModes" :key="mode.id">
                            <td class="px-4 py-2.5 font-medium text-slate-800 dark:text-slate-200">{{ mode.name }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs text-slate-500 dark:text-slate-400">{{ mode.code }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400">{{ mode.book_titles_count }}</td>
                            <td class="px-4 py-2.5">
                                <StatusBadge :label="mode.is_active ? 'Active' : 'Inactive'" :color="mode.is_active ? 'green' : 'slate'" />
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <button type="button" @click="remove(mode)" :disabled="mode.book_titles_count > 0"
                                        class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-slate-400 dark:hover:bg-rose-950/40 transition"
                                        :title="mode.book_titles_count > 0 ? 'Books are classified under this mode' : 'Remove'">
                                    <Icon name="Trash2" :size="15" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
