<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Pagination from '@/Components/Library/Pagination.vue';
import Icon from '@/Components/Icon.vue';

defineProps({ returns: Object, filters: Object, options: Object });
</script>

<template>
    <Head title="Returns" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h1 class="text-base font-semibold text-slate-900 dark:text-white">Returns</h1>
                <Link :href="route('bookstore.returns.create')"
                      class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                    <Icon name="Plus" :size="15" /> Record a return
                </Link>
            </div>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-5">

            <p class="text-sm text-slate-500 dark:text-slate-400">
                Unsold copies coming back from a centre. Sound copies go back on the shelf; damaged ones are received
                and then written off, so both facts stay on the ledger.
            </p>

            <div class="space-y-3">
                <div v-for="r in returns.data" :key="r.id"
                     class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-mono text-xs font-medium text-slate-600 dark:text-slate-400">{{ r.return_number }}</p>
                            <p class="mt-0.5 text-sm font-medium text-slate-800 dark:text-slate-200">
                                {{ r.center?.name || r.campus?.name || r.campus?.name_en || '—' }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ r.returned_on }} · re-shelved at {{ r.shelf_section?.shelf?.store_room?.name }} › {{ r.shelf_section?.code }}
                                · received by {{ r.received_by?.name }}
                            </p>
                        </div>
                        <p class="text-lg font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">+{{ r.total_quantity }}</p>
                    </div>

                    <ul class="mt-3 flex flex-wrap gap-2 border-t border-slate-100 dark:border-slate-800 pt-3">
                        <li v-for="item in r.items" :key="item.id"
                            class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs text-slate-700 dark:text-slate-300">
                            {{ item.book_title?.title }} · {{ item.quantity_returned }}
                            <span v-if="item.quantity_damaged" class="text-rose-600 dark:text-rose-400">
                                ({{ item.quantity_damaged }} damaged)
                            </span>
                        </li>
                    </ul>

                    <p v-if="r.condition_note" class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ r.condition_note }}</p>
                </div>

                <p v-if="!returns.data.length"
                   class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                    No returns recorded yet.
                </p>
            </div>

            <Pagination :links="returns.links" />
        </div>
    </AuthenticatedLayout>
</template>
