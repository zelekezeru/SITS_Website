<script setup>
import { useConfirm } from '@/Composables/useConfirm';
import { AlertCircle } from 'lucide-vue-next';

const { confirmState } = useConfirm();

const handleCancel = () => {
  if (confirmState.value.onCancel) {
    confirmState.value.onCancel();
  }
};

const handleConfirm = () => {
  if (confirmState.value.onConfirm) {
    confirmState.value.onConfirm();
  }
};
</script>

<template>
  <Transition
    enter-active-class="ease-out duration-300 transition"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="ease-in duration-200 transition"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="confirmState.isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-md" @click="handleCancel"></div>

      <!-- Modal Content -->
      <div class="relative w-full max-w-md overflow-hidden rounded-3xl border border-slate-900 bg-slate-900/90 p-6 shadow-2xl backdrop-blur-xl transition-all">
        <!-- Decorative Glow -->
        <div class="absolute top-[-20%] right-[-10%] w-60 h-60 rounded-full bg-amber-500/10 blur-[80px] pointer-events-none"></div>

        <div class="relative z-10 flex flex-col items-center text-center">
          <!-- Icon -->
          <div class="w-16 h-16 rounded-full bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 animate-bounce">
            <AlertCircle :size="32" class="stroke-[1.75]" />
          </div>

          <!-- Title -->
          <h3 class="text-lg font-bold text-white tracking-tight">
            {{ confirmState.title }}
          </h3>

          <!-- Message -->
          <p class="text-sm text-slate-400 mt-2 leading-relaxed">
            {{ confirmState.message }}
          </p>

          <!-- Buttons -->
          <div class="flex items-center justify-center gap-3 w-full mt-6">
            <button
              type="button"
              @click="handleCancel"
              class="w-full rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-900 hover:text-white transition-all cursor-pointer focus:outline-none"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="handleConfirm"
              class="w-full rounded-xl bg-amber-500 text-slate-950 px-4 py-2.5 text-xs font-extrabold hover:bg-amber-400 hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer shadow-lg shadow-amber-500/20 focus:outline-none"
            >
              Yes, proceed
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>
