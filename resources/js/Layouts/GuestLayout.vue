<script setup>
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const page = usePage();
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
  <div class="relative min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center px-6 py-12 overflow-hidden">
    <!-- Background aura flares -->
    <div class="absolute top-[-10%] right-[-10%] w-[450px] h-[450px] rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] left-[-10%] w-[450px] h-[450px] rounded-full bg-purple-600/10 blur-[110px] pointer-events-none"></div>

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

    <div class="w-full max-w-lg relative z-10">
      <slot />
    </div>
  </div>
</template>
