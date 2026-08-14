<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import StatCard from '@/Components/Bookstore/StatCard.vue';
import QrPanel from '@/Components/Bookstore/QrPanel.vue';
import Pagination from '@/Components/Library/Pagination.vue';

defineProps({ section: Object, movements: Object });

const date = (v) => (v ? new Date(v).toLocaleDateString() : '—');
</script>

<template>
    <Head :title="section.code" />

    <AuthenticatedLayout>
        <template #header>
            <div class="min-w-0">
                <h1 class="truncate text-base font-semibold text-slate-900 dark:text-white">{{ section.code }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ section.path }}</p>
            </div>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-6">

            <div class="grid gap-4 sm:grid-cols-3">
                <StatCard label="Books in this section" :value="section.total_on_hand" icon="Boxes" tone="indigo" />
                <StatCard label="Titles" :value="(section.stocks ?? []).length" icon="BookCopy" tone="slate" />
                <StatCard label="Space left" :value="section.remaining_capacity ?? '—'"
                          :sub="section.capacity ? `Capacity ${section.capacity}` : 'No capacity set'"
                          icon="Layers" tone="slate" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-6">
                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                        <h2 class="px-5 pt-5 text-sm font-semibold text-slate-900 dark:text-white">What is on this shelf</h2>
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                    <tr>
                                        <th class="px-5 py-2 text-left font-medium">Code</th>
                                        <th class="px-3 py-2 text-left font-medium">Title</th>
                                        <th class="px-3 py-2 text-right font-medium">On hand</th>
                                        <th class="px-5 py-2 text-right font-medium">Reserved</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="stock in section.stocks" :key="stock.id">
                                        <td class="px-5 py-2.5 font-mono text-xs text-slate-500 dark:text-slate-400">{{ stock.book_title?.code }}</td>
                                        <td class="px-3 py-2.5">
                                            <Link :href="route('bookstore.titles.show', stock.book_title_id)"
                                                  class="text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400">
                                                {{ stock.book_title?.title }}
                                            </Link>
                                        </td>
                                        <td class="px-3 py-2.5 text-right tabular-nums font-medium text-slate-900 dark:text-white">{{ stock.quantity }}</td>
                                        <td class="px-5 py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400">{{ stock.reserved_quantity }}</td>
                                    </tr>
                                    <tr v-if="!section.stocks.length">
                                        <td colspan="4" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">This section is empty.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                        <h2 class="px-5 pt-5 text-sm font-semibold text-slate-900 dark:text-white">Movement through this section</h2>
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                    <tr>
                                        <th class="px-5 py-2 text-left font-medium">Date</th>
                                        <th class="px-3 py-2 text-left font-medium">Book</th>
                                        <th class="px-3 py-2 text-left font-medium">Type</th>
                                        <th class="px-3 py-2 text-right font-medium">Qty</th>
                                        <th class="px-5 py-2 text-right font-medium">Balance</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="m in movements.data" :key="m.id">
                                        <td class="px-5 py-2 text-slate-500 dark:text-slate-400">{{ date(m.occurred_at) }}</td>
                                        <td class="px-3 py-2 text-slate-800 dark:text-slate-200">{{ m.book_title?.title }}</td>
                                        <td class="px-3 py-2 text-slate-600 dark:text-slate-400">{{ m.type }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums">{{ m.quantity }}</td>
                                        <td class="px-5 py-2 text-right tabular-nums font-medium">{{ m.balance_after }}</td>
                                    </tr>
                                    <tr v-if="!movements.data.length">
                                        <td colspan="5" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Nothing has moved here.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4"><Pagination :links="movements.links" /></div>
                    </section>
                </div>

                <QrPanel type="section" :id="section.id" :name="section.code" :sub="section.path" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
