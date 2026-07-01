<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import EmptyState from '@/Components/Library/EmptyState.vue';
import LineChart from '@/Components/Library/Charts/LineChart.vue';
import BarChart from '@/Components/Library/Charts/BarChart.vue';
import DonutChart from '@/Components/Library/Charts/DonutChart.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    report: String,
    from: String,
    to: String,
    data: Object,
    currency: { type: String, default: '' },
    reports: Array,
});

const selectedReport = ref(props.report);
const dateFrom = ref(props.from);
const dateTo = ref(props.to);
const exportOpen = ref(false);

function loadReport() {
    router.get(route('library.reports.index'), {
        report: selectedReport.value,
        from: dateFrom.value,
        to: dateTo.value,
    }, { preserveState: true });
}

function exportReport(format) {
    exportOpen.value = false;
    const params = new URLSearchParams({
        report_type: selectedReport.value,
        from: dateFrom.value,
        to: dateTo.value,
        format,
    });
    window.location.href = `${route('library.reports.export')}?${params}`;
}

const reportIcons = {
    'chart-bar': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'library': 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    'currency-dollar': 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    'users': 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    'building-office': 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    'clock': 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
};

const statusColors = {
    active: '#6366f1', returned: '#10b981', overdue_returned: '#f59e0b', lost: '#ef4444',
};

// Chart data adapters
const trendData = computed(() => props.data?.trend ?? props.data?.summary ? [] : []);
const loanStatusSegments = computed(() => {
    const m = props.data?.by_status ?? {};
    return Object.entries(m).map(([label, value]) => ({
        label: label.replace(/_/g, ' '),
        value,
        color: statusColors[label] ?? '#94a3b8',
    }));
});
const topBooksItems = computed(() =>
    (props.data?.top_books ?? []).map(t => ({ label: t.title, value: t.total ?? t.loans }))
);
const campusItems = computed(() =>
    (props.data?.by_campus ?? props.data?.comparison ?? []).map(c => ({
        label: c.campus ?? c.campus,
        value: c.copies ?? c.checkouts ?? 0,
    }))
);
const categoryItems = computed(() =>
    (props.data?.by_category ?? []).map(c => ({ label: c.category, value: c.titles }))
);
const borrowerItems = computed(() =>
    (props.data?.top_borrowers ?? []).map(b => ({ label: b.name, value: b.total_loans }))
);
</script>

<template>
    <Head title="Reports" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-gray-900 dark:text-white">Reports & Analytics</h1>
        </template>

        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Report type selector + date range -->
            <div class="flex flex-col sm:flex-row gap-4">
                <!-- Report tabs -->
                <div class="flex gap-1 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-1 flex-1">
                    <button
                        v-for="r in reports"
                        :key="r.key"
                        @click="selectedReport = r.key; loadReport()"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition whitespace-nowrap"
                        :class="selectedReport === r.key
                            ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="reportIcons[r.icon]" />
                        </svg>
                        {{ r.label }}
                    </button>
                </div>

                <!-- Date range -->
                <div v-if="selectedReport !== 'collection' && selectedReport !== 'overdue'" class="flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2 shrink-0">
                    <input v-model="dateFrom" type="date" class="border-none bg-transparent text-sm text-gray-700 dark:text-gray-300 focus:ring-0 w-32" />
                    <span class="text-gray-400">→</span>
                    <input v-model="dateTo" type="date" class="border-none bg-transparent text-sm text-gray-700 dark:text-gray-300 focus:ring-0 w-32" />
                    <button @click="loadReport" class="rounded-lg bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-700 transition">
                        Apply
                    </button>
                </div>

                <!-- Export -->
                <div class="relative shrink-0">
                    <button
                        @click="exportOpen = !exportOpen"
                        class="flex h-full items-center gap-1.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export
                    </button>
                    <div
                        v-if="exportOpen"
                        class="absolute right-0 z-10 mt-2 w-40 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 py-1 shadow-lg"
                    >
                        <button v-for="fmt in ['xlsx', 'csv', 'pdf']" :key="fmt" @click="exportReport(fmt)"
                            class="block w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 uppercase">
                            {{ fmt }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Circulation Report -->
            <template v-if="selectedReport === 'circulation' && data?.summary">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <div v-for="(value, key) in data.summary" :key="key" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 capitalize">{{ key.replace(/_/g, ' ') }}</p>
                        <p class="mt-1 text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ value ?? 0 }}</p>
                    </div>
                </div>
                <div class="grid gap-4 lg:grid-cols-3">
                    <div v-if="data.trend?.length" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 lg:col-span-2">
                        <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Daily Circulation Trend</p>
                        <LineChart :data="data.trend.map(d => ({ date: d.date, checkout: d.checkouts, return: d.returns }))" />
                    </div>
                    <div v-if="loanStatusSegments.length" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
                        <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Loans by Status</p>
                        <DonutChart :segments="loanStatusSegments" />
                    </div>
                    <div v-if="topBooksItems.length" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 lg:col-span-3">
                        <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Top Borrowed Titles</p>
                        <BarChart :items="topBooksItems" bar-class="bg-indigo-500" />
                    </div>
                </div>
            </template>

            <!-- Collection Report -->
            <template v-if="selectedReport === 'collection' && data?.summary">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div v-for="(value, key) in data.summary" :key="key" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 capitalize">{{ key.replace(/_/g, ' ') }}</p>
                        <p class="mt-1 text-xl font-bold text-blue-600 dark:text-blue-400">{{ value }}</p>
                    </div>
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    <div v-if="campusItems.length" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
                        <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Copies by Campus</p>
                        <BarChart :items="campusItems" bar-class="bg-teal-500" />
                    </div>
                    <div v-if="categoryItems.length" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
                        <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Titles by Category</p>
                        <BarChart :items="categoryItems" bar-class="bg-violet-500" />
                    </div>
                </div>
            </template>

            <!-- Fines Report -->
            <template v-if="selectedReport === 'fines' && data?.summary">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <div v-for="(value, key) in data.summary" :key="key" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 capitalize">{{ key.replace(/_/g, ' ') }}</p>
                        <p class="mt-1 text-xl font-bold text-amber-600 dark:text-amber-400">
                            {{ typeof value === 'number' && key.includes('count') ? value : `${currency} ${value}` }}
                        </p>
                    </div>
                </div>
                <div v-if="data.trend?.length" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
                    <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Fines Trend — Assessed vs Collected</p>
                    <LineChart :data="data.trend.map(d => ({ date: d.date, checkout: d.assessed, return: d.collected }))" />
                </div>
            </template>

            <!-- Patrons Report -->
            <template v-if="selectedReport === 'patrons' && data?.summary">
                <div class="grid grid-cols-3 gap-3">
                    <div v-for="(value, key) in data.summary" :key="key" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 capitalize">{{ key.replace(/_/g, ' ') }}</p>
                        <p class="mt-1 text-xl font-bold text-green-600 dark:text-green-400">{{ value }}</p>
                    </div>
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    <div v-if="borrowerItems.length" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
                        <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Top Borrowers</p>
                        <BarChart :items="borrowerItems" bar-class="bg-indigo-500" />
                    </div>
                    <div v-if="data.top_debtors?.length" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
                        <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Top Outstanding Fines</p>
                        <BarChart :items="data.top_debtors.map(d => ({ label: d.name, value: parseFloat(d.balance) }))" bar-class="bg-rose-500" />
                    </div>
                </div>
            </template>

            <!-- Campus Report -->
            <template v-if="selectedReport === 'campus' && data?.comparison">
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Campus</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Copies</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Checkouts</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Returns</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Transfers In</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Transfers Out</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Utilization</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="c in data.comparison" :key="c.code" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ c.campus }}
                                    <span class="text-xs text-gray-400 ml-1">({{ c.code }})</span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">{{ c.copies }}</td>
                                <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400 font-medium">{{ c.checkouts }}</td>
                                <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ c.returns }}</td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">{{ c.transfers_in }}</td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">{{ c.transfers_out }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-medium" :class="c.utilization > 50 ? 'text-emerald-600 dark:text-emerald-400' : c.utilization > 20 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400'">
                                        {{ c.utilization }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <!-- Overdue Loans Report -->
            <template v-if="selectedReport === 'overdue' && data?.summary">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div v-for="(value, key) in data.summary" :key="key" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 capitalize">{{ key.replace(/_/g, ' ') }}</p>
                        <p class="mt-1 text-xl font-bold text-rose-600 dark:text-rose-400">
                            {{ key === 'total_fines_due' ? `${currency} ${value}` : value }}
                        </p>
                    </div>
                </div>
                <div v-if="data.loans?.length" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Patron</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Barcode</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Campus</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Due</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Days Late</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Fine</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="l in data.loans" :key="l.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3">
                                    <Link :href="route('library.patrons.show', l.user_id)" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ l.patron }}
                                    </Link>
                                    <p class="text-xs text-gray-400">{{ l.email }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ l.title }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ l.barcode }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ l.campus }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ l.due_on }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-bold" :class="l.days_overdue > 14 ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400'">
                                        {{ l.days_overdue }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-medium">
                                    {{ l.fine_balance > 0 ? `${currency} ${l.fine_balance}` : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState v-else title="No overdue loans" description="Every active loan is within its due date. Nice work!" />
            </template>
        </div>
    </AuthenticatedLayout>
</template>
