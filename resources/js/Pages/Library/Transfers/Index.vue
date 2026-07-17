<script setup>
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    incoming: Array,
    outgoing: Array,
    history: Array,
})
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Inter-Campus Transfers</h2>
                <Link :href="route('library.transfers.create')" class="w-full sm:w-auto text-center bg-indigo-600 dark:bg-indigo-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 transition shadow-md shadow-indigo-500/20">
                    Request Transfer
                </Link>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-12 sm:px-6 lg:px-8 space-y-8">
            
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Incoming -->
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border dark:border-slate-800 p-6">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 border-b dark:border-slate-800 pb-2 mb-4">Incoming to My Campus</h3>
                    <ul v-if="incoming.length" class="divide-y dark:divide-slate-800">
                        <li v-for="t in incoming" :key="t.id" class="py-3 flex justify-between items-center hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition px-2 -mx-2 rounded">
                            <div>
                                <Link :href="route('library.transfers.show', t.id)" class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline">
                                    {{ t.copy.book.title }}
                                </Link>
                                <div class="text-xs text-slate-500 dark:text-slate-500 mt-1">From: {{ t.from_campus.code }} &bull; Req by: {{ t.requester.name }}</div>
                            </div>
                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-bold uppercase text-slate-800 dark:text-slate-300 border dark:border-slate-700">{{ t.status }}</span>
                        </li>
                    </ul>
                    <p v-else class="text-slate-500 dark:text-slate-500 text-sm italic">No incoming transfers.</p>
                </div>

                <!-- Outgoing -->
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border dark:border-slate-800 p-6">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 border-b dark:border-slate-800 pb-2 mb-4">Outgoing from My Campus</h3>
                    <ul v-if="outgoing.length" class="divide-y dark:divide-slate-800">
                        <li v-for="t in outgoing" :key="t.id" class="py-3 flex justify-between items-center hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition px-2 -mx-2 rounded">
                            <div>
                                <Link :href="route('library.transfers.show', t.id)" class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline">
                                    {{ t.copy.book.title }}
                                </Link>
                                <div class="text-xs text-slate-500 dark:text-slate-500 mt-1">To: {{ t.to_campus.code }} &bull; Req by: {{ t.requester.name }}</div>
                            </div>
                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-bold uppercase text-slate-800 dark:text-slate-300 border dark:border-slate-700">{{ t.status }}</span>
                        </li>
                    </ul>
                    <p v-else class="text-slate-500 dark:text-slate-500 text-sm italic">No outgoing transfers.</p>
                </div>
            </div>

            <!-- History -->
            <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border dark:border-slate-800 p-6">
                <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 border-b dark:border-slate-800 pb-2 mb-4">Recent History</h3>
                <div class="overflow-x-auto">
                    <table v-if="history.length" class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Title</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">From</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">To</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr v-for="t in history" :key="t.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-indigo-600 dark:text-indigo-400 font-medium">
                                    <Link :href="route('library.transfers.show', t.id)" class="hover:underline">{{ t.copy.book.title }}</Link>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">{{ t.from_campus.code }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">{{ t.to_campus.code }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm">
                                    <span class="text-slate-700 dark:text-slate-300">{{ t.status }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-slate-500 dark:text-slate-500 text-sm italic py-4">No history found.</p>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
