<script>
import PortalLayout from '@/Layouts/PortalLayout.vue';
export default { layout: PortalLayout };
</script>

<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  kpis: { type: Array, default: () => [] },
});

const summary = computed(() => ({
  total: props.kpis.length,
  confirmed: props.kpis.filter((k) => k.confirmed_by).length,
  achieved: props.kpis.filter((k) => k.status === 'achieved').length,
}));

const KPI_BADGE = {
  created: 'bg-slate-800/60 border-slate-800 text-slate-400',
  in_progress: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  achieved: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
};
const badge = (s) => KPI_BADGE[s] ?? KPI_BADGE.created;
const label = (s) => (s ?? '').replace(/_/g, ' ');

// A KPI counts toward scoring only once it has cleared the checker step.
const stage = (k) => (k.confirmed_by ? 'Confirmed' : k.approved_by ? 'Approved' : 'Created');
const stageClass = (k) => k.confirmed_by
  ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400'
  : k.approved_by ? 'bg-blue-500/10 border-blue-500/20 text-blue-400'
  : 'bg-amber-500/10 border-amber-500/20 text-amber-400';
</script>

<template>
  <Head title="My KPIs — SITS ERP" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0"><Icon name="Gauge" :size="26" /></span>
        <div>
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">My Work</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">My KPIs</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">The indicators you are measured against. Only <span class="text-emerald-400 font-semibold">confirmed</span> KPIs count toward your score.</p>
        </div>
      </div>
    </section>

    <div class="grid grid-cols-3 gap-5">
      <div class="p-5 rounded-2xl border border-slate-900 bg-slate-900/35"><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Assigned</span><p class="text-2xl font-extrabold text-white mt-1">{{ summary.total }}</p></div>
      <div class="p-5 rounded-2xl border border-slate-900 bg-slate-900/35"><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Confirmed</span><p class="text-2xl font-extrabold text-emerald-400 mt-1">{{ summary.confirmed }}</p></div>
      <div class="p-5 rounded-2xl border border-slate-900 bg-slate-900/35"><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Achieved</span><p class="text-2xl font-extrabold text-white mt-1">{{ summary.achieved }}</p></div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div v-for="k in kpis" :key="k.id" class="rounded-2xl border border-slate-900 bg-slate-900/20 p-6 shadow-md">
        <div class="flex items-start justify-between gap-3">
          <h3 class="font-bold text-white leading-snug">{{ k.title_en }}</h3>
          <span class="text-[9px] font-bold uppercase tracking-wide px-2 py-1 rounded border shrink-0" :class="stageClass(k)">{{ stage(k) }}</span>
        </div>
        <p v-if="k.title_am" class="text-sm text-slate-500 mt-1">{{ k.title_am }}</p>

        <div class="flex flex-wrap items-center gap-2 mt-4 text-xs">
          <span class="px-2 py-0.5 rounded-md font-bold uppercase tracking-wider border capitalize" :class="badge(k.status)">{{ label(k.status) }}</span>
          <span class="px-2 py-0.5 rounded-md bg-slate-950/50 border border-slate-900 text-slate-400 capitalize">{{ label(k.measure_type) }}</span>
          <span class="px-2 py-0.5 rounded-md bg-slate-950/50 border border-slate-900 text-slate-400">Weight {{ Number(k.weight) }}</span>
        </div>

        <div v-if="k.target_value" class="mt-4 pt-4 border-t border-slate-900 flex items-center justify-between text-sm">
          <span class="text-slate-500">Target</span>
          <span class="font-mono font-bold text-slate-200">{{ Number(k.target_value) }} <span class="text-slate-500 font-sans">{{ k.unit }}</span></span>
        </div>
      </div>
      <div v-if="!kpis.length" class="md:col-span-2 rounded-2xl border border-slate-900 bg-slate-900/10 p-12 text-center text-slate-600 italic">
        No KPIs have been assigned to you yet.
      </div>
    </div>
  </div>
</template>
