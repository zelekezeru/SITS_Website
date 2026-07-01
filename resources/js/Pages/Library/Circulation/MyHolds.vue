<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';

const props = defineProps({
    holds: Object,
});

const getStatusClass = (status) => {
    const map = {
        waiting: 'bg-amber-50 text-amber-700 border border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/40',
        ready: 'bg-emerald-50 text-emerald-700 border border-emerald-100 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/40 animate-pulse',
        expired: 'bg-rose-50 text-rose-700 border border-rose-100 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/40',
        cancelled: 'bg-gray-100 text-gray-500 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700',
        fulfilled: 'bg-blue-50 text-blue-700 border border-blue-100 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/40',
    };
    return map[status] || 'bg-gray-100 text-gray-800';
};

const cancelHold = (id) => {
    if (confirm('Are you sure you want to cancel this reservation?')) {
        router.delete(route('library.holds.cancel', id), { preserveScroll: true });
    }
};

const activeHolds = computed(() => 
    props.holds.data.filter(h => h.status === 'waiting' || h.status === 'ready')
);

const historicalHolds = computed(() => 
    props.holds.data.filter(h => ['fulfilled', 'cancelled', 'expired'].includes(h.status))
);
</script>

<template>
    <Head title="My Holds & Reservations" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-black text-3xl text-gray-900 dark:text-white leading-tight tracking-tight">
                    My Book Holds
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-medium">Track your reservations and collection queue status</p>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                
                <!-- Active Reservations Section -->
                <section class="space-y-6">
                    <div class="flex items-center gap-4">
                        <h3 class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-[0.3em] whitespace-nowrap">Active Queue</h3>
                        <div class="h-px w-full bg-gradient-to-r from-indigo-500/20 to-transparent dark:from-indigo-500/10"></div>
                    </div>

                    <div v-if="activeHolds.length === 0" class="text-center py-16 bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-xl border border-gray-100 dark:border-gray-800 flex flex-col items-center">
                        <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-3xl flex items-center justify-center text-gray-400 dark:text-gray-655 mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        </div>
                        <h4 class="text-base font-bold text-gray-900 dark:text-gray-150">No Active Holds</h4>
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 max-w-xs mx-auto">
                            You have no pending book reservations. You can place holds on books through the library catalog.
                        </p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div 
                            v-for="hold in activeHolds" 
                            :key="hold.id"
                            class="group bg-white dark:bg-gray-900 rounded-[2.5rem] p-6 border border-gray-100 dark:border-gray-805 hover:border-indigo-500/30 dark:hover:border-indigo-500/30 shadow-xl shadow-gray-200/50 dark:shadow-none hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
                        >
                            <!-- Hold Head Details -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="px-2.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider" :class="getStatusClass(hold.status)">
                                        {{ hold.status }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 font-bold">Placed: {{ new Date(hold.placed_at).toLocaleDateString() }}</span>
                                </div>
                                
                                <div class="space-y-1">
                                    <h4 class="text-base font-black text-gray-900 dark:text-white leading-snug group-hover:text-indigo-650 dark:group-hover:text-indigo-400 transition-colors text-left">
                                        {{ hold.book.title }}
                                    </h4>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500 font-semibold">
                                        <svg class="h-3.5 w-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        <span>Collection: {{ hold.campus.name }} ({{ hold.campus.code }})</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Stepper Queue Visual -->
                            <div class="mt-6 pt-5 border-t border-gray-50 dark:border-gray-800 space-y-4">
                                <!-- Stepper Lines -->
                                <div class="relative flex items-center justify-between">
                                    <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-0.5 bg-gray-100 dark:bg-gray-800 z-0"></div>
                                    <div 
                                        class="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 bg-indigo-500 transition-all duration-500 z-0"
                                        :style="{ width: hold.status === 'ready' ? '100%' : '50%' }"
                                    ></div>
                                    
                                    <!-- Step 1 -->
                                    <div class="relative z-10 flex flex-col items-center">
                                        <div class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] font-black border-4 border-white dark:border-gray-900">1</div>
                                        <span class="text-[9px] font-bold text-gray-400 dark:text-gray-500 mt-1 uppercase">Reserved</span>
                                    </div>
                                    <!-- Step 2 -->
                                    <div class="relative z-10 flex flex-col items-center">
                                        <div 
                                            class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black border-4 border-white dark:border-gray-900 transition-colors"
                                            :class="hold.status === 'ready' ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400'"
                                        >2</div>
                                        <span class="text-[9px] font-bold text-gray-400 dark:text-gray-500 mt-1 uppercase">In Queue</span>
                                    </div>
                                    <!-- Step 3 -->
                                    <div class="relative z-10 flex flex-col items-center">
                                        <div 
                                            class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black border-4 border-white dark:border-gray-900 transition-all duration-300"
                                            :class="hold.status === 'ready' ? 'bg-emerald-500 text-white animate-pulse shadow-md shadow-emerald-500/20' : 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-600'"
                                        >3</div>
                                        <span class="text-[9px] font-bold mt-1 uppercase" :class="hold.status === 'ready' ? 'text-emerald-500 font-black' : 'text-gray-400 dark:text-gray-500'">Ready</span>
                                    </div>
                                </div>

                                <!-- Pickup Alert Glow Banner -->
                                <div v-if="hold.status === 'ready'" class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40 rounded-2xl flex items-center gap-2.5 animate-pulse text-left">
                                    <div class="w-6 h-6 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                                    </div>
                                    <div class="text-[10px] text-emerald-705 dark:text-emerald-400 font-bold leading-normal">
                                        Collection notice ready! Collect this book copy at the {{ hold.campus.code }} Desk.
                                    </div>
                                </div>
                            </div>

                            <!-- Cancel Button -->
                            <div class="mt-4 flex justify-end">
                                <button
                                    @click="cancelHold(hold.id)"
                                    class="w-full sm:w-auto px-4 py-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/15 dark:hover:bg-rose-900/20 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-900/30 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all active:scale-95"
                                >
                                    Cancel Hold
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Reservation History Section -->
                <section class="space-y-6">
                    <div class="flex items-center gap-4">
                        <h3 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.3em] whitespace-nowrap">Reservation History</h3>
                        <div class="h-px w-full bg-gradient-to-r from-gray-200 to-transparent dark:from-gray-800/50"></div>
                    </div>

                    <div v-if="historicalHolds.length === 0" class="text-center py-10 bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-850 text-gray-400 dark:text-gray-500 text-xs italic">
                        No historical reservations recorded in your account.
                    </div>

                    <div v-else class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-850 overflow-hidden shadow-xl shadow-gray-200/20 dark:shadow-none">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                                <thead>
                                    <tr class="bg-gray-50/50 dark:bg-gray-800/30">
                                        <th class="px-8 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-[0.25em]">Book Title</th>
                                        <th class="px-8 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-[0.25em]">Pickup Campus</th>
                                        <th class="px-8 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-[0.25em]">Date Placed</th>
                                        <th class="px-8 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-[0.25em]">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    <tr v-for="hold in historicalHolds" :key="hold.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors duration-200">
                                        <td class="px-8 py-4 text-sm font-bold text-gray-900 dark:text-gray-200 text-left">
                                            {{ hold.book.title }}
                                        </td>
                                        <td class="px-8 py-4 text-xs font-semibold text-gray-650 dark:text-gray-400 text-left">
                                            {{ hold.campus.name }} ({{ hold.campus.code }})
                                        </td>
                                        <td class="px-8 py-4 text-xs text-gray-550 dark:text-gray-400 text-left">
                                            {{ new Date(hold.placed_at).toLocaleDateString() }}
                                        </td>
                                        <td class="px-8 py-4 text-xs text-left">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider" :class="getStatusClass(hold.status)">
                                                {{ hold.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Smart Pagination -->
                <div class="mt-10 flex justify-center" v-if="holds.links.length > 3">
                    <div class="flex gap-2 p-2 bg-white dark:bg-gray-900 rounded-3xl border border-gray-150 dark:border-gray-800 shadow-xl shadow-gray-100/50 dark:shadow-none">
                        <template v-for="(link, i) in holds.links" :key="i">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label" 
                                class="px-4 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all" 
                                :class="{'bg-indigo-600 text-white shadow-lg': link.active, 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800': !link.active}"
                            ></Link>
                            <span v-else v-html="link.label" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-gray-300 dark:text-gray-700"></span>
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
