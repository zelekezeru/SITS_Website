<script setup>
import { ref, computed, reactive, onMounted, onUnmounted, watch } from 'vue';
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

const { isDark, toggle: toggleDarkMode } = useDarkMode();

const userAvatar = computed(() => {
    const img = auth.value?.user?.profile_image;
    return img ? `/storage/${img}` : '/img/user.png';
});

const initials = computed(() =>
    (auth.value?.user?.name ?? 'S').split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase());

const hasErpAccess = computed(() => {
    const roles = (auth.value?.user?.roles ?? []).map(r => r.toLowerCase());
    if (!roles.length) return false;
    return !roles.includes('student');
});

const isWebsiteAdmin = computed(() => {
    const roles = (auth.value?.user?.roles ?? []).map(r => r.toLowerCase());
    return roles.some(r => ['superadmin', 'admin', 'editor'].includes(r));
});

// ── Sidebar collapse + mobile drawer (persisted) ───────────────────────────
const collapsed = ref(false);
const mobileOpen = ref(false);

onMounted(() => {
    collapsed.value = localStorage.getItem('sits.library.sidebar.collapsed') === '1';
    document.addEventListener('click', handleOutsideClick);
});
onUnmounted(() => document.removeEventListener('click', handleOutsideClick));

const toggleCollapsed = () => {
    collapsed.value = !collapsed.value;
    localStorage.setItem('sits.library.sidebar.collapsed', collapsed.value ? '1' : '0');
};

// ── User menu ──────────────────────────────────────────────────────────────
const userMenuOpen = ref(false);
const userMenuRef = ref(null);
const handleOutsideClick = (e) => {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
        userMenuOpen.value = false;
    }
};

// ── Expandable groups (persisted) ──────────────────────────────────────────
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
        if (groupLabel === t('Overview') || groupLabel === t('My Library') || hasActiveItem) {
            return true;
        }
        return false;
    }
    return expandedGroups.value[groupLabel];
};

// ── Navigation (permission-scoped, Lucide icon names) ──────────────────────
const navGroups = computed(() => {
    const groups = [];

    groups.push({
        label: t('Overview'),
        items: [
            { label: t('Dashboard'), route: 'library.dashboard', icon: 'LayoutDashboard' },
        ],
    });

    if (can('view_books')) {
        const items = [
            { label: t('Catalog'), route: 'library.catalog.index', icon: 'LibraryBig' },
        ];
        if (can('create_book')) {
            items.push({ label: t('Add Book'), route: 'library.books.create', icon: 'BookPlus' });
            items.push({ label: t('Scan & Place'), route: 'library.scan.place', icon: 'ScanLine' });
        }
        groups.push({ label: t('Library'), items });
    }

    groups.push({
        label: t('My Library'),
        items: [
            { label: t('My Loans'), route: 'library.my.loans', icon: 'BookMarked' },
            { label: t('My Holds'), route: 'library.my.holds', icon: 'Bookmark' },
            { label: t('My Fines'), route: 'library.my.fines', icon: 'ReceiptText' },
            { label: t('Resources'), route: 'library.resources.index', icon: 'Link2' },
        ],
    });

    if (can('checkout_book') || can('return_book') || can('collect_fine')) {
        const items = [];
        if (can('checkout_book')) items.push({ label: t('Checkout Desk'), route: 'library.circulation.desk', icon: 'ScanBarcode' });
        if (can('return_book')) items.push({ label: t('Returns'), route: 'library.circulation.returns', icon: 'Undo2' });
        if (can('collect_fine')) items.push({ label: t('Fines'), route: 'library.fines.index', icon: 'HandCoins' });
        groups.push({ label: t('Circulation'), items });
    }

    if (can('request_transfer') || can('approve_transfer') || can('receive_transfer')) {
        groups.push({
            label: t('Transfers'),
            items: [{ label: t('Transfers'), route: 'library.transfers.index', icon: 'ArrowLeftRight' }],
        });
    }

    if (can('view_secure_pdf')) {
        groups.push({
            label: t('Digital Archive'),
            items: [{ label: t('Archive'), route: 'library.archive.index', icon: 'Archive' }],
        });
    }

    const adminItems = [];
    if (can('manage_users'))          adminItems.push({ label: t('Users'),          route: 'library.users.index',      icon: 'Users' });
    if (can('manage_campus'))         adminItems.push({ label: t('Campuses'),       route: 'library.campuses.index',   icon: 'Building2' });
    if (can('manage_campus'))         adminItems.push({ label: t('Subscriptions'),  route: 'library.subscriptions',    icon: 'CreditCard', external: true });
    if (can('manage_external_links')) adminItems.push({ label: t('Ext. Resources'), route: 'library.resources.index',  icon: 'Globe' });
    if (can('manage_campus'))         adminItems.push({ label: t('Audit Log'),      route: 'library.admin.audit',      icon: 'ScrollText' });
    if (can('view_loans'))            adminItems.push({ label: t('Reports'),        route: 'library.reports.index',    icon: 'BarChart3' });
    if (can('manage_shelf_box'))      adminItems.push({ label: t('Stocktake'),      route: 'library.stocktakes.index', icon: 'ClipboardCheck' });
    if (adminItems.length) groups.push({ label: t('Administration'), items: adminItems });

    return groups;
});

// Flatten for breadcrumb resolution.
const activeCrumb = computed(() => {
    for (const group of navGroups.value) {
        for (const item of group.items) {
            if (!item.external && route().current(item.route)) {
                return { section: group.label, label: item.label };
            }
        }
    }
    return { section: t('Digital Library'), label: t('Dashboard') };
});

const roleBadge = computed(() => {
    const map = {
        super_admin:  { label: 'Super Admin',  cls: 'bg-violet-500/10 text-violet-600 dark:text-violet-300 border-violet-500/20' },
        campus_admin: { label: 'Campus Admin', cls: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 border-indigo-500/20' },
        librarian:    { label: 'Librarian',    cls: 'bg-blue-500/10 text-blue-600 dark:text-blue-300 border-blue-500/20' },
        instructor:   { label: 'Instructor',   cls: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 border-emerald-500/20' },
        staff_user:   { label: 'Staff',        cls: 'bg-slate-500/10 text-slate-600 dark:text-slate-300 border-slate-500/20' },
        student:      { label: 'Student',      cls: 'bg-amber-500/10 text-amber-600 dark:text-amber-300 border-amber-500/20' },
    };
    const details = map[role.value] ?? { label: 'Member', cls: 'bg-slate-500/10 text-slate-600 dark:text-slate-300 border-slate-500/20' };
    return { label: t(details.label), cls: details.cls };
});

watch(() => page.url, () => {
    mobileOpen.value = false;
    userMenuOpen.value = false;
});
</script>

<template>
    <div class="relative min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100">
        <!-- Ambient glow (dark) -->
        <div class="pointer-events-none absolute top-[-15%] right-[-8%] w-[520px] h-[520px] rounded-full bg-indigo-600/10 blur-[150px] hidden dark:block"></div>
        <div class="pointer-events-none absolute bottom-[-15%] left-[-8%] w-[520px] h-[520px] rounded-full bg-violet-600/10 blur-[150px] hidden dark:block"></div>

        <!-- Mobile backdrop -->
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
                    leave-active-class="transition-opacity duration-200" leave-to-class="opacity-0">
            <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-sm lg:hidden" @click="mobileOpen = false" />
        </Transition>

        <!-- ===================== SIDEBAR ===================== -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex flex-col border-r border-slate-200 dark:border-slate-900 bg-white/95 dark:bg-slate-950/95 backdrop-blur-xl transition-all duration-300 ease-in-out lg:translate-x-0"
            :class="[
                collapsed ? 'w-[76px]' : 'w-[268px]',
                mobileOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Brand -->
            <div class="h-16 flex items-center gap-3 px-4 border-b border-slate-200 dark:border-slate-900 shrink-0">
                <Link :href="route('library.dashboard')" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-violet-500 p-0.5 shadow-lg shadow-indigo-500/25 shrink-0 flex items-center justify-center group-hover:scale-105 transition-transform duration-300">
                        <div class="w-full h-full bg-white dark:bg-slate-950 rounded-[10px] flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <Icon name="LibraryBig" :size="20" :stroke-width="2" />
                        </div>
                    </div>
                    <div v-if="!collapsed" class="min-w-0">
                        <p class="text-sm font-bold tracking-tight text-slate-900 dark:text-white leading-tight">SITS Library</p>
                        <p class="text-[10px] text-indigo-500 dark:text-indigo-400/80 font-semibold uppercase tracking-widest truncate">Digital Repository</p>
                    </div>
                </Link>
                <button class="ml-auto hidden lg:flex items-center justify-center w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-900 transition-colors"
                        @click="toggleCollapsed" :title="collapsed ? t('Expand') : t('Collapse')">
                    <Icon :name="collapsed ? 'PanelLeftOpen' : 'PanelLeftClose'" :size="18" />
                </button>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 px-3 space-y-5 sidebar-scroll">
                <div v-for="group in navGroups" :key="group.label">
                    <!-- Group header -->
                    <button v-if="!collapsed"
                            @click="toggleGroup(group.label)"
                            class="w-full flex items-center justify-between px-3 mb-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors">
                        <span>{{ group.label }}</span>
                        <Icon name="ChevronDown" :size="13"
                              class="transition-transform duration-200" :class="isGroupExpanded(group.label) ? '' : '-rotate-90'" />
                    </button>
                    <div v-else class="mx-3 mb-2 border-t border-slate-200 dark:border-slate-900"></div>

                    <!-- Items -->
                    <div class="grid transition-all duration-300 ease-in-out"
                         :class="(collapsed || isGroupExpanded(group.label)) ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0 pointer-events-none'">
                        <div class="overflow-hidden">
                            <ul class="space-y-1">
                                <li v-for="item in group.items" :key="item.route" class="group/item relative">
                                    <component
                                        :is="item.external ? 'a' : Link"
                                        :href="route(item.route, item.params || {})"
                                        :target="item.target || '_self'"
                                        class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-150"
                                        :class="[
                                            route().current(item.route)
                                                ? 'bg-gradient-to-r from-indigo-500/15 to-violet-500/5 text-indigo-700 dark:text-white shadow-sm'
                                                : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100/70 dark:hover:bg-slate-900/60',
                                            collapsed ? 'justify-center' : '',
                                        ]"
                                    >
                                        <span class="relative flex items-center justify-center shrink-0">
                                            <span v-if="route().current(item.route)" class="absolute -left-3 h-5 w-1 rounded-r bg-indigo-500"></span>
                                            <Icon :name="item.icon" :size="19" :class="route().current(item.route) ? 'text-indigo-500 dark:text-indigo-400' : ''" />
                                        </span>
                                        <span v-if="!collapsed" class="flex-1 text-left truncate">{{ item.label }}</span>
                                    </component>

                                    <!-- Collapsed tooltip -->
                                    <span v-if="collapsed"
                                          class="pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 z-50 whitespace-nowrap rounded-lg bg-slate-800 dark:bg-slate-800 px-2.5 py-1.5 text-xs font-medium text-slate-100 border border-slate-700 opacity-0 group-hover/item:opacity-100 transition-opacity shadow-xl">
                                        {{ item.label }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- User card -->
            <div class="border-t border-slate-200 dark:border-slate-900 p-3 shrink-0">
                <div class="flex items-center gap-3" :class="collapsed ? 'justify-center' : ''">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-500 to-violet-500 flex items-center justify-center text-xs font-bold text-white shrink-0 overflow-hidden">
                        <img v-if="auth.user.profile_image" :src="userAvatar" alt="" class="w-full h-full object-cover" />
                        <span v-else>{{ initials }}</span>
                    </div>
                    <div v-if="!collapsed" class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ auth.user.name }}</p>
                        <span class="inline-flex items-center rounded-full border px-1.5 py-0.5 text-[10px] font-semibold" :class="roleBadge.cls">
                            {{ roleBadge.label }}
                        </span>
                    </div>
                    <Link v-if="!collapsed" :href="route('logout')" method="post" as="button"
                          class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 transition-colors" :title="t('Sign out')">
                        <Icon name="LogOut" :size="17" />
                    </Link>
                </div>
            </div>
        </aside>

        <!-- ===================== MAIN ===================== -->
        <div class="relative z-10 transition-all duration-300 ease-in-out" :class="collapsed ? 'lg:pl-[76px]' : 'lg:pl-[268px]'">
            <!-- Topbar -->
            <header class="sticky top-0 z-30 min-h-16 py-2.5 flex items-center gap-2 sm:gap-3 px-3 sm:px-6 border-b border-slate-200 dark:border-slate-900 bg-white/80 dark:bg-slate-950/80 backdrop-blur-md">
                <button class="lg:hidden w-9 h-9 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-900" @click="mobileOpen = true">
                    <Icon name="Menu" :size="20" />
                </button>

                <!-- Breadcrumb + page header slot -->
                <div class="min-w-0 flex-1">
                    <p class="hidden sm:block text-[11px] text-slate-400 dark:text-slate-500 font-medium uppercase tracking-wider truncate">{{ activeCrumb.section }}</p>
                    <div class="min-w-0 [&_h2]:truncate [&_h2]:text-base [&_h2]:sm:text-lg [&_h2]:font-bold [&_h2]:tracking-tight [&_h2]:normal-case [&_h2]:text-slate-900 dark:[&_h2]:text-white">
                        <slot name="header">
                            <h2 class="-mt-0.5">{{ activeCrumb.label }}</h2>
                        </slot>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 sm:gap-2">
                    <GlobalSearch />
                    <LanguageSwitcher />
                    <NotificationBell />
                    <button
                        @click="toggleDarkMode"
                        class="w-9 h-9 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-900 hover:text-slate-800 dark:hover:text-slate-200 transition"
                        :title="isDark ? t('Switch to light mode') : t('Switch to dark mode')"
                    >
                        <Icon :name="isDark ? 'Sun' : 'Moon'" :size="19" />
                    </button>

                    <div class="hidden sm:block w-px h-6 bg-slate-200 dark:bg-slate-800"></div>

                    <!-- User Profile Dropdown -->
                    <div class="relative" ref="userMenuRef">
                        <button @click="userMenuOpen = !userMenuOpen"
                            class="flex items-center gap-2 p-1 rounded-full hover:bg-slate-100 dark:hover:bg-slate-900 border border-transparent hover:border-slate-200 dark:hover:border-slate-800 transition focus:outline-none cursor-pointer">
                            <img :src="userAvatar" alt="Profile" class="h-8 w-8 rounded-full object-cover ring-2 ring-indigo-500/20" />
                            <Icon name="ChevronDown" :size="14" class="text-slate-400 hidden sm:block transition-transform duration-200" :class="{'rotate-180': userMenuOpen}" />
                        </button>

                        <Transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                            <div v-if="userMenuOpen" class="absolute right-0 mt-2 w-60 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl shadow-2xl z-50 overflow-hidden">
                                <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800/60">
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider">{{ t('Logged in as') }}</p>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ auth.user.name }}</p>
                                </div>
                                <div class="py-1">
                                    <Link :href="route('portal')" @click="userMenuOpen = false" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <Icon name="LayoutDashboard" :size="15" class="text-slate-400 dark:text-slate-500" /><span>{{ t('Dashboard Hub') }}</span>
                                    </Link>
                                    <Link :href="route('profile.edit', { from: 'library' })" @click="userMenuOpen = false" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <Icon name="User" :size="15" class="text-slate-400 dark:text-slate-500" /><span>{{ t('View Profile') }}</span>
                                    </Link>
                                    <div class="border-t border-slate-100 dark:border-slate-800/60 my-1"></div>
                                    <Link v-if="hasErpAccess" :href="route('dashboard')" @click="userMenuOpen = false" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <Icon name="LayoutDashboard" :size="15" class="text-slate-400 dark:text-slate-500" /><span>SITS ERP</span>
                                    </Link>
                                    <a href="https://lms.sits.edu.et" target="_blank" @click="userMenuOpen = false" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <Icon name="GraduationCap" :size="15" class="text-slate-400 dark:text-slate-500" /><span>SITS LMS</span>
                                    </a>
                                    <a href="/go/lms" target="_blank" @click="userMenuOpen = false" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <Icon name="Laptop" :size="15" class="text-slate-400 dark:text-slate-500" /><span>Moodle</span>
                                    </a>
                                    <a v-if="isWebsiteAdmin" :href="route('website.admin.dashboard')" @click="userMenuOpen = false" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <Icon name="Globe" :size="15" class="text-slate-400 dark:text-slate-500" /><span>Website Admin</span>
                                    </a>
                                    <div class="border-t border-slate-100 dark:border-slate-800/60 my-1"></div>
                                    <Link :href="route('logout')" method="post" as="button" @click="userMenuOpen = false" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-left text-xs font-bold text-rose-500 hover:bg-rose-500/10 hover:text-rose-400 transition-colors cursor-pointer">
                                        <Icon name="LogOut" :size="15" /><span>{{ t('Sign out') }}</span>
                                    </Link>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="min-h-[calc(100vh-4rem)]">
                <!-- Flash messages -->
                <div v-if="flash.success || flash.error" class="px-4 pt-4 sm:px-6">
                    <div
                        class="mx-auto max-w-7xl rounded-xl border px-4 py-3 text-sm font-medium"
                        :class="flash.success
                            ? 'border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300'
                            : 'border-rose-500/30 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300'"
                    >
                        {{ flash.success || flash.error }}
                    </div>
                </div>
                <slot />
            </main>
        </div>

        <!-- Toast notifications -->
        <Toast />
    </div>
</template>

<style scoped>
.sidebar-scroll::-webkit-scrollbar { width: 6px; }
.sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: rgb(203 213 225 / 0.6); border-radius: 3px; }
.sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgb(148 163 184 / 0.8); }
:global(.dark) .sidebar-scroll::-webkit-scrollbar-thumb { background: rgb(30 41 59); }
:global(.dark) .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgb(51 65 85); }
</style>
