<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import Icon from '@/Components/Icon.vue';
import CommandPalette from '@/Components/CommandPalette.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import LocaleToggle from '@/Components/LocaleToggle.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
const page = usePage();
const nav = computed(() => page.props.nav ?? []);
const user = computed(() => page.props.auth?.user);

// ---- Portal identity (brand subtitle, accent) ----------------------------
// Shared per-role by PortalContext; falls back to the President defaults so
// the admin area renders unchanged when no portal payload is present.
const portal = computed(() => page.props.portal ?? null);
const brandSubtitle = computed(() => portal.value?.roleLabel ?? 'President · Super Admin');

// ---- Fiscal year context -------------------------------------------------
const fiscalYear = computed(() => page.props.fiscalYear ?? null);
const yearOpen = ref(false);
const switchYear = (yearId) => {
  yearOpen.value = false;
  router.post('/admin/fiscal-year', { year_id: yearId }, { preserveScroll: true });
};

// ---- Flash toasts (CRUD success/error feedback) --------------------------
const toast = ref(null);
let toastTimer = null;
const showToast = (message, type = 'success') => {
  toast.value = { message, type };
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { toast.value = null; }, 4500);
};
watch(() => page.props.flash, (flash) => {
  if (flash?.success) showToast(flash.success, 'success');
  else if (flash?.error) showToast(flash.error, 'error');
}, { deep: true, immediate: true });

// ---- Notifications -------------------------------------------------------
const notifications = computed(() => page.props.notifications ?? []);
const notifCount = computed(() => notifications.value.reduce((sum, n) => sum + (n.count || 0), 0));
const notifOpen = ref(false);
const notifTone = {
  amber: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
  blue: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  emerald: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  violet: 'bg-violet-500/10 border-violet-500/20 text-violet-400',
};

// ---- Sidebar collapse (persisted) ----------------------------------------
const collapsed = ref(false);
const mobileOpen = ref(false);
const paletteOpen = ref(false);

onMounted(() => {
  collapsed.value = localStorage.getItem('sits.sidebar.collapsed') === '1';
  window.addEventListener('keydown', onGlobalKey);
});
onUnmounted(() => window.removeEventListener('keydown', onGlobalKey));

const toggleCollapsed = () => {
  collapsed.value = !collapsed.value;
  localStorage.setItem('sits.sidebar.collapsed', collapsed.value ? '1' : '0');
};

const onGlobalKey = (e) => {
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault();
    paletteOpen.value = true;
  }
};

// ---- Active route detection ----------------------------------------------
const currentPath = computed(() => page.url.split('?')[0]);
const isExact = (path) => currentPath.value === path;
const isWithin = (path) => currentPath.value === path || currentPath.value.startsWith(path + '/');

// ---- Expandable groups ----------------------------------------------------
const expanded = reactive({});
const isOpen = (item) => expanded[item.name] ?? isWithin(item.path);
const toggle = (item) => { expanded[item.name] = !isOpen(item); };

// Auto-open the group that owns the current route.
watch(currentPath, () => {
  nav.value.forEach((section) =>
    section.items.forEach((item) => {
      if (item.children && isWithin(item.path)) expanded[item.name] = true;
    }));
}, { immediate: true });

// ---- Flatten for command palette + breadcrumb ----------------------------
const flatModules = computed(() => {
  const out = [];
  nav.value.forEach((section) =>
    section.items.forEach((item) => {
      out.push({ name: item.name, label: item.label, path: item.path, icon: item.icon, section: section.label });
      (item.children || []).forEach((c) =>
        out.push({ name: c.name, label: c.label, path: c.path, icon: item.icon, section: `${section.label} · ${item.label}` }));
    }));
  return out;
});

const crumb = computed(() => {
  // Deepest module whose path matches the current URL.
  const matches = flatModules.value
    .filter((m) => isWithin(m.path))
    .sort((a, b) => b.path.length - a.path.length);
  return matches[0] ?? { label: 'Dashboard', section: 'Overview' };
});

const initials = computed(() =>
  (user.value?.name ?? 'SA').split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase());

const userMenuOpen = ref(false);

const lmsLabel = computed(() => {
  const roles = user.value?.roles ?? [];
  const rolesLower = roles.map(r => r.toLowerCase());
  if (rolesLower.includes('student')) {
    return 'Go to Student Learning Portal';
  } else if (rolesLower.includes('trainer')) {
    return 'Instructors Portal';
  }
  return 'LMS Portal';
});

const lmsUrl = computed(() => {
  const roles = user.value?.roles ?? [];
  const rolesLower = roles.map(r => r.toLowerCase());
  if (rolesLower.includes('student') || rolesLower.includes('trainer')) {
    return '/go/lms';
  }
  return 'https://lms.sits.edu.et';
});

const hasLibraryAccess = computed(() => {
  const roles = user.value?.roles ?? [];
  const rolesLower = roles.map(r => r.toLowerCase());
  const allowed = ['student', 'trainer', 'librarian', 'admin', 'superadmin', 'president / super admin', 'staff', 'editor'];
  return rolesLower.some(r => allowed.includes(r));
});

const hasErpAccess = computed(() => {
  const roles = user.value?.roles ?? [];
  const rolesLower = roles.map(r => r.toLowerCase());
  const allowed = ['superadmin', 'admin', 'president / super admin', 'editor', 'trainer', 'staff', 'librarian', 'vice president', 'dean of the seminary', 'operational manager', 'finance officer', 'department head', 'registrar'];
  return rolesLower.some(r => allowed.includes(r));
});

watch(currentPath, () => {
  mobileOpen.value = false;
  notifOpen.value = false;
  yearOpen.value = false;
  userMenuOpen.value = false;
});
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <!-- Flash toast -->
    <Transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100" leave-to-class="opacity-0"
    >
      <div v-if="toast"
           class="fixed bottom-6 right-6 z-[70] p-4 rounded-2xl border shadow-2xl backdrop-blur-xl flex items-center gap-3 max-w-sm"
           :class="toast.type === 'success'
             ? 'border-emerald-500/30 bg-emerald-950/90 text-emerald-300'
             : 'border-rose-500/30 bg-rose-950/90 text-rose-300'">
        <Icon :name="toast.type === 'success' ? 'ShieldCheck' : 'Bell'" :size="20" class="shrink-0" />
        <div class="text-sm font-semibold tracking-wide">{{ toast.message }}</div>
      </div>
    </Transition>

    <!-- Mobile backdrop -->
    <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-200" leave-to-class="opacity-0">
      <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-sm lg:hidden" @click="mobileOpen = false" />
    </Transition>

    <!-- ===================== SIDEBAR ===================== -->
    <aside
      class="fixed inset-y-0 left-0 z-50 flex flex-col border-r border-slate-900 bg-slate-950/95 backdrop-blur-xl transition-all duration-300 ease-in-out lg:translate-x-0"
      :class="[
        collapsed ? 'w-[76px]' : 'w-[270px]',
        mobileOpen ? 'translate-x-0' : '-translate-x-full',
      ]"
    >
      <!-- Brand -->
      <div class="h-20 flex items-center gap-3 px-4 border-b border-slate-900 shrink-0">
        <Link href="/" class="flex items-center gap-3 hover:opacity-90 group transition-all duration-300">
          <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-indigo-500 to-cyan-400 p-0.5 shadow-lg shadow-indigo-500/20 shrink-0 flex items-center justify-center group-hover:scale-105 transition-transform duration-300">
            <div class="w-full h-full bg-slate-900 rounded-[10px] flex items-center justify-center overflow-hidden">
              <img :src="'/img/logo.png'" alt="SITS Logo" class="h-7 w-auto object-contain" />
            </div>
          </div>
          <div v-if="!collapsed" class="min-w-0">
            <p class="text-base font-bold tracking-tight text-white leading-tight">SITS ERP</p>
            <p class="text-[11px] text-blue-400/80 font-semibold uppercase tracking-wider truncate">{{ brandSubtitle }}</p>
          </div>
        </Link>
        <button class="ml-auto hidden lg:flex items-center justify-center w-7 h-7 rounded-lg text-slate-500 hover:text-slate-200 hover:bg-slate-900 transition-colors"
                @click="toggleCollapsed" :title="collapsed ? 'Expand' : 'Collapse'">
          <Icon :name="collapsed ? 'PanelLeftOpen' : 'PanelLeftClose'" :size="18" />
        </button>
      </div>

      <!-- Nav -->
      <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 px-3 space-y-6 sidebar-scroll">
        <div v-for="section in nav" :key="section.label">
          <p v-if="!collapsed" class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-600">{{ section.label }}</p>
          <div v-else class="mx-3 mb-2 border-t border-slate-900"></div>

          <ul class="space-y-1">
            <li v-for="item in section.items" :key="item.name" class="group/item relative">
              <!-- Leaf or parent row -->
              <component
                :is="item.children ? 'button' : Link"
                v-bind="item.children ? {} : { href: item.path }"
                type="button"
                class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-150"
                :class="[
                  isExact(item.path)
                    ? 'bg-gradient-to-r from-blue-500/20 to-blue-500/5 text-white shadow-sm'
                    : isWithin(item.path)
                      ? 'text-white bg-slate-900/60'
                      : 'text-slate-400 hover:text-slate-100 hover:bg-slate-900/50',
                  collapsed ? 'justify-center' : '',
                ]"
                @click="item.children ? toggle(item) : null"
              >
                <span class="relative flex items-center justify-center shrink-0">
                  <span v-if="isExact(item.path)" class="absolute -left-3 h-5 w-1 rounded-r bg-blue-400"></span>
                  <Icon :name="item.icon" :size="19" :class="isWithin(item.path) ? 'text-blue-400' : ''" />
                </span>

                <template v-if="!collapsed">
                  <span class="flex-1 text-left truncate">{{ item.label }}</span>
                  <span v-if="item.badge" class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-400 border border-amber-500/20">{{ item.badge }}</span>
                  <Icon v-if="item.children" name="ChevronDown" :size="15"
                        class="text-slate-500 transition-transform duration-200" :class="isOpen(item) ? 'rotate-180' : ''" />
                </template>
              </component>

              <!-- Collapsed tooltip -->
              <span v-if="collapsed"
                    class="pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 z-50 whitespace-nowrap rounded-lg bg-slate-800 px-2.5 py-1.5 text-xs font-medium text-slate-100 border border-slate-700 opacity-0 group-hover/item:opacity-100 transition-opacity shadow-xl">
                {{ item.label }}
              </span>

              <!-- Children -->
              <Transition
                enter-active-class="transition-all duration-200 ease-out overflow-hidden"
                enter-from-class="max-h-0 opacity-0" enter-to-class="max-h-96 opacity-100"
                leave-active-class="transition-all duration-150 ease-in overflow-hidden"
                leave-from-class="max-h-96 opacity-100" leave-to-class="max-h-0 opacity-0"
              >
                <ul v-if="item.children && isOpen(item) && !collapsed" class="mt-1 ml-5 pl-3 border-l border-slate-800 space-y-0.5">
                  <li v-for="child in item.children" :key="child.name">
                    <Link :href="child.path"
                          class="flex items-center gap-2 rounded-lg px-3 py-2 text-[13px] transition-colors"
                          :class="isExact(child.path) ? 'text-blue-400 font-semibold' : 'text-slate-500 hover:text-slate-200 hover:bg-slate-900/40'">
                      <span class="w-1 h-1 rounded-full shrink-0" :class="isExact(child.path) ? 'bg-blue-400' : 'bg-slate-700'"></span>
                      <span class="truncate">{{ child.label }}</span>
                    </Link>
                  </li>
                </ul>
              </Transition>
            </li>
          </ul>
        </div>
      </nav>

      <!-- User card -->
      <div class="border-t border-slate-900 p-3 shrink-0">
        <div class="flex items-center gap-3" :class="collapsed ? 'justify-center' : ''">
          <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-500 to-purple-600 flex items-center justify-center text-xs font-bold text-white shrink-0">{{ initials }}</div>
          <div v-if="!collapsed" class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-white truncate">{{ user?.name }}</p>
            <p class="text-[11px] text-slate-500 truncate">{{ user?.roles?.[0] ?? 'Administrator' }}</p>
          </div>
          <Link v-if="!collapsed" href="/logout" method="post" as="button"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:text-rose-400 hover:bg-rose-950/30 transition-colors" :title="t('sign_out', 'Sign out')">
            <Icon name="LogOut" :size="17" />
          </Link>
        </div>
      </div>
    </aside>

    <!-- ===================== MAIN ===================== -->
    <div class="transition-all duration-300 ease-in-out" :class="collapsed ? 'lg:pl-[76px]' : 'lg:pl-[270px]'">
      <!-- Topbar -->
      <header class="sticky top-0 z-30 h-16 flex items-center gap-3 px-4 sm:px-6 border-b border-slate-900 bg-slate-950/80 backdrop-blur-md">
        <button class="lg:hidden w-9 h-9 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-900" @click="mobileOpen = true">
          <Icon name="Menu" :size="20" />
        </button>

        <!-- Breadcrumb -->
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-medium uppercase tracking-wider truncate">{{ crumb.section }}</p>
          <h1 class="text-sm font-bold text-white truncate -mt-0.5">{{ crumb.label }}</h1>
        </div>

        <div class="flex-1"></div>

        <!-- Search trigger -->
        <button class="hidden sm:flex items-center gap-2 h-9 px-3 rounded-xl border border-slate-800 bg-slate-900/50 text-slate-500 hover:text-slate-300 hover:border-slate-700 transition-colors"
                @click="paletteOpen = true">
          <Icon name="Search" :size="16" />
          <span class="text-xs">{{ t('search', 'Search…') }}</span>
          <kbd class="text-[10px] font-semibold border border-slate-700 rounded px-1 ml-2">⌘K</kbd>
        </button>
        <button class="sm:hidden w-9 h-9 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-900" @click="paletteOpen = true">
          <Icon name="Search" :size="19" />
        </button>

        <!-- Language toggle (English / Amharic) -->
        <LocaleToggle />

        <!-- Fiscal year switcher -->
        <div v-if="fiscalYear" class="relative">
          <button class="flex items-center gap-2 h-9 px-3 rounded-xl border text-xs font-semibold transition-colors"
                  :class="fiscalYear.isHistorical
                    ? 'border-amber-500/30 bg-amber-500/10 text-amber-300'
                    : 'border-slate-800 bg-slate-900/50 text-slate-300 hover:border-slate-700'"
                  @click="yearOpen = !yearOpen">
            <Icon name="CalendarDays" :size="15" />
            <span>{{ fiscalYear.currentLabel || 'No year' }}</span>
            <span v-if="fiscalYear.isHistorical" class="hidden md:inline text-[9px] uppercase tracking-wide">· Historical</span>
            <Icon name="ChevronDown" :size="14" class="transition-transform" :class="yearOpen ? 'rotate-180' : ''" />
          </button>

          <div v-if="yearOpen" class="fixed inset-0 z-40" @click="yearOpen = false"></div>

          <Transition
            enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-1"
          >
            <div v-if="yearOpen" class="absolute right-0 mt-2 w-64 rounded-2xl border border-slate-800 bg-slate-900/95 backdrop-blur-xl shadow-2xl z-50 overflow-hidden">
              <div class="px-4 py-3 border-b border-slate-800">
                <p class="text-sm font-bold text-white">Fiscal Year</p>
                <p class="text-xs text-slate-500">Data scopes to the selected year.</p>
              </div>
              <div class="max-h-72 overflow-y-auto py-1">
                <button v-for="y in fiscalYear.years" :key="y.id"
                        class="w-full flex items-center gap-2 px-4 py-2.5 text-left hover:bg-slate-800/50 transition-colors"
                        :class="y.id === fiscalYear.currentId ? 'text-white' : 'text-slate-400'"
                        @click="switchYear(y.id)">
                  <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="y.id === fiscalYear.currentId ? 'bg-blue-400' : 'bg-transparent'"></span>
                  <span class="flex-1 text-sm font-medium truncate">{{ y.label }}</span>
                  <span v-if="y.active" class="text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-emerald-500/15 text-emerald-400 border border-emerald-500/20">Active</span>
                </button>
                <p v-if="!fiscalYear.years.length" class="px-4 py-6 text-center text-xs text-slate-500">No fiscal years yet.</p>
              </div>
              <div v-if="fiscalYear.isHistorical && fiscalYear.activeId" class="border-t border-slate-800 p-2">
                <button class="w-full text-xs font-semibold text-blue-400 hover:bg-slate-800/50 rounded-lg px-3 py-2 text-left flex items-center gap-1.5"
                        @click="switchYear(fiscalYear.activeId)">
                  <Icon name="ArrowRight" :size="13" class="rotate-180" /> Return to active year
                </button>
              </div>
            </div>
          </Transition>
        </div>

        <!-- Notifications -->
        <div class="relative">
          <button class="relative w-9 h-9 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-900 hover:text-slate-200 transition-colors"
                  :title="t('notifications', 'Notifications')" @click="notifOpen = !notifOpen">
            <Icon name="Bell" :size="19" />
            <span v-if="notifCount > 0"
                  class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-blue-500 text-[10px] font-bold text-white flex items-center justify-center">
              {{ notifCount > 9 ? '9+' : notifCount }}
            </span>
          </button>

          <!-- click-away overlay -->
          <div v-if="notifOpen" class="fixed inset-0 z-40" @click="notifOpen = false"></div>

          <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-1"
          >
            <div v-if="notifOpen"
                 class="absolute right-0 mt-2 w-80 rounded-2xl border border-slate-800 bg-slate-900/95 backdrop-blur-xl shadow-2xl z-50 overflow-hidden">
              <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <span class="text-sm font-bold text-white">{{ t('notifications', 'Notifications') }}</span>
                <span v-if="notifCount > 0" class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-blue-500/15 text-blue-400 border border-blue-500/20">
                  {{ notifCount }} pending
                </span>
              </div>

              <div class="max-h-96 overflow-y-auto">
                <Link v-for="n in notifications" :key="n.id" :href="n.href" @click="notifOpen = false"
                      class="flex items-start gap-3 px-4 py-3 hover:bg-slate-800/50 transition-colors border-b border-slate-900/60 last:border-0">
                  <span class="w-9 h-9 rounded-lg border flex items-center justify-center shrink-0" :class="notifTone[n.tone] || notifTone.blue">
                    <Icon :name="n.icon" :size="17" />
                  </span>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white">{{ n.title }}</p>
                    <p class="text-xs text-slate-400 leading-snug">{{ n.message }}</p>
                  </div>
                  <Icon name="ChevronRight" :size="15" class="text-slate-600 mt-1 shrink-0" />
                </Link>

                <div v-if="!notifications.length" class="px-4 py-12 text-center">
                  <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-slate-800/60 flex items-center justify-center text-emerald-400">
                    <Icon name="ShieldCheck" :size="22" />
                  </div>
                  <p class="text-sm font-medium text-slate-300">{{ t('all_caught_up', "You're all caught up") }}</p>
                  <p class="text-xs text-slate-500 mt-1">No items need your attention.</p>
                </div>
              </div>
            </div>
          </Transition>
        </div>

        <div class="w-px h-6 bg-slate-800"></div>

        <!-- Avatar & Dropdown -->
        <div class="relative">
          <button 
            @click="userMenuOpen = !userMenuOpen"
            class="flex items-center space-x-2 p-1 rounded-full hover:bg-slate-900 border border-transparent hover:border-slate-800 transition focus:outline-none cursor-pointer"
          >
            <img 
              :src="user?.profile_image ? (user.profile_image.startsWith('http') ? user.profile_image : '/storage/' + user.profile_image) : '/img/user.png'" 
              alt="Profile Image" 
              class="h-8 w-8 rounded-full object-cover ring-2 ring-indigo-500/20" 
            />
            <span class="text-xs font-semibold text-slate-350 hidden sm:inline-block">{{ user?.name }}</span>
            <Icon 
              name="ChevronDown" 
              :size="10" 
              class="text-slate-500 hidden sm:inline-block transition-transform duration-200" 
              :class="{ 'rotate-180': userMenuOpen }" 
            />
          </button>

          <!-- click-away overlay -->
          <div v-if="userMenuOpen" class="fixed inset-0 z-40" @click="userMenuOpen = false"></div>

          <Transition
            enter-active-class="transition duration-150 ease-out" 
            enter-from-class="opacity-0 -translate-y-1" 
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in" 
            leave-from-class="opacity-100" 
            leave-to-class="opacity-0 -translate-y-1"
          >
            <div 
              v-if="userMenuOpen" 
              class="absolute right-0 mt-2 w-64 rounded-2xl border border-slate-850 bg-slate-900/95 backdrop-blur-xl shadow-2xl z-50 overflow-hidden"
            >
              <div class="px-4 py-3 border-b border-slate-800/60">
                <p class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">Logged In As</p>
                <p class="text-sm font-bold text-white truncate">{{ user?.name }}</p>
                <div class="mt-1 flex flex-wrap gap-1">
                  <span 
                    v-for="r in user?.roles" 
                    :key="r" 
                    class="text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-blue-500/10 border border-blue-500/20 text-blue-400"
                  >
                    {{ r }}
                  </span>
                </div>
              </div>
              <div class="py-1">
                <!-- Dashboard Hub -->
                <Link 
                  :href="route('portal')" 
                  class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-355 hover:text-white hover:bg-slate-800/50 transition-colors"
                  @click="userMenuOpen = false"
                >
                  <Icon name="LayoutDashboard" :size="15" class="text-slate-500" />
                  <span>Dashboard Hub</span>
                </Link>

                <!-- Profile -->
                <Link 
                  :href="route('profile.edit')" 
                  class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-355 hover:text-white hover:bg-slate-800/50 transition-colors"
                  @click="userMenuOpen = false"
                >
                  <Icon name="User" :size="15" class="text-slate-500" />
                  <span>View Profile</span>
                </Link>

                <div class="border-t border-slate-800/60 my-1"></div>

                <!-- LMS Link -->
                <a 
                  :href="lmsUrl" 
                  class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-355 hover:text-white hover:bg-slate-800/50 transition-colors"
                  @click="userMenuOpen = false"
                >
                  <Icon name="Briefcase" :size="15" class="text-slate-500" />
                  <span>{{ lmsLabel }}</span>
                </a>

                <!-- ERP Portal (if they have roles to access ERP modules) -->
                <Link 
                  v-if="hasErpAccess"
                  :href="route('dashboard')" 
                  class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-355 hover:text-white hover:bg-slate-800/50 transition-colors"
                  @click="userMenuOpen = false"
                >
                  <Icon name="ShieldCheck" :size="15" class="text-slate-500" />
                  <span>ERP Portal</span>
                </Link>

                <!-- Digital Library -->
                <Link 
                  v-if="hasLibraryAccess"
                  :href="route('library.dashboard')" 
                  class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-355 hover:text-white hover:bg-slate-800/50 transition-colors"
                  @click="userMenuOpen = false"
                >
                  <Icon name="FolderOpen" :size="15" class="text-slate-500" />
                  <span>Digital Library</span>
                </Link>

                <div class="border-t border-slate-800/60 my-1"></div>

                <!-- Logout -->
                <Link 
                  :href="route('logout')" 
                  method="post" 
                  as="button" 
                  class="w-full flex items-center gap-2 px-4 py-2.5 text-left text-xs font-bold text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition-colors cursor-pointer w-full"
                  @click="userMenuOpen = false"
                >
                  <Icon name="LogOut" :size="15" />
                  <span>Sign Out</span>
                </Link>
              </div>
            </div>
          </Transition>
        </div>
      </header>

      <!-- Page content -->
      <main class="p-4 sm:p-6 lg:p-8">
        <slot />
      </main>
    </div>

    <CommandPalette :open="paletteOpen" :modules="flatModules" @close="paletteOpen = false" />
    <ConfirmationModal />
  </div>
</template>

<style scoped>
.sidebar-scroll::-webkit-scrollbar { width: 6px; }
.sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: rgb(30 41 59); border-radius: 3px; }
.sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgb(51 65 85); }
</style>
