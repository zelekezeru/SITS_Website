<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import EmptyState from '@/Components/Library/EmptyState.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    patron: Object,
    summary: Object,
    loans: Object,
    holds: Array,
    fines: Array,
    activities: Array,
    currency: { type: String, default: '' },
});

const page = usePage();
const activeTab = ref('overview');

const tabs = [
    { key: 'overview',  label: 'Overview',  icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z' },
    { key: 'loans',     label: 'Loans',     icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2' },
    { key: 'holds',     label: 'Holds',     icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11' },
    { key: 'fines',     label: 'Fines',     icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2' },
    { key: 'activity',  label: 'Activity',  icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
];

const statusColors = {
    active: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    returned: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    overdue_returned: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    waiting: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
    ready: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    expired: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    fulfilled: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    open: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    paid: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    waived: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
};

const roleBadge = computed(() => {
    const map = {
        super_admin:  'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
        campus_admin: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
        librarian:    'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        instructor:   'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
        staff_user:   'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        student:      'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    };
    return map[props.patron.role_value] ?? 'bg-gray-100 text-gray-600';
});
</script>

<template>
    <Head :title="`${patron.name} — Patron Profile`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('library.users.index')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h1 class="text-base font-semibold text-gray-900 dark:text-white">Patron Profile</h1>
            </div>
        </template>

        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Patron header card -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6">
                <div class="flex flex-col sm:flex-row items-start gap-5">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-2xl font-bold">
                        {{ patron.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ patron.name }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ patron.email }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="roleBadge">
                                {{ patron.role }}
                            </span>
                            <span class="text-xs text-gray-400">•</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ patron.campus }}</span>
                            <span class="text-xs text-gray-400">•</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Member since {{ patron.member_since }}</span>
                        </div>
                    </div>
                </div>

                <!-- Summary stat chips -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mt-6">
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3 text-center">
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ summary.total_loans }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Loans</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3 text-center">
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ summary.active_loans }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Active</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3 text-center">
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ summary.overdue_loans }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Overdue</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3 text-center">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ summary.active_holds }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Active Holds</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3 text-center">
                        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ currency }} {{ summary.total_fines }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Fines</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3 text-center">
                        <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ currency }} {{ summary.outstanding_fines }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Outstanding</p>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition whitespace-nowrap"
                    :class="activeTab === tab.key
                        ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800'"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="tab.icon" />
                    </svg>
                    {{ tab.label }}
                </button>
            </div>

            <!-- Tab content -->

            <!-- Overview tab -->
            <div v-if="activeTab === 'overview'" class="space-y-4">
                <div class="grid gap-4 lg:grid-cols-2">
                    <!-- Recent loans -->
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Recent Loans</h3>
                        <div v-if="loans.data.length" class="space-y-2">
                            <div v-for="loan in loans.data.slice(0, 5)" :key="loan.id" class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ loan.book_title }}</p>
                                    <p class="text-xs text-gray-400">{{ loan.checked_out_at }} — Due: {{ loan.due_on }}</p>
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="statusColors[loan.status] ?? 'bg-gray-100 text-gray-600'">
                                    {{ loan.status.replace(/_/g, ' ') }}
                                </span>
                            </div>
                        </div>
                        <EmptyState v-else title="No loans" description="This patron has no loan history." />
                    </div>

                    <!-- Recent activity -->
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Recent Activity</h3>
                        <div v-if="activities.length" class="space-y-2">
                            <div v-for="activity in activities.slice(0, 8)" :key="activity.id" class="flex items-start gap-2 py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                <div class="h-1.5 w-1.5 rounded-full bg-indigo-500 mt-2 shrink-0" />
                                <div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ activity.description }}</p>
                                    <p class="text-xs text-gray-400">{{ activity.created_at }}</p>
                                </div>
                            </div>
                        </div>
                        <EmptyState v-else title="No activity" description="No recorded activity for this patron." />
                    </div>
                </div>
            </div>

            <!-- Loans tab -->
            <div v-if="activeTab === 'loans'">
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
                    <table v-if="loans.data.length" class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Book</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Checked Out</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Due</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Returned</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="loan in loans.data" :key="loan.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ loan.book_title }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ loan.checked_out_at }}</td>
                                <td class="px-4 py-3" :class="loan.is_overdue ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-500 dark:text-gray-400'">
                                    {{ loan.due_on }}
                                    <span v-if="loan.days_overdue > 0" class="text-xs"> ({{ loan.days_overdue }}d late)</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ loan.returned_at ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="statusColors[loan.status] ?? 'bg-gray-100 text-gray-600'">
                                        {{ loan.status.replace(/_/g, ' ') }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <EmptyState v-else title="No loans" description="This patron has no loan records." />
                </div>
            </div>

            <!-- Holds tab -->
            <div v-if="activeTab === 'holds'">
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
                    <table v-if="holds.length" class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Book</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Campus</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Placed</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="hold in holds" :key="hold.id">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ hold.book_title }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ hold.campus ?? 'Any' }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ hold.placed_at }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="statusColors[hold.status]">{{ hold.status }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <EmptyState v-else title="No holds" description="This patron has no hold records." />
                </div>
            </div>

            <!-- Fines tab -->
            <div v-if="activeTab === 'fines'">
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
                    <table v-if="fines.length" class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Book</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Reason</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Paid</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Balance</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="fine in fines" :key="fine.id">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ fine.book_title ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 capitalize">{{ fine.reason }}</td>
                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ currency }} {{ fine.amount }}</td>
                                <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ currency }} {{ fine.paid_amount }}</td>
                                <td class="px-4 py-3 text-right font-medium" :class="fine.balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400'">
                                    {{ currency }} {{ fine.balance }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="statusColors[fine.status]">{{ fine.status }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <EmptyState v-else title="No fines" description="This patron has no fine records." />
                </div>
            </div>

            <!-- Activity tab -->
            <div v-if="activeTab === 'activity'">
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
                    <div v-if="activities.length" class="space-y-3">
                        <div v-for="activity in activities" :key="activity.id" class="flex items-start gap-3 pb-3 border-b border-gray-100 dark:border-gray-800 last:border-0 last:pb-0">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ activity.description }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span v-if="activity.subject" class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">{{ activity.subject }}</span>
                                    <span class="text-xs text-gray-400">{{ activity.created_at }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <EmptyState v-else title="No activity" description="No recorded activity for this patron." />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
