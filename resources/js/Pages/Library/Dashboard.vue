<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import LineChart from '@/Components/Library/Charts/LineChart.vue';
import BarChart from '@/Components/Library/Charts/BarChart.vue';
import DonutChart from '@/Components/Library/Charts/DonutChart.vue';
import Icon from '@/Components/Icon.vue';
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
        { label: 'Active loans', value: p.active_loans ?? 0, route: 'library.my.loans', icon: 'BookMarked', color: 'indigo' },
        { label: 'Due soon', value: p.due_soon ?? 0, route: 'library.my.loans', icon: 'Clock', color: 'amber' },
        { label: 'Overdue', value: p.overdue ?? 0, route: 'library.my.loans', icon: 'AlertTriangle', color: 'red' },
        { label: 'Holds', value: p.holds ?? 0, route: 'library.my.holds', icon: 'Bookmark', color: 'emerald' },
        { label: 'Fines due', value: (p.fines_due ? `${sym} ${p.fines_due}` : `${sym} 0`), route: 'library.my.fines', icon: 'ReceiptText', color: 'rose' },
    ];
});

// ── Analytics KPIs & charts ──────────────────────────────────────────────
const kpiCards = computed(() => {
    const a = props.analytics;
    if (!a) return [];
    return [
        { label: 'Titles', value: animatedKpis.books, icon: 'BookText', color: 'indigo' },
        { label: 'Copies', value: animatedKpis.copies, icon: 'BookCopy', color: 'blue' },
        { label: 'Active loans', value: animatedKpis.active_loans, icon: 'BookMarked', color: 'violet' },
        { label: 'Overdue', value: animatedKpis.overdue, icon: 'AlertTriangle', color: 'red' },
        { label: 'Holds waiting', value: animatedKpis.holds_waiting, icon: 'Bookmark', color: 'emerald' },
        { label: 'Transfers pending', value: animatedKpis.transfers_pending, icon: 'ArrowLeftRight', color: 'sky' },
        { label: 'Checkouts today', value: animatedKpis.checkouts_today, icon: 'ScanBarcode', color: 'amber' },
        { label: 'Fines outstanding', value: `${a.currency} ${animatedKpis.fines_outstanding}`, icon: 'HandCoins', color: 'rose' },
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

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'Good morning';
    if (h < 17) return 'Good afternoon';
    return 'Good evening';
});

// Role-specific quick actions
const quickActions = computed(() => {
    const all = [
        { label: 'Browse Catalog',  route: 'library.catalog.index',          icon: 'LibraryBig',    permission: 'view_books',            color: 'indigo' },
        { label: 'Checkout Desk',   route: 'library.circulation.desk',       icon: 'ScanBarcode',   permission: 'checkout_book',         color: 'blue' },
        { label: 'Process Returns', route: 'library.circulation.returns',    icon: 'Undo2',         permission: 'return_book',           color: 'emerald' },
        { label: 'Add Book',        route: 'library.books.create',           icon: 'BookPlus',      permission: 'create_book',           color: 'violet' },
        { label: 'Scan & Place',    route: 'library.scan.place',             icon: 'ScanLine',      permission: 'create_book',           color: 'amber' },
        { label: 'Transfers',       route: 'library.transfers.index',        icon: 'ArrowLeftRight',permission: 'request_transfer',      color: 'sky' },
        { label: 'Add Resource',    route: 'library.admin.resources.create', icon: 'Plus',          permission: 'manage_external_links', color: 'blue' },
        { label: 'Digital Archive', route: 'library.archive.index',          icon: 'Archive',       permission: 'view_secure_pdf',       color: 'rose' },
        { label: 'Manage Users',    route: 'library.users.index',            icon: 'Users',         permission: 'manage_users',          color: 'purple' },
        { label: 'Campuses',        route: 'library.campuses.index',         icon: 'Building2',     permission: 'manage_campus',         color: 'teal' },
    ];
    return all.filter(a => can(a.permission));
});

// Self-service cards (always visible)
const selfServiceCards = [
    { label: 'My Loans',  route: 'library.my.loans',       icon: 'BookMarked', desc: 'View your current checkouts & due dates' },
    { label: 'My Holds',  route: 'library.my.holds',       icon: 'Bookmark',   desc: 'Track your reserved items' },
    { label: 'Resources', route: 'library.resources.index', icon: 'Link2',     desc: 'Access databases & online tools' },
];

const chipColorMap = {
    indigo:  'text-indigo-600 dark:text-indigo-400',
    amber:   'text-amber-600 dark:text-amber-400',
    red:     'text-red-600 dark:text-red-400',
    emerald: 'text-emerald-600 dark:text-emerald-400',
    rose:    'text-rose-600 dark:text-rose-400',
    blue:    'text-blue-600 dark:text-blue-400',
    violet:  'text-violet-600 dark:text-violet-400',
    sky:     'text-sky-600 dark:text-sky-400',
};

const iconTintMap = {
    indigo:  'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
    blue:    'bg-blue-500/10 text-blue-600 dark:text-blue-400',
    emerald: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    violet:  'bg-violet-500/10 text-violet-600 dark:text-violet-400',
    amber:   'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    sky:     'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    rose:    'bg-rose-500/10 text-rose-600 dark:text-rose-400',
    red:     'bg-red-500/10 text-red-600 dark:text-red-400',
    purple:  'bg-purple-500/10 text-purple-600 dark:text-purple-400',
    teal:    'bg-teal-500/10 text-teal-600 dark:text-teal-400',
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2>{{ __('Dashboard') }}</h2>
        </template>

        <div class="p-4 sm:p-6 lg:p-8 space-y-8 max-w-7xl mx-auto">

            <!-- Greeting / Hero -->
            <div class="space-y-4">
                <div v-if="!analytics" class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-800 to-violet-900 p-8 text-white shadow-2xl shadow-indigo-900/30">
                    <div class="absolute right-0 top-0 -mt-10 -mr-10 h-40 w-40 rounded-full bg-indigo-400/20 blur-3xl"></div>
                    <div class="absolute left-1/3 bottom-0 -mb-20 h-56 w-56 rounded-full bg-violet-400/20 blur-3xl"></div>

                    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div class="space-y-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-indigo-100 backdrop-blur-md">
                                <Icon name="Sparkles" :size="13" /> {{ __(greeting) }}
                            </span>
                            <h2 class="text-3xl font-black tracking-tight text-white sm:text-4xl">
                                {{ __('Welcome, :name!', { name: auth.user.name.split(' ')[0] }) }}
                            </h2>
                            <p class="max-w-md text-sm text-indigo-100/90 leading-relaxed font-medium">
                                {{ __('"The only thing that you absolutely have to know, is the location of the library."') }}
                                <span class="italic text-indigo-200 font-bold">— {{ __('Albert Einstein') }}</span>
                            </p>
                            <div class="pt-1">
                                <Link
                                    v-if="can('view_books')"
                                    :href="route('library.catalog.index')"
                                    class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2 text-xs font-bold text-white hover:bg-white/20 border border-white/15 hover:border-white/30 shadow-sm transition active:scale-95 duration-200"
                                >
                                    <Icon name="LibraryBig" :size="15" class="text-indigo-200" />
                                    {{ __('Browse the Catalog') }}
                                </Link>
                            </div>
                        </div>

                        <!-- Search console inside hero -->
                        <div class="w-full md:max-w-md shrink-0 bg-white/10 p-4 rounded-2xl border border-white/15 backdrop-blur-md shadow-inner">
                            <p class="text-xs font-bold text-indigo-100 uppercase tracking-widest mb-2">{{ __('Find your next read') }}</p>
                            <form @submit.prevent="searchCatalog" class="relative">
                                <input
                                    v-model="dashboardSearchQuery"
                                    type="text"
                                    :placeholder="__('Search catalog by title, author, or ISBN...')"
                                    class="w-full pl-11 pr-4 py-3 text-xs bg-white dark:bg-slate-950 border border-transparent rounded-xl text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-white/40 transition-all shadow-inner font-semibold"
                                />
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                    <Icon name="Search" :size="16" />
                                </span>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Simple greeting if staff with analytics -->
                <div v-else>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                        {{ __('Welcome, :name!', { name: auth.user.name.split(' ')[0] }) }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
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
                        class="group rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 hover:border-indigo-400/60 dark:hover:border-indigo-600/60 hover:shadow-lg hover:shadow-indigo-500/5 transition"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __(chip.label) }}</p>
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg" :class="iconTintMap[chip.color]">
                                <Icon :name="chip.icon" :size="15" />
                            </span>
                        </div>
                        <p class="mt-2 text-2xl font-bold tracking-tight" :class="chipColorMap[chip.color]">{{ chip.value }}</p>
                    </Link>
                </div>
            </section>

            <!-- Analytics (staff with circulation visibility) -->
            <section v-if="analytics" class="space-y-5">
                <!-- Filters and Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ __('Library Analytics') }}</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Operational trends and collection statistics') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <input v-model="startDate" type="date" class="text-xs bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 focus:ring-indigo-500 focus:border-indigo-500" />
                        <span class="text-xs text-slate-400">{{ __('to') }}</span>
                        <input v-model="endDate" type="date" class="text-xs bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 focus:ring-indigo-500 focus:border-indigo-500" />
                        <button @click="filterAnalytics" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold uppercase tracking-wide transition active:scale-95">
                            <Icon name="Search" :size="14" /> {{ __('Filter') }}
                        </button>
                    </div>
                </div>

                <!-- KPI cards -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div
                        v-for="kpi in kpiCards"
                        :key="kpi.label"
                        class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __(kpi.label) }}</p>
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg" :class="iconTintMap[kpi.color]">
                                <Icon :name="kpi.icon" :size="16" />
                            </span>
                        </div>
                        <p class="mt-2 text-xl font-bold tracking-tight" :class="chipColorMap[kpi.color]">{{ kpi.value }}</p>
                    </div>
                </div>

                <!-- Charts -->
                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 lg:col-span-2">
                        <p class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">{{ __('Circulation — last 14 days') }}</p>
                        <LineChart :data="analytics.checkouts_trend" />
                    </div>
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                        <p class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">{{ __('Loans by status') }}</p>
                        <DonutChart :segments="loanStatusSegments" />
                    </div>
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                        <p class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">{{ __('Most borrowed titles') }}</p>
                        <BarChart :items="topTitleItems" bar-class="bg-indigo-500" />
                    </div>
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 lg:col-span-2">
                        <p class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">{{ __('Copies by campus') }}</p>
                        <BarChart :items="campusItems" bar-class="bg-violet-500" />
                    </div>

                    <!-- Recent Activity Card -->
                    <div v-if="recent_activity && recent_activity.length" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 flex flex-col h-[380px]">
                        <p class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">{{ __('Recent System Activity') }}</p>
                        <div class="space-y-4 overflow-y-auto flex-1 pr-1">
                            <div v-for="act in recent_activity" :key="act.id" class="flex items-start gap-3 text-xs">
                                <div class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold uppercase shrink-0">
                                    {{ act.causer_name.charAt(0) }}
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-slate-700 dark:text-slate-300 font-medium leading-relaxed text-left">
                                        <span class="font-bold text-slate-900 dark:text-white">{{ act.causer_name }}</span>
                                        {{ act.description }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 font-medium text-left">{{ act.created_at }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Recently Added Books Carousel -->
            <section v-if="recent_books && recent_books.length" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ __('Recently Added Titles') }}</h3>
                    <div class="flex gap-1.5 shrink-0">
                        <button @click="scrollCarousel('left')" class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-500 hover:text-indigo-600 transition active:scale-90" title="Scroll left">
                            <Icon name="ArrowLeft" :size="15" />
                        </button>
                        <button @click="scrollCarousel('right')" class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-500 hover:text-indigo-600 transition active:scale-90" title="Scroll right">
                            <Icon name="ArrowRight" :size="15" />
                        </button>
                    </div>
                </div>

                <div ref="carouselRef" class="flex gap-4 overflow-x-auto pb-4 scrollbar-none scroll-smooth">
                    <Link
                        v-for="book in recent_books"
                        :key="book.id"
                        :href="route('library.catalog.show', book.id)"
                        class="flex-shrink-0 w-44 group bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden hover:border-indigo-400/60 dark:hover:border-indigo-600/60 hover:shadow-lg transition-all"
                    >
                        <div class="aspect-[3/4] bg-slate-50 dark:bg-slate-800/40 relative overflow-hidden border-b border-slate-100 dark:border-slate-800">
                            <img v-if="book.cover_path" :src="'/storage/' + book.cover_path" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" />
                            <img v-else-if="book.cover_url" :src="book.cover_url" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" />
                            <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-300 dark:text-slate-700">
                                <Icon name="BookOpen" :size="36" class="mb-1" />
                                <span class="text-[9px] font-bold uppercase tracking-wider">{{ __('No Cover') }}</span>
                            </div>
                        </div>
                        <div class="p-3.5 space-y-1">
                            <p class="text-xs font-bold text-slate-900 dark:text-slate-100 line-clamp-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition text-left">{{ book.title }}</p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate text-left">{{ book.authors || __('Unknown Author') }}</p>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Quick Actions (permission-gated) -->
            <section v-if="quickActions.length">
                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-3">{{ __('Quick Actions') }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                    <Link
                        v-for="action in quickActions"
                        :key="action.route"
                        :href="route(action.route, action.params || {})"
                        :target="action.target || '_self'"
                        class="group flex flex-col items-center gap-2.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 text-center hover:border-indigo-400/60 dark:hover:border-indigo-600/60 hover:shadow-lg hover:-translate-y-0.5 transition"
                    >
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl" :class="iconTintMap[action.color]">
                            <Icon :name="action.icon" :size="20" />
                        </div>
                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white leading-tight">
                            {{ __(action.label) }}
                        </span>
                    </Link>
                </div>
            </section>

            <!-- Self-service (all users) -->
            <section>
                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-3">{{ __('My Library') }}</h3>
                <div class="grid gap-4 sm:grid-cols-3">
                    <Link
                        v-for="card in selfServiceCards"
                        :key="card.route"
                        :href="route(card.route)"
                        class="group flex items-start gap-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 hover:border-indigo-400/60 dark:hover:border-indigo-600/60 hover:shadow-lg transition"
                    >
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-500/20 transition">
                            <Icon :name="card.icon" :size="20" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __(card.label) }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __(card.desc) }}</p>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Admin panel hint for super_admin / campus_admin -->
            <section v-if="role === 'super_admin' || role === 'campus_admin'">
                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-3">{{ __('Administration') }}</h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link v-if="can('manage_users')" :href="route('library.users.index')" class="group flex items-center gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3.5 hover:border-indigo-400/60 dark:hover:border-indigo-600/60 hover:shadow-lg transition">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400"><Icon name="Users" :size="17" /></div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Manage Users') }}</span>
                    </Link>
                    <Link v-if="can('manage_campus')" :href="route('library.campuses.index')" class="group flex items-center gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3.5 hover:border-indigo-400/60 dark:hover:border-indigo-600/60 hover:shadow-lg transition">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-500/10 text-teal-600 dark:text-teal-400"><Icon name="Building2" :size="17" /></div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Campuses') }}</span>
                    </Link>
                    <Link v-if="can('manage_external_links')" :href="route('library.resources.index')" class="group flex items-center gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3.5 hover:border-indigo-400/60 dark:hover:border-indigo-600/60 hover:shadow-lg transition">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400"><Icon name="Globe" :size="17" /></div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Ext. Resources') }}</span>
                    </Link>
                    <Link v-if="can('manage_campus')" :href="route('library.admin.audit')" class="group flex items-center gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3.5 hover:border-indigo-400/60 dark:hover:border-indigo-600/60 hover:shadow-lg transition">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400"><Icon name="ScrollText" :size="17" /></div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Audit Log') }}</span>
                    </Link>
                </div>
            </section>

        </div>
    </AuthenticatedLayout>
</template>
