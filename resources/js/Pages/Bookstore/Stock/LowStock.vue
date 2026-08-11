<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';

defineProps({ titles: Array });
</script>

<template>
    <Head title="Low stock" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-slate-900 dark:text-white">Low stock</h1>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-5">

            <p class="text-sm text-slate-500 dark:text-slate-400">
                Titles at or below their reorder level. Weeks of cover is measured against the last 90 days' issue rate,
                so a title nobody is taking shows no figure at all.
            </p>

            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Book</th>
                                <th class="px-4 py-2.5 text-left font-medium">Category</th>
                                <th class="px-4 py-2.5 text-right font-medium">On hand</th>
                                <th class="px-4 py-2.5 text-right font-medium">Reorder at</th>
                                <th class="px-4 py-2.5 text-right font-medium">Reprint</th>
                                <th class="px-4 py-2.5 text-right font-medium">Weeks of cover</th>
                                <th class="px-4 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="t in titles" :key="t.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-2.5">
                                    <Link :href="route('bookstore.titles.show', t.id)"
                                          class="font-medium text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ t.title }}
                                    </Link>
                                    <p class="text-xs text-slate-400">{{ t.code }}</p>
                                </td>
                                <td class="px-4 py-2.5 text-xs text-slate-500 dark:text-slate-400">{{ t.category || '—' }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums font-medium text-slate-900 dark:text-white">{{ t.on_hand }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400">{{ t.reorder_level }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400">{{ t.reorder_quantity ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums"
                                    :class="t.weeks_of_cover !== null && t.weeks_of_cover < 4 ? 'text-rose-600 dark:text-rose-400 font-medium' : 'text-slate-500 dark:text-slate-400'">
                                    {{ t.weeks_of_cover ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <StatusBadge :label="t.out_of_stock ? 'Out of stock' : 'Low'"
                                                 :color="t.out_of_stock ? 'red' : 'amber'" />
                                </td>
                            </tr>
                            <tr v-if="!titles.length">
                                <td colspan="7" class="px-4 py-12 text-center">
                                    <Icon name="CheckCircle" :size="28" class="mx-auto text-emerald-500" />
                                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Every title is above its reorder level.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
