<script>
import IntegrityLayout from '@/Layouts/Integrity/AuthenticatedLayout.vue';
export default { layout: IntegrityLayout };
</script>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  stats: { type: Object, required: true },
  recentDocuments: { type: Array, default: () => [] },
});

const statusTone = {
  pending: 'bg-slate-500/10 border-slate-500/25 text-slate-400',
  processing: 'bg-blue-500/10 border-blue-500/25 text-blue-400',
  complete: 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400',
  failed: 'bg-rose-500/10 border-rose-500/25 text-rose-400',
};

const verdictTone = {
  likely_human: 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400',
  mixed: 'bg-amber-500/10 border-amber-500/25 text-amber-400',
  likely_ai: 'bg-rose-500/10 border-rose-500/25 text-rose-400',
  insufficient_text: 'bg-slate-500/10 border-slate-500/25 text-slate-400',
};

// ----- New Analysis modal --------------------------------------------------
const showModal = ref(false);
const mode = ref('paste'); // 'paste' | 'upload'

const form = useForm({
  title: '',
  text: '',
  file: null,
});

const openModal = () => {
  form.reset();
  form.clearErrors();
  mode.value = 'paste';
  showModal.value = true;
};

const onFileChange = (e) => {
  form.file = e.target.files[0] ?? null;
};

const submit = () => {
  if (mode.value === 'paste') {
    form.file = null;
  } else {
    form.text = '';
  }

  form.post(route('integrity.documents.store'), {
    forceFormData: true,
    onSuccess: () => { showModal.value = false; },
  });
};
</script>

<template>
  <Head title="Academic Integrity — Dashboard" />

  <div class="space-y-8">
    <!-- Hero -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-amber-500/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/25 flex items-center justify-center text-amber-400 shrink-0">
            <Icon name="ShieldCheck" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Academic Integrity Suite</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Dashboard</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              AI-detection and plagiarism triage for student submissions. Every score is advisory — you decide what it means.
            </p>
          </div>
        </div>

        <button
          type="button"
          class="shrink-0 flex items-center gap-2 text-xs font-semibold bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-slate-950 px-5 py-3 rounded-xl transition-all shadow-md shadow-amber-500/10"
          @click="openModal"
        >
          <Icon name="Plus" :size="16" />
          New Analysis
        </button>
      </div>
    </section>

    <!-- Stat cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-5">
        <div class="flex items-center gap-2 text-slate-500">
          <Icon name="FileText" :size="16" />
          <span class="text-[11px] font-semibold uppercase tracking-wider">Analyzed this term</span>
        </div>
        <p class="text-3xl font-extrabold text-white mt-2">{{ stats.analyzed_this_term }}</p>
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-5">
        <div class="flex items-center gap-2 text-slate-500">
          <Icon name="ShieldAlert" :size="16" />
          <span class="text-[11px] font-semibold uppercase tracking-wider">Flagged, awaiting review</span>
        </div>
        <p class="text-3xl font-extrabold mt-2" :class="stats.flagged_awaiting_review > 0 ? 'text-amber-400' : 'text-white'">
          {{ stats.flagged_awaiting_review }}
        </p>
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-5">
        <div class="flex items-center gap-2 text-slate-500">
          <Icon name="Percent" :size="16" />
          <span class="text-[11px] font-semibold uppercase tracking-wider">Avg. AI probability</span>
        </div>
        <p class="text-3xl font-extrabold text-white mt-2">{{ stats.avg_ai_probability }}%</p>
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-5">
        <div class="flex items-center gap-2 text-slate-500">
          <Icon name="Layers" :size="16" />
          <span class="text-[11px] font-semibold uppercase tracking-wider">Corpus size</span>
        </div>
        <p class="text-3xl font-extrabold text-white mt-2">{{ stats.corpus_size }}</p>
      </div>
    </section>

    <!-- Recent documents -->
    <section class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold text-white">Recent Documents</h3>
        <Link :href="route('integrity.history')" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition-colors">
          View all →
        </Link>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Title</th>
              <th class="pb-3">Status</th>
              <th class="pb-3">Verdict</th>
              <th class="pb-3">AI Probability</th>
              <th class="pb-3">Submitted</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="doc in recentDocuments" :key="doc.uuid" class="hover:bg-slate-900/40 cursor-pointer" @click="$inertia.visit(route('integrity.documents.show', doc.uuid))">
              <td class="py-4 font-semibold text-slate-200">{{ doc.title }}</td>
              <td class="py-4">
                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border" :class="statusTone[doc.status] ?? statusTone.pending">
                  {{ doc.status }}
                </span>
              </td>
              <td class="py-4">
                <span
                  v-if="doc.report?.verdict_label"
                  class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border"
                  :class="verdictTone[doc.report.verdict_label] ?? verdictTone.mixed"
                >
                  {{ doc.report.verdict_label.replace('_', ' ') }}
                </span>
                <span v-else class="text-slate-600 text-xs">—</span>
              </td>
              <td class="py-4 text-slate-300 font-mono text-xs">
                {{ doc.report?.ai_probability !== null && doc.report?.ai_probability !== undefined ? doc.report.ai_probability + '%' : '—' }}
              </td>
              <td class="py-4 text-slate-450 text-xs">{{ new Date(doc.created_at).toLocaleDateString() }}</td>
            </tr>
            <tr v-if="!recentDocuments.length">
              <td colspan="5" class="py-8 text-center text-slate-600 italic">No documents analyzed yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- New Analysis modal -->
    <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0" leave-active-class="transition duration-100 ease-in" leave-to-class="opacity-0">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" @click.self="showModal = false">
        <div class="w-full max-w-lg rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl overflow-hidden">
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <h3 class="text-sm font-bold text-white">New Analysis</h3>
            <button type="button" class="text-slate-500 hover:text-slate-200" @click="showModal = false">
              <Icon name="X" :size="18" />
            </button>
          </div>

          <form class="p-6 space-y-4" @submit.prevent="submit">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Title</label>
              <input
                v-model="form.title" type="text" required placeholder="e.g. Midterm Essay — J. Doe"
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-amber-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-amber-500/50 transition-all text-sm"
              />
              <p v-if="form.errors.title" class="text-xs text-rose-400 mt-2">{{ form.errors.title }}</p>
            </div>

            <div class="flex gap-1 p-1 rounded-xl bg-slate-950/60 border border-slate-850 w-fit">
              <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors" :class="mode === 'paste' ? 'bg-slate-800 text-white' : 'text-slate-500'" @click="mode = 'paste'">Paste text</button>
              <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors" :class="mode === 'upload' ? 'bg-slate-800 text-white' : 'text-slate-500'" @click="mode = 'upload'">Upload file</button>
            </div>

            <div v-if="mode === 'paste'">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Pasted text</label>
              <textarea
                v-model="form.text" rows="6" placeholder="Paste the student's submission here…"
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-amber-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-amber-500/50 transition-all text-sm resize-none"
              ></textarea>
              <p v-if="form.errors.text" class="text-xs text-rose-400 mt-2">{{ form.errors.text }}</p>
            </div>

            <div v-else>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">File (.docx, .pdf, .txt)</label>
              <input
                type="file" accept=".docx,.pdf,.txt" required
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-amber-500/50 rounded-xl px-4 py-3 text-slate-300 text-sm file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-amber-600 file:text-slate-950 file:text-xs file:font-semibold"
                @change="onFileChange"
              />
              <p v-if="form.errors.file" class="text-xs text-rose-400 mt-2">{{ form.errors.file }}</p>
            </div>

            <p class="text-xs text-slate-500 leading-relaxed">
              Analysis results are advisory triage signals, not proof — see the disclaimer on every report.
            </p>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
              <button type="button" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors" @click="showModal = false">
                Cancel
              </button>
              <button
                type="submit" :disabled="form.processing"
                class="text-xs font-semibold bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-slate-950 px-5 py-2.5 rounded-xl transition-all shadow-md shadow-amber-500/10 disabled:opacity-50"
              >
                {{ form.processing ? 'Starting…' : 'Start Analysis' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </div>
</template>
