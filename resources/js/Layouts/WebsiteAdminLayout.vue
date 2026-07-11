<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import { useDarkMode } from '@/composables/useDarkMode';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const { isDark, toggle: toggleDarkMode } = useDarkMode();

const userAvatar = computed(() => {
  const img = user.value?.profile_image;
  return img ? (img.startsWith('http') ? img : `/storage/${img}`) : '/img/user.png';
});

const userMenuOpen = ref(false);
const userMenuRef = ref(null);
const sidebarOpen = ref(false);

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

// Sidebar links
const links = [
  { label: 'Overview', path: '/website/admin', icon: 'LayoutDashboard' },
  { label: 'Courses', path: '/courses/list', icon: 'BookOpen' },
  { label: 'Blogs', path: '/blogs/list', icon: 'Newspaper' },
  { label: 'Programs', path: '/programs/list', icon: 'GraduationCap' },
  { label: 'Events', path: '/events/list', icon: 'CalendarDays' },
  { label: 'Gallery', path: '/galleries/list', icon: 'Image' },
  { label: 'Trainers', path: '/trainers/list', icon: 'Briefcase' },
  { label: 'Libraries', path: '/libraries/list', icon: 'BookOpen' },
  { label: 'Library Subscriptions', path: '/library/subscriptions', icon: 'BookOpen' },
  { label: 'Users', path: '/users/list', icon: 'Users' },
  { label: 'Contacts', path: '/contacts/list', icon: 'FolderOpen' },
  { label: 'Subscriptions', path: '/subscriptions', icon: 'FolderOpen' },
];

const isExact = (path) => page.url.split('?')[0] === path;

const lmsLabel = computed(() => {
  const roles = (user.value?.roles ?? []).map(r => r.toLowerCase());
  if (roles.includes('student')) return 'Student Portal';
  if (roles.includes('trainer')) return 'Instructor Portal';
  return 'LMS Portal';
});

const lmsUrl = computed(() => {
  const roles = (user.value?.roles ?? []).map(r => r.toLowerCase());
  if (roles.includes('student') || roles.includes('trainer')) {
    return '/go/lms';
  }
  return 'https://lms.sits.edu.et';
});

const hasLibraryAccess = computed(() => {
  const roles = (user.value?.roles ?? []).map(r => r.toLowerCase());
  const allowed = ['student', 'trainer', 'librarian', 'admin', 'superadmin', 'president / super admin', 'staff', 'editor'];
  return roles.some(r => allowed.includes(r));
});

const hasErpAccess = computed(() => {
  const roles = (user.value?.roles ?? []).map(r => r.toLowerCase());
  const allowed = ['superadmin', 'admin', 'president / super admin', 'editor', 'trainer', 'staff', 'librarian', 'vice president', 'dean of the seminary', 'operational manager', 'finance officer', 'department head', 'registrar'];
  return roles.some(r => allowed.includes(r));
});

const isWebsiteAdmin = computed(() => {
  const roles = (user.value?.roles ?? []).map(r => r.toLowerCase());
  return roles.some(r => ['superadmin', 'admin', 'editor'].includes(r));
});
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 font-sans relative overflow-x-hidden selection:bg-indigo-500 selection:text-white">
    <!-- Background glowing ambient elements -->
    <div class="fixed top-[-10%] right-[-5%] w-[500px] h-[500px] rounded-full bg-gradient-to-tr from-indigo-500 to-cyan-400 opacity-[0.08] blur-[120px] pointer-events-none"></div>
    <div class="fixed bottom-[-10%] left-[-10%] w-[600px] h-[600px] rounded-full bg-gradient-to-tr from-amber-500 to-rose-500 opacity-[0.08] blur-[120px] pointer-events-none"></div>

    <!-- Mobile sidebar backdrop -->
    <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-200" leave-to-class="opacity-0">
      <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false" />
    </Transition>

    <!-- ===================== SIDEBAR ===================== -->
    <aside
      class="fixed inset-y-0 left-0 z-50 flex flex-col border-r border-slate-900 bg-slate-950/95 backdrop-blur-xl transition-all duration-300 ease-in-out lg:translate-x-0 w-[260px]"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <!-- Brand -->
      <div class="h-20 flex items-center gap-3 px-6 border-b border-slate-900 shrink-0">
        <Link href="/" class="flex items-center gap-3 hover:opacity-90 group transition-all duration-300">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-cyan-400 p-0.5 shadow-lg shadow-indigo-500/20 shrink-0 flex items-center justify-center group-hover:scale-105 transition-transform duration-300">
            <div class="w-full h-full bg-slate-900 rounded-[10px] flex items-center justify-center overflow-hidden">
              <img :src="'/img/logo.png'" alt="SITS Logo" class="h-6 w-auto object-contain" />
            </div>
          </div>
          <div>
            <p class="text-sm font-bold tracking-tight text-white leading-tight uppercase bg-gradient-to-r from-white to-slate-400 bg-clip-text text-transparent">SITS CMS</p>
            <p class="text-[10px] text-indigo-400 font-semibold uppercase tracking-wider">Website Console</p>
          </div>
        </Link>
      </div>

      <!-- Nav Links -->
      <nav class="flex-grow overflow-y-auto py-4 px-4 space-y-1.5">
        <a 
          v-for="link in links" 
          :key="link.path"
          :href="link.path"
          class="w-full flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-medium transition-all duration-150 border border-transparent"
          :class="isExact(link.path)
            ? 'bg-gradient-to-r from-indigo-500/20 to-indigo-500/5 border-indigo-500/25 text-white shadow-sm font-semibold'
            : 'text-slate-400 hover:text-slate-100 hover:bg-slate-900/50'"
        >
          <Icon :name="link.icon" :size="18" :class="isExact(link.path) ? 'text-indigo-400' : 'text-slate-500'" />
          <span>{{ link.label }}</span>
        </a>
      </nav>
      
      <!-- Back to Portal Hub -->
      <div class="p-4 border-t border-slate-900">
        <Link href="/portal" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border border-slate-800 bg-slate-900/30 text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">
          <Icon name="ArrowLeft" :size="14" />
          <span>Dashboard Hub</span>
        </Link>
      </div>
    </aside>

    <!-- ===================== MAIN CONTENT PANEL ===================== -->
    <div class="lg:pl-[260px] min-h-screen flex flex-col">
      <!-- Topbar -->
      <header class="sticky top-0 z-30 h-16 flex items-center gap-3 px-4 sm:px-6 border-b border-slate-900 bg-slate-950/80 backdrop-blur-md">
        <button class="lg:hidden w-9 h-9 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-900" @click="sidebarOpen = true">
          <Icon name="Menu" :size="20" />
        </button>

        <div class="min-w-0">
          <h1 class="text-sm font-bold text-white uppercase tracking-wider">Website Administration</h1>
        </div>

        <div class="flex-1"></div>

        <button
          @click="toggleDarkMode"
          class="rounded-lg p-2 text-slate-400 hover:bg-slate-900 hover:text-slate-200 transition"
          :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        >
          <Icon v-if="isDark" name="Sun" :size="18" />
          <Icon v-else name="Moon" :size="18" />
        </button>

        <!-- Profile Dropdown -->
        <div class="relative" ref="userMenuRef">
          <button 
            @click="userMenuOpen = !userMenuOpen"
            class="flex items-center space-x-2 p-1 rounded-full hover:bg-slate-900 border border-transparent hover:border-slate-800 transition focus:outline-none cursor-pointer"
          >
            <img :src="userAvatar" alt="Profile" class="h-8 w-8 rounded-full object-cover ring-2 ring-indigo-500/20" />
            <span class="text-xs font-semibold text-slate-300 hidden sm:inline-block">{{ user?.name }}</span>
            <Icon name="ChevronDown" :size="10" class="text-slate-500 hidden sm:inline-block transition-transform duration-200" :class="{ 'rotate-180': userMenuOpen }" />
          </button>

          <Transition
            enter-active-class="transition duration-150 ease-out" 
            enter-from-class="opacity-0 -translate-y-1" 
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in" 
            leave-from-class="opacity-100" 
            leave-to-class="opacity-0 -translate-y-1"
          >
            <div v-if="userMenuOpen" class="absolute right-0 mt-2 w-64 rounded-2xl border border-slate-850 bg-slate-900/95 backdrop-blur-xl shadow-2xl z-50 overflow-hidden py-1">
              <div class="px-4 py-3 border-b border-slate-800/60 mb-1">
                <p class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">Logged In As</p>
                <p class="text-sm font-bold text-white truncate">{{ user?.name }}</p>
              </div>

              <Link href="/portal" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/50 transition" @click="userMenuOpen = false">
                <Icon name="LayoutDashboard" :size="15" class="text-slate-500" />
                <span>Dashboard Hub</span>
              </Link>

              <a :href="route('profile.edit', { from: 'website' })" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/50 transition" @click="userMenuOpen = false">
                <Icon name="User" :size="15" class="text-slate-500" />
                <span>View Profile</span>
              </a>

              <div class="border-t border-slate-800/60 my-1"></div>

              <a :href="lmsUrl" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/50 transition" @click="userMenuOpen = false">
                <Icon name="Briefcase" :size="15" class="text-slate-500" />
                <span>{{ lmsLabel }}</span>
              </a>

              <a v-if="hasErpAccess" :href="route('dashboard')" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/50 transition" @click="userMenuOpen = false">
                <Icon name="ShieldCheck" :size="15" class="text-slate-500" />
                <span>ERP Portal</span>
              </a>

              <a v-if="hasLibraryAccess" :href="route('library.dashboard')" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/50 transition" @click="userMenuOpen = false">
                <Icon name="FolderOpen" :size="15" class="text-slate-500" />
                <span>Digital Library</span>
              </a>

              <a v-if="isWebsiteAdmin" :href="route('website.admin.dashboard')" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/50 transition" @click="userMenuOpen = false">
                <Icon name="Globe" :size="15" class="text-slate-500" />
                <span>Website Admin</span>
              </a>

              <div class="border-t border-slate-800/60 my-1"></div>

              <Link :href="route('logout')" method="post" as="button" class="w-full flex items-center gap-2 px-4 py-2.5 text-left text-xs font-bold text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition-colors cursor-pointer" @click="userMenuOpen = false">
                <Icon name="LogOut" :size="15" />
                <span>Sign Out</span>
              </Link>
            </div>
          </Transition>
        </div>
      </header>

      <!-- Main Slot Content -->
      <main class="flex-grow p-4 sm:p-6 lg:p-8">
        <slot />
      </main>
    </div>
  </div>
</template>
