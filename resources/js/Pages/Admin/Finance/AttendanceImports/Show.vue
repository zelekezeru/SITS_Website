<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, reactive } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  attendanceImport: { type: Object, required: true },
  rows: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  baseUrl: { type: String, default: '/admin/attendance-imports' },
  canApprove: { type: Boolean, default: true },
});

const editable = computed(() => props.attendanceImport.status === 'pending_review');

// Per-row local edit state, seeded from the server snapshot.
const rowState = reactive({});
props.rows.forEach((row) => {
  rowState[row.id] = {
    employee_id: row.employee_id ?? '',
    is_excluded: row.is_excluded,
    exclusion_reason: row.exclusion_reason ?? '',
    suggested_permitted_days: row.suggested_permitted_days,
    persist_exemption: false,
  };
});

const isDirty = (row) => {
  const s = rowState[row.id];
  return s.employee_id != (row.employee_id ?? '')
    || s.is_excluded !== row.is_excluded
    || s.exclusion_reason !== (row.exclusion_reason ?? '')
    || Number(s.suggested_permitted_days) !== row.suggested_permitted_days
    || s.persist_exemption;
};

const savingRowId = ref(null);
const saveRow = (row) => {
  savingRowId.value = row.id;
  router.patch(`${props.baseUrl}/${props.attendanceImport.id}/rows/${row.id}`, rowState[row.id], {
    preserveScroll: true,
    onFinish: () => { savingRowId.value = null; },
  });
};

const hasUnresolvedRows = computed(() =>
  props.rows.some((row) => {
    const s = rowState[row.id];
    return !s.is_excluded && ['ambiguous', 'unmatched'].includes(row.match_status);
  })
);

const approve = async () => {
  const confirmed = await confirm({
    title: 'Approve Attendance Import',
    message: 'This posts verified attendance records into the selected payroll period. Continue?',
  });
  if (confirmed) {
    router.post(`/admin/attendance-imports/${props.attendanceImport.id}/approve`, {}, { preserveScroll: true });
  }
};

const rejectModalOpen = ref(false);
const rejectNotes = ref('');
const reject = () => {
  router.post(`/admin/attendance-imports/${props.attendanceImport.id}/reject`, { review_notes: rejectNotes.value }, {
    onSuccess: () => { rejectModalOpen.value = false; },
  });
};

const deleteImport = async () => {
  const confirmed = await confirm({
    title: 'Delete Attendance Import',
    message: 'Are you sure you want to delete this attendance import session? This action cannot be undone.',
  });
  if (confirmed) {
    router.delete(`${props.baseUrl}/${props.attendanceImport.id}`);
  }
};

const MATCH_BADGE = {
  matched: { icon: 'CheckCircle2', cls: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' },
  ambiguous: { icon: 'AlertTriangle', cls: 'bg-amber-500/10 border-amber-500/20 text-amber-400' },
  unmatched: { icon: 'CircleHelp', cls: 'bg-rose-500/10 border-rose-500/20 text-rose-400' },
};
const METHOD_LABELS = {
  device_code: 'Auto (device ID)',
  name_exact: 'Auto (exact name)',
  name_fuzzy: 'Auto (similar name)',
  manual: 'Manual link',
};

const STATUS_CLASSES = {
  pending_review: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
  approved: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  rejected: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
};

const hrs = (minutes) => (Number(minutes || 0) / 60).toFixed(1);
</script>

<template>
  <Head title="Review Attendance Import — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="UploadCloud" :size="26" />
          </span>
          <div class="min-w-0">
            <Link :href="baseUrl" class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest inline-flex items-center gap-1 hover:text-slate-300">
              <Icon name="ArrowLeft" :size="12" /> Attendance Imports
            </Link>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ attendanceImport.payroll_period?.name }}</h2>
            <div class="flex items-center gap-2 mt-2">
              <span class="text-slate-400 text-sm truncate max-w-xl">{{ attendanceImport.original_filename }}</span>
              <a
                v-if="attendanceImport.file_path"
                :href="`/attendance-imports/${attendanceImport.id}/file`"
                target="_blank"
                class="text-[11px] font-semibold px-2 py-1 border border-slate-850 hover:border-slate-700 bg-slate-900/50 text-blue-400 hover:text-blue-300 rounded-lg transition-colors cursor-pointer inline-flex items-center gap-1 shrink-0"
                title="Download original file"
              >
                <Icon name="Download" :size="12" /> Download
              </a>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <span class="px-3 py-1 text-xs rounded-full font-bold border" :class="STATUS_CLASSES[attendanceImport.status]">
            {{ attendanceImport.status.replace('_', ' ') }}
          </span>
          <template v-if="attendanceImport.status !== 'approved'">
            <button
              @click="deleteImport"
              class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-rose-900 hover:text-rose-400 hover:bg-rose-500/10 text-slate-400 bg-slate-900/50 rounded-xl transition-colors cursor-pointer inline-flex items-center gap-1.5"
            >
              <Icon name="Trash2" :size="14" /> Delete
            </button>
          </template>
          <template v-if="editable && canApprove">
            <button
              @click="rejectModalOpen = true"
              class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-rose-700 text-rose-400 bg-slate-900/50 rounded-xl transition-colors cursor-pointer"
            >
              Reject
            </button>
            <button
              @click="approve"
              :disabled="hasUnresolvedRows"
              class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
              :title="hasUnresolvedRows ? 'Resolve or exclude every ambiguous/unmatched row first' : ''"
            >
              Approve & Post
            </button>
          </template>
        </div>
      </div>
    </section>

    <!-- Notes / warnings -->
    <div v-if="attendanceImport.review_notes" class="rounded-xl border border-amber-500/20 bg-amber-500/10 text-amber-300 text-sm px-5 py-4">
      {{ attendanceImport.review_notes }}
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-5">
        <p class="text-[11px] text-slate-500 uppercase tracking-widest font-semibold">Matched</p>
        <p class="text-2xl font-bold text-emerald-400 mt-1">{{ attendanceImport.matched_count }}</p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-5">
        <p class="text-[11px] text-slate-500 uppercase tracking-widest font-semibold">Ambiguous</p>
        <p class="text-2xl font-bold text-amber-400 mt-1">{{ attendanceImport.ambiguous_count }}</p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-5">
        <p class="text-[11px] text-slate-500 uppercase tracking-widest font-semibold">Unmatched</p>
        <p class="text-2xl font-bold text-rose-400 mt-1">{{ attendanceImport.unmatched_count }}</p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-5">
        <p class="text-[11px] text-slate-500 uppercase tracking-widest font-semibold">Excluded</p>
        <p class="text-2xl font-bold text-slate-400 mt-1">{{ attendanceImport.excluded_count }}</p>
      </div>
    </div>

    <!-- Rows -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Device Row</th>
              <th class="pb-3">Match</th>
              <th class="pb-3">Hours (Act/Std)</th>
              <th class="pb-3">Late</th>
              <th class="pb-3">Context</th>
              <th class="pb-3">Absent</th>
              <th class="pb-3">Permitted</th>
              <th class="pb-3">Exclude</th>
              <th class="pb-3 text-right">Save</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="row in rows" :key="row.id" class="align-top hover:bg-slate-900/40">
              <td class="py-4 pr-4">
                <p class="font-semibold text-slate-200">{{ row.device_name }}</p>
                <p class="text-[11px] text-slate-500">ID {{ row.device_employee_code }} &middot; {{ row.device_department || '—' }}</p>
              </td>
              <td class="py-4 pr-4 min-w-[220px]">
                <span
                  v-if="editable"
                  class="px-2 py-0.5 text-[10px] rounded-full font-bold border inline-flex items-center gap-1 mb-2"
                  :class="MATCH_BADGE[row.match_status]?.cls"
                >
                  <Icon :name="MATCH_BADGE[row.match_status]?.icon" :size="11" />
                  {{ row.match_method ? METHOD_LABELS[row.match_method] : row.match_status }}
                  <span v-if="row.match_confidence">({{ Number(row.match_confidence).toFixed(0) }}%)</span>
                </span>
                <select
                  v-if="editable"
                  v-model="rowState[row.id].employee_id"
                  class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-lg px-2 py-1.5 text-slate-100 text-xs focus:outline-none"
                >
                  <option value="">— unmatched —</option>
                  <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.full_name_en }}</option>
                </select>
                <p v-else class="text-xs text-slate-400">{{ row.employee?.full_name_en || '—' }}</p>
              </td>
              <td class="py-4 pr-4 font-mono text-slate-300 whitespace-nowrap">{{ hrs(row.work_duration_actual_minutes) }}h / {{ hrs(row.work_duration_standard_minutes) }}h</td>
              <td class="py-4 pr-4 font-mono text-amber-500">{{ row.late_minutes }}m <span class="text-slate-600">({{ row.late_times }}x)</span></td>
              <td class="py-4 pr-4 text-[11px] text-slate-500 leading-relaxed">
                <span v-if="row.leave_early_minutes">Left early {{ row.leave_early_minutes }}m ({{ row.leave_early_times }}x)<br /></span>
                <span v-if="row.lack_minutes">Lack {{ row.lack_minutes }}m ({{ row.lack_times }}x)<br /></span>
                <span v-if="row.overtime_normal_minutes || row.overtime_special_minutes">OT {{ row.overtime_normal_minutes }}m normal / {{ row.overtime_special_minutes }}m special<br /></span>
                <span v-if="row.remarks" class="italic">{{ row.remarks }}</span>
              </td>
              <td class="py-4 pr-4">
                <span :class="row.absent_days > 0 ? 'text-rose-400 font-bold' : 'text-slate-500'">{{ row.absent_days }}d</span>
              </td>
              <td class="py-4 pr-4">
                <input
                  v-if="editable"
                  v-model.number="rowState[row.id].suggested_permitted_days"
                  type="number" min="0"
                  class="w-16 bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-lg px-2 py-1.5 text-slate-100 text-xs focus:outline-none"
                />
                <span v-else>{{ row.suggested_permitted_days }}</span>
              </td>
              <td class="py-4 pr-4 min-w-[180px]">
                <template v-if="editable">
                  <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                    <input type="checkbox" v-model="rowState[row.id].is_excluded" class="rounded border-slate-700 bg-slate-950" />
                    Exclude
                  </label>
                  <div v-if="rowState[row.id].is_excluded" class="mt-2 space-y-1.5">
                    <input
                      v-model="rowState[row.id].exclusion_reason"
                      type="text" placeholder="Reason"
                      class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-lg px-2 py-1.5 text-slate-100 text-xs focus:outline-none"
                    />
                    <label v-if="rowState[row.id].employee_id" class="flex items-center gap-1.5 text-[11px] text-slate-500">
                      <input type="checkbox" v-model="rowState[row.id].persist_exemption" class="rounded border-slate-700 bg-slate-950" />
                      Always exclude this employee
                    </label>
                  </div>
                </template>
                <span v-else-if="row.is_excluded" class="text-xs text-slate-500 italic">Excluded — {{ row.exclusion_reason }}</span>
                <span v-else class="text-xs text-slate-600">—</span>
              </td>
              <td class="py-4 text-right">
                <button
                  v-if="editable"
                  @click="saveRow(row)"
                  :disabled="!isDirty(row) || savingRowId === row.id"
                  class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-blue-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                >
                  {{ savingRowId === row.id ? 'Saving…' : 'Save' }}
                </button>
              </td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="9" class="py-8 text-center text-slate-650 italic">No rows parsed from this file.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Reject modal -->
    <div v-if="rejectModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-4">Reject Import</h3>
        <p class="text-sm text-slate-400 mb-4">This discards the import without posting anything to payroll. You can re-upload a corrected file afterward.</p>
        <textarea
          v-model="rejectNotes"
          rows="3"
          placeholder="Reason (optional)"
          class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none"
        ></textarea>
        <div class="flex items-center justify-end gap-3 mt-6">
          <button @click="rejectModalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer">
            Cancel
          </button>
          <button @click="reject" class="text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white px-5 py-2.5 rounded-xl transition-all cursor-pointer">
            Reject Import
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
