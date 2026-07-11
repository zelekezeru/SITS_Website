<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const toast = ref(null);

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

const showToast = (message, type = 'success') => {
  toast.value = { message, type };
  setTimeout(() => {
    toast.value = null;
  }, 4500);
};

// Monitor Inertia session flash messages
watch(() => page.props.flash, (flash) => {
  if (flash?.success) {
    showToast(flash.success, 'success');
  } else if (flash?.error) {
    showToast(flash.error, 'error');
  }
}, { deep: true, immediate: true });
</script>

<template>
  <div class="relative min-h-screen bg-slate-950 text-slate-100 flex flex-col overflow-hidden">
    <!-- Glowing background accent -->
    <div class="absolute top-[-20%] right-[-10%] w-[600px] h-[600px] rounded-full bg-blue-600/5 blur-[150px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[600px] h-[600px] rounded-full bg-purple-600/5 blur-[150px] pointer-events-none"></div>

    <!-- Sliding Floating Toast Alert -->
    <Transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="toast" 
        class="fixed bottom-6 right-6 z-50 p-4 rounded-2xl border shadow-2xl backdrop-blur-xl flex items-center gap-3 max-w-sm"
        :class="toast.type === 'success' 
          ? 'border-emerald-500/30 bg-emerald-950/90 text-emerald-300' 
          : 'border-rose-500/30 bg-rose-950/90 text-rose-300'"
      >
        <span class="text-xl shrink-0">{{ toast.type === 'success' ? '🎉' : '⚠️' }}</span>
        <div class="text-sm font-semibold tracking-wide">{{ toast.message }}</div>
      </div>
    </Transition>

    <!-- Header Navigation -->
    <header class="relative z-10 border-b border-slate-900 bg-slate-950/80 backdrop-blur-md sticky top-0">
      <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Link href="/" class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-500 to-purple-600 flex items-center justify-center font-bold text-lg text-white shadow-lg shadow-blue-500/20">
            S
          </Link>
          <div>
            <span class="text-xl font-bold tracking-tight text-white">SITS</span>
            <span class="text-xs font-semibold px-1.5 py-0.5 ml-2 rounded bg-blue-500/10 border border-blue-500/20 text-blue-400 uppercase tracking-widest">Dashboard</span>
          </div>
        </div>

        <div class="flex items-center gap-6">
          <!-- User Profile Dropdown -->
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
              <ul v-if="userMenuOpen" class="absolute right-0 mt-2 w-56 bg-slate-900/95 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-2xl py-2 z-50">
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
        </div>
      </div>
    </header>

    <!-- Main Content wrapper -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-6 py-10 relative z-10">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-900 py-8 bg-slate-950/40 mt-12 text-center text-xs text-slate-650">
      <p>© 2026 SITS ERP. Core Dashboard System. Authorized Access Only.</p>
    </footer>
  </div>
</template>
