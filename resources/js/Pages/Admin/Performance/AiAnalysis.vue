<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  module:      { type: Object, required: true },
  reports:     { type: Array,  default: () => [] },
  analyses:    { type: Array,  default: () => [] },
  evaluations: { type: Array,  default: () => [] },
  employees:   { type: Array,  default: () => [] },
  periods:     { type: Array,  default: () => [] },
  aiProvider:  { type: String,  default: 'claude_pro' },
  aiModel:     { type: String,  default: 'claude-opus-4-8' },
  aiEnabled:   { type: Boolean, default: false },
});

// Make analyses reactive so we can push new results without a page reload
const localAnalyses = ref([...props.analyses]);

// ── Submit narrative report form ──────────────────────────────────────────────
const reportForm = useForm({
  employee_id:          '',
  evaluation_period_id: '',
  language:             'en',
  body:                 '',
});

function submitReport() {
  reportForm.post(route('admin.narrative-reports.store'), {
    preserveScroll: true,
    onSuccess: () => reportForm.reset(),
  });
}

// ── Per-card loading/status state ─────────────────────────────────────────────
const loadingReports = ref({});    // report.id → bool
const loadingEvals   = ref({});    // evaluation.id → bool
const loadingConfirm = ref({});    // analysis.id → bool
const toasts         = ref([]);    // { id, type, message }

function toast(type, message) {
  const id = Date.now();
  toasts.value.push({ id, type, message });
  setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 4000);
}

// ── Trigger narrative analysis ────────────────────────────────────────────────
async function analyzeReport(report) {
  loadingReports.value[report.id] = true;
  try {
    const res = await fetch(route('admin.ai-analysis.narrative.trigger', report.id), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
      },
    });
    const data = await res.json();
    if (res.ok) {
      toast('success', data.message || 'Analysis complete.');
      // Immediately inject the new analysis card (sync local run returns it)
      if (data.analysis) {
        // Replace if already exists, otherwise prepend
        const idx = localAnalyses.value.findIndex(a => a.id === data.analysis.id);
        if (idx >= 0) localAnalyses.value.splice(idx, 1, data.analysis);
        else localAnalyses.value.unshift(data.analysis);
        activeTab.value = 'analyses';
      }
    } else {
      toast('error', data.message || 'Analysis failed. Check that CLAUDE_PRO_API_KEY is set in .env');
    }
  } catch (e) {
    toast('error', 'Network error: ' + e.message);
  } finally {
    loadingReports.value[report.id] = false;
  }
}

// ── Trigger full performance analysis ─────────────────────────────────────────
async function analyzeEvaluation(evaluation) {
  loadingEvals.value[evaluation.id] = true;
  try {
    const res = await fetch(route('admin.ai-analysis.performance.trigger', evaluation.id), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
      },
    });
    const data = await res.json();
    if (res.ok) {
      toast('success', data.message || 'Performance analysis complete.');
      if (data.analysis) {
        const idx = localAnalyses.value.findIndex(a => a.id === data.analysis.id);
        if (idx >= 0) localAnalyses.value.splice(idx, 1, data.analysis);
        else localAnalyses.value.unshift(data.analysis);
        activeTab.value = 'analyses';
      }
    } else {
      toast('error', data.message || 'Analysis failed. Check that CLAUDE_PRO_API_KEY is set in .env');
    }
  } catch (e) {
    toast('error', 'Network error: ' + e.message);
  } finally {
    loadingEvals.value[evaluation.id] = false;
  }
}

// ── Confirm an AI analysis ────────────────────────────────────────────────────
async function confirmAnalysis(analysis) {
  loadingConfirm.value[analysis.id] = true;
  try {
    const res = await fetch(route('admin.ai-analysis.confirm', analysis.id), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
      },
    });
    const data = await res.json();
    if (res.ok) {
      analysis.human_confirmed = true;
      toast('success', 'Analysis confirmed.');
    } else {
      toast('error', data.message || 'Failed to confirm.');
    }
  } catch (e) {
    toast('error', 'Network error: ' + e.message);
  } finally {
    loadingConfirm.value[analysis.id] = false;
  }
}

// ── Dismiss an AI analysis ────────────────────────────────────────────────────
async function dismissAnalysis(analysis, index) {
  if (!confirm('Remove this AI analysis result?')) return;
  try {
    const res = await fetch(route('admin.ai-analysis.dismiss', analysis.id), {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
      },
    });
    if (res.ok) {
      localAnalyses.value.splice(index, 1);
      toast('success', 'Analysis dismissed.');
    }
  } catch (e) {
    toast('error', 'Network error: ' + e.message);
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function sentimentColor(overall) {
  if (overall === 'positive') return 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20';
  if (overall === 'negative') return 'text-rose-400 bg-rose-500/10 border-rose-500/20';
  return 'text-amber-400 bg-amber-500/10 border-amber-500/20';
}

function sentimentIcon(overall) {
  if (overall === 'positive') return 'TrendingUp';
  if (overall === 'negative') return 'TrendingDown';
  return 'Minus';
}

function riskLabel(flag) {
  if (flag.startsWith('[STRENGTH] ')) return { label: flag.replace('[STRENGTH] ', ''), color: 'emerald' };
  if (flag.startsWith('[DEVELOP] '))  return { label: flag.replace('[DEVELOP] ', ''),  color: 'blue' };
  return { label: flag, color: 'rose' };
}

function fmtDate(d) {
  return d ? new Date(d).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) : '—';
}

function pct(val) {
  return val !== null && val !== undefined ? Math.round(val) + '%' : '—';
}

// Active tab
const activeTab = ref('analyses');
</script>

<template>
  <Head title="AI Analysis — SITS PMS" />

  <!-- Toast notifications -->
  <div class="fixed top-6 right-6 z-50 space-y-2 pointer-events-none">
    <transition-group name="toast">
      <div
        v-for="t in toasts"
        :key="t.id"
        :class="[
          'pointer-events-auto px-4 py-3 rounded-xl text-sm font-medium shadow-2xl border backdrop-blur-md flex items-center gap-3',
          t.type === 'success'
            ? 'bg-emerald-950/90 border-emerald-500/30 text-emerald-300'
            : 'bg-rose-950/90 border-rose-500/30 text-rose-300',
        ]"
      >
        <Icon :name="t.type === 'success' ? 'CheckCircle' : 'AlertCircle'" :size="16" />
        {{ t.message }}
      </div>
    </transition-group>
  </div>

  <div class="space-y-8">

    <!-- ── Hero header ────────────────────────────────────────────────────── -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-900 via-indigo-950/40 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-96 h-96 rounded-full bg-indigo-600/10 blur-[120px] pointer-events-none" />
      <div class="absolute bottom-[-20%] left-[10%] w-64 h-64 rounded-full bg-blue-600/8 blur-[100px] pointer-events-none" />
      <div class="relative z-10 flex items-start gap-5">
        <span class="w-14 h-14 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
          <Icon name="Sparkles" :size="26" />
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
          <div class="mt-4 flex flex-wrap gap-3">
            <span class="inline-flex items-center gap-1.5 text-xs bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 px-3 py-1.5 rounded-full font-medium">
              <Icon name="Cpu" :size="12" /> {{ aiProvider.replace('_', ' ').toUpperCase() }} ({{ aiModel }})
            </span>
            <span v-if="aiEnabled" class="inline-flex items-center gap-1.5 text-xs bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 px-3 py-1.5 rounded-full font-medium">
              <Icon name="ShieldCheck" :size="12" /> AI Active
            </span>
            <span v-else class="inline-flex items-center gap-1.5 text-xs bg-rose-500/10 border border-rose-500/20 text-rose-300 px-3 py-1.5 rounded-full font-medium">
              <Icon name="XCircle" :size="12" /> AI Disabled
            </span>
            <span class="inline-flex items-center gap-1.5 text-xs bg-slate-500/10 border border-slate-500/20 text-slate-400 px-3 py-1.5 rounded-full font-medium">
              <Icon name="FileText" :size="12" /> {{ reports.length }} reports · {{ localAnalyses.length }} analyses
            </span>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Tab nav ─────────────────────────────────────────────────────────── -->
    <div class="flex gap-1 bg-slate-900/60 border border-slate-800 p-1 rounded-2xl w-fit">
      <button
        v-for="tab in [
          { key: 'analyses',    label: 'AI Insights',         icon: 'Sparkles'  },
          { key: 'reports',     label: 'Narrative Reports',   icon: 'FileText'  },
          { key: 'evaluations', label: 'Deep Analysis',       icon: 'BarChart2' },
          { key: 'submit',      label: 'Submit Report',       icon: 'Plus'      },
        ]"
        :key="tab.key"
        @click="activeTab = tab.key"
        :class="[
          'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all',
          activeTab === tab.key
            ? 'bg-indigo-600 text-white shadow'
            : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800',
        ]"
      >
        <Icon :name="tab.icon" :size="14" />
        {{ tab.label }}
      </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB: AI Insights (analyses) -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'analyses'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">AI Analysis Results</h3>
        <span class="text-xs text-slate-600">{{ localAnalyses.length }} total</span>
      </div>

      <div v-if="!localAnalyses.length" class="py-16 text-center text-slate-600 italic border border-dashed border-slate-800 rounded-2xl">
        <Icon name="Sparkles" :size="32" class="mx-auto mb-3 opacity-30" />
        No AI analyses yet. Submit a narrative report or trigger analysis from the Deep Analysis tab.
      </div>

      <div class="grid lg:grid-cols-2 gap-5">
        <div
          v-for="(analysis, idx) in localAnalyses"
          :key="analysis.id"
          class="relative p-5 rounded-2xl border border-slate-800 bg-slate-900/30 hover:border-slate-700 transition-all space-y-4 group"
        >
          <!-- Header row -->
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-sm font-bold text-slate-200 truncate">
                {{ analysis.narrative_report?.employee?.full_name_en ?? 'Unknown Employee' }}
              </p>
              <p class="text-[11px] text-slate-500 mt-0.5">{{ fmtDate(analysis.created_at) }}</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <!-- Provider badge -->
              <span class="text-[10px] font-mono bg-indigo-500/10 text-indigo-400 px-2 py-0.5 rounded border border-indigo-500/20 uppercase">
                {{ analysis.provider?.replace('_', ' ') }}
              </span>
              <!-- Confirmed badge -->
              <span
                v-if="analysis.human_confirmed"
                class="text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded border border-emerald-500/20 flex items-center gap-1"
              >
                <Icon name="ShieldCheck" :size="11" /> Confirmed
              </span>
            </div>
          </div>

          <!-- Sentiment -->
          <div
            v-if="analysis.sentiment?.overall"
            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold border"
            :class="sentimentColor(analysis.sentiment.overall)"
          >
            <Icon :name="sentimentIcon(analysis.sentiment.overall)" :size="13" />
            {{ analysis.sentiment.overall.charAt(0).toUpperCase() + analysis.sentiment.overall.slice(1) }} Sentiment
            <span class="opacity-60">({{ Math.round((analysis.sentiment.confidence ?? 0) * 100) }}%)</span>
          </div>

          <!-- English summary -->
          <div v-if="analysis.summary_en" class="space-y-1.5">
            <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Summary</p>
            <p class="text-sm text-slate-300 leading-relaxed">{{ analysis.summary_en }}</p>
          </div>

          <!-- Amharic summary -->
          <div v-if="analysis.summary_am" class="space-y-1.5">
            <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">ማጠቃለያ (Amharic)</p>
            <p class="text-sm text-slate-400 leading-relaxed font-[ethiopic]">{{ analysis.summary_am }}</p>
          </div>

          <!-- KPI scores -->
          <div v-if="analysis.kpi_scores_json?.length" class="space-y-2">
            <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">AI KPI Scores</p>
            <div class="space-y-1.5">
              <div
                v-for="kpi in analysis.kpi_scores_json"
                :key="kpi.kpi_id ?? kpi.kpi_title"
                class="flex items-center gap-3"
              >
                <span class="text-xs text-slate-400 truncate flex-1">{{ kpi.kpi_title }}</span>
                <div class="w-24 h-1.5 bg-slate-800 rounded-full overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all"
                    :style="{ width: (kpi.ai_score ?? 0) + '%' }"
                    :class="(kpi.ai_score ?? 0) >= 70 ? 'bg-emerald-500' : (kpi.ai_score ?? 0) >= 50 ? 'bg-amber-500' : 'bg-rose-500'"
                  />
                </div>
                <span class="text-xs font-mono text-slate-300 w-8 text-right">{{ kpi.ai_score ?? '—' }}</span>
              </div>
            </div>
          </div>

          <!-- Risk flags / strengths / development -->
          <div v-if="analysis.risk_flags?.length" class="space-y-2">
            <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Flags & Insights</p>
            <div class="flex flex-wrap gap-1.5">
              <span
                v-for="flag in analysis.risk_flags"
                :key="flag"
                class="text-[11px] px-2 py-0.5 rounded-full border font-medium"
                :class="{
                  'text-emerald-400 bg-emerald-500/10 border-emerald-500/20': riskLabel(flag).color === 'emerald',
                  'text-blue-400 bg-blue-500/10 border-blue-500/20': riskLabel(flag).color === 'blue',
                  'text-rose-400 bg-rose-500/10 border-rose-500/20': riskLabel(flag).color === 'rose',
                }"
              >
                {{ riskLabel(flag).label }}
              </span>
            </div>
          </div>

          <!-- Action row -->
          <div class="flex items-center justify-between pt-3 border-t border-slate-800">
            <span class="text-[11px] text-slate-600">
              Model: <span class="font-mono text-slate-500">{{ analysis.model || '—' }}</span>
            </span>
            <div class="flex items-center gap-2">
              <button
                v-if="!analysis.human_confirmed"
                @click="confirmAnalysis(analysis)"
                :disabled="loadingConfirm[analysis.id]"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600/10 text-emerald-400 border border-emerald-600/20 hover:bg-emerald-600/20 transition disabled:opacity-50"
              >
                <Icon :name="loadingConfirm[analysis.id] ? 'Loader2' : 'ShieldCheck'" :size="13"
                  :class="{ 'animate-spin': loadingConfirm[analysis.id] }" />
                Confirm
              </button>
              <button
                @click="dismissAnalysis(analysis, idx)"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-600/10 text-rose-400 border border-rose-600/20 hover:bg-rose-600/20 transition"
              >
                <Icon name="Trash2" :size="13" />
                Dismiss
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB: Narrative Reports -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'reports'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Submitted Narrative Reports</h3>
        <span class="text-xs text-slate-600">{{ reports.length }} total</span>
      </div>

      <div v-if="!reports.length" class="py-16 text-center text-slate-600 italic border border-dashed border-slate-800 rounded-2xl">
        <Icon name="FileText" :size="32" class="mx-auto mb-3 opacity-30" />
        No narrative reports submitted yet.
      </div>

      <div class="space-y-4">
        <div
          v-for="report in reports"
          :key="report.id"
          class="p-5 rounded-2xl border border-slate-800 bg-slate-900/30 hover:border-slate-700 transition-all space-y-3"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-sm font-bold text-slate-200">{{ report.employee?.full_name_en }}</p>
              <p class="text-xs text-slate-500 mt-0.5">Period: {{ report.period?.name ?? '—' }}</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <span class="text-[10px] font-medium text-slate-400 bg-slate-800 px-2 py-0.5 rounded border border-slate-700">
                {{ report.language === 'am' ? 'Amharic' : 'English' }}
              </span>
              <span class="text-[10px] text-slate-600">{{ fmtDate(report.created_at) }}</span>
            </div>
          </div>

          <p class="text-sm text-slate-400 leading-relaxed line-clamp-4 italic">"{{ report.body }}"</p>

          <div class="flex justify-end pt-2 border-t border-slate-900">
            <button
              @click="analyzeReport(report)"
              :disabled="loadingReports[report.id]"
              id="btn-analyze-report"
              class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-indigo-600/10 text-indigo-400 border border-indigo-600/20 hover:bg-indigo-600/20 transition disabled:opacity-50"
            >
              <Icon
                :name="loadingReports[report.id] ? 'Loader2' : 'Sparkles'"
                :size="14"
                :class="{ 'animate-spin': loadingReports[report.id] }"
              />
              {{ loadingReports[report.id] ? 'Queuing…' : 'Analyze with AI' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB: Deep Performance Analysis (per Evaluation) -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'evaluations'" class="space-y-4">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Deep Performance Analysis</h3>
          <p class="text-xs text-slate-650 mt-1">Uses {{ aiProvider.replace('_', ' ') }} ({{ aiModel }}) — analyzes all KPI ratings, attendance, tasks and salary data.</p>
        </div>
        <span class="text-xs text-slate-600 shrink-0">{{ evaluations.length }} evaluations</span>
      </div>

      <div v-if="!evaluations.length" class="py-16 text-center text-slate-600 italic border border-dashed border-slate-800 rounded-2xl">
        <Icon name="BarChart2" :size="32" class="mx-auto mb-3 opacity-30" />
        No evaluations found.
      </div>

      <div class="grid lg:grid-cols-2 gap-4">
        <div
          v-for="evaluation in evaluations"
          :key="evaluation.id"
          class="p-5 rounded-2xl border border-slate-800 bg-slate-900/30 hover:border-slate-700 transition-all space-y-3"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-sm font-bold text-slate-200 truncate">{{ evaluation.employee?.full_name_en }}</p>
              <p class="text-xs text-slate-500 mt-0.5">{{ evaluation.period?.name ?? '—' }}</p>
            </div>
            <span
              v-if="evaluation.final_score !== null"
              class="shrink-0 text-lg font-extrabold"
              :class="evaluation.final_score >= 70 ? 'text-emerald-400' : evaluation.final_score >= 50 ? 'text-amber-400' : 'text-rose-400'"
            >
              {{ pct(evaluation.final_score) }}
            </span>
          </div>

          <!-- Score bars -->
          <div class="grid grid-cols-3 gap-2 text-center text-[11px]">
            <div class="space-y-1">
              <div class="text-slate-500">Auto</div>
              <div class="font-mono text-slate-300">{{ pct(evaluation.auto_score) }}</div>
            </div>
            <div class="space-y-1">
              <div class="text-slate-500">Manager</div>
              <div class="font-mono text-slate-300">{{ pct(evaluation.manager_score) }}</div>
            </div>
            <div class="space-y-1">
              <div class="text-slate-500">Executive</div>
              <div class="font-mono text-slate-300">{{ pct(evaluation.executive_score) }}</div>
            </div>
          </div>

          <div class="flex justify-end pt-2 border-t border-slate-900">
            <button
              @click="analyzeEvaluation(evaluation)"
              :disabled="loadingEvals[evaluation.id]"
              class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-indigo-600/10 text-indigo-400 border border-indigo-600/20 hover:bg-indigo-600/20 transition disabled:opacity-50"
            >
              <Icon
                :name="loadingEvals[evaluation.id] ? 'Loader2' : 'Brain'"
                :size="14"
                :class="{ 'animate-spin': loadingEvals[evaluation.id] }"
              />
              {{ loadingEvals[evaluation.id] ? 'Queuing…' : 'Deep Analysis' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB: Submit narrative report -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'submit'" class="max-w-2xl space-y-6">
      <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Submit Narrative Report</h3>

      <form @submit.prevent="submitReport" class="space-y-5">
        <!-- Employee -->
        <div class="space-y-1.5">
          <label class="block text-xs font-semibold text-slate-400">Employee</label>
          <select
            v-model="reportForm.employee_id"
            required
            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition"
          >
            <option value="" disabled>Select employee…</option>
            <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.full_name_en }}</option>
          </select>
          <p v-if="reportForm.errors.employee_id" class="text-xs text-rose-400">{{ reportForm.errors.employee_id }}</p>
        </div>

        <!-- Period -->
        <div class="space-y-1.5">
          <label class="block text-xs font-semibold text-slate-400">Evaluation Period</label>
          <select
            v-model="reportForm.evaluation_period_id"
            required
            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition"
          >
            <option value="" disabled>Select period…</option>
            <option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }}</option>
          </select>
          <p v-if="reportForm.errors.evaluation_period_id" class="text-xs text-rose-400">{{ reportForm.errors.evaluation_period_id }}</p>
        </div>

        <!-- Language -->
        <div class="space-y-1.5">
          <label class="block text-xs font-semibold text-slate-400">Language</label>
          <div class="flex gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="reportForm.language" value="en" class="accent-indigo-500" />
              <span class="text-sm text-slate-300">English</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="reportForm.language" value="am" class="accent-indigo-500" />
              <span class="text-sm text-slate-300">አማርኛ (Amharic)</span>
            </label>
          </div>
        </div>

        <!-- Body -->
        <div class="space-y-1.5">
          <label class="block text-xs font-semibold text-slate-400">Narrative</label>
          <textarea
            v-model="reportForm.body"
            required
            rows="8"
            placeholder="Describe the employee's performance during the period — achievements, challenges, KPI progress, conduct, and any notable events…"
            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-indigo-500 transition resize-none leading-relaxed"
          />
          <p v-if="reportForm.errors.body" class="text-xs text-rose-400">{{ reportForm.errors.body }}</p>
        </div>

        <div class="flex items-center gap-3">
          <button
            type="submit"
            :disabled="reportForm.processing"
            class="flex items-center gap-2 px-6 py-2.5 rounded-xl font-semibold text-sm bg-indigo-600 hover:bg-indigo-500 text-white transition disabled:opacity-50 shadow"
          >
            <Icon :name="reportForm.processing ? 'Loader2' : 'Send'" :size="15"
              :class="{ 'animate-spin': reportForm.processing }" />
            {{ reportForm.processing ? 'Submitting…' : 'Submit & Queue Analysis' }}
          </button>
          <p v-if="reportForm.wasSuccessful" class="text-xs text-emerald-400 flex items-center gap-1.5">
            <Icon name="CheckCircle" :size="13" /> Submitted successfully.
          </p>
        </div>
      </form>
    </div>

  </div>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from { opacity: 0; transform: translateX(24px); }
.toast-leave-to   { opacity: 0; transform: translateX(24px); }

.line-clamp-4 {
  display: -webkit-box;
  -webkit-line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
