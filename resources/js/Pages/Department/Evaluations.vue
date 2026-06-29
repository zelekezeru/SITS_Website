<script>
import PortalLayout from '@/Layouts/PortalLayout.vue';
export default { layout: PortalLayout };
</script>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  evaluations: { type: Array, default: () => [] },
  team: { type: Array, default: () => [] },
  periods: { type: Array, default: () => [] },
  weights: { type: Object, default: () => ({ auto: 0.4, manager: 0.4, executive: 0.2 }) },
  can: { type: Object, default: () => ({ score: false }) },
});

const openPeriods = computed(() => props.periods.filter((p) => p.status !== 'locked'));

const modalOpen = ref(false);
const mode = ref('create'); // 'create' | 'score'
const current = ref(null);

const createForm = useForm({ employee_id: '', evaluation_period_id: '', manager_score: 0 });
const scoreForm = useForm({ manager_score: 0 });

const openCreate = () => {
  mode.value = 'create';
  createForm.reset();
  if (props.team.length) createForm.employee_id = props.team[0].id;
  if (openPeriods.value.length) createForm.evaluation_period_id = openPeriods.value[0].id;
  createForm.clearErrors();
  modalOpen.value = true;
};

const openScore = (evaluation) => {
  mode.value = 'score';
  current.value = evaluation;
  scoreForm.manager_score = evaluation.manager_score != null ? Number(evaluation.manager_score) : 0;
  scoreForm.clearErrors();
  modalOpen.value = true;
};

const submit = () => {
  if (mode.value === 'create') {
    createForm.post('/department/evaluations', { onSuccess: () => (modalOpen.value = false) });
  } else {
    scoreForm.post(`/department/evaluations/${current.value.id}/score`, { onSuccess: () => (modalOpen.value = false) });
  }
};

// Live preview of the weighted final score.
const preview = computed(() => {
  const w = props.weights;
  if (mode.value === 'create') {
    return (Number(createForm.manager_score) || 0) * w.manager; // auto & exec unknown yet
  }
  const e = current.value || {};
  return (Number(e.auto_score) || 0) * w.auto
    + (Number(scoreForm.manager_score) || 0) * w.manager
    + (Number(e.executive_score) || 0) * w.executive;
});

const periodLocked = (e) => e.period?.status === 'locked';

const label = (s) => (s ?? '').replace(/_/g, ' ');
const STATUS = {
  draft: 'bg-slate-800/60 border-slate-800 text-slate-400',
  employee_ack: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  disputed: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
  finalized: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
};
const statusClass = (s) => STATUS[s] ?? STATUS.draft;
const scoreColor = (v) => {
  if (v == null) return 'text-slate-500';
  const n = Number(v);
  if (n >= 80) return 'text-emerald-400';
  if (n >= 60) return 'text-blue-400';
  if (n >= 40) return 'text-amber-400';
  return 'text-rose-400';
};
</script>

<template>
  <Head title="Team Evaluations — SITS ERP" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-amber-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0"><Icon name="Star" :size="26" /></span>
          <div>
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Performance</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Team Evaluations</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">Enter your <span class="text-slate-300 font-semibold">manager score</span> (40% of the final). Auto and executive components are added elsewhere.</p>
          </div>
        </div>
        <button v-if="can.score" @click="openCreate"
                class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 cursor-pointer">
          + New Scorecard
        </button>
      </div>
    </section>

    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Employee</th>
              <th class="pb-3">Period</th>
              <th class="pb-3 text-center">Auto</th>
              <th class="pb-3 text-center">Manager</th>
              <th class="pb-3 text-center">Exec</th>
              <th class="pb-3 text-center">Final</th>
              <th class="pb-3">Grade</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="e in evaluations" :key="e.id" class="hover:bg-slate-900/40">
              <td class="py-4 font-semibold text-slate-200">{{ e.employee?.full_name_en }}</td>
              <td class="py-4 text-slate-400">
                {{ e.period?.name }}
                <span v-if="periodLocked(e)" class="ml-1 text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-slate-800 text-slate-500 border border-slate-700">Locked</span>
              </td>
              <td class="py-4 text-center font-mono text-slate-400">{{ e.auto_score != null ? Number(e.auto_score).toFixed(0) : '—' }}</td>
              <td class="py-4 text-center font-mono text-slate-200 font-bold">{{ e.manager_score != null ? Number(e.manager_score).toFixed(0) : '—' }}</td>
              <td class="py-4 text-center font-mono text-slate-400">{{ e.executive_score != null ? Number(e.executive_score).toFixed(0) : '—' }}</td>
              <td class="py-4 text-center font-mono font-bold" :class="scoreColor(e.final_score)">{{ e.final_score != null ? Number(e.final_score).toFixed(1) : '—' }}</td>
              <td class="py-4">
                <span v-if="e.grade_band" class="text-[11px] font-bold px-2 py-1 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400">{{ e.grade_band.label_en }}</span>
                <span v-else class="text-slate-600">—</span>
              </td>
              <td class="py-4 text-right">
                <button v-if="can.score && !periodLocked(e)" @click="openScore(e)"
                        class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-blue-500/40 bg-slate-900/50 text-slate-300 hover:text-blue-300 rounded-lg transition-colors cursor-pointer">Score</button>
                <span v-else class="text-[10px] uppercase tracking-wide font-bold px-2 py-0.5 rounded border capitalize" :class="statusClass(e.status)">{{ label(e.status) }}</span>
              </td>
            </tr>
            <tr v-if="!evaluations.length"><td colspan="8" class="py-12 text-center text-slate-600 italic">No evaluations yet. Create a scorecard to begin.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-1">{{ mode === 'create' ? 'New Scorecard' : 'Manager Score' }}</h3>
        <p class="text-xs text-slate-500 mb-6">
          {{ mode === 'create' ? 'Open a draft scorecard and record your manager score.' : `Scoring ${current?.employee?.full_name_en} · ${current?.period?.name}` }}
        </p>

        <form @submit.prevent="submit" class="space-y-5">
          <template v-if="mode === 'create'">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Team Member</label>
              <select v-model="createForm.employee_id" required class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none text-sm">
                <option value="" disabled>Select</option>
                <option v-for="m in team" :key="m.id" :value="m.id">{{ m.full_name_en }}</option>
              </select>
              <p v-if="createForm.errors.employee_id" class="text-xs text-rose-400 mt-1">{{ createForm.errors.employee_id }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Evaluation Period</label>
              <select v-model="createForm.evaluation_period_id" required class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none text-sm">
                <option value="" disabled>Select an open period</option>
                <option v-for="p in openPeriods" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
              <p v-if="!openPeriods.length" class="text-xs text-amber-400 mt-1">No open evaluation periods. Ask an administrator to open one.</p>
              <p v-if="createForm.errors.evaluation_period_id" class="text-xs text-rose-400 mt-1">{{ createForm.errors.evaluation_period_id }}</p>
            </div>
          </template>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Manager Score (0–100)</label>
            <input v-if="mode === 'create'" v-model="createForm.manager_score" type="number" min="0" max="100" step="0.5" required
                   class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none text-sm font-mono" />
            <input v-else v-model="scoreForm.manager_score" type="number" min="0" max="100" step="0.5" required
                   class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none text-sm font-mono" />
          </div>

          <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-4 flex items-center justify-between">
            <span class="text-xs text-slate-500">{{ mode === 'create' ? 'Your contribution (40%)' : 'Projected final score' }}</span>
            <span class="text-lg font-bold font-mono" :class="scoreColor(preview)">{{ Number(preview).toFixed(1) }}</span>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="modalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer">Cancel</button>
            <button type="submit" :disabled="createForm.processing || scoreForm.processing" class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 cursor-pointer">Save Score</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
