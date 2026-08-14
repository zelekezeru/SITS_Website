<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Pagination from '@/Components/Library/Pagination.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';

defineProps({ audits: Object, stores: Array });

const showForm = ref(false);
const form = useForm({ store_room_id: '', notes: '' });

const colors = { draft: 'gray', in_progress: 'blue', completed: 'amber', approved: 'green', cancelled: 'rose' };
const date = (v) => (v ? new Date(v).toLocaleDateString() : '—');

const field = 'mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-xs font-medium text-slate-600 dark:text-slate-400';
</script>

<template>
    <Head title="Stock audits" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-base font-semibold text-slate-900 dark:text-white">Stock audits</h1>
                <button type="button" @click="showForm = !showForm"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                    <Icon name="Plus" :size="15" /> Start a count
                </button>
            </div>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-5">

            <p class="text-sm text-slate-500 dark:text-slate-400">
                Starting a count freezes what the system believes. Walk the aisle scanning section QR codes, then a
                second person signs the variance off — only then do corrections reach the ledger.
            </p>

            <form v-if="showForm"
                  @submit.prevent="form.post(route('bookstore.audits.store'), { onSuccess: () => { showForm = false; form.reset(); } })"
                  class="grid gap-4 sm:grid-cols-3 rounded-xl border border-indigo-200 dark:border-indigo-900 bg-white dark:bg-slate-900 p-5">
                <div>
                    <label :class="label">Store room <span class="text-rose-500">*</span></label>
                    <select v-model="form.store_room_id" :class="field" required>
                        <option value="">Choose…</option>
                        <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label :class="label">Notes</label>
                    <input v-model="form.notes" type="text" :class="field" />
                </div>
                <div class="sm:col-span-3 flex justify-end gap-2">
                    <button type="button" @click="showForm = false" class="rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-sm">Cancel</button>
                    <button type="submit" :disabled="form.processing"
                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">Start</button>
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Reference</th>
                                <th class="px-4 py-2.5 text-left font-medium">Store</th>
                                <th class="px-4 py-2.5 text-right font-medium">Lines</th>
                                <th class="px-4 py-2.5 text-left font-medium">Started</th>
                                <th class="px-4 py-2.5 text-left font-medium">Approved</th>
                                <th class="px-4 py-2.5 text-left font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="a in audits.data" :key="a.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-2.5">
                                    <Link :href="route('bookstore.audits.show', a.id)"
                                          class="font-mono text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ a.reference }}
                                    </Link>
                                </td>
                                <td class="px-4 py-2.5 text-slate-800 dark:text-slate-200">{{ a.store_room?.name }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ a.lines_count }}</td>
                                <td class="px-4 py-2.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ date(a.started_at) }}<span v-if="a.started_by"> · {{ a.started_by.name }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ a.approved_by ? `${date(a.approved_at)} · ${a.approved_by.name}` : '—' }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <StatusBadge :label="a.status.replace(/_/g, ' ')" :color="colors[a.status]" />
                                </td>
                            </tr>
                            <tr v-if="!audits.data.length">
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">No audits yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :links="audits.links" />
        </div>
    </AuthenticatedLayout>
</template>
