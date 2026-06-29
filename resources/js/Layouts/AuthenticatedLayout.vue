<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const toast = ref(null);

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
          <div class="hidden sm:flex flex-col text-right">
            <span class="text-sm font-semibold text-white">{{ user?.name }}</span>
            <span class="text-xs text-slate-400 font-medium">{{ user?.roles.join(', ') || 'No Role Assigned' }}</span>
          </div>
          <Link 
            href="/logout" 
            method="post" 
            as="button" 
            class="text-xs font-semibold px-4 py-2 border border-slate-800 hover:border-slate-700 bg-slate-900/50 hover:bg-rose-950/20 hover:text-rose-400 rounded-lg transition-all duration-200 cursor-pointer"
          >
            Sign Out
          </Link>
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
