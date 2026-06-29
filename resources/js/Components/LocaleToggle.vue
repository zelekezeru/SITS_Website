<script setup>
import { router } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { locale } = useTranslations();

const setLocale = (value) => {
  if (value === locale.value) return;
  router.post('/locale', { locale: value }, { preserveScroll: true });
};
</script>

<template>
  <div class="flex items-center rounded-lg border border-slate-800 bg-slate-900/50 p-0.5 text-[11px] font-bold">
    <button
      v-for="opt in [{ v: 'en', l: 'EN' }, { v: 'am', l: 'አማ' }]" :key="opt.v"
      @click="setLocale(opt.v)"
      class="px-2 py-1 rounded-md transition-colors"
      :class="locale === opt.v ? 'bg-slate-800 text-white' : 'text-slate-500 hover:text-slate-300'"
    >
      {{ opt.l }}
    </button>
  </div>
</template>
