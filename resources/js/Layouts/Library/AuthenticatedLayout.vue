<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/Library/ApplicationLogo.vue';
import NotificationBell from '@/Components/Library/NotificationBell.vue';
import Toast from '@/Components/Library/Toast.vue';
import GlobalSearch from '@/Components/Library/GlobalSearch.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useDarkMode } from '@/composables/useDarkMode';
import LanguageSwitcher from '@/Components/Library/LanguageSwitcher.vue';
import Icon from '@/Components/Icon.vue';

const page = usePage();
const translations = computed(() => page.props.translations || {});
const t = (key, replace = {}) => {
    let trans = translations.value[key] || key;
    Object.keys(replace).forEach((r) => {
        trans = trans.replace(`:${r}`, replace[r]);
    });
    return trans;
};
const auth = computed(() => page.props.auth);
const role = computed(() => auth.value.role);
const permissions = computed(() => auth.value.permissions ?? []);
const flash = computed(() => page.props.flash ?? {});

const can = (permission) => permissions.value.includes(permission);

const sidebarOpen = ref(false);
const { isDark, toggle: toggleDarkMode } = useDarkMode();

const userAvatar = computed(() => {
    const img = auth.value?.user?.profile_image;
    return img ? `/storage/${img}` : '/img/user.png';
});

const hasErpAccess = computed(() => {
    const roles = (auth.value?.user?.roles ?? []).map(r => r.toLowerCase());
    if (!roles.length) return false;
    return !roles.includes('student');
});

const isWebsiteAdmin = computed(() => {
    const roles = (auth.value?.user?.roles ?? []).map(r => r.toLowerCase());
    return roles.some(r => ['superadmin', 'admin', 'editor'].includes(r));
});

const lmsLabel = computed(() => {
    const roles = (auth.value?.user?.roles ?? []).map(r => r.toLowerCase());
    if (roles.includes('student')) return t('Student Portal');
    if (roles.includes('trainer')) return t('Instructor Portal');
    return t('LMS Portal', 'LMS Portal');
});

const lmsUrl = computed(() => {
    const roles = (auth.value?.user?.roles ?? []).map(r => r.toLowerCase());
    if (roles.includes('student') || roles.includes('trainer')) {
        return '/go/lms';
    }
    return 'https://lms.sits.edu.et';
});

const hasLibraryAccess = computed(() => {
    const roles = (auth.value?.user?.roles ?? []).map(r => r.toLowerCase());
    const allowed = ['student', 'trainer', 'librarian', 'admin', 'superadmin', 'president / super admin', 'staff', 'editor'];
    return roles.some(r => allowed.includes(r));
});

const userMenuOpen = ref(false);
const userMenuRef = ref(null);

const handleOutsideClick = (e) => {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
        userMenuOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
});

onUnmounted(() => {
    document.removeEventListener('click', handleOutsideClick);
});

const loadExpandedState = () => {
    try {
        const saved = localStorage.getItem('sits_library_sidebar_expanded');
        return saved ? JSON.parse(saved) : {};
    } catch (e) {
        return {};
    }
};

const expandedGroups = ref(loadExpandedState());

const toggleGroup = (groupLabel) => {
    expandedGroups.value[groupLabel] = !isGroupExpanded(groupLabel);
    try {
        localStorage.setItem('sits_library_sidebar_expanded', JSON.stringify(expandedGroups.value));
    } catch (e) {}
};

const isGroupExpanded = (groupLabel) => {
    if (expandedGroups.value[groupLabel] === undefined) {
        const group = navGroups.value.find(g => g.label === groupLabel);
        const hasActiveItem = group?.items.some(item => route().current(item.route)) ?? false;
        if (groupLabel === 'Overview' || groupLabel === 'My Library' || hasActiveItem) {
            return true;
        }
        return false;
    }
    return expandedGroups.value[groupLabel];
};


const navGroups = computed(() => {
    const groups = [];

    groups.push({
        label: t('Overview'),
        items: [
            {
                label: t('Dashboard'),
                route: 'library.dashboard',
                icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            },
        ],
    });

    if (can('view_books')) {
        const items = [
            {
                label: t('Catalog'),
                route: 'library.catalog.index',
                icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
            },
        ];
        if (can('create_book')) {
            items.push({ label: t('Add Book'), route: 'library.books.create', icon: 'M12 4v16m8-8H4' });
            items.push({ label: t('Scan & Place'), route: 'library.scan.place', icon: 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z' });
        }
        groups.push({ label: t('Library'), items });
    }

    groups.push({
        label: t('My Library'),
        items: [
            { label: t('My Loans'), route: 'library.my.loans', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
            { label: t('My Holds'), route: 'library.my.holds', icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' },
            { label: t('My Fines'), route: 'library.my.fines', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' },
            { label: t('Resources'), route: 'library.resources.index', icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1' },
        ],
    });

    if (can('checkout_book') || can('return_book') || can('collect_fine')) {
        const items = [];
        if (can('checkout_book')) items.push({ label: t('Checkout Desk'), route: 'library.circulation.desk', icon: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z' });
        if (can('return_book')) items.push({ label: t('Returns'), route: 'library.circulation.returns', icon: 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6' });
        if (can('collect_fine')) items.push({ label: t('Fines'), route: 'library.fines.index', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' });
        groups.push({ label: t('Circulation'), items });
    }

    if (can('request_transfer') || can('approve_transfer') || can('receive_transfer')) {
        groups.push({
            label: t('Transfers'),
            items: [{ label: t('Transfers'), route: 'library.transfers.index', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' }],
        });
    }

    if (can('view_secure_pdf')) {
        groups.push({
            label: t('Digital Archive'),
            items: [{ label: t('Archive'), route: 'library.archive.index', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' }],
        });
    }

    const adminItems = [];
    if (can('manage_users'))          adminItems.push({ label: t('Users'),           route: 'library.users.index',            icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' });
    if (can('manage_campus'))         adminItems.push({ label: t('Campuses'),        route: 'library.campuses.index',         icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' });
    if (can('manage_campus'))         adminItems.push({ label: t('Subscriptions'),   route: 'library.subscriptions',          icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', external: true });
    if (can('manage_external_links')) adminItems.push({ label: t('Ext. Resources'),  route: 'library.resources.index',  icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' });
    if (can('manage_campus'))         adminItems.push({ label: t('Audit Log'),       route: 'library.admin.audit',            icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' });
    if (can('view_loans'))            adminItems.push({ label: t('Reports'),          route: 'library.reports.index',          icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' });
    if (can('manage_shelf_box'))      adminItems.push({ label: t('Stocktake'),        route: 'library.stocktakes.index',       icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' });
    if (adminItems.length) groups.push({ label: t('Administration'), items: adminItems });

    return groups;
});

const roleBadge = computed(() => {
    const map = {
        super_admin:  { label: 'Super Admin',  cls: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' },
        campus_admin: { label: 'Campus Admin', cls: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' },
        librarian:    { label: 'Librarian',    cls: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' },
        instructor:   { label: 'Instructor',   cls: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' },
        staff_user:   { label: 'Staff',        cls: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' },
        student:      { label: 'Student',      cls: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' },
    };
    const details = map[role.value] ?? { label: 'User', cls: 'bg-gray-100 text-gray-600' };
    return {
        label: t(details.label),
        cls: details.cls
    };
});
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-950">

        <!-- Mobile sidebar backdrop -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-20 bg-black/40 lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- ── Sidebar ──────────────────────────────────────────────────── -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 transition-transform duration-200 lg:static lg:translate-x-0"
        >
            <!-- Logo -->
            <div class="flex h-16 shrink-0 items-center gap-3 px-5 border-b border-gray-200 dark:border-gray-800">
                <Link :href="route('library.dashboard')" class="flex items-center gap-2.5">
                    <ApplicationLogo class="h-7 w-7 fill-current text-indigo-600 dark:text-indigo-400" />
                    <span class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">SITS Library</span>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-4">
                <div v-for="group in navGroups" :key="group.label" class="space-y-1">
                    <button
                        @click="toggleGroup(group.label)"
                        class="w-full flex items-center justify-between mb-1 px-2 py-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 rounded transition text-left"
                    >
                        <span>{{ group.label }}</span>
                        <svg
                            class="h-3 w-3 transform transition-transform duration-200"
                            :class="isGroupExpanded(group.label) ? 'rotate-0' : '-rotate-90'"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div
                        class="grid transition-all duration-300 ease-in-out"
                        :class="isGroupExpanded(group.label) ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0 pointer-events-none'"
                    >
                        <div class="overflow-hidden">
                            <ul class="space-y-0.5 px-0.5">
                                <li v-for="item in group.items" :key="item.route" class="relative">
                                    <div v-if="route().current(item.route)" class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r bg-indigo-600 dark:bg-indigo-400"></div>
                                    <component
                                        :is="item.external ? 'a' : Link"
                                        :href="route(item.route, item.params || {})"
                                        :target="item.target || '_self'"
                                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition duration-200"
                                        :class="route().current(item.route)
                                            ? 'bg-indigo-50/80 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold shadow-sm shadow-indigo-100/10'
                                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/60 hover:text-gray-900 dark:hover:text-gray-200'"
                                    >
                                        <svg class="h-[17px] w-[17px] shrink-0 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                        </svg>
                                        {{ item.label }}
                                    </component>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- User footer -->
            <div class="shrink-0 border-t border-gray-200 dark:border-gray-800 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white text-sm font-semibold">
                        {{ auth.user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ auth.user.name }}</p>
                        <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold" :class="roleBadge.cls">
                            {{ roleBadge.label }}
                        </span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ── Main content ─────────────────────────────────────────────── -->
        <div class="flex flex-1 flex-col min-w-0 overflow-hidden">

            <!-- Top bar -->
            <header class="flex h-16 shrink-0 items-center gap-2 sm:gap-4 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 sm:px-6">
                <button
                    class="lg:hidden rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    @click="sidebarOpen = !sidebarOpen"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="hidden sm:block flex-1 min-w-0 [&_h2]:truncate [&_h2]:text-sm [&_h2]:sm:text-xl [&_h2]:font-semibold">
                    <slot name="header" />
                </div>

                <div class="flex items-center gap-1.5 sm:gap-2">
                    <GlobalSearch />
                    <LanguageSwitcher />
                    <NotificationBell />
                    <button
                        @click="toggleDarkMode"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition mr-2"
                        :title="isDark ? t('Switch to light mode') : t('Switch to dark mode')"
                    >
                        <svg v-if="isDark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M16.95 16.95l.707.707M7.05 7.05l.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    </button>

                    <!-- User Profile Dropdown -->
                    <div class="relative" ref="userMenuRef">
                      <button @click="userMenuOpen = !userMenuOpen"
                        class="flex items-center gap-2 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition focus:outline-none cursor-pointer">
                        <img :src="userAvatar" alt="Profile" class="h-8 w-8 rounded-full object-cover ring-2 ring-indigo-500/20" />
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 hidden sm:inline-block">{{ auth.user.name }}</span>
                        <svg class="w-3 h-3 text-gray-500 hidden sm:block transition-transform duration-200" :class="{'rotate-180': userMenuOpen}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                      </button>

                      <Transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                        <ul v-if="userMenuOpen" class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-2xl py-2 z-50">
                          <li class="px-4 py-2 border-b border-gray-100 dark:border-gray-800 mb-1 sm:hidden">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ t('Logged in as') }}</p>
                            <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">{{ auth.user.name }}</p>
                          </li>
                          <li><Link :href="route('portal')" class="block px-4 py-2.5 text-xs text-gray-650 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">{{ t('Dashboard Hub', 'Dashboard Hub') }}</Link></li>
                          <li><Link :href="route('profile.edit', { from: 'library' })" class="block px-4 py-2.5 text-xs text-gray-650 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">{{ t('View Profile', 'View Profile') }}</Link></li>
                          <li class="border-t border-gray-150 dark:border-gray-800 my-1"></li>
                          <li v-if="hasErpAccess">
                            <Link :href="route('dashboard')" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-650 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                              <Icon name="LayoutDashboard" :size="15" class="text-gray-400 dark:text-gray-500" />
                              <span>SITS ERP</span>
                            </Link>
                          </li>
                          <li>
                            <Link :href="route('library.dashboard')" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-650 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                              <Icon name="BookOpen" :size="15" class="text-gray-400 dark:text-gray-500" />
                              <span>Digital Library</span>
                            </Link>
                          </li>
                          <li>
                            <a href="https://lms.sits.edu.et" target="_blank" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-650 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                              <Icon name="GraduationCap" :size="15" class="text-gray-400 dark:text-gray-500" />
                              <span>SITS LMS</span>
                            </a>
                          </li>
                          <li>
                            <a href="/go/lms" target="_blank" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-650 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                              <Icon name="Laptop" :size="15" class="text-gray-400 dark:text-gray-500" />
                              <span>Moodle</span>
                            </a>
                          </li>
                          <li v-if="isWebsiteAdmin">
                            <a :href="route('website.admin.dashboard')" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-650 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                              <Icon name="Globe" :size="15" class="text-gray-400 dark:text-gray-500" />
                              <span>Website Admin</span>
                            </a>
                          </li>
                          <li class="border-t border-gray-150 dark:border-gray-800 my-1"></li>
                          <li>
                            <Link :href="route('logout')" method="post" as="button" class="block w-full text-left px-4 py-2.5 text-xs text-rose-500 hover:text-rose-455 hover:bg-rose-500/10 transition cursor-pointer">{{ t('Sign out') }}</Link>
                          </li>
                        </ul>
                      </Transition>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto">
                <!-- Flash messages -->
                <div v-if="flash.success || flash.error" class="px-4 pt-4 sm:px-6">
                    <div
                        class="mx-auto max-w-7xl rounded-lg border px-4 py-3 text-sm"
                        :class="flash.success
                            ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-300'
                            : 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300'"
                    >
                        {{ flash.success || flash.error }}
                    </div>
                </div>
                <slot />
            </main>
        </div>

    </div>

    <!-- Toast notifications (replaces inline flash) -->
    <Toast />
</template>
