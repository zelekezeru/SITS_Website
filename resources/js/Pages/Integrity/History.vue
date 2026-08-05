<script>
import IntegrityLayout from '@/Layouts/Integrity/AuthenticatedLayout.vue';
export default { layout: IntegrityLayout };
</script>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  documents: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const statusTone = {
  pending: 'bg-slate-500/10 border-slate-500/25 text-slate-400',
  processing: 'bg-blue-500/10 border-blue-500/25 text-blue-400',
  complete: 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400',
  failed: 'bg-rose-500/10 border-rose-500/25 text-rose-400',
};

const filters = reactive({
  status: props.filters.status ?? '',
  flagged: props.filters.flagged ?? '',
  from: props.filters.from ?? '',
  to: props.filters.to ?? '',
});

const applyFilters = () => {
  router.get(route('integrity.history'), { ...filters }, { preserveState: true, replace: true });
};

const resetFilters = () => {
  filters.status = '';
  filters.flagged = '';
  filters.from = '';
  filters.to = '';
  applyFilters();
};

const exportCsv = () => {
  const rows = [
    ['Title', 'Status', 'Verdict', 'AI Probability', 'Flagged', 'Review Status', 'Submitted'],
    ...props.documents.data.map((d) => [
      d.title,
      d.status,
      d.report?.verdict_label ?? '',
      d.report?.ai_probability ?? '',
      d.report?.flagged ? 'yes' : 'no',
      d.report?.review_status ?? '',
      d.created_at,
    ]),
  ];
  const csv = rows.map((r) => r.map((cell) => `"${String(cell ?? '').replace(/"/g, '""')}"`).join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `integrity-history-${new Date().toISOString().slice(0, 10)}.csv`;
  link.click();
  URL.revokeObjectURL(url);
};
</script>

<template>
  <Head title="Academic Integrity — History" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-amber-500/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/25 flex items-center justify-center text-amber-400 shrink-0">
            <Icon name="History" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Academic Integrity Suite</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">History</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">Every analysis you've run, filterable by status, flag state, and date range.</p>
          </div>
        </div>

        <button
          type="button" class="shrink-0 flex items-center gap-2 text-xs font-semibold border border-slate-800 hover:border-slate-700 bg-slate-900/50 text-slate-200 px-4 py-2.5 rounded-xl transition-colors"
          @click="exportCsv"
        >
          <Icon name="Download" :size="15" />
          Export CSV
        </button>
      </div>
    </section>

    <!-- Filters -->
    <section class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-5">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <select v-model="filters.status" class="bg-slate-950/60 border border-slate-850 rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-amber-500/50">
          <option value="">All statuses</option>
          <option value="pending">Pending</option>
          <option value="processing">Processing</option>
          <option value="complete">Complete</option>
          <option value="failed">Failed</option>
        </select>

        <select v-model="filters.flagged" class="bg-slate-950/60 border border-slate-850 rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-amber-500/50">
          <option value="">Flagged or not</option>
          <option value="1">Flagged only</option>
        </select>

        <input v-model="filters.from" type="date" class="bg-slate-950/60 border border-slate-850 rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-amber-500/50" />
        <input v-model="filters.to" type="date" class="bg-slate-950/60 border border-slate-850 rounded-xl px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-amber-500/50" />

        <div class="flex gap-2">
          <button type="button" class="flex-1 text-xs font-semibold bg-amber-600 hover:bg-amber-500 text-slate-950 px-3 py-2.5 rounded-xl transition-colors" @click="applyFilters">Apply</button>
          <button type="button" class="text-xs font-semibold border border-slate-800 hover:border-slate-700 text-slate-300 px-3 py-2.5 rounded-xl transition-colors" @click="resetFilters">Reset</button>
        </div>
      </div>
    </section>

    <!-- Table -->
    <section class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Title</th>
              <th class="pb-3">Status</th>
              <th class="pb-3">Verdict</th>
              <th class="pb-3">AI Probability</th>
              <th class="pb-3">Review</th>
              <th class="pb-3">Submitted</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="doc in documents.data" :key="doc.uuid" class="hover:bg-slate-900/40 cursor-pointer" @click="$inertia.visit(route('integrity.documents.show', doc.uuid))">
              <td class="py-4 font-semibold text-slate-200">{{ doc.title }}</td>
              <td class="py-4">
                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border" :class="statusTone[doc.status] ?? statusTone.pending">
                  {{ doc.status }}
                </span>
              </td>
              <td class="py-4 text-slate-300 text-xs">{{ doc.report?.verdict_label?.replace('_', ' ') ?? '—' }}</td>
              <td class="py-4 text-slate-300 font-mono text-xs">{{ doc.report?.ai_probability ?? '—' }}{{ doc.report?.ai_probability !== null && doc.report?.ai_probability !== undefined ? '%' : '' }}</td>
              <td class="py-4 text-slate-400 text-xs">{{ doc.report?.review_status ?? '—' }}</td>
              <td class="py-4 text-slate-450 text-xs">{{ new Date(doc.created_at).toLocaleDateString() }}</td>
            </tr>
            <tr v-if="!documents.data.length">
              <td colspan="6" class="py-8 text-center text-slate-600 italic">No documents match these filters.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="documents.links?.length > 3" class="flex flex-wrap gap-1 mt-6 pt-4 border-t border-slate-900">
        <Link
          v-for="link in documents.links" :key="link.label"
          :href="link.url ?? ''"
          v-html="link.label"
          class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
          :class="[
            link.active ? 'bg-amber-500/15 text-amber-400 border border-amber-500/25' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-900/60',
            !link.url ? 'pointer-events-none opacity-40' : '',
          ]"
          preserve-state
        />
      </div>
    </section>
  </div>
</template>
