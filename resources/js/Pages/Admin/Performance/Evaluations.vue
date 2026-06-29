<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, nextTick } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const page = usePage();
const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, required: true },
  routeName: { type: String, required: true },
  periods:     { type: Array, default: () => [] },
  evaluations: { type: Array, default: () => [] },
  ratings:     { type: Array, default: () => [] },
  employees:   { type: Array, default: () => [] },
  kpis:        { type: Array, default: () => [] },
  users:       { type: Array, default: () => [] },
  years:       { type: Array, default: () => [] },
  fortnights:  { type: Array, default: () => [] },
});

// ─── Active Tab ───────────────────────────────────────────────────────────────
const activeTab = ref(
  props.routeName === 'admin.evaluations.periods'  ? 'periods' :
  props.routeName === 'admin.evaluations.ratings'  ? 'ratings' : 'scorecards'
);

// ─── Period Modal ─────────────────────────────────────────────────────────────
const periodModalOpen = ref(false);

const periodForm = useForm({
  name: '',
  cadence: 'quarterly',
  start_date: '',
  end_date: '',
  formula_version: 'v1',
});

const openPeriodModal = () => {
  periodForm.reset();
  periodForm.clearErrors();
  periodModalOpen.value = true;
};

const submitPeriodForm = () => {
  periodForm.post('/admin/evaluations/periods', {
    onSuccess: () => { periodModalOpen.value = false; },
  });
};

const togglePeriod = (id) => router.post(`/admin/evaluations/periods/${id}/toggle`);

// ─── Scorecard Modal ──────────────────────────────────────────────────────────
const evalModalOpen = ref(false);
const editingEval = ref(null);

const evalForm = useForm({
  employee_id: '',
  evaluation_period_id: '',
  auto_score: '',
  manager_score: '',
  executive_score: '',
});

const openEvalModal = (evalItem = null) => {
  editingEval.value = evalItem;
  if (evalItem) {
    evalForm.employee_id            = evalItem.employee_id;
    evalForm.evaluation_period_id   = evalItem.evaluation_period_id;
    evalForm.auto_score             = evalItem.auto_score ?? '';
    evalForm.manager_score          = evalItem.manager_score ?? '';
    evalForm.executive_score        = evalItem.executive_score ?? '';
  } else {
    evalForm.reset();
    evalForm.auto_score = '';
    evalForm.manager_score = '';
    evalForm.executive_score = '';
    if (props.employees.length) evalForm.employee_id          = props.employees[0].id;
    if (props.periods.length)   evalForm.evaluation_period_id = props.periods[0].id;
  }
  evalForm.clearErrors();
  evalModalOpen.value = true;
};

const submitEvalForm = () => {
  if (editingEval.value) {
    evalForm.put(`/admin/evaluations/${editingEval.value.id}`, {
      onSuccess: () => { evalModalOpen.value = false; },
    });
  } else {
    evalForm.post('/admin/evaluations', {
      onSuccess: () => { evalModalOpen.value = false; },
    });
  }
};

// ─── Rating Modal ─────────────────────────────────────────────────────────────
const ratingModalOpen = ref(false);
const editingRating = ref(null);

const ratingForm = useForm({
  evaluation_id: '',
  rater_user_id: '',
  rater_type: 'manager',
  kpi_id: '',
  score: 0,
  comment_en: '',
  comment_am: '',
});

const openRatingModal = (ratingItem = null) => {
  editingRating.value = ratingItem;
  if (ratingItem) {
    ratingForm.evaluation_id = ratingItem.evaluation_id;
    ratingForm.rater_user_id = ratingItem.rater_user_id;
    ratingForm.rater_type    = ratingItem.rater_type;
    ratingForm.kpi_id        = ratingItem.kpi_id;
    ratingForm.score         = ratingItem.score;
    ratingForm.comment_en    = ratingItem.comment_en || '';
    ratingForm.comment_am    = ratingItem.comment_am || '';
  } else {
    ratingForm.reset();
    ratingForm.score = 0;
    ratingForm.comment_en = '';
    ratingForm.comment_am = '';
    ratingForm.rater_user_id = page.props.auth?.user?.id || '';
    if (props.evaluations.length) ratingForm.evaluation_id = props.evaluations[0].id;
    if (props.kpis.length) ratingForm.kpi_id = props.kpis[0].id;
  }
  ratingForm.clearErrors();
  ratingModalOpen.value = true;
};

const submitRatingForm = () => {
  if (editingRating.value) {
    ratingForm.put(`/admin/evaluations/ratings/${editingRating.value.id}`, {
      onSuccess: () => { ratingModalOpen.value = false; }
    });
  } else {
    ratingForm.post('/admin/evaluations/ratings', {
      onSuccess: () => { ratingModalOpen.value = false; }
    });
  }
};

const deleteRating = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Rating',
    message: 'Are you sure you want to delete this evaluation rating?',
  });
  if (confirmed) {
    router.delete(`/admin/evaluations/ratings/${id}`, { preserveScroll: true });
  }
};

// ─── Auto Score Computation ───────────────────────────────────────────────────
const computingAutoScore = ref({});  // { [evalId]: true }
const autoScoreBreakdowns = ref({}); // { [evalId]: { tasks: {...}, ... } }
const breakdownPopover = ref(null);  // which evalId popover is open
const computingAll = ref({});        // { [periodId]: true }

const computeAutoScore = async (evalId) => {
  computingAutoScore.value[evalId] = true;
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const res = await fetch(`/admin/evaluations/${evalId}/auto-score`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
      },
    });
    const data = await res.json();
    if (res.ok) {
      autoScoreBreakdowns.value[evalId] = data.breakdown;
      // Refresh the page data to reflect new scores
      router.reload({ preserveScroll: true });
    }
  } catch (e) {
    console.error('Auto-score computation failed:', e);
  } finally {
    computingAutoScore.value[evalId] = false;
  }
};

const computeAllAutoScores = async (periodId) => {
  computingAll.value[periodId] = true;
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const res = await fetch(`/admin/evaluations/periods/${periodId}/auto-score-all`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
      },
    });
    if (res.ok) {
      router.reload({ preserveScroll: true });
    }
  } catch (e) {
    console.error('Batch auto-score computation failed:', e);
  } finally {
    computingAll.value[periodId] = false;
  }
};

const toggleBreakdown = (evalId) => {
  breakdownPopover.value = breakdownPopover.value === evalId ? null : evalId;
};

const breakdownLabel = (key) => {
  const labels = { tasks: 'Tasks', deliverables: 'Deliverables', kpis: 'KPIs', attendance: 'Attendance' };
  return labels[key] || key;
};

const breakdownIcon = (key) => {
  const icons = { tasks: 'CheckSquare', deliverables: 'FileCheck', kpis: 'Target', attendance: 'Clock' };
  return icons[key] || 'Circle';
};

// ─── Computed stats ───────────────────────────────────────────────────────────
const openPeriods  = computed(() => props.periods.filter(p => p.status === 'open').length);
const avgFinalScore = computed(() => {
  if (!props.evaluations.length) return 0;
  const total = props.evaluations.reduce((s, e) => s + Number(e.final_score || 0), 0);
  return (total / props.evaluations.length).toFixed(1);
});
const gradedCount = computed(() => props.evaluations.filter(e => e.grade_band).length);

// ─── Score colour helper ──────────────────────────────────────────────────────
const scoreColor = (score) => {
  const n = Number(score || 0);
  if (n >= 90) return 'text-emerald-400';
  if (n >= 75) return 'text-blue-400';
  if (n >= 60) return 'text-amber-400';
  return 'text-rose-400';
};

const scoreBg = (score) => {
  const n = Number(score || 0);
  if (n >= 90) return 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400';
  if (n >= 75) return 'bg-blue-500/10 border-blue-500/25 text-blue-400';
  if (n >= 60) return 'bg-amber-500/10 border-amber-500/25 text-amber-400';
  return 'bg-rose-500/10 border-rose-500/25 text-rose-400';
};

const periodStatus = (p) =>
  p.status === 'open'
    ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400'
    : 'bg-slate-800/60 border-slate-800 text-slate-500';

const getOverlappingFortnights = (period) => {
  if (!props.fortnights) return [];
  const pStart = new Date(period.start_date);
  const pEnd = new Date(period.end_date);
  return props.fortnights.filter(f => {
    const fStart = new Date(f.start_date);
    const fEnd = new Date(f.end_date);
    return fStart <= pEnd && fEnd >= pStart;
  });
};

const generateModalOpen = ref(false);
const generateForm = useForm({
  year_id: '',
});
const openGenerateModal = () => {
  generateForm.reset();
  generateForm.clearErrors();
  if (props.years && props.years.length) {
    const activeYear = props.years.find(y => y.active);
    generateForm.year_id = activeYear ? activeYear.id : props.years[0].id;
  }
  generateModalOpen.value = true;
};
const submitGenerateForm = () => {
  generateForm.post('/admin/evaluations/periods/generate-monthly', {
    onSuccess: () => {
      generateModalOpen.value = false;
    },
  });
};

// ─── Helpers ──────────────────────────────────────────────────────────────────
const fmt = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';

const formulaPreview = (e) => {
  const a = Number(e.auto_score || 0) * 0.40;
  const m = Number(e.manager_score || 0) * 0.40;
  const x = Number(e.executive_score || 0) * 0.20;
  return `${a.toFixed(1)} + ${m.toFixed(1)} + ${x.toFixed(1)}`;
};
</script>

<template>
  <Head title="Performance Evaluations — SITS ERP" />

  <div class="space-y-8">

    <!-- ══════════════════════════════════════════════════════
         HERO HEADER
    ═══════════════════════════════════════════════════════════ -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-96 h-96 rounded-full bg-amber-600/8 blur-[120px] pointer-events-none"></div>
      <div class="absolute bottom-[-20%] left-[-5%]  w-72 h-72 rounded-full bg-blue-600/8  blur-[100px] pointer-events-none"></div>

      <div class="relative z-10 flex items-start justify-between gap-6 flex-wrap">
        <!-- Title block -->
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
            <Icon name="Star" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
          </div>
        </div>

        <!-- CTA buttons -->
        <div class="flex gap-2 shrink-0 flex-wrap">
          <button
            v-if="activeTab === 'periods'"
            @click="openGenerateModal"
            class="text-sm font-semibold bg-blue-900/60 hover:bg-blue-900 border border-blue-800 hover:border-blue-700 text-blue-200 px-4 py-2.5 rounded-xl transition-all cursor-pointer flex items-center gap-2"
          >
            <Icon name="RefreshCw" :size="15" />
            Generate Monthly
          </button>
          <button
            @click="openPeriodModal"
            class="text-sm font-semibold bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 text-slate-200 px-4 py-2.5 rounded-xl transition-all cursor-pointer flex items-center gap-2"
          >
            <Icon name="CalendarPlus" :size="15" />
            New Period
          </button>
          <button
            @click="openEvalModal()"
            class="text-sm font-semibold bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white px-4 py-2.5 rounded-xl transition-all shadow-md shadow-amber-500/20 hover:shadow-amber-500/30 cursor-pointer flex items-center gap-2"
          >
            <Icon name="ClipboardList" :size="15" />
            New Scorecard
          </button>
        </div>
      </div>

      <!-- ── KPI stat strip ─────────────────────────────────── -->
      <div class="relative z-10 mt-8 grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-900 bg-slate-950/50 px-5 py-4">
          <div class="text-2xl font-black text-amber-400">{{ periods.length }}</div>
          <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold mt-0.5">Total Periods</div>
        </div>
        <div class="rounded-2xl border border-slate-900 bg-slate-950/50 px-5 py-4">
          <div class="text-2xl font-black text-emerald-400">{{ openPeriods }}</div>
          <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold mt-0.5">Open Periods</div>
        </div>
        <div class="rounded-2xl border border-slate-900 bg-slate-950/50 px-5 py-4">
          <div class="text-2xl font-black text-blue-400">{{ evaluations.length }}</div>
          <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold mt-0.5">Scorecards</div>
        </div>
        <div class="rounded-2xl border border-slate-900 bg-slate-950/50 px-5 py-4">
          <div class="text-2xl font-black" :class="scoreColor(avgFinalScore)">{{ avgFinalScore }}%</div>
          <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold mt-0.5">Avg. Final Score</div>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════════════
         TAB BAR
    ═══════════════════════════════════════════════════════════ -->
    <div class="flex border-b border-slate-900 gap-1">
      <button
        v-for="tab in [
          { key:'periods',    label:'Periods',    icon:'CalendarRange', count: periods.length },
          { key:'scorecards', label:'Scorecards', icon:'ClipboardList', count: evaluations.length },
          { key:'ratings',    label:'Ratings',    icon:'BarChart2',     count: ratings.length },
        ]"
        :key="tab.key"
        @click="activeTab = tab.key"
        class="flex items-center gap-2 pb-3 px-4 text-sm font-bold tracking-wide transition-colors cursor-pointer"
        :class="activeTab === tab.key
          ? 'text-amber-400 border-b-2 border-amber-500'
          : 'text-slate-500 hover:text-slate-300'"
      >
        <Icon :name="tab.icon" :size="15" />
        {{ tab.label }}
        <span
          class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
          :class="activeTab === tab.key
            ? 'bg-amber-500/15 text-amber-400 border border-amber-500/20'
            : 'bg-slate-900 text-slate-600 border border-slate-800'"
        >{{ tab.count }}</span>
      </button>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB: PERIODS
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'periods'">

      <!-- Empty state -->
      <div v-if="!periods.length" class="py-16 flex flex-col items-center gap-4 border border-dashed border-slate-900 rounded-3xl">
        <div class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
          <Icon name="CalendarRange" :size="26" />
        </div>
        <div class="text-center">
          <p class="font-bold text-slate-300">No evaluation periods yet</p>
          <p class="text-sm text-slate-500 mt-1">Create a period to begin collecting scorecards.</p>
        </div>
        <button @click="openPeriodModal" class="text-sm font-semibold bg-gradient-to-r from-amber-500 to-orange-500 text-white px-5 py-2.5 rounded-xl cursor-pointer">
          Create First Period
        </button>
      </div>

      <!-- Period grid -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="period in periods"
          :key="period.id"
          class="group rounded-2xl border bg-slate-900/10 p-6 flex flex-col justify-between transition-all hover:shadow-lg"
          :class="period.status === 'open'
            ? 'border-emerald-900/50 hover:border-emerald-800/60'
            : 'border-slate-900 hover:border-slate-800'"
        >
          <!-- Top row -->
          <div>
            <div class="flex items-start justify-between gap-3 mb-4">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                :class="period.status === 'open' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-900 text-slate-500'">
                <Icon name="CalendarRange" :size="20" />
              </div>
              <span
                class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border"
                :class="periodStatus(period)"
              >
                {{ period.status === 'open' ? '● Open' : '⬤ Locked' }}
              </span>
            </div>

            <h3 class="text-base font-bold text-white leading-snug">{{ period.name }}</h3>
            <p class="text-xs text-slate-500 mt-1 capitalize">{{ period.cadence }}</p>

            <div class="mt-4 space-y-2 text-xs text-slate-400">
              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-slate-500">
                  <Icon name="CalendarCheck" :size="12" />
                  Start
                </span>
                <span class="text-slate-300 font-semibold">{{ fmt(period.start_date) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-slate-500">
                  <Icon name="CalendarX" :size="12" />
                  End
                </span>
                <span class="text-slate-300 font-semibold">{{ fmt(period.end_date) }}</span>
              </div>
              <div v-if="period.formula_version" class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-slate-500">
                  <Icon name="Sigma" :size="12" />
                  Formula
                </span>
                <span class="text-slate-400 font-mono">{{ period.formula_version }}</span>
              </div>
            </div>

            <!-- Fortnights list -->
            <div class="mt-4 pt-3 border-t border-slate-900/60">
              <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mb-1.5">Linked Fortnights:</span>
              <div class="flex flex-wrap gap-1">
                <span 
                  v-for="f in getOverlappingFortnights(period)" 
                  :key="f.id"
                  class="text-[9px] bg-slate-950/60 border border-slate-850 text-slate-400 px-2 py-0.5 rounded font-mono"
                  :title="`${fmt(f.start_date)} - ${fmt(f.end_date)}`"
                >
                  {{ f.name }}
                </span>
                <span v-if="!getOverlappingFortnights(period).length" class="text-[10px] text-slate-650 italic">No overlapping fortnights</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between gap-3 mt-6 pt-4 border-t border-slate-900">
            <span class="text-[10px] text-slate-600 font-semibold">
              {{ evaluations.filter(e => e.evaluation_period_id === period.id).length }} scorecard(s)
            </span>
            <div class="flex items-center gap-1.5">
              <button
                v-if="period.status === 'open' && evaluations.filter(e => e.evaluation_period_id === period.id).length"
                @click="computeAllAutoScores(period.id)"
                :disabled="computingAll[period.id]"
                class="text-[10px] font-bold px-3 py-1.5 rounded-lg border transition-all cursor-pointer bg-violet-950/20 border-violet-900/30 text-violet-400 hover:bg-violet-950/40 disabled:opacity-50"
              >
                <Icon name="Zap" :size="10" class="inline mr-1" :class="{ 'animate-spin': computingAll[period.id] }" />
                {{ computingAll[period.id] ? 'Computing…' : '⚡ Auto-Score All' }}
              </button>
              <button
                @click="togglePeriod(period.id)"
                class="text-[10px] font-bold px-3 py-1.5 rounded-lg border transition-all cursor-pointer"
                :class="period.status === 'open'
                  ? 'bg-rose-950/20 border-rose-900/30 text-rose-400 hover:bg-rose-950/40'
                  : 'bg-emerald-950/20 border-emerald-900/30 text-emerald-400 hover:bg-emerald-950/40'"
              >
                <Icon :name="period.status === 'open' ? 'Lock' : 'Unlock'" :size="10" class="inline mr-1" />
                {{ period.status === 'open' ? 'Lock Period' : 'Reopen' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB: SCORECARDS
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'scorecards'" class="space-y-4">

      <!-- Empty state -->
      <div v-if="!evaluations.length" class="py-16 flex flex-col items-center gap-4 border border-dashed border-slate-900 rounded-3xl">
        <div class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400">
          <Icon name="ClipboardList" :size="26" />
        </div>
        <div class="text-center">
          <p class="font-bold text-slate-300">No scorecards yet</p>
          <p class="text-sm text-slate-500 mt-1">Create a period first, then record employee evaluations.</p>
        </div>
        <button @click="openEvalModal()" class="text-sm font-semibold bg-gradient-to-r from-amber-500 to-orange-500 text-white px-5 py-2.5 rounded-xl cursor-pointer">
          Create First Scorecard
        </button>
      </div>

      <!-- Scorecard table -->
      <div v-else class="rounded-2xl border border-slate-900 bg-slate-900/10 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-900 bg-slate-950/40">
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Employee</th>
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Period</th>
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">
                  <span class="flex items-center gap-1 justify-center">Auto <span class="text-slate-700">40%</span></span>
                </th>
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">
                  <span class="flex items-center gap-1 justify-center">Manager <span class="text-slate-700">40%</span></span>
                </th>
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">
                  <span class="flex items-center gap-1 justify-center">Exec <span class="text-slate-700">20%</span></span>
                </th>
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Formula</th>
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Final Score</th>
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Grade</th>
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-900/60">
              <tr
                v-for="evalItem in evaluations"
                :key="evalItem.id"
                class="hover:bg-slate-900/30 transition-colors"
              >
                <!-- Employee -->
                <td class="px-5 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-500/20 to-purple-500/20 border border-slate-800 flex items-center justify-center text-xs font-bold text-slate-300 shrink-0">
                      {{ (evalItem.employee?.full_name_en || '?').charAt(0) }}
                    </div>
                    <div>
                      <div class="font-semibold text-slate-100 text-sm">{{ evalItem.employee?.full_name_en }}</div>
                      <div class="text-[11px] text-slate-500">{{ evalItem.employee?.position?.title_en || 'Staff' }}</div>
                    </div>
                  </div>
                </td>

                <!-- Period -->
                <td class="px-5 py-4">
                  <span class="text-xs font-semibold text-slate-400 bg-slate-900/60 border border-slate-800 px-2.5 py-1 rounded-lg">
                    {{ evalItem.period?.name || '—' }}
                  </span>
                </td>

                <!-- Auto (with compute button + breakdown) -->
                <td class="px-5 py-4 text-center">
                  <div class="relative inline-flex items-center gap-1.5">
                    <span class="font-mono text-sm font-bold" :class="scoreColor(evalItem.auto_score)">
                      {{ Number(evalItem.auto_score || 0).toFixed(1) }}
                    </span>
                    <button
                      @click="computeAutoScore(evalItem.id)"
                      :disabled="computingAutoScore[evalItem.id]"
                      class="w-6 h-6 rounded-md bg-violet-500/10 border border-violet-500/20 text-violet-400 hover:bg-violet-500/20 hover:border-violet-500/30 flex items-center justify-center transition-all cursor-pointer disabled:opacity-40"
                      title="Compute auto score from system data"
                    >
                      <Icon v-if="!computingAutoScore[evalItem.id]" name="Zap" :size="11" />
                      <span v-else class="w-3 h-3 border-2 border-violet-400 border-t-transparent rounded-full animate-spin"></span>
                    </button>
                    <!-- Breakdown info button -->
                    <button
                      v-if="autoScoreBreakdowns[evalItem.id]"
                      @click="toggleBreakdown(evalItem.id)"
                      class="w-6 h-6 rounded-md bg-slate-800 border border-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-all cursor-pointer"
                      title="View score breakdown"
                    >
                      <Icon name="Info" :size="11" />
                    </button>

                    <!-- Breakdown popover -->
                    <Transition
                      enter-active-class="transition duration-150 ease-out"
                      enter-from-class="opacity-0 translate-y-1"
                      enter-to-class="opacity-100 translate-y-0"
                      leave-active-class="transition duration-100 ease-in"
                      leave-from-class="opacity-100 translate-y-0"
                      leave-to-class="opacity-0 translate-y-1"
                    >
                      <div
                        v-if="breakdownPopover === evalItem.id && autoScoreBreakdowns[evalItem.id]"
                        class="absolute top-full left-1/2 -translate-x-1/2 mt-2 z-50 w-72 rounded-2xl border border-slate-800 bg-slate-900 p-4 shadow-2xl shadow-slate-950/80"
                      >
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-3">Auto Score Breakdown</div>
                        <div class="space-y-2.5">
                          <div
                            v-for="(data, key) in autoScoreBreakdowns[evalItem.id]"
                            :key="key"
                            class="flex items-center justify-between gap-3"
                          >
                            <div class="flex items-center gap-2 min-w-0">
                              <Icon :name="breakdownIcon(key)" :size="12" class="text-slate-500 shrink-0" />
                              <span class="text-xs text-slate-300 font-semibold truncate">{{ breakdownLabel(key) }}</span>
                              <span v-if="!data.has_data" class="text-[9px] text-slate-600 italic">(no data)</span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                              <span class="text-[10px] text-slate-600 font-mono">{{ (data.effective_weight * 100).toFixed(0) }}%</span>
                              <span
                                class="text-xs font-mono font-bold px-1.5 py-0.5 rounded border"
                                :class="data.has_data ? scoreBg(data.score) : 'bg-slate-800/60 border-slate-800 text-slate-600'"
                              >
                                {{ data.has_data ? data.score.toFixed(1) : '—' }}
                              </span>
                            </div>
                          </div>
                        </div>
                        <!-- Detail summary -->
                        <div class="mt-3 pt-3 border-t border-slate-800 space-y-1">
                          <div v-if="autoScoreBreakdowns[evalItem.id]?.tasks?.has_data" class="text-[10px] text-slate-500">
                            Tasks: {{ autoScoreBreakdowns[evalItem.id].tasks.completed }}/{{ autoScoreBreakdowns[evalItem.id].tasks.total }} done
                            <span v-if="autoScoreBreakdowns[evalItem.id].tasks.overdue" class="text-rose-400">
                              · {{ autoScoreBreakdowns[evalItem.id].tasks.overdue }} overdue
                            </span>
                          </div>
                          <div v-if="autoScoreBreakdowns[evalItem.id]?.deliverables?.has_data" class="text-[10px] text-slate-500">
                            Deliverables: {{ autoScoreBreakdowns[evalItem.id].deliverables.completed }}/{{ autoScoreBreakdowns[evalItem.id].deliverables.total }} completed
                          </div>
                          <div v-if="autoScoreBreakdowns[evalItem.id]?.kpis?.has_data" class="text-[10px] text-slate-500">
                            KPIs: {{ autoScoreBreakdowns[evalItem.id].kpis.rated_count }} rated · avg {{ autoScoreBreakdowns[evalItem.id].kpis.avg_rating?.toFixed(1) }}
                          </div>
                          <div v-if="autoScoreBreakdowns[evalItem.id]?.attendance?.has_data" class="text-[10px] text-slate-500">
                            Attendance: {{ autoScoreBreakdowns[evalItem.id].attendance.absent_days }} absent · {{ autoScoreBreakdowns[evalItem.id].attendance.late_minutes }}min late
                          </div>
                        </div>
                      </div>
                    </Transition>
                  </div>
                </td>

                <!-- Manager -->
                <td class="px-5 py-4 text-center">
                  <span class="font-mono text-sm font-bold" :class="scoreColor(evalItem.manager_score)">
                    {{ Number(evalItem.manager_score || 0).toFixed(1) }}
                  </span>
                </td>

                <!-- Exec -->
                <td class="px-5 py-4 text-center">
                  <span class="font-mono text-sm font-bold" :class="scoreColor(evalItem.executive_score)">
                    {{ Number(evalItem.executive_score || 0).toFixed(1) }}
                  </span>
                </td>

                <!-- Formula breakdown -->
                <td class="px-5 py-4 text-center">
                  <span class="text-[10px] font-mono text-slate-600">{{ formulaPreview(evalItem) }}</span>
                </td>

                <!-- Final Score -->
                <td class="px-5 py-4 text-center">
                  <div class="flex flex-col items-center gap-1">
                    <span
                      class="font-mono text-sm font-black px-2.5 py-0.5 rounded-lg border"
                      :class="scoreBg(evalItem.final_score)"
                    >
                      {{ Number(evalItem.final_score || 0).toFixed(1) }}%
                    </span>
                    <!-- Mini progress bar -->
                    <div class="w-16 h-1 rounded-full bg-slate-900 overflow-hidden">
                      <div
                        class="h-full rounded-full transition-all"
                        :class="Number(evalItem.final_score||0) >= 90 ? 'bg-emerald-500'
                              : Number(evalItem.final_score||0) >= 75 ? 'bg-blue-500'
                              : Number(evalItem.final_score||0) >= 60 ? 'bg-amber-500' : 'bg-rose-500'"
                        :style="`width:${evalItem.final_score || 0}%`"
                      ></div>
                    </div>
                  </div>
                </td>

                <!-- Grade Band -->
                <td class="px-5 py-4">
                  <span
                    v-if="evalItem.grade_band"
                    class="text-[11px] font-bold px-2.5 py-1 rounded-lg border"
                    :class="scoreBg(evalItem.final_score)"
                  >
                    {{ evalItem.grade_band.label_en }}
                  </span>
                  <span v-else class="text-[11px] text-slate-700 italic">Ungraded</span>
                </td>

                <!-- Actions -->
                <td class="px-5 py-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      @click="computeAutoScore(evalItem.id)"
                      :disabled="computingAutoScore[evalItem.id]"
                      class="text-[10px] font-bold px-3 py-1.5 border border-violet-900/30 bg-violet-950/20 text-violet-400 hover:bg-violet-950/40 hover:border-violet-800/40 rounded-lg transition-all cursor-pointer disabled:opacity-40"
                    >
                      <Icon name="Zap" :size="10" class="inline mr-0.5" />
                      {{ computingAutoScore[evalItem.id] ? 'Computing…' : 'Auto Score' }}
                    </button>
                    <button
                      @click="openEvalModal(evalItem)"
                      class="text-[10px] font-bold px-3 py-1.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
                    >
                      Edit
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Table footer summary -->
        <div class="border-t border-slate-900 px-5 py-3 flex items-center justify-between bg-slate-950/30">
          <span class="text-xs text-slate-500">{{ evaluations.length }} scorecard{{ evaluations.length !== 1 ? 's' : '' }}</span>
          <span class="text-xs text-slate-500">
            {{ gradedCount }} graded ·
            <span :class="scoreColor(avgFinalScore)" class="font-bold">{{ avgFinalScore }}% avg</span>
          </span>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB: RATINGS
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'ratings'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">KPI Ratings Log</h3>
        <button
          @click="openRatingModal()"
          class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-655 hover:from-blue-500 hover:to-purple-550 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer"
        >
          + New Rating
        </button>
      </div>

      <!-- Empty state -->
      <div v-if="!ratings.length" class="py-16 flex flex-col items-center gap-4 border border-dashed border-slate-900 rounded-3xl">
        <div class="w-14 h-14 rounded-2xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-400">
          <Icon name="BarChart2" :size="26" />
        </div>
        <div class="text-center">
          <p class="font-bold text-slate-300">No ratings recorded</p>
          <p class="text-sm text-slate-500 mt-1">Ratings are created when raters submit individual KPI scores.</p>
        </div>
      </div>

      <!-- Ratings table -->
      <div v-else class="rounded-2xl border border-slate-900 bg-slate-900/10 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-900 bg-slate-950/40">
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Employee</th>
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">KPI</th>
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Rater</th>
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Rater Type</th>
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Score</th>
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Comment</th>
                <th class="px-5 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-900/60">
              <tr
                v-for="rating in ratings"
                :key="rating.id"
                class="hover:bg-slate-900/30 transition-colors"
              >
                <!-- Employee -->
                <td class="px-5 py-4">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-violet-500/20 to-blue-500/20 border border-slate-800 flex items-center justify-center text-[10px] font-bold text-slate-300 shrink-0">
                      {{ (rating.evaluation?.employee?.full_name_en || '?').charAt(0) }}
                    </div>
                    <span class="font-semibold text-sm text-slate-200">{{ rating.evaluation?.employee?.full_name_en || '—' }}</span>
                  </div>
                </td>

                <!-- KPI -->
                <td class="px-5 py-4">
                  <div class="max-w-[200px]">
                    <p class="text-sm font-semibold text-slate-200 truncate">{{ rating.kpi?.title_en || '—' }}</p>
                    <p v-if="rating.kpi?.title_am" class="text-[11px] text-slate-500 truncate mt-0.5">{{ rating.kpi.title_am }}</p>
                  </div>
                </td>

                <!-- Rater -->
                <td class="px-5 py-4">
                  <span class="text-sm text-slate-400">{{ rating.rater?.name || '—' }}</span>
                </td>

                <!-- Rater type -->
                <td class="px-5 py-4">
                  <span
                    class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-lg border"
                    :class="rating.rater_type === 'manager'
                      ? 'bg-blue-500/10 border-blue-500/20 text-blue-400'
                      : rating.rater_type === 'executive'
                        ? 'bg-amber-500/10 border-amber-500/20 text-amber-400'
                        : 'bg-slate-800/60 border-slate-800 text-slate-500'"
                  >
                    {{ rating.rater_type || 'auto' }}
                  </span>
                </td>

                <!-- Score -->
                <td class="px-5 py-4 text-center">
                  <div class="flex flex-col items-center gap-1">
                    <span
                      class="font-mono text-sm font-black px-2.5 py-0.5 rounded-lg border"
                      :class="scoreBg(rating.score)"
                    >
                      {{ Number(rating.score || 0).toFixed(1) }}
                    </span>
                    <div class="w-14 h-1 bg-slate-900 rounded-full overflow-hidden">
                      <div
                        class="h-full rounded-full transition-all"
                        :class="Number(rating.score||0) >= 90 ? 'bg-emerald-500'
                              : Number(rating.score||0) >= 75 ? 'bg-blue-500'
                              : Number(rating.score||0) >= 60 ? 'bg-amber-500' : 'bg-rose-500'"
                        :style="`width:${Math.min(rating.score||0,100)}%`"
                      ></div>
                    </div>
                  </div>
                </td>

                <!-- Comment -->
                <td class="px-5 py-4">
                  <span v-if="rating.comment_en" class="text-xs text-slate-400 line-clamp-2 max-w-[200px]">{{ rating.comment_en }}</span>
                  <span v-else-if="rating.comment_am" class="text-xs text-slate-400 line-clamp-2 max-w-[200px]">{{ rating.comment_am }}</span>
                  <span v-else class="text-xs text-slate-700 italic">—</span>
                </td>

                <!-- Actions -->
                <td class="px-5 py-4 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      @click="openRatingModal(rating)"
                      class="text-[10px] font-bold px-2.5 py-1 bg-slate-900 hover:bg-slate-850 border border-slate-850 text-blue-450 hover:text-blue-400 rounded-lg transition-colors cursor-pointer"
                    >
                      Edit
                    </button>
                    <button
                      @click="deleteRating(rating.id)"
                      class="text-[10px] font-bold px-2.5 py-1 bg-slate-900 hover:bg-rose-950/20 border border-slate-850 hover:border-rose-900/20 text-slate-450 hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer -->
        <div class="border-t border-slate-900 px-5 py-3 bg-slate-950/30">
          <span class="text-xs text-slate-500">{{ ratings.length }} rating{{ ratings.length !== 1 ? 's' : '' }} recorded</span>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         MODAL: NEW / EDIT PERIOD
    ═══════════════════════════════════════════════════════════ -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="periodModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
          <!-- Header -->
          <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
              <Icon name="CalendarPlus" :size="20" />
            </div>
            <h3 class="text-xl font-bold text-white">Create Evaluation Period</h3>
          </div>

          <form @submit.prevent="submitPeriodForm" class="space-y-5">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Period Name</label>
              <input
                v-model="periodForm.name"
                type="text"
                required
                placeholder="e.g. Q2 2026 — Mid-Year"
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-amber-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-amber-500/50 transition-all text-sm"
                :class="periodForm.errors.name ? 'border-rose-500/50' : ''"
              />
              <p v-if="periodForm.errors.name" class="text-xs text-rose-400 mt-1 font-semibold">{{ periodForm.errors.name }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Cadence</label>
              <select
                v-model="periodForm.cadence"
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-amber-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-amber-500/50 transition-all text-sm"
              >
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="biannual">Biannual</option>
                <option value="annual">Annual</option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Start Date</label>
                <input
                  v-model="periodForm.start_date"
                  type="date"
                  required
                  class="w-full bg-slate-950/60 border border-slate-850 focus:border-amber-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-amber-500/50 transition-all text-sm"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">End Date</label>
                <input
                  v-model="periodForm.end_date"
                  type="date"
                  required
                  class="w-full bg-slate-950/60 border border-slate-850 focus:border-amber-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-amber-500/50 transition-all text-sm"
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Formula Version</label>
              <select
                v-model="periodForm.formula_version"
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-amber-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-amber-500/50 transition-all text-sm"
              >
                <option value="v1">v1 — 40% Auto + 40% Manager + 20% Executive</option>
              </select>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
              <button type="button" @click="periodModalOpen = false"
                class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer">
                Cancel
              </button>
              <button type="submit" :disabled="periodForm.processing"
                class="text-xs font-semibold bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer disabled:opacity-50">
                {{ periodForm.processing ? 'Saving…' : 'Save Period' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════════════════
         MODAL: NEW / EDIT SCORECARD
    ═══════════════════════════════════════════════════════════ -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="evalModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
        <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
          <!-- Header -->
          <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
              <Icon name="ClipboardList" :size="20" />
            </div>
            <div>
              <h3 class="text-xl font-bold text-white">{{ editingEval ? 'Update Scorecard' : 'Create Scorecard' }}</h3>
              <p class="text-xs text-slate-500 mt-0.5">Formula: 40% Auto + 40% Manager + 20% Executive</p>
            </div>
          </div>

          <form @submit.prevent="submitEvalForm" class="space-y-5">
            <!-- Employee -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Employee</label>
              <select
                v-model="evalForm.employee_id"
                required
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              >
                <option value="" disabled>Select Employee</option>
                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                  {{ emp.full_name_en }} — {{ emp.position?.title_en || 'Staff' }}
                </option>
              </select>
              <p v-if="evalForm.errors.employee_id" class="text-xs text-rose-400 mt-1 font-semibold">{{ evalForm.errors.employee_id }}</p>
            </div>

            <!-- Period -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Evaluation Period</label>
              <select
                v-model="evalForm.evaluation_period_id"
                required
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              >
                <option value="" disabled>Select Period</option>
                <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
              <p v-if="evalForm.errors.evaluation_period_id" class="text-xs text-rose-400 mt-1 font-semibold">{{ evalForm.errors.evaluation_period_id }}</p>
            </div>

            <!-- Score inputs -->
            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                  Auto Score
                  <span class="text-violet-500/70 normal-case font-normal">(system‑computed)</span>
                </label>
                <div class="relative">
                  <input
                    v-model="evalForm.auto_score"
                    type="number"
                    min="0" max="100" step="0.1"
                    placeholder="—"
                    readonly
                    class="w-full bg-violet-950/20 border border-violet-900/30 rounded-xl px-3 py-3 text-violet-300 placeholder-slate-700 focus:outline-none transition-all text-sm font-mono cursor-not-allowed"
                  />
                  <button
                    v-if="editingEval"
                    type="button"
                    @click="computeAutoScore(editingEval.id)"
                    :disabled="computingAutoScore[editingEval?.id]"
                    class="absolute right-1.5 top-1/2 -translate-y-1/2 text-[9px] font-bold px-2 py-1 rounded-lg bg-violet-500/15 border border-violet-500/25 text-violet-400 hover:bg-violet-500/25 transition-all cursor-pointer disabled:opacity-40"
                  >
                    <Icon name="Zap" :size="9" class="inline mr-0.5" />
                    {{ computingAutoScore[editingEval?.id] ? '…' : 'Compute' }}
                  </button>
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                  Manager
                  <span class="text-slate-700 normal-case font-normal">(40%)</span>
                </label>
                <input
                  v-model="evalForm.manager_score"
                  type="number"
                  min="0" max="100" step="0.1"
                  placeholder="0–100"
                  class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-3 py-3 text-slate-100 placeholder-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm font-mono"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                  Executive
                  <span class="text-slate-700 normal-case font-normal">(20%)</span>
                </label>
                <input
                  v-model="evalForm.executive_score"
                  type="number"
                  min="0" max="100" step="0.1"
                  placeholder="0–100"
                  class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-3 py-3 text-slate-100 placeholder-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm font-mono"
                />
              </div>
            </div>

            <!-- Live formula preview -->
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-900">
              <Icon name="Sigma" :size="15" class="text-slate-500 shrink-0" />
              <span class="text-xs text-slate-500 font-semibold">Preview:</span>
              <span class="text-xs font-mono text-slate-400">
                {{ Number(evalForm.auto_score || 0) * 0.40 | 0 }}.{{
                  Math.round((Number(evalForm.auto_score || 0) * 0.40 % 1) * 10)
                }} +
                {{ Number(evalForm.manager_score || 0) * 0.40 | 0 }}.{{
                  Math.round((Number(evalForm.manager_score || 0) * 0.40 % 1) * 10)
                }} +
                {{ Number(evalForm.executive_score || 0) * 0.20 | 0 }}.{{
                  Math.round((Number(evalForm.executive_score || 0) * 0.20 % 1) * 10)
                }}
              </span>
              <span class="ml-auto text-sm font-black"
                :class="scoreColor(
                  (Number(evalForm.auto_score||0)*0.40) +
                  (Number(evalForm.manager_score||0)*0.40) +
                  (Number(evalForm.executive_score||0)*0.20)
                )">
                {{
                  ((Number(evalForm.auto_score||0)*0.40) +
                   (Number(evalForm.manager_score||0)*0.40) +
                   (Number(evalForm.executive_score||0)*0.20)).toFixed(1)
                }}%
              </span>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
              <button type="button" @click="evalModalOpen = false"
                class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer">
                Cancel
              </button>
              <button type="submit" :disabled="evalForm.processing"
                class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer disabled:opacity-50">
                {{ evalForm.processing ? 'Saving…' : editingEval ? 'Update Scorecard' : 'Calculate & Save' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- ══════════════════════════════════════════════════════
         MODAL: NEW / EDIT RATING
    ═══════════════════════════════════════════════════════════ -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="ratingModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative max-h-[90vh] overflow-y-auto">
          <h3 class="text-xl font-bold text-white mb-6">
            {{ editingRating ? 'Edit KPI Rating' : 'Add KPI Rating' }}
          </h3>

          <form @submit.prevent="submitRatingForm" class="space-y-4">
            <!-- Evaluation Card Select -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Evaluation Card</label>
              <select 
                v-model="ratingForm.evaluation_id" 
                :disabled="!!editingRating" 
                required 
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none"
              >
                <option value="" disabled>Select Evaluation Card</option>
                <option v-for="e in evaluations" :key="e.id" :value="e.id">
                  {{ e.employee?.full_name_en }} ({{ e.period?.name }})
                </option>
              </select>
            </div>

            <!-- KPI Select -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Select KPI</label>
              <select 
                v-model="ratingForm.kpi_id" 
                :disabled="!!editingRating" 
                required 
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none"
              >
                <option value="" disabled>Select KPI</option>
                <option v-for="k in kpis" :key="k.id" :value="k.id">
                  {{ k.title_en }} ({{ k.measure_type }})
                </option>
              </select>
            </div>

            <!-- Rater User -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Rater User</label>
              <select 
                v-model="ratingForm.rater_user_id" 
                :disabled="!!editingRating" 
                required 
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none"
              >
                <option value="" disabled>Select Rater User</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
            </div>

            <!-- Rater Type -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Rater Type</label>
              <select 
                v-model="ratingForm.rater_type" 
                :disabled="!!editingRating" 
                required 
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none"
              >
                <option value="system">System (Auto)</option>
                <option value="manager">Manager / Department Head</option>
                <option value="executive">Executive</option>
              </select>
            </div>

            <!-- Score -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Score (0 - 100)</label>
              <input 
                v-model="ratingForm.score" 
                type="number" 
                min="0" 
                max="100" 
                step="0.1" 
                required 
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none font-mono" 
              />
            </div>

            <!-- Comments -->
            <div class="grid grid-cols-1 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Comments (English)</label>
                <textarea v-model="ratingForm.comment_en" placeholder="Feedback in English..." class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none h-20 resize-none"></textarea>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Comments (Amharic)</label>
                <textarea v-model="ratingForm.comment_am" placeholder="አስተያየት በአማርኛ..." class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none h-20 resize-none"></textarea>
              </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
              <button type="button" @click="ratingModalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer">
                Cancel
              </button>
              <button type="submit" :disabled="ratingForm.processing" class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-650 hover:from-blue-500 hover:to-purple-550 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 cursor-pointer">
                {{ editingRating ? 'Update Rating' : 'Save Rating' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Generate Monthly Periods Modal -->
    <div v-if="generateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-4">Generate Monthly Periods</h3>
        <p class="text-xs text-slate-400 mb-6">Select a Fiscal Year. The system will generate 12 monthly Evaluation and Payroll periods matching the year's calendar months, mapping overlapping fortnights automatically.</p>
        
        <form @submit.prevent="submitGenerateForm" class="space-y-5">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Fiscal Year</label>
            <select 
              v-model="generateForm.year_id"
              required
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            >
              <option value="" disabled>Select a Fiscal Year</option>
              <option v-for="y in years" :key="y.id" :value="y.id">{{ y.label }}</option>
            </select>
            <div v-if="generateForm.errors.year_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ generateForm.errors.year_id }}</div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button 
              type="button" 
              @click="generateModalOpen = false" 
              class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer text-slate-350"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="generateForm.processing"
              class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 cursor-pointer"
            >
              {{ generateForm.processing ? 'Generating...' : 'Generate' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>
