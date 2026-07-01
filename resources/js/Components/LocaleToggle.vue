<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import Icon from '@/Components/Icon.vue';

const { locale } = useTranslations();
const open = ref(false);

const options = [
  { v: 'en', l: 'English' },
  { v: 'am', l: 'አማርኛ (Amharic)' },
  { v: 'om', l: 'Oromoo (Oromo)' },
  { v: 'ti', l: 'ትግርኛ (Tigrinya)' },
  { v: 'so', l: 'Soomaali (Somali)' },
  { v: 'sw', l: 'Kiswahili (Swahili)' },
  { v: 'zh', l: '中文 (Mandarin)' },
  { v: 'fr', l: 'Français (French)' },
  { v: 'es', l: 'Español (Spanish)' },
  { v: 'ku', l: 'Kurdî (Kurdish)' },
  { v: 'ur', l: 'اردو (Urdu)' }
];

const currentLabel = computed(() => {
  return options.find(opt => opt.v === locale.value)?.l.split(' ')[0] ?? 'English';
});

const setLocale = (value) => {
  open.value = false;
  if (value === locale.value) return;
  router.post('/locale', { locale: value }, { preserveScroll: true });
};
</script>

<template>
  <div class="relative">
    <button
      @click="open = !open"
      class="flex items-center gap-1.5 h-9 px-3 rounded-xl border border-slate-800 bg-slate-900/50 text-xs font-semibold text-slate-300 hover:border-slate-700 transition-colors focus:outline-none cursor-pointer"
    >
      <Icon name="Globe" :size="14" class="text-slate-400" />
      <span>{{ currentLabel }}</span>
      <Icon name="ChevronDown" :size="12" class="text-slate-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" />
    </button>

    <!-- click-away overlay -->
    <div v-if="open" class="fixed inset-0 z-40" @click="open = false"></div>

    <Transition
      enter-active-class="transition duration-150 ease-out" 
      enter-from-class="opacity-0 -translate-y-1" 
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in" 
      leave-from-class="opacity-100" 
      leave-to-class="opacity-0 -translate-y-1"
    >
      <div 
        v-if="open" 
        class="absolute right-0 mt-2 w-48 rounded-2xl border border-slate-800 bg-slate-900/95 backdrop-blur-xl shadow-2xl z-50 py-1.5 max-h-80 overflow-y-auto"
      >
        <button
          v-for="opt in options" :key="opt.v"
          @click="setLocale(opt.v)"
          class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-left transition-colors cursor-pointer"
          :class="locale === opt.v ? 'text-blue-400 bg-slate-800/40' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/20'"
        >
          <span>{{ opt.l }}</span>
          <span v-if="locale === opt.v" class="text-blue-400 font-bold">✓</span>
        </button>
      </div>
    </Transition>
  </div>
</template>
