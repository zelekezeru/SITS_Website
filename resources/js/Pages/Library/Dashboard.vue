<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import LineChart from '@/Components/Library/Charts/LineChart.vue';
import BarChart from '@/Components/Library/Charts/BarChart.vue';
import DonutChart from '@/Components/Library/Charts/DonutChart.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    personal: { type: Object, default: () => ({}) },
    analytics: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
    recent_books: { type: Array, default: () => [] },
    recent_activity: { type: Array, default: () => [] },
});

const dashboardSearchQuery = ref('');
const searchCatalog = () => {
    if (dashboardSearchQuery.value.trim()) {
        router.get(route('library.catalog.index'), { q: dashboardSearchQuery.value.trim() });
    }
};

const carouselRef = ref(null);
const scrollCarousel = (direction) => {
    if (carouselRef.value) {
        const scrollAmount = 300;
        carouselRef.value.scrollBy({
            left: direction === 'left' ? -scrollAmount : scrollAmount,
            behavior: 'smooth'
        });
    }
};


const page = usePage();
const auth = computed(() => page.props.auth);
const role = computed(() => auth.value.role);
const permissions = computed(() => auth.value.permissions ?? []);
const can = (p) => permissions.value.includes(p);

// ── Date range filtering ────────────────────────────────────────────────
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const filterAnalytics = () => {
    router.get(route('library.dashboard'), {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// ── Animated counters for KPIs ──────────────────────────────────────────
const animatedKpis = reactive({
    books: 0,
    copies: 0,
    active_loans: 0,
    overdue: 0,
    holds_waiting: 0,
    transfers_pending: 0,
    checkouts_today: 0,
    fines_outstanding: '0.00',
});

onMounted(() => {
    const kpis = props.analytics?.kpis || {};
    Object.keys(kpis).forEach(key => {
        const val = parseFloat(kpis[key]) || 0;
        
        const duration = 1000; // 1s animation
        const startTime = performance.now();
        
        const animate = (timestamp) => {
            const runtime = timestamp - startTime;
            const progress = Math.min(runtime / duration, 1);
            const ease = progress * (2 - progress); // easeOutQuad
            
            if (key === 'fines_outstanding') {
                animatedKpis[key] = (ease * val).toFixed(2);
            } else {
                animatedKpis[key] = Math.floor(ease * val);
            }
            
            if (runtime < duration) {
                requestAnimationFrame(animate);
            } else {
                animatedKpis[key] = key === 'fines_outstanding' ? val.toFixed(2) : val;
            }
        };
        
        requestAnimationFrame(animate);
    });
});

// ── Personal stat chips ──────────────────────────────────────────────────
const personalChips = computed(() => {
    const p = props.personal ?? {};
    const sym = props.analytics?.currency ?? '';
    return [
        { label: 'Active loans', value: p.active_loans ?? 0, route: 'my.loans', color: 'text-indigo-600 dark:text-indigo-400' },
        { label: 'Due soon', value: p.due_soon ?? 0, route: 'my.loans', color: 'text-amber-600 dark:text-amber-400' },
        { label: 'Overdue', value: p.overdue ?? 0, route: 'my.loans', color: 'text-red-600 dark:text-red-400' },
        { label: 'Holds', value: p.holds ?? 0, route: 'my.holds', color: 'text-green-600 dark:text-green-400' },
        { label: 'Fines due', value: (p.fines_due ? `${sym} ${p.fines_due}` : `${sym} 0`), route: 'my.fines', color: 'text-rose-600 dark:text-rose-400' },
    ];
});

// ── Analytics KPIs & charts ──────────────────────────────────────────────
const kpiCards = computed(() => {
    const a = props.analytics;
    if (!a) return [];
    return [
        { label: 'Titles', value: animatedKpis.books, color: 'indigo' },
        { label: 'Copies', value: animatedKpis.copies, color: 'blue' },
        { label: 'Active loans', value: animatedKpis.active_loans, color: 'violet' },
        { label: 'Overdue', value: animatedKpis.overdue, color: 'red' },
        { label: 'Holds waiting', value: animatedKpis.holds_waiting, color: 'green' },
        { label: 'Transfers pending', value: animatedKpis.transfers_pending, color: 'sky' },
        { label: 'Checkouts today', value: animatedKpis.checkouts_today, color: 'amber' },
        { label: 'Fines outstanding', value: `${a.currency} ${animatedKpis.fines_outstanding}`, color: 'rose' },
    ];
});

const statusColors = {
    active: '#6366f1',
    returned: '#10b981',
    overdue_returned: '#f59e0b',
    lost: '#ef4444',
};
const loanStatusSegments = computed(() => {
    const m = props.analytics?.loans_by_status ?? {};
    return Object.entries(m).map(([label, value]) => ({
        label: label.replace(/_/g, ' '),
        value,
        color: statusColors[label] ?? '#94a3b8',
    }));
});

const topTitleItems = computed(() =>
    (props.analytics?.top_titles ?? []).map((t) => ({ label: t.title, value: t.loans }))
);
const campusItems = computed(() =>
    (props.analytics?.copies_by_campus ?? []).map((c) => ({ label: c.campus, value: c.copies }))
);

const kpiColorMap = {
    indigo: 'text-indigo-600 dark:text-indigo-400',
    blue: 'text-blue-600 dark:text-blue-400',
    violet: 'text-violet-600 dark:text-violet-400',
    red: 'text-red-600 dark:text-red-400',
    green: 'text-green-600 dark:text-green-400',
    sky: 'text-sky-600 dark:text-sky-400',
    amber: 'text-amber-600 dark:text-amber-400',
    rose: 'text-rose-600 dark:text-rose-400',
};

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'Good morning';
    if (h < 17) return 'Good afternoon';
    return 'Good evening';
});

// Role-specific quick actions
const quickActions = computed(() => {
    const all = [
        { label: 'Browse Catalog',   route: 'catalog.index',       icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', permission: 'view_books',       color: 'indigo' },
        { label: 'External Library', route: 'sso.redirect', params: { destination: 'library' }, target: '_blank', icon: 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14', permission: 'view_books', color: 'sky' },
        { label: 'Checkout Desk',    route: 'circulation.desk',     icon: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z', permission: 'checkout_book',    color: 'blue' },
        { label: 'Process Returns',  route: 'circulation.returns',  icon: 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6', permission: 'return_book',      color: 'green' },
        { label: 'Add Book',         route: 'books.create',         icon: 'M12 4v16m8-8H4', permission: 'create_book',      color: 'violet' },
        { label: 'Scan & Place',     route: 'scan.place',           icon: 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z', permission: 'create_book',      color: 'amber' },
        { label: 'Transfers',        route: 'transfers.index',      icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4', permission: 'request_transfer', color: 'sky' },
        { label: 'Add Resource',     route: 'admin.resources.create', icon: 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z', permission: 'manage_external_links', color: 'blue' },
        { label: 'Digital Archive',  route: 'archive.index',        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', permission: 'view_secure_pdf',  color: 'rose' },
        { label: 'Manage Users',     route: 'users.index',          icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', permission: 'manage_users',     color: 'purple' },
        { label: 'Campuses',         route: 'campuses.index',       icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', permission: 'manage_campus',    color: 'teal' },
    ];
    return all.filter(a => can(a.permission));
});

// Self-service cards (always visible)
const selfServiceCards = [
    { label: 'My Loans',  route: 'my.loans',  icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',   desc: 'View your current checkouts & due dates' },
    { label: 'My Holds',  route: 'my.holds',  icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', desc: 'Track your reserved items' },
    { label: 'Resources', route: 'resources.index', icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1', desc: 'Access databases & online tools' },
];

const colorMap = {
    indigo: 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400',
    blue:   'bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400',
    green:  'bg-green-50 dark:bg-green-950 text-green-600 dark:text-green-400',
    violet: 'bg-violet-50 dark:bg-violet-950 text-violet-600 dark:text-violet-400',
    amber:  'bg-amber-50 dark:bg-amber-950 text-amber-600 dark:text-amber-400',
    sky:    'bg-sky-50 dark:bg-sky-950 text-sky-600 dark:text-sky-400',
    rose:   'bg-rose-50 dark:bg-rose-950 text-rose-600 dark:text-rose-400',
    purple: 'bg-purple-50 dark:bg-purple-950 text-purple-600 dark:text-purple-400',
    teal:   'bg-teal-50 dark:bg-teal-950 text-teal-600 dark:text-teal-400',
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Dashboard') }}</h1>
        </template>

        <div class="p-6 space-y-8 max-w-7xl mx-auto">

            <!-- Greeting and Student Welcome Hero -->
            <div class="space-y-4">
                <div v-if="!analytics" class="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-indigo-900 via-indigo-950 to-slate-900 p-8 text-white border border-indigo-950 dark:border-gray-800 shadow-2xl">
                    <!-- Deco element -->
                    <div class="absolute right-0 top-0 -mt-10 -mr-10 h-40 w-40 rounded-full bg-indigo-500/10 blur-3xl"></div>
                    <div class="absolute left-1/3 bottom-0 -mb-20 h-56 w-56 rounded-full bg-purple-500/10 blur-3xl"></div>
                    
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div class="space-y-2">
                            <span class="inline-flex items-center rounded-full bg-indigo-500/20 px-3 py-1 text-xs font-semibold text-indigo-300 backdrop-blur-md">
                                {{ __(greeting) }}
                            </span>
                            <h2 class="text-3xl font-black tracking-tight text-white sm:text-4xl">
                                {{ __('Welcome, :name!', { name: auth.user.name.split(' ')[0] }) }}
                            </h2>
                            <p class="max-w-md text-sm text-indigo-200 leading-relaxed font-medium">
                                {{ __('"The only thing that you absolutely have to know, is the location of the library."') }} <span class="italic text-indigo-300 font-bold">— {{ __('Albert Einstein') }}</span>
                            </p>
                            <div class="pt-2">
                                <Link
                                    v-if="can('view_books')"
                                    :href="route('portal', { destination: 'library' })"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2 text-xs font-bold text-white hover:bg-white/20 border border-white/10 hover:border-white/25 shadow-sm transition active:scale-95 duration-200"
                                >
                                    <svg class="h-4 w-4 text-indigo-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    {{ __('Go to External Library (SSO)') }}
                                </Link>
                            </div>
                        </div>
                        
                        <!-- Search console inside hero -->
                        <div class="w-full md:max-w-md shrink-0 bg-white/5 p-4 rounded-3xl border border-white/10 backdrop-blur-md shadow-inner">
                            <p class="text-xs font-bold text-indigo-200 uppercase tracking-widest mb-2">{{ __('Find your next read') }}</p>
                            <form @submit.prevent="searchCatalog" class="relative">
                                <input 
                                    v-model="dashboardSearchQuery" 
                                    type="text" 
                                    :placeholder="__('Search catalog by title, author, or ISBN...')" 
                                    class="w-full pl-11 pr-4 py-3 text-xs bg-white dark:bg-gray-950 border border-transparent dark:border-gray-800 rounded-2xl text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-inner font-bold"
                                />
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-405">
                                    <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                </span>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Simple greeting if staff with analytics -->
                <div v-else>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ __('Welcome, :name!', { name: auth.user.name.split(' ')[0] }) }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Welcome to SITS Library. Here\'s your overview.') }}
                    </p>
                </div>
            </div>

            <!-- Personal stat chips (everyone) -->
            <section>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <Link
                        v-for="chip in personalChips"
                        :key="chip.label"
                        :href="route(chip.route)"
                        class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-sm transition"
                    >
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __(chip.label) }}</p>
                        <p class="mt-1 text-2xl font-bold" :class="chip.color">{{ chip.value }}</p>
                    </Link>
                </div>
            </section>

            <!-- Analytics (staff with circulation visibility) -->
            <section v-if="analytics" class="space-y-5">
                <!-- Filters and Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">{{ __('Library Analytics') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Operational trends and collection statistics') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <input v-model="startDate" type="date" class="text-xs bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 focus:ring-indigo-500 focus:border-indigo-500" />
                        <span class="text-xs text-gray-400">{{ __('to') }}</span>
                        <input v-model="endDate" type="date" class="text-xs bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 focus:ring-indigo-500 focus:border-indigo-500" />
                        <button @click="filterAnalytics" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold uppercase transition active:scale-95">
                            {{ __('Filter') }}
                        </button>
                    </div>
                </div>

                <!-- KPI cards -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div
                        v-for="kpi in kpiCards"
                        :key="kpi.label"
                        class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"
                    >
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __(kpi.label) }}</p>
                        <p class="mt-1 text-xl font-bold" :class="kpiColorMap[kpi.color]">{{ kpi.value }}</p>
                    </div>
                </div>

                <!-- Charts -->
                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 lg:col-span-2">
                        <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Circulation — last 14 days') }}</p>
                        <LineChart :data="analytics.checkouts_trend" />
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
                        <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Loans by status') }}</p>
                        <DonutChart :segments="loanStatusSegments" />
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
                        <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Most borrowed titles') }}</p>
                        <BarChart :items="topTitleItems" bar-class="bg-indigo-500" />
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 lg:col-span-2">
                        <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Copies by campus') }}</p>
                        <BarChart :items="campusItems" bar-class="bg-teal-500" />
                    </div>

                    <!-- Recent Activity Card -->
                    <div v-if="recent_activity && recent_activity.length" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 flex flex-col h-[380px]">
                        <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Recent System Activity') }}</p>
                        <div class="space-y-4 overflow-y-auto flex-1 pr-1">
                            <div v-for="act in recent_activity" :key="act.id" class="flex items-start gap-3 text-xs">
                                <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold uppercase shrink-0">
                                    {{ act.causer_name.charAt(0) }}
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-gray-700 dark:text-gray-300 font-medium leading-relaxed text-left">
                                        <span class="font-bold text-gray-900 dark:text-white">{{ act.causer_name }}</span> 
                                        {{ act.description }}
                                    </p>
                                    <p class="text-[10px] text-gray-400 font-medium text-left">{{ act.created_at }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Recently Added Books Carousel -->
            <section v-if="recent_books && recent_books.length" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">{{ __('Recently Added Titles') }}</h3>
                    <div class="flex gap-1.5 shrink-0">
                        <button 
                            @click="scrollCarousel('left')" 
                            class="p-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-500 hover:text-indigo-650 transition shadow-sm active:scale-90"
                            title="Scroll left"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button 
                            @click="scrollCarousel('right')" 
                            class="p-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-500 hover:text-indigo-650 transition shadow-sm active:scale-90"
                            title="Scroll right"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div 
                    ref="carouselRef"
                    class="flex gap-4 overflow-x-auto pb-4 scrollbar-none scroll-smooth"
                >
                    <Link
                        v-for="book in recent_books"
                        :key="book.id"
                        :href="route('library.catalog.show', book.id)"
                        class="flex-shrink-0 w-44 group bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-md transition-all active:scale-98"
                    >
                        <div class="aspect-[3/4] bg-gray-50 dark:bg-gray-800/30 relative overflow-hidden border-b border-gray-100 dark:border-gray-800">
                            <img v-if="book.cover_path" :src="'/storage/' + book.cover_path" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" />
                            <img v-else-if="book.cover_url" :src="book.cover_url" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" />
                            <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-300 dark:text-gray-700">
                                <svg class="w-10 h-10 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                <span class="text-[9px] font-bold uppercase tracking-wider">{{ __('No Cover') }}</span>
                            </div>
                        </div>
                        <div class="p-3.5 space-y-1">
                            <p class="text-xs font-bold text-gray-900 dark:text-gray-100 line-clamp-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition text-left">{{ book.title }}</p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate text-left">{{ book.authors || __('Unknown Author') }}</p>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Quick Actions (permission-gated) -->
            <section v-if="quickActions.length">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">{{ __('Quick Actions') }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                    <Link
                        v-for="action in quickActions"
                        :key="action.route"
                        :href="route(action.route, action.params || {})"
                        :target="action.target || '_self'"
                        class="group flex flex-col items-center gap-2.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 text-center hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-sm transition"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl" :class="colorMap[action.color]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="action.icon" />
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white leading-tight">
                            {{ __(action.label) }}
                        </span>
                    </Link>
                </div>
            </section>

            <!-- Self-service (all users) -->
            <section>
                <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">{{ __('My Library') }}</h3>
                <div class="grid gap-4 sm:grid-cols-3">
                    <Link
                        v-for="card in selfServiceCards"
                        :key="card.route"
                        :href="route(card.route)"
                        class="group flex items-start gap-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-sm transition"
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __(card.label) }}</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __(card.desc) }}</p>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Admin panel hint for super_admin / campus_admin -->
            <section v-if="role === 'super_admin' || role === 'campus_admin'">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">{{ __('Administration') }}</h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-if="can('manage_users')"
                        :href="route('library.users.index')"
                        class="group flex items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3.5 hover:border-purple-300 dark:hover:border-purple-700 hover:shadow-sm transition"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-950 text-purple-600 dark:text-purple-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Manage Users') }}</span>
                    </Link>
                    <Link
                        v-if="can('manage_campus')"
                        :href="route('library.campuses.index')"
                        class="group flex items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3.5 hover:border-teal-300 dark:hover:border-teal-700 hover:shadow-sm transition"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-teal-50 dark:bg-teal-950 text-teal-600 dark:text-teal-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Campuses') }}</span>
                    </Link>
                    <Link
                        v-if="can('manage_external_links')"
                        :href="route('library.resources.index')"
                        class="group flex items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3.5 hover:border-sky-300 dark:hover:border-sky-700 hover:shadow-sm transition"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-50 dark:bg-sky-950 text-sky-600 dark:text-sky-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Ext. Resources') }}</span>
                    </Link>
                    <Link
                        v-if="can('manage_campus')"
                        :href="route('library.admin.audit')"
                        class="group flex items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3.5 hover:border-rose-300 dark:hover:border-rose-700 hover:shadow-sm transition"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-50 dark:bg-rose-950 text-rose-600 dark:text-rose-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Audit Log') }}</span>
                    </Link>
                </div>
            </section>

        </div>
    </AuthenticatedLayout>
</template>
