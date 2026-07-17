<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Pagination from '@/Components/Library/Pagination.vue';

defineProps({
    campuses: Object,
});
</script>

<template>
    <Head title="Manage Campuses" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-black text-2xl text-slate-900 dark:text-white leading-tight tracking-tight uppercase">
                    Institutional Campuses
                </h2>
                <Link 
                    :href="route('library.campuses.create')" 
                    class="inline-flex items-center px-6 py-2.5 bg-indigo-600 border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition-all active:scale-95"
                >
                    + Add Campus
                </Link>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-2xl shadow-slate-200/50 dark:shadow-none rounded-[2.5rem] border border-transparent dark:border-slate-800 transition-all">
                    <div class="p-8">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y dark:divide-slate-800">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/30 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Campus Name</th>
                                        <th class="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/30 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Code</th>
                                        <th class="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/30 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Inventory Area</th>
                                        <th class="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/30 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Status</th>
                                        <th class="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/30 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y dark:divide-slate-800">
                                    <tr v-for="campus in campuses.data" :key="campus.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="text-sm font-black text-slate-900 dark:text-slate-100 uppercase tracking-tight">{{ campus.name }}</div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-[200px]">{{ campus.address || 'No address provided' }}</div>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-lg text-xs font-black font-mono">
                                                {{ campus.code }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <div class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div>
                                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ campus.floors_count }} Floors</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <span :class="[
                                                'px-3 py-1 text-[10px] font-black rounded-lg uppercase tracking-widest border transition-colors',
                                                campus.is_active 
                                                    ? 'bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800' 
                                                    : 'bg-red-50 text-red-600 border-red-100 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800'
                                            ]">
                                                {{ campus.is_active ? 'Operational' : 'Archived' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap text-right text-sm font-bold space-x-4">
                                            <Link :href="route('library.campuses.show', campus.id)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors">Visual Tree</Link>
                                            <Link :href="route('library.campuses.edit', campus.id)" class="text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">Settings</Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-8 pt-8 border-t dark:border-slate-800">
                            <Pagination :links="campuses.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
