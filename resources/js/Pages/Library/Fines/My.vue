<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';

defineProps({
    fines: Object,
    outstanding: Number,
    currency: String,
    onlineAvailable: Boolean,
});

const statusClass = (s) => ({
    open:   'bg-rose-50 text-rose-700 border border-rose-100 dark:bg-rose-950/20 dark:text-rose-450 dark:border-rose-900/40',
    paid:   'bg-emerald-50 text-emerald-700 border border-emerald-100 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/40',
    waived: 'bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
}[s] || 'bg-blue-50 text-blue-700');

const money = (v) => Number(v ?? 0).toFixed(2);

// Map reasons to icons
const getReasonIcon = (reason) => {
    const r = String(reason).toLowerCase();
    if (r.includes('overdue') || r.includes('late')) {
        return 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'; // Clock
    } else if (r.includes('lost') || r.includes('missing')) {
        return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'; // Danger/Exclamation
    } else if (r.includes('damage') || r.includes('broken')) {
        return 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'; // Gear/Wrench
    }
    return 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'; // Dollar/Fine
};
</script>

<template>
    <Head title="My Fines" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-black text-3xl text-slate-900 dark:text-white leading-tight tracking-tight">
                    My Account Fines
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Review pending payments, penalties, and payment history</p>
            </div>
        </template>

        <div class="mx-auto max-w-4xl p-6 space-y-8">
            
            <!-- Glassmorphic credit-card style outstanding banner -->
            <div
                class="relative overflow-hidden rounded-[2.5rem] p-8 text-white border shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-6"
                :class="outstanding > 0
                    ? 'bg-gradient-to-br from-rose-900 via-rose-950 to-slate-900 border-rose-950/80 dark:border-slate-800'
                    : 'bg-gradient-to-br from-indigo-900 via-indigo-950 to-slate-900 border-indigo-950/80 dark:border-slate-800'"
            >
                <!-- Decos -->
                <div class="absolute right-0 top-0 -mt-10 -mr-10 h-40 w-40 rounded-full bg-white/5 blur-3xl"></div>
                <div class="absolute left-1/4 bottom-0 -mb-20 h-56 w-56 rounded-full bg-white/5 blur-3xl"></div>

                <div class="relative z-10 space-y-2.5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/60">SITS Library Fine Account</p>
                    <div>
                        <p class="text-xs font-semibold text-white/80 uppercase tracking-widest">Total Outstanding Balance</p>
                        <p class="text-4xl font-black mt-1" :class="outstanding > 0 ? 'text-rose-300' : 'text-emerald-450'">
                            {{ currency }} {{ money(outstanding) }}
                        </p>
                    </div>
                </div>

                <div class="relative z-10 shrink-0">
                    <div v-if="outstanding > 0 && !onlineAvailable" class="px-5 py-3 rounded-2xl bg-white/10 border border-white/20 backdrop-blur-md text-xs font-bold text-white max-w-xs text-left leading-normal">
                        Please settle your balance directly at any SITS Campus Circulation Desk.
                    </div>
                    <div v-else-if="outstanding === 0" class="px-5 py-3 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 backdrop-blur-md text-xs font-black text-emerald-300 uppercase tracking-widest flex items-center gap-2">
                        <span>All Clear â€” No Fines Due</span> ðŸŽ‰
                    </div>
                </div>
            </div>

            <!-- Fines list card -->
            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-850 overflow-hidden shadow-xl shadow-slate-200/30 dark:shadow-none">
                <div class="p-6 border-b dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Fine Register</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">History of all fee statements and payments</p>
                    </div>
                </div>

                <div v-if="!fines.data.length" class="px-4 py-20 text-center flex flex-col items-center">
                    <div class="w-16 h-16 bg-slate-50 dark:bg-slate-850 rounded-3xl flex items-center justify-center text-slate-400 dark:text-slate-655 mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                    </div>
                    <p class="text-sm font-bold text-slate-905 dark:text-slate-400">Excellent Record! No Fines Found</p>
                </div>

                <div v-else class="divide-y divide-slate-100 dark:divide-slate-850">
                    <div
                        v-for="fine in fines.data"
                        :key="fine.id"
                        class="p-6 hover:bg-slate-50/50 dark:hover:bg-slate-850/20 transition-all duration-200 flex flex-col sm:flex-row sm:items-center justify-between gap-6"
                    >
                        <div class="flex items-start gap-4">
                            <!-- Left icon matching reason -->
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="getReasonIcon(fine.reason)" />
                                </svg>
                            </div>
                            
                            <!-- Description -->
                            <div class="space-y-1 text-left">
                                <h4 class="text-sm font-black text-slate-900 dark:text-white leading-tight">
                                    {{ fine.loan?.copy?.book?.title || 'Library Fee Penalty' }}
                                </h4>
                                <p class="text-xs font-semibold capitalize text-indigo-600 dark:text-indigo-455 tracking-wide">
                                    {{ fine.reason }}
                                </p>
                                <p v-if="fine.note" class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">
                                    Note: {{ fine.note }}
                                </p>
                            </div>
                        </div>

                        <!-- Right details and status actions -->
                        <div class="flex items-center gap-5 justify-between sm:justify-end shrink-0">
                            <div class="text-right space-y-0.5">
                                <p class="text-sm font-black text-slate-900 dark:text-white">{{ currency }} {{ money(fine.amount) }}</p>
                                <p v-if="fine.paid_amount > 0" class="text-[10px] text-emerald-600 dark:text-emerald-450 font-bold uppercase tracking-wider">
                                    Paid {{ currency }} {{ money(fine.paid_amount) }}
                                </p>
                            </div>

                            <span class="rounded-lg px-2.5 py-1 text-[9px] font-black uppercase tracking-wider" :class="statusClass(fine.status)">
                                {{ fine.status }}
                            </span>

                            <Link
                                v-if="fine.status === 'open' && fine.balance > 0 && onlineAvailable"
                                :href="route('library.payments.pay', fine.id)"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all active:scale-95 shadow-md shadow-indigo-650/10"
                            >
                                Pay {{ currency }}{{ money(fine.balance) }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-8 flex justify-center" v-if="fines.links.length > 3">
                <div class="flex gap-2 p-2 bg-white dark:bg-slate-900 rounded-3xl border border-slate-150 dark:border-slate-800 shadow-xl shadow-slate-100/50 dark:shadow-none">
                    <template v-for="(link, i) in fines.links" :key="i">
                        <Link 
                            v-if="link.url" 
                            :href="link.url" 
                            v-html="link.label" 
                            class="px-4 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all" 
                            :class="{'bg-indigo-600 text-white shadow-lg': link.active, 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800': !link.active}"
                        ></Link>
                        <span v-else v-html="link.label" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-300 dark:text-slate-700"></span>
                    </template>
                </div>
            </div>
            
        </div>
    </AuthenticatedLayout>
</template>
