<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const userAvatar = computed(() => {
  const img = user.value?.profile_image;
  return img ? `/storage/${img}` : '/img/user.png';
});

const hasErpAccess = computed(() => {
  const roles = (user.value?.roles ?? []).map(r => r.toLowerCase());
  if (!roles.length) return false;
  return !roles.includes('student');
});

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

const isWebsiteAdmin = computed(() => {
  const roles = (user.value?.roles ?? []).map(r => r.toLowerCase());
  return roles.some(r => ['superadmin', 'admin', 'editor'].includes(r));
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
</script>

<template>
  <Head title="Welcome to SITS ERP" />
  
  <div class="relative min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between items-center p-6 overflow-hidden">
    <!-- Glowing background accents -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] rounded-full bg-blue-600/10 blur-[120px] pointer-events-none"></div>
    <div class="absolute top-1/3 left-1/3 -translate-x-1/2 -translate-y-1/2 w-[300px] h-[300px] rounded-full bg-purple-600/10 blur-[100px] pointer-events-none"></div>

    <!-- Top Navigation -->
    <header class="w-full max-w-5xl flex items-center justify-between py-6 relative z-10">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-500 to-purple-600 flex items-center justify-center font-bold text-base text-white shadow-lg shadow-blue-500/20">
          S
        </div>
        <span class="text-lg font-bold tracking-tight text-white">SITS ERP</span>
      </div>

      <div v-if="user" class="relative" ref="userMenuRef">
        <button @click="userMenuOpen = !userMenuOpen"
          class="flex items-center gap-2 p-1 rounded-full hover:bg-slate-900 border border-transparent hover:border-slate-800 transition focus:outline-none cursor-pointer">
          <img :src="userAvatar" alt="Profile" class="h-8 w-8 rounded-full object-cover ring-2 ring-indigo-500/20" />
          <span class="text-xs font-semibold text-slate-300 hidden sm:inline-block">{{ user.name }}</span>
          <svg class="w-3 h-3 text-slate-500 hidden sm:block transition-transform duration-200" :class="{'rotate-180': userMenuOpen}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>

        <!-- Dropdown -->
        <Transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100"
          leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
          <ul v-if="userMenuOpen" class="absolute right-0 mt-2 w-56 bg-slate-900/95 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-2xl py-2 z-50 text-left">
            <li class="px-4 py-2 border-b border-slate-800/60 mb-1 sm:hidden">
              <p class="text-[10px] text-slate-500 uppercase tracking-wider">Logged in as</p>
              <p class="text-xs font-semibold text-white truncate">{{ user.name }}</p>
            </li>
            <li><Link :href="route('portal')" class="block px-4 py-2.5 text-xs text-slate-400 hover:text-white hover:bg-slate-800/40 transition">Dashboard Hub</Link></li>
            <li><Link :href="route('profile.edit', { from: 'website' })" class="block px-4 py-2.5 text-xs text-slate-400 hover:text-white hover:bg-slate-800/40 transition">View Profile</Link></li>
            <li class="border-t border-slate-800/60 my-1"></li>
            <li><a :href="lmsUrl" class="block px-4 py-2.5 text-xs text-slate-400 hover:text-white hover:bg-slate-800/40 transition">{{ lmsLabel }}</a></li>
            <li v-if="hasErpAccess"><Link :href="route('dashboard')" class="block px-4 py-2.5 text-xs text-slate-400 hover:text-white hover:bg-slate-800/40 transition">ERP Portal</Link></li>
            <li v-if="hasLibraryAccess"><Link :href="route('library.dashboard')" class="block px-4 py-2.5 text-xs text-slate-400 hover:text-white hover:bg-slate-800/40 transition">Digital Library</Link></li>
            <li v-if="isWebsiteAdmin">
              <a :href="route('website.admin.dashboard')" class="block px-4 py-2.5 text-xs text-slate-400 hover:text-white hover:bg-slate-800/40 transition">
                Website Admin
              </a>
            </li>
            <li class="border-t border-slate-800/60 my-1"></li>
            <li>
              <Link :href="route('logout')" method="post" as="button" class="block w-full text-left px-4 py-2.5 text-xs text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition cursor-pointer">Sign Out</Link>
            </li>
          </ul>
        </Transition>
      </div>

      <Link v-else :href="route('login')" class="text-sm font-medium text-slate-400 hover:text-white transition-colors">
        Sign In
      </Link>
    </header>

    <!-- Centered Hero / CTA -->
    <main class="w-full max-w-2xl text-center my-auto relative z-10 flex flex-col items-center">
      <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight leading-tight">
        Enterprise Management 
        <span class="block bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400 bg-clip-text text-transparent mt-2">
          Simplified.
        </span>
      </h1>
      
      <p class="mt-6 text-base md:text-lg text-slate-400 max-w-md leading-relaxed">
        SITS ERP unifies financial ledgers, inventory streams, resource planning, and deep analytics in one fluid interface.
      </p>
      
      <div class="mt-10 flex flex-col sm:flex-row gap-4 items-center justify-center w-full sm:w-auto">
        <Link :href="route('login')" class="w-full sm:w-auto px-8 py-3.5 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white font-semibold rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 hover:scale-[1.02]">
          Sign In to System
        </Link>
        <Link :href="route('register')" class="w-full sm:w-auto px-8 py-3.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 hover:bg-slate-900/80 text-slate-300 hover:text-white font-semibold rounded-xl transition-all duration-200">
          Create Account
        </Link>
      </div>
    </main>

    <!-- Footer -->
    <footer class="w-full max-w-5xl py-6 border-t border-slate-900 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-600 relative z-10 gap-2">
      <p>© 2026 SITS ERP. All rights reserved.</p>
      <div class="flex gap-4">
        <a href="#" class="hover:text-slate-400 transition-colors">Privacy Policy</a>
        <a href="#" class="hover:text-slate-400 transition-colors">Terms of Service</a>
      </div>
    </footer>
  </div>
</template>
