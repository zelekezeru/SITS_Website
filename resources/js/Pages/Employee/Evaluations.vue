<script>
import PortalLayout from '@/Layouts/PortalLayout.vue';
export default { layout: PortalLayout };
</script>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';

defineProps({
  evaluations: { type: Array, default: () => [] },
});

const expanded = ref(null);
const toggle = (id) => (expanded.value = expanded.value === id ? null : id);

const label = (s) => (s ?? '').replace(/_/g, ' ');
const fmtDate = (d) => (d ? new Date(d).toLocaleDateString() : '—');

const STATUS = {
  draft: 'bg-slate-800/60 border-slate-800 text-slate-400',
  employee_ack: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  disputed: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
  finalized: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
};
const statusClass = (s) => STATUS[s] ?? STATUS.draft;

const scoreColor = (v) => {
  const n = Number(v);
  if (n >= 80) return 'text-emerald-400';
  if (n >= 60) return 'text-blue-400';
  if (n >= 40) return 'text-amber-400';
  return 'text-rose-400';
};
</script>

<template>
  <Head title="My Evaluations — SITS ERP" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-amber-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0"><Icon name="Star" :size="26" /></span>
        <div>
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">My Growth</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">My Evaluations</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">Your multi-rater scorecards: <span class="text-slate-300">0.40 auto + 0.40 manager + 0.20 executive</span>.</p>
        </div>
      </div>
    </section>

    <div class="space-y-4">
      <div v-for="e in evaluations" :key="e.id" class="rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md overflow-hidden">
        <button @click="toggle(e.id)" class="w-full flex items-center gap-4 p-6 text-left hover:bg-slate-900/30 transition-colors">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <h3 class="font-bold text-white truncate">{{ e.period?.name || 'Evaluation' }}</h3>
              <span class="text-[9px] font-bold uppercase tracking-wide px-2 py-0.5 rounded border capitalize" :class="statusClass(e.status)">{{ label(e.status) }}</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
              <span v-if="e.period">{{ fmtDate(e.period.start_date) }} – {{ fmtDate(e.period.end_date) }}</span>
              <span v-if="e.grade_band"> · Grade {{ e.grade_band.label_en }}</span>
            </p>
          </div>
          <div class="text-right shrink-0">
            <p class="text-3xl font-extrabold" :class="scoreColor(e.final_score)">{{ e.final_score !== null ? Number(e.final_score).toFixed(1) : '—' }}</p>
            <p class="text-[10px] uppercase tracking-wide text-slate-500">Final / 100</p>
          </div>
          <Icon name="ChevronDown" :size="18" class="text-slate-500 transition-transform shrink-0" :class="expanded === e.id ? 'rotate-180' : ''" />
        </button>

        <div v-if="expanded === e.id" class="border-t border-slate-900 p-6 space-y-5">
          <!-- Component scores -->
          <div class="grid grid-cols-3 gap-4">
            <div class="rounded-xl border border-slate-900 bg-slate-950/40 p-4 text-center">
              <p class="text-[10px] uppercase tracking-wide text-slate-500">Auto (40%)</p>
              <p class="text-xl font-bold text-slate-200 mt-1">{{ e.auto_score !== null ? Number(e.auto_score).toFixed(0) : '—' }}</p>
            </div>
            <div class="rounded-xl border border-slate-900 bg-slate-950/40 p-4 text-center">
              <p class="text-[10px] uppercase tracking-wide text-slate-500">Manager (40%)</p>
              <p class="text-xl font-bold text-slate-200 mt-1">{{ e.manager_score !== null ? Number(e.manager_score).toFixed(0) : '—' }}</p>
            </div>
            <div class="rounded-xl border border-slate-900 bg-slate-950/40 p-4 text-center">
              <p class="text-[10px] uppercase tracking-wide text-slate-500">Executive (20%)</p>
              <p class="text-xl font-bold text-slate-200 mt-1">{{ e.executive_score !== null ? Number(e.executive_score).toFixed(0) : '—' }}</p>
            </div>
          </div>

          <!-- Per-KPI ratings + feedback -->
          <div v-if="e.ratings?.length">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Rater Feedback</p>
            <div class="space-y-2">
              <div v-for="r in e.ratings" :key="r.id" class="flex items-start gap-3 p-3 rounded-xl border border-slate-900 bg-slate-950/30">
                <span class="w-9 h-9 rounded-lg bg-slate-900 flex items-center justify-center text-sm font-bold shrink-0" :class="scoreColor(r.score)">{{ Number(r.score).toFixed(0) }}</span>
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-slate-200">{{ r.kpi?.title_en || 'KPI' }}</p>
                  <p class="text-xs text-slate-500"><span class="capitalize">{{ label(r.rater_type) }}</span><span v-if="r.rater"> · {{ r.rater.name }}</span></p>
                  <p v-if="r.comment_en" class="text-xs text-slate-400 mt-1 italic">“{{ r.comment_en }}”</p>
                </div>
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-slate-600 italic">No individual rater feedback recorded.</p>
        </div>
      </div>

      <div v-if="!evaluations.length" class="rounded-2xl border border-slate-900 bg-slate-900/10 p-12 text-center text-slate-600 italic">
        You have no evaluations on record yet.
      </div>
    </div>
  </div>
</template>
