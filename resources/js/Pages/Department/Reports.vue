<script>
import PortalLayout from '@/Layouts/PortalLayout.vue';
export default { layout: PortalLayout };
</script>

<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  metrics: { type: Object, default: () => ({}) },
  kpiStatus: { type: Object, default: () => ({ created: 0, approved: 0, confirmed: 0 }) },
  scorecards: { type: Array, default: () => [] },
});

const kpiTotal = computed(() => (props.kpiStatus.created || 0) + (props.kpiStatus.approved || 0) + (props.kpiStatus.confirmed || 0));
const pct = (n) => (kpiTotal.value ? Math.round((n / kpiTotal.value) * 100) : 0);

const scoreColor = (v) => {
  if (v == null) return 'text-slate-500';
  const n = Number(v);
  if (n >= 80) return 'text-emerald-400';
  if (n >= 60) return 'text-blue-400';
  if (n >= 40) return 'text-amber-400';
  return 'text-rose-400';
};

const cards = computed(() => [
  { label: 'Team Members', value: props.metrics.team ?? 0, hint: 'In your department(s)' },
  { label: 'Task Completion', value: (props.metrics.completion_rate ?? 0) + '%', hint: `${props.metrics.tasks_completed ?? 0} of ${props.metrics.tasks_total ?? 0} done` },
  { label: 'Avg. Score', value: props.metrics.avg_score != null ? props.metrics.avg_score : '—', hint: 'Latest evaluations' },
  { label: 'KPIs Confirmed', value: `${props.metrics.kpis_confirmed ?? 0}/${props.metrics.kpis_total ?? 0}`, hint: 'Counting toward scores' },
]);

const print = () => window.print();
</script>

<template>
  <Head title="Department Report — SITS ERP" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8 print:bg-white print:text-black">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none print:hidden"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0 print:hidden"><Icon name="PieChart" :size="26" /></span>
          <div>
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Insights</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Department Report</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">Completion, KPI progress and evaluation analytics for your team.</p>
          </div>
        </div>
        <button @click="print" class="shrink-0 text-sm font-semibold border border-slate-800 hover:border-slate-700 bg-slate-900/50 text-slate-300 px-5 py-2.5 rounded-xl transition-colors cursor-pointer flex items-center gap-2 print:hidden">
          <Icon name="FileText" :size="16" /> Print / Export
        </button>
      </div>
    </section>

    <!-- Metric cards -->
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="c in cards" :key="c.label" class="p-6 rounded-2xl border border-slate-900 bg-slate-900/35">
        <span class="text-xs text-slate-500 block font-semibold uppercase tracking-wider mb-2">{{ c.label }}</span>
        <span class="text-3xl font-extrabold text-white">{{ c.value }}</span>
        <span class="text-xs text-slate-500 block mt-2 font-medium">{{ c.hint }}</span>
      </div>
    </div>

    <div class="grid lg:grid-cols-12 gap-6">
      <!-- KPI pipeline -->
      <div class="lg:col-span-5 rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md p-6">
        <h3 class="font-bold text-lg text-white flex items-center gap-2 mb-5"><Icon name="Gauge" :size="19" class="text-blue-400" /> KPI Pipeline</h3>
        <div class="space-y-4">
          <div>
            <div class="flex justify-between text-sm mb-1"><span class="text-amber-400 font-semibold">Created (awaiting approval)</span><span class="font-mono text-slate-300">{{ kpiStatus.created }}</span></div>
            <div class="bg-slate-950/80 rounded-full h-2 overflow-hidden"><div class="bg-amber-500 h-full rounded-full" :style="`width:${pct(kpiStatus.created)}%`"></div></div>
          </div>
          <div>
            <div class="flex justify-between text-sm mb-1"><span class="text-blue-400 font-semibold">Approved (maker done)</span><span class="font-mono text-slate-300">{{ kpiStatus.approved }}</span></div>
            <div class="bg-slate-950/80 rounded-full h-2 overflow-hidden"><div class="bg-blue-500 h-full rounded-full" :style="`width:${pct(kpiStatus.approved)}%`"></div></div>
          </div>
          <div>
            <div class="flex justify-between text-sm mb-1"><span class="text-emerald-400 font-semibold">Confirmed (counts to score)</span><span class="font-mono text-slate-300">{{ kpiStatus.confirmed }}</span></div>
            <div class="bg-slate-950/80 rounded-full h-2 overflow-hidden"><div class="bg-emerald-500 h-full rounded-full" :style="`width:${pct(kpiStatus.confirmed)}%`"></div></div>
          </div>
          <p v-if="!kpiTotal" class="text-sm text-slate-600 italic pt-2">No KPIs assigned to your team yet.</p>
        </div>
      </div>

      <!-- Scorecards -->
      <div class="lg:col-span-7 rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md p-6">
        <h3 class="font-bold text-lg text-white flex items-center gap-2 mb-5"><Icon name="Users" :size="19" class="text-emerald-400" /> Team Scorecard</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
                <th class="pb-3">Member</th>
                <th class="pb-3 text-center">Tasks</th>
                <th class="pb-3">Completion</th>
                <th class="pb-3 text-right">Score</th>
              </tr>
            </thead>
            <tbody class="text-sm divide-y divide-slate-900">
              <tr v-for="s in scorecards" :key="s.id" class="hover:bg-slate-900/40">
                <td class="py-3 font-semibold text-slate-200">{{ s.name }}</td>
                <td class="py-3 text-center font-mono text-slate-400">{{ s.completed }}/{{ s.tasks }}</td>
                <td class="py-3">
                  <div class="flex items-center gap-2">
                    <div class="w-24 bg-slate-950/80 rounded-full h-1.5 overflow-hidden"><div class="bg-emerald-500 h-full rounded-full" :style="`width:${s.completion}%`"></div></div>
                    <span class="text-xs font-mono text-slate-400">{{ s.completion }}%</span>
                  </div>
                </td>
                <td class="py-3 text-right font-bold" :class="scoreColor(s.score)">{{ s.score != null ? s.score : '—' }}</td>
              </tr>
              <tr v-if="!scorecards.length"><td colspan="4" class="py-10 text-center text-slate-600 italic">No team data to report.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
