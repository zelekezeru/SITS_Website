<script>
import IntegrityLayout from '@/Layouts/Integrity/AuthenticatedLayout.vue';
export default { layout: IntegrityLayout };
</script>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  document: { type: Object, required: true },
});

const report = computed(() => props.document.report ?? null);

// ============================================================================
// Polling — documents/reports/plagiarism/writing-tools all complete async via
// queued jobs. Reuses the existing `show` route via Inertia partial reloads
// instead of a separate JSON endpoint.
// ============================================================================
const polling = ref(false);
let pollHandle = null;
let pollAttempts = 0;
const MAX_POLL_ATTEMPTS = 30; // ~90s at 3s intervals

function startPolling() {
  if (polling.value) return;
  polling.value = true;
  pollAttempts = 0;
  pollHandle = setInterval(() => {
    pollAttempts++;
    if (pollAttempts > MAX_POLL_ATTEMPTS) {
      stopPolling();
      return;
    }
    router.reload({ only: ['document'], preserveScroll: true });
  }, 3000);
}

function stopPolling() {
  polling.value = false;
  if (pollHandle) clearInterval(pollHandle);
  pollHandle = null;
}

watch(() => props.document.status, (status) => {
  if (status === 'complete' || status === 'failed') stopPolling();
});

onMounted(() => {
  if (['pending', 'processing'].includes(props.document.status)) startPolling();
});
onUnmounted(stopPolling);

const reanalyze = () => {
  router.post(route('integrity.documents.reanalyze', props.document.uuid), {}, { preserveScroll: true, onSuccess: startPolling });
};

// ============================================================================
// Gauge / verdict
// ============================================================================
const verdictLabel = {
  likely_human: 'Likely Human-Written',
  mixed: 'Mixed Signals',
  likely_ai: 'Likely AI-Generated',
  insufficient_text: 'Insufficient Text',
};

const gaugeColor = computed(() => {
  const p = report.value?.ai_probability ?? 0;
  if (p >= 70) return 'bg-gradient-to-r from-rose-500 to-rose-600';
  if (p <= 30) return 'bg-gradient-to-r from-emerald-500 to-emerald-600';
  return 'bg-gradient-to-r from-amber-500 to-amber-600';
});

const confidenceTone = {
  low: 'bg-slate-500/10 border-slate-500/25 text-slate-400',
  medium: 'bg-blue-500/10 border-blue-500/25 text-blue-400',
  high: 'bg-violet-500/10 border-violet-500/25 text-violet-400',
};

// ============================================================================
// Sentence heatmap
// ============================================================================
const selectedSentence = ref(null);

const heatmapSegments = computed(() => {
  const text = props.document.extracted_text ?? '';
  const scores = [...(report.value?.sentence_scores ?? [])].sort((a, b) => a.start - b.start);
  const segments = [];
  let cursor = 0;

  for (const s of scores) {
    if (s.start > cursor) segments.push({ text: text.slice(cursor, s.start), scored: false });
    segments.push({ text: text.slice(s.start, s.end), scored: true, ...s });
    cursor = Math.max(cursor, s.end);
  }
  if (cursor < text.length) segments.push({ text: text.slice(cursor), scored: false });

  return segments;
});

function heatStyle(score) {
  const t = Math.min(1, Math.max(0, score / 100));
  const r = Math.round(245 + (239 - 245) * t);
  const g = Math.round(158 + (68 - 158) * t);
  const b = Math.round(11 + (68 - 11) * t);
  return { backgroundColor: `rgba(${r}, ${g}, ${b}, ${(0.08 + t * 0.4).toFixed(2)})` };
}

// ============================================================================
// Statistical signals panel
// ============================================================================
const SIGNAL_META = {
  burstiness: { label: 'Sentence Rhythm (Burstiness)', explain: 'How much sentence length varies. Low variation can look unusually uniform, like AI writing.' },
  sentence_length_uniformity: { label: 'Sentence Length Uniformity', explain: 'Share of sentences close to the average length. High uniformity can look mechanical.' },
  type_token_ratio: { label: 'Vocabulary Diversity', explain: 'How varied the word choice is across the text.' },
  ngram_repetition: { label: 'Phrase Repetition', explain: 'How often 3–4 word phrases repeat.' },
  transition_density: { label: 'Generic Transitions', explain: 'Frequency of stock phrases like "furthermore" or "it is important to note".' },
  em_dash_rate: { label: 'Em-dash Usage', explain: 'Rate of em-dashes and similar punctuation quirks.' },
  paragraph_uniformity: { label: 'Paragraph Uniformity', explain: 'How similar paragraph lengths are to one another.' },
  sentence_opener_diversity: { label: 'Sentence-Opener Diversity', explain: 'How varied the first few words of each sentence are.' },
  personal_voice_markers: { label: 'Personal Voice', explain: 'First-person and hedging language — weak evidence of human drafting.' },
  list_structure_density: { label: 'Structure / List Density', explain: 'Headers, numbered lists, and bold markers per 1,000 words.' },
  readability_delta: { label: 'Readability vs. Baseline', explain: "How this text's reading ease compares to a graduate theological-writing baseline." },
};

const signalRows = computed(() => {
  const signals = report.value?.statistical_signals ?? {};
  return Object.entries(signals).map(([key, s]) => ({
    key,
    meta: SIGNAL_META[key] ?? { label: key, explain: '' },
    width: Math.min(100, Math.max(0, 50 + s.zscore_vs_baseline * 20)),
    ...s,
  }));
});

const directionTone = {
  ai_like: 'text-rose-400',
  human_like: 'text-emerald-400',
  neutral: 'text-slate-500',
};

// ============================================================================
// Tabs
// ============================================================================
const TABS = [
  { key: 'ai', label: 'AI Analysis', icon: 'Sparkles' },
  { key: 'plagiarism', label: 'Plagiarism', icon: 'Search' },
  { key: 'grammar', label: 'Grammar', icon: 'ClipboardCheck' },
  { key: 'summary', label: 'Summary', icon: 'FileText' },
  { key: 'factcheck', label: 'Fact-check', icon: 'BadgeCheck' },
  { key: 'feedback', label: 'Feedback draft', icon: 'ScrollText' },
];
const activeTab = ref('ai');

const writingReportByType = computed(() => {
  const map = {};
  for (const wr of props.document.writingReports ?? []) map[wr.type] = wr;
  return map;
});

const latestPlagiarismReport = computed(() => {
  const reports = props.document.plagiarismReports ?? [];
  return reports.length ? reports[reports.length - 1] : null;
});

function runWritingTool(type, notes = '') {
  router.post(route('integrity.documents.tools.run', [props.document.uuid, type]), { notes }, { preserveScroll: true, onSuccess: startPolling });
}

function runPlagiarism(type) {
  router.post(route('integrity.documents.plagiarism', props.document.uuid), { type }, { preserveScroll: true, onSuccess: startPolling });
}

const feedbackNotes = ref('');

// ============================================================================
// Review workflow
// ============================================================================
const reviewNotes = ref(report.value?.review_notes ?? '');
const meetingDate = ref(report.value?.student_meeting_date ?? '');

const reviewStatusLabel = {
  none: 'Not Reviewed',
  under_review: 'Under Review',
  cleared: 'Cleared',
  upheld: 'Upheld',
};

function submitReview(action) {
  router.patch(route('integrity.reports.review', report.value.id), {
    action,
    notes: reviewNotes.value,
    student_meeting_date: meetingDate.value || null,
  }, { preserveScroll: true });
}
</script>

<template>
  <Head :title="`${document.title} — Academic Integrity`" />

  <div class="space-y-6">
    <!-- Disclaimer banner: permanent, non-dismissable -->
    <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 px-4 py-3 flex items-start gap-3">
      <Icon name="AlertTriangle" :size="16" class="text-amber-400 shrink-0 mt-0.5" />
      <p class="text-xs text-amber-200/80 leading-relaxed">
        AI-detection scores are probabilistic indicators, not proof. ESL writing is more likely to be falsely
        flagged. Use this report as a starting point for a conversation, not a conclusion.
      </p>
    </div>

    <!-- Header -->
    <section class="rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="flex items-start justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">
            {{ document.course?.title ?? 'No course' }} · {{ document.student?.name ?? 'No student linked' }}
          </p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1 text-white">{{ document.title }}</h2>
          <p class="text-slate-500 text-xs mt-2">
            {{ document.word_count }} words · uploaded {{ new Date(document.created_at).toLocaleString() }}
            <span v-if="report?.engine_version"> · engine v{{ report.engine_version }}</span>
          </p>
        </div>

        <div class="shrink-0 flex items-center gap-2">
          <a
            v-if="report"
            :href="route('integrity.documents.export.pdf', document.uuid)"
            class="flex items-center gap-2 text-xs font-semibold border border-slate-800 hover:border-slate-700 bg-slate-900/50 text-slate-200 px-4 py-2.5 rounded-xl transition-colors"
          >
            <Icon name="Download" :size="14" />
            Export PDF
          </a>
          <button
            type="button" class="flex items-center gap-2 text-xs font-semibold border border-slate-800 hover:border-slate-700 bg-slate-900/50 text-slate-200 px-4 py-2.5 rounded-xl transition-colors disabled:opacity-50"
            :disabled="polling" @click="reanalyze"
          >
            <Icon name="RefreshCw" :size="14" :class="polling ? 'animate-spin' : ''" />
            {{ polling ? 'Working…' : 'Reanalyze' }}
          </button>
        </div>
      </div>

      <!-- Pending/processing state -->
      <div v-if="['pending', 'processing'].includes(document.status)" class="mt-6 flex items-center gap-3 text-slate-400 text-sm">
        <Icon name="Loader2" :size="18" class="animate-spin text-amber-400" />
        Analysis in progress — this updates automatically.
      </div>

      <!-- Failed state -->
      <div v-else-if="document.status === 'failed'" class="mt-6 flex items-center gap-3 text-rose-400 text-sm">
        <Icon name="AlertTriangle" :size="18" />
        Extraction failed: {{ document.failure_reason ?? 'unknown reason' }}
      </div>

      <!-- Gauge -->
      <div v-else-if="report" class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-6 items-center">
        <div class="sm:col-span-2">
          <div class="flex items-baseline gap-3 mb-2">
            <span class="text-4xl font-extrabold text-white">{{ report.ai_probability ?? '—' }}<span class="text-lg text-slate-500">%</span></span>
            <span class="text-sm font-semibold text-slate-300">{{ verdictLabel[report.verdict_label] ?? report.verdict_label }}</span>
          </div>
          <div class="w-full h-3 rounded-full bg-slate-800 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500" :class="gaugeColor" :style="{ width: (report.ai_probability ?? 0) + '%' }"></div>
          </div>
        </div>
        <div class="flex sm:justify-end">
          <span class="text-[10px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-full border" :class="confidenceTone[report.confidence] ?? confidenceTone.low">
            {{ report.confidence }} confidence
          </span>
        </div>
      </div>
    </section>

    <template v-if="report && document.status === 'complete'">
      <!-- Review bar -->
      <section v-if="report.flagged || report.review_status !== 'none'" class="rounded-2xl border border-amber-500/20 bg-amber-500/5 shadow-md p-6">
        <div class="flex items-center justify-between gap-4 flex-wrap">
          <div class="flex items-center gap-2">
            <Icon name="ShieldAlert" :size="18" class="text-amber-400" />
            <span class="text-sm font-bold text-white">Review status:</span>
            <span class="text-xs font-semibold text-amber-300">{{ reviewStatusLabel[report.review_status] }}</span>
          </div>
          <div class="flex gap-2">
            <button v-if="report.review_status === 'none'" type="button" class="text-xs font-semibold bg-amber-600 hover:bg-amber-500 text-slate-950 px-4 py-2 rounded-xl transition-colors" @click="submitReview('start')">
              Start Review
            </button>
            <template v-if="report.review_status === 'under_review'">
              <button type="button" class="text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-xl transition-colors" @click="submitReview('clear')">Clear</button>
              <button type="button" class="text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white px-4 py-2 rounded-xl transition-colors" @click="submitReview('uphold')">Uphold</button>
            </template>
          </div>
        </div>

        <div v-if="report.review_status === 'under_review'" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
          <textarea v-model="reviewNotes" rows="2" placeholder="Notes from the conversation with the student…" class="sm:col-span-2 bg-slate-950/60 border border-slate-850 rounded-xl px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-amber-500/50 resize-none"></textarea>
          <input v-model="meetingDate" type="date" class="bg-slate-950/60 border border-slate-850 rounded-xl px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-amber-500/50" />
        </div>
        <p v-if="report.review_notes && report.review_status !== 'under_review'" class="mt-3 text-xs text-slate-400 italic">"{{ report.review_notes }}"</p>
      </section>

      <!-- Tabs -->
      <section class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md overflow-hidden">
        <div class="flex flex-wrap border-b border-slate-900">
          <button
            v-for="tab in TABS" :key="tab.key" type="button"
            class="flex items-center gap-1.5 px-4 py-3 text-xs font-semibold transition-colors border-b-2"
            :class="activeTab === tab.key ? 'text-amber-400 border-amber-400' : 'text-slate-500 border-transparent hover:text-slate-300'"
            @click="activeTab = tab.key"
          >
            <Icon :name="tab.icon" :size="14" />
            {{ tab.label }}
          </button>
        </div>

        <div class="p-6">
          <!-- AI Analysis tab: heatmap + signals -->
          <div v-if="activeTab === 'ai'" class="space-y-8">
            <div>
              <h4 class="text-sm font-bold text-white mb-3">Sentence Heatmap</h4>
              <div class="rounded-xl border border-slate-900 bg-slate-950/40 p-5 text-sm leading-relaxed text-slate-300 whitespace-pre-wrap">
                <span
                  v-for="(seg, i) in heatmapSegments" :key="i"
                  :style="seg.scored ? heatStyle(seg.score) : {}"
                  class="rounded px-0.5"
                  :class="seg.scored ? 'cursor-pointer' : ''"
                  @click="seg.scored ? (selectedSentence = seg) : null"
                >{{ seg.text }}</span>
              </div>
            </div>

            <div v-if="selectedSentence" class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-white">Sentence score: {{ selectedSentence.score }}/100</span>
                <button type="button" class="text-slate-500 hover:text-slate-300" @click="selectedSentence = null"><Icon name="X" :size="14" /></button>
              </div>
              <div class="flex flex-wrap gap-1.5">
                <span v-for="sig in selectedSentence.signals" :key="sig" class="text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-slate-800 text-slate-300">{{ sig.replace('_', ' ') }}</span>
                <span v-if="!selectedSentence.signals?.length" class="text-xs text-slate-500">No specific signals flagged this sentence.</span>
              </div>
            </div>

            <div>
              <h4 class="text-sm font-bold text-white mb-3">Statistical Signals</h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-for="row in signalRows" :key="row.key" class="rounded-xl border border-slate-900 bg-slate-950/40 p-4">
                  <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs font-semibold text-slate-200">{{ row.meta.label }}</span>
                    <span class="text-[10px] font-bold uppercase tracking-wide" :class="directionTone[row.direction] ?? directionTone.neutral">{{ row.direction.replace('_', ' ') }}</span>
                  </div>
                  <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden mb-2">
                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 via-amber-500 to-rose-500" :style="{ width: row.width + '%' }"></div>
                  </div>
                  <p class="text-[11px] text-slate-500 leading-snug">{{ row.meta.explain }}</p>
                </div>
              </div>
            </div>

            <div v-if="report.claude_analysis?.reasoning_summary" class="rounded-xl border border-slate-900 bg-slate-950/40 p-4">
              <h4 class="text-xs font-bold text-white mb-2 flex items-center gap-1.5"><Icon name="Sparkles" :size="14" class="text-amber-400" /> Claude's reasoning</h4>
              <p class="text-xs text-slate-400 leading-relaxed">{{ report.claude_analysis.reasoning_summary }}</p>
            </div>
          </div>

          <!-- Plagiarism tab -->
          <div v-else-if="activeTab === 'plagiarism'" class="space-y-6">
            <div class="flex flex-wrap gap-3">
              <button type="button" class="text-xs font-semibold bg-amber-600 hover:bg-amber-500 text-slate-950 px-4 py-2.5 rounded-xl transition-colors" @click="runPlagiarism('corpus')">
                Check Internal Corpus
              </button>
              <button type="button" class="text-xs font-semibold border border-slate-800 hover:border-slate-700 bg-slate-900/50 text-slate-200 px-4 py-2.5 rounded-xl transition-colors" @click="runPlagiarism('web')">
                Check Published Sources (5× quota)
              </button>
            </div>

            <div v-if="latestPlagiarismReport" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="rounded-xl border border-slate-900 bg-slate-950/40 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Corpus similarity</p>
                <p class="text-2xl font-extrabold text-white mt-1">{{ latestPlagiarismReport.overall_similarity }}%</p>
                <p class="text-[11px] text-slate-500 mt-1">against {{ latestPlagiarismReport.corpus_size }} prior submission(s)</p>
              </div>
              <div class="rounded-xl border border-slate-900 bg-slate-950/40 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Web similarity</p>
                <p class="text-2xl font-extrabold text-white mt-1">{{ latestPlagiarismReport.web_similarity ?? '—' }}<span v-if="latestPlagiarismReport.web_similarity !== null">%</span></p>
                <p class="text-[11px] text-slate-500 mt-1">not blended into the corpus score</p>
              </div>
            </div>

            <div v-if="latestPlagiarismReport?.matches?.length" class="space-y-4">
              <div v-for="(match, i) in latestPlagiarismReport.matches" :key="i" class="rounded-xl border border-slate-900 bg-slate-950/40 p-4">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-bold text-white">
                    {{ match.source_type === 'web' ? (match.source_title ?? match.url) : match.matched_title }}
                  </span>
                  <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border" :class="match.source_type === 'web' ? 'bg-blue-500/10 border-blue-500/25 text-blue-400' : 'bg-amber-500/10 border-amber-500/25 text-amber-400'">
                    {{ match.source_type }} · {{ match.similarity_pct ?? match.match_quality }}{{ match.similarity_pct !== undefined ? '%' : '' }}
                  </span>
                </div>
                <div v-if="match.shared_passages?.length" class="space-y-2 mt-3">
                  <div v-for="(passage, j) in match.shared_passages" :key="j" class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                    <p class="rounded-lg bg-slate-900/60 p-2 text-slate-300">{{ passage.source_excerpt }}</p>
                    <p class="rounded-lg bg-slate-900/60 p-2 text-slate-300">{{ passage.matched_excerpt ?? '—' }}</p>
                  </div>
                </div>
                <p v-else-if="match.matched_excerpt" class="text-[11px] text-slate-400 mt-2 italic">"{{ match.matched_excerpt }}"</p>
                <a v-if="match.url" :href="match.url" target="_blank" class="text-[11px] text-blue-400 hover:text-blue-300 mt-2 inline-block truncate">{{ match.url }}</a>
              </div>
            </div>
            <p v-else-if="latestPlagiarismReport" class="text-xs text-slate-500 italic">No matches above the reporting threshold.</p>
            <p v-else class="text-xs text-slate-500 italic">No plagiarism check has been run yet.</p>
          </div>

          <!-- Grammar tab -->
          <div v-else-if="activeTab === 'grammar'">
            <button v-if="!writingReportByType.grammar" type="button" class="text-xs font-semibold bg-amber-600 hover:bg-amber-500 text-slate-950 px-4 py-2.5 rounded-xl transition-colors" @click="runWritingTool('grammar')">
              Run Grammar Check
            </button>
            <div v-else class="space-y-2">
              <div v-for="(s, i) in writingReportByType.grammar.payload" :key="i" class="rounded-xl border border-slate-900 bg-slate-950/40 p-3 flex items-start gap-3">
                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border shrink-0" :class="s.severity === 'high' ? 'bg-rose-500/10 border-rose-500/25 text-rose-400' : s.severity === 'medium' ? 'bg-amber-500/10 border-amber-500/25 text-amber-400' : 'bg-slate-500/10 border-slate-500/25 text-slate-400'">{{ s.category }}</span>
                <div class="text-xs">
                  <p class="text-slate-400 line-through decoration-rose-500/60">{{ s.original }}</p>
                  <p class="text-emerald-300 mt-0.5">{{ s.suggestion }}</p>
                </div>
              </div>
              <p v-if="!writingReportByType.grammar.payload?.length" class="text-xs text-slate-500 italic">No issues found.</p>
            </div>
          </div>

          <!-- Summary tab -->
          <div v-else-if="activeTab === 'summary'">
            <button v-if="!writingReportByType.summary" type="button" class="text-xs font-semibold bg-amber-600 hover:bg-amber-500 text-slate-950 px-4 py-2.5 rounded-xl transition-colors" @click="runWritingTool('summary')">
              Generate Summary
            </button>
            <div v-else class="space-y-4">
              <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Suggested title</p>
                <p class="text-sm font-bold text-white">{{ writingReportByType.summary.payload.suggested_title }}</p>
              </div>
              <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Abstract</p>
                <p class="text-sm text-slate-300 leading-relaxed">{{ writingReportByType.summary.payload.abstract }}</p>
              </div>
              <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Key claims</p>
                <ul class="list-disc list-inside text-sm text-slate-300 space-y-1">
                  <li v-for="(c, i) in writingReportByType.summary.payload.key_claims" :key="i">{{ c }}</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Fact-check tab -->
          <div v-else-if="activeTab === 'factcheck'">
            <button v-if="!writingReportByType.factcheck" type="button" class="text-xs font-semibold bg-amber-600 hover:bg-amber-500 text-slate-950 px-4 py-2.5 rounded-xl transition-colors" @click="runWritingTool('factcheck')">
              Run Fact-check
            </button>
            <div v-else class="space-y-2">
              <p class="text-[11px] text-slate-500 mb-3">Advisory only — checkability, not a truth verdict.</p>
              <div v-for="(c, i) in writingReportByType.factcheck.payload" :key="i" class="rounded-xl border border-slate-900 bg-slate-950/40 p-3">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-xs font-semibold text-slate-200">{{ c.claim }}</span>
                  <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 shrink-0 ml-2">{{ c.checkability.replace('_', ' ') }}</span>
                </div>
                <p class="text-[11px] text-slate-500">{{ c.note }}</p>
              </div>
            </div>
          </div>

          <!-- Feedback draft tab -->
          <div v-else-if="activeTab === 'feedback'" class="space-y-4">
            <div v-if="!writingReportByType.feedback">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Your rough notes (optional)</label>
              <textarea v-model="feedbackNotes" rows="3" placeholder="e.g. Seems rushed, ask about their process…" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-3 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-amber-500/50 resize-none mb-3"></textarea>
              <button type="button" class="text-xs font-semibold bg-amber-600 hover:bg-amber-500 text-slate-950 px-4 py-2.5 rounded-xl transition-colors" @click="runWritingTool('feedback', feedbackNotes)">
                Draft Feedback
              </button>
            </div>
            <div v-else class="rounded-xl border border-slate-900 bg-slate-950/40 p-4">
              <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2">Draft — edit before sharing</p>
              <p class="text-sm text-slate-300 leading-relaxed whitespace-pre-wrap">{{ writingReportByType.feedback.payload.draft }}</p>
              <p class="text-[11px] text-slate-600 mt-3 italic">This is a draft only. Nothing here is sent automatically.</p>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>
