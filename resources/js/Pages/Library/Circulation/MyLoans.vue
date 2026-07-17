<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';

const props = defineProps({
    loans: Object,
    maxRenewals: { type: Number, default: 0 },
});

function renew(loan) {
    router.post(route('library.my.loans.renew', loan.id), {}, { preserveScroll: true });
}

const getStatusClass = (status) => {
    const map = {
        active: 'bg-emerald-50 text-emerald-700 border border-emerald-100 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/40',
        overdue: 'bg-rose-50 text-rose-700 border border-rose-100 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/40',
        returned: 'bg-slate-100 text-slate-650 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
    };
    return map[status] || 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400';
};

const getProgressDetails = (loan) => {
    if (loan.status === 'returned') {
        return { percent: 100, color: 'bg-slate-300 dark:bg-slate-700', text: 'Returned', bg: 'bg-slate-100 text-slate-500' };
    }
    const now = new Date();
    const borrowed = new Date(loan.borrowed_at);
    const due = new Date(loan.due_on);
    
    const totalPeriod = Math.max(due - borrowed, 1);
    const elapsed = now - borrowed;
    const percent = Math.min(Math.max((elapsed / totalPeriod) * 100, 0), 100);
    
    const diffTime = due - now;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    let color = 'bg-indigo-600 dark:bg-indigo-550';
    let text = `${diffDays} days remaining`;
    let bg = 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300';
    
    if (diffDays < 0) {
        color = 'bg-rose-500 animate-pulse';
        text = `${Math.abs(diffDays)} days overdue`;
        bg = 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-450';
    } else if (diffDays <= 2) {
        color = 'bg-amber-500';
        text = `Due in ${diffDays} days!`;
        bg = 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-450';
    }
    
    return { percent, color, text, bg, diffDays };
};

const activeLoans = computed(() => 
    props.loans.data.filter(l => l.status === 'active' || l.status === 'overdue')
);

const previousLoans = computed(() => 
    props.loans.data.filter(l => l.status === 'returned')
);
</script>

<template>
    <Head title="My Loans" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-black text-3xl text-slate-900 dark:text-white leading-tight tracking-tight">
                        My Book Loans
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Manage your active checkouts, renewals, and history</p>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                
                <!-- Active Loans Section -->
                <section class="space-y-6">
                    <div class="flex items-center gap-4">
                        <h3 class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-[0.3em] whitespace-nowrap">Active Borrowings</h3>
                        <div class="h-px w-full bg-gradient-to-r from-indigo-500/20 to-transparent dark:from-indigo-500/10"></div>
                    </div>

                    <div v-if="activeLoans.length === 0" class="text-center py-16 bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-800 flex flex-col items-center">
                        <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-3xl flex items-center justify-center text-slate-400 dark:text-slate-650 mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                        </div>
                        <h4 class="text-base font-bold text-slate-900 dark:text-slate-150">No Active Loans</h4>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto">
                            You have no physical books currently checked out. Visit the library catalog to find something to read.
                        </p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div 
                            v-for="loan in activeLoans" 
                            :key="loan.id"
                            class="group bg-white dark:bg-slate-900 rounded-[2.5rem] p-6 border border-slate-100 dark:border-slate-800 hover:border-indigo-500/30 dark:hover:border-indigo-500/30 shadow-xl shadow-slate-200/50 dark:shadow-none hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
                        >
                            <div class="flex gap-5 items-start">
                                <!-- Book Cover Thumbnail -->
                                <div class="w-20 h-28 shrink-0 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-100 dark:border-slate-800 overflow-hidden relative shadow-sm">
                                    <img v-if="loan.copy.book.cover_path" :src="'/storage/' + loan.copy.book.cover_path" class="w-full h-full object-cover" />
                                    <img v-else-if="loan.copy.book.cover_url" :src="loan.copy.book.cover_url" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center bg-indigo-50 dark:bg-indigo-950/20 text-indigo-500 dark:text-indigo-400">
                                        <span class="text-sm font-black uppercase">{{ loan.copy.book.title.charAt(0) }}</span>
                                    </div>
                                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-r from-black/10 to-transparent"></div>
                                </div>

                                <!-- Loan Details -->
                                <div class="flex-1 min-w-0 space-y-1.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider" :class="getStatusClass(loan.status)">
                                            {{ loan.status }}
                                        </span>
                                        <span v-if="loan.fine_amount > 0" class="px-2.5 py-0.5 bg-rose-50 border border-rose-100 dark:bg-rose-950/20 dark:border-rose-900/40 text-rose-600 dark:text-rose-400 rounded text-[9px] font-black uppercase tracking-wider">
                                            Fine: ${{ loan.fine_amount.toFixed(2) }}
                                        </span>
                                    </div>
                                    <h4 class="text-sm font-black text-slate-900 dark:text-white leading-snug truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-450 transition-colors text-left">
                                        {{ loan.copy.book.title }}
                                    </h4>
                                    <p class="text-[10px] font-mono text-slate-400 dark:text-slate-500 text-left">Barcode: {{ loan.copy.barcode || loan.copy.tracking_hash.substring(0, 12) }}</p>
                                    
                                    <div class="flex items-center gap-4 text-[10px] text-slate-500 font-medium">
                                        <div>
                                            <span class="text-slate-400">Borrowed:</span> {{ new Date(loan.borrowed_at).toLocaleDateString() }}
                                        </div>
                                        <div>
                                            <span class="text-slate-400">Due On:</span> {{ new Date(loan.due_on).toLocaleDateString() }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress timeline indicator -->
                            <div class="mt-6 pt-5 border-t border-slate-50 dark:border-slate-800 space-y-2">
                                <div class="flex justify-between items-center text-[10px] font-bold">
                                    <span class="px-2.5 py-0.5 rounded-lg uppercase tracking-wider" :class="getProgressDetails(loan).bg">
                                        {{ getProgressDetails(loan).text }}
                                    </span>
                                    <span class="text-slate-400 uppercase tracking-widest font-black">
                                        {{ Math.round(getProgressDetails(loan).percent) }}% time elapsed
                                    </span>
                                </div>
                                <div class="w-full h-2 bg-slate-50 dark:bg-slate-800 rounded-full overflow-hidden shadow-inner">
                                    <div 
                                        class="h-full rounded-full transition-all duration-500" 
                                        :class="getProgressDetails(loan).color"
                                        :style="{ width: `${getProgressDetails(loan).percent}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-4 flex justify-end">
                                <button
                                    @click="renew(loan)"
                                    :disabled="loan.renewal_count >= maxRenewals"
                                    class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white disabled:bg-slate-100 disabled:text-slate-400 dark:disabled:bg-slate-800 dark:disabled:text-slate-600 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all active:scale-95 disabled:scale-100 shadow-md shadow-indigo-600/10 disabled:shadow-none"
                                >
                                    Renew Loan <span v-if="maxRenewals">({{ loan.renewal_count }}/{{ maxRenewals }})</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Previous Loans History Section -->
                <section class="space-y-6">
                    <div class="flex items-center gap-4">
                        <h3 class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] whitespace-nowrap">Borrowing History</h3>
                        <div class="h-px w-full bg-gradient-to-r from-slate-200 to-transparent dark:from-slate-800/50"></div>
                    </div>

                    <div v-if="previousLoans.length === 0" class="text-center py-10 bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-850 text-slate-400 dark:text-slate-500 text-xs italic">
                        No previous loans recorded in your history.
                    </div>

                    <div v-else class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-850 overflow-hidden shadow-xl shadow-slate-200/20 dark:shadow-none">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead>
                                    <tr class="bg-slate-50/50 dark:bg-slate-800/30">
                                        <th class="px-8 py-4 text-left text-[9px] font-black text-slate-400 uppercase tracking-[0.25em]">Book Title</th>
                                        <th class="px-8 py-4 text-left text-[9px] font-black text-slate-400 uppercase tracking-[0.25em]">Barcode</th>
                                        <th class="px-8 py-4 text-left text-[9px] font-black text-slate-400 uppercase tracking-[0.25em]">Borrowed</th>
                                        <th class="px-8 py-4 text-left text-[9px] font-black text-slate-400 uppercase tracking-[0.25em]">Returned</th>
                                        <th class="px-8 py-4 text-left text-[9px] font-black text-slate-400 uppercase tracking-[0.25em]">Fine Settle</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="loan in previousLoans" :key="loan.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors duration-200">
                                        <td class="px-8 py-4 text-sm font-bold text-slate-900 dark:text-slate-200 text-left">
                                            {{ loan.copy.book.title }}
                                        </td>
                                        <td class="px-8 py-4 text-xs font-mono text-slate-400 dark:text-slate-500 text-left">
                                            {{ loan.copy.barcode || loan.copy.tracking_hash.substring(0, 10) }}
                                        </td>
                                        <td class="px-8 py-4 text-xs text-slate-650 dark:text-slate-400 text-left">
                                            {{ new Date(loan.borrowed_at).toLocaleDateString() }}
                                        </td>
                                        <td class="px-8 py-4 text-xs text-slate-650 dark:text-slate-400 text-left">
                                            {{ loan.returned_at ? new Date(loan.returned_at).toLocaleDateString() : '-' }}
                                        </td>
                                        <td class="px-8 py-4 text-xs text-slate-550 dark:text-slate-400 text-left">
                                            {{ loan.fine_amount > 0 ? `$${loan.fine_amount.toFixed(2)}` : 'No fine' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Smart Pagination -->
                <div class="mt-10 flex justify-center" v-if="loans.links.length > 3">
                    <div class="flex gap-2 p-2 bg-white dark:bg-slate-900 rounded-3xl border border-slate-150 dark:border-slate-800 shadow-xl shadow-slate-100/50 dark:shadow-none">
                        <template v-for="(link, i) in loans.links" :key="i">
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
        </div>
    </AuthenticatedLayout>
</template>
