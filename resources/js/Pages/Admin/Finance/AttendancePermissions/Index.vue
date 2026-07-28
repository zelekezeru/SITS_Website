<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, default: () => ({}) },
  permissions: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  periods: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
});

const STATUS = {
  pending: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
  approved: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  rejected: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
};

// ─── Period scoping ──────────────────────────────────────────────────────────
// Permissions are reviewed one payroll period at a time — that is the unit
// payroll actually runs on. Land on the newest period that has anything in it.
const defaultPeriod = () =>
  props.periods.find((p) => p.total > 0)?.id ?? props.periods[0]?.id ?? null;

const activePeriodId = ref(defaultPeriod());

watch(() => props.periods, () => {
  if (!props.periods.some((p) => p.id === activePeriodId.value)) {
    activePeriodId.value = defaultPeriod();
  }
});

const activePeriod = computed(() =>
  props.periods.find((p) => p.id === activePeriodId.value) ?? null);

const filter = ref('all'); // all | pending | missing_evidence
const search = ref('');

const periodPermissions = computed(() =>
  props.permissions.filter((p) => p.payroll_period_id === activePeriodId.value));

const visible = computed(() => {
  const q = search.value.trim().toLowerCase();

  return periodPermissions.value.filter((p) => {
    if (filter.value === 'pending' && p.status !== 'pending') return false;
    if (filter.value === 'missing_evidence' && p.has_evidence) return false;
    if (!q) return true;
    return [p.employee, p.staff_no, p.reason].filter(Boolean)
      .some((v) => String(v).toLowerCase().includes(q));
  });
});

const missingEvidence = computed(() =>
  periodPermissions.value.filter((p) => !p.has_evidence).length);

// ─── Create / edit ───────────────────────────────────────────────────────────
const modalOpen = ref(false);
const editing = ref(null);

const form = useForm({
  employee_id: '', payroll_period_id: '', start_date: '', end_date: '',
  days: 1, reason: '', file: null, remove_file: false,
});

const openCreate = () => {
  editing.value = null;
  form.reset();
  form.employee_id = props.employees[0]?.id ?? '';
  form.payroll_period_id = activePeriodId.value ?? props.periods[0]?.id ?? '';
  form.clearErrors();
  modalOpen.value = true;
};

const openEdit = (p) => {
  editing.value = p;
  form.reset();
  form.employee_id = p.employee_id;
  form.payroll_period_id = p.payroll_period_id;
  form.start_date = p.start_date ?? '';
  form.end_date = p.end_date ?? '';
  form.days = p.days;
  form.reason = p.reason ?? '';
  form.file = null;
  form.remove_file = false;
  form.clearErrors();
  modalOpen.value = true;
};

const submit = () => {
  const opts = { preserveScroll: true, onSuccess: () => { modalOpen.value = false; editing.value = null; } };

  if (editing.value) form.post(`/attendance-permissions/${editing.value.id}`, opts);
  else form.post('/attendance-permissions', opts);
};

// ─── Attach evidence after the fact ──────────────────────────────────────────
const attachTarget = ref(null);
const attachForm = useForm({ file: null });

const openAttach = (p) => {
  attachTarget.value = p;
  attachForm.reset();
  attachForm.clearErrors();
};

const submitAttach = () => {
  attachForm.post(`/attendance-permissions/${attachTarget.value.id}/attachment`, {
    preserveScroll: true,
    onSuccess: () => { attachTarget.value = null; },
  });
};

// ─── Approval ────────────────────────────────────────────────────────────────
const approve = async (p) => {
  const ok = await confirm({
    title: 'Approve Permission',
    message: p.has_evidence
      ? `Approve ${p.days} excused day(s) for ${p.employee}?`
      : `${p.employee} has no supporting document on file. Approve ${p.days} excused day(s) anyway? The document can still be attached afterwards.`,
  });
  if (ok) router.post(`/attendance-permissions/${p.id}/approve`, {}, { preserveScroll: true });
};

const rejectModal = ref(null);
const rejectNotes = ref('');
const reject = () => {
  router.post(`/attendance-permissions/${rejectModal.value}/reject`, { review_notes: rejectNotes.value }, {
    preserveScroll: true, onSuccess: () => { rejectModal.value = null; rejectNotes.value = ''; },
  });
};

// ─── Attachment preview ──────────────────────────────────────────────────────
const previewOpen = ref(false);
const previewSrc = ref('');

const openPreview = (p) => {
  previewSrc.value = p.file_path;
  previewOpen.value = true;
};

const previewName = computed(() => {
  const path = (previewSrc.value || '').split('?')[0];
  return decodeURIComponent(path.split('/').pop() || 'attachment');
});

const previewKind = computed(() => {
  const path = (previewSrc.value || '').split('?')[0].toLowerCase();
  if (/\.(png|jpe?g|gif|webp|bmp|svg)$/.test(path)) return 'image';
  if (/\.pdf$/.test(path)) return 'pdf';
  return 'other';
});
</script>

<template>
  <Head title="Attendance Permissions — SITS ERP" />

  <div class="space-y-6">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="CalendarCheck" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Finance · Attendance</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Attendance Permissions</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Excused-absence requests, reviewed one payroll period at a time. Approved days reduce unpaid
              absence when that period is computed — so clear a month before its payroll runs.
            </p>
          </div>
        </div>
        <button v-if="can.create" @click="openCreate" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
          + New Permission
        </button>
      </div>
    </section>

    <!-- Period tabs -->
    <div class="flex gap-2 overflow-x-auto pb-1">
      <button v-for="p in periods" :key="p.id" @click="activePeriodId = p.id; filter = 'all'"
              class="shrink-0 text-left px-4 py-3 rounded-2xl border transition-all cursor-pointer min-w-[9.5rem]"
              :class="activePeriodId === p.id
                ? 'border-blue-500/40 bg-blue-500/[0.07]'
                : 'border-slate-900 bg-slate-900/10 hover:border-slate-850'">
        <span class="flex items-center justify-between gap-2">
          <span class="text-sm font-bold" :class="activePeriodId === p.id ? 'text-white' : 'text-slate-300'">{{ p.name }}</span>
          <span v-if="p.missing_evidence" class="w-2 h-2 rounded-full bg-rose-500 shrink-0" title="Some permissions have no document"></span>
        </span>
        <span class="block text-[11px] mt-1" :class="activePeriodId === p.id ? 'text-blue-300/80' : 'text-slate-600'">
          {{ p.total }} request(s)<template v-if="p.pending"> · {{ p.pending }} pending</template>
        </span>
      </button>
      <p v-if="!periods.length" class="text-sm text-slate-500 italic py-3">No payroll periods in the active year.</p>
    </div>

    <template v-if="activePeriod">
      <!-- Period summary -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <button @click="filter = 'all'" class="text-left p-4 rounded-2xl border transition-all cursor-pointer"
                :class="filter === 'all' ? 'border-slate-700 bg-slate-900/40' : 'border-slate-900 bg-slate-900/10 hover:border-slate-850'">
          <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Requests</p>
          <p class="text-2xl font-extrabold text-slate-100 mt-1">{{ activePeriod.total }}</p>
        </button>
        <button @click="filter = 'pending'" class="text-left p-4 rounded-2xl border transition-all cursor-pointer"
                :class="filter === 'pending' ? 'border-amber-500/40 bg-amber-500/[0.07]' : 'border-slate-900 bg-slate-900/10 hover:border-slate-850'">
          <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-500/80">Awaiting approval</p>
          <p class="text-2xl font-extrabold text-amber-400 mt-1">{{ activePeriod.pending }}</p>
        </button>
        <div class="p-4 rounded-2xl border border-slate-900 bg-slate-900/10">
          <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-500/80">Approved days</p>
          <p class="text-2xl font-extrabold text-emerald-400 mt-1">{{ activePeriod.approved_days }}</p>
        </div>
        <button @click="filter = 'missing_evidence'" class="text-left p-4 rounded-2xl border transition-all cursor-pointer"
                :class="filter === 'missing_evidence' ? 'border-rose-500/40 bg-rose-500/[0.07]' : 'border-slate-900 bg-slate-900/10 hover:border-slate-850'">
          <p class="text-[11px] font-semibold uppercase tracking-wider text-rose-500/80">No document</p>
          <p class="text-2xl font-extrabold mt-1" :class="missingEvidence ? 'text-rose-400' : 'text-slate-600'">{{ missingEvidence }}</p>
        </button>
      </div>

      <!-- Missing-evidence callout -->
      <div v-if="missingEvidence" class="rounded-2xl border border-rose-500/20 bg-rose-500/[0.04] px-5 py-3.5 flex items-start gap-3">
        <Icon name="AlertTriangle" :size="17" class="text-rose-400 shrink-0 mt-0.5" />
        <p class="text-sm text-slate-300">
          <strong class="text-rose-300">{{ missingEvidence }}</strong> permission(s) in {{ activePeriod.name }} have no
          supporting document.
          <button @click="filter = 'missing_evidence'" class="text-rose-300 underline underline-offset-2 hover:text-rose-200 cursor-pointer font-semibold">Show them</button>
          — evidence can be attached at any time, including after approval.
        </p>
      </div>

      <!-- Search -->
      <div class="flex items-center gap-3 rounded-2xl border border-slate-900 bg-slate-950/40 px-4 py-3">
        <Icon name="Search" :size="16" class="text-slate-500" />
        <input v-model="search" type="text" placeholder="Search by employee, staff no. or reason…"
               class="flex-1 bg-transparent text-sm text-slate-100 placeholder-slate-600 focus:outline-none" />
        <span class="text-xs text-slate-600">{{ visible.length }} shown</span>
      </div>

      <!-- Table -->
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-sm">
            <thead>
              <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase bg-slate-950/40">
                <th class="p-3">Employee</th>
                <th class="p-3">Dates</th>
                <th class="p-3 text-center">Days</th>
                <th class="p-3">Reason</th>
                <th class="p-3">Evidence</th>
                <th class="p-3">Requested by</th>
                <th class="p-3 text-center">Status</th>
                <th class="p-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-900">
              <tr v-for="p in visible" :key="p.id" class="hover:bg-slate-900/30"
                  :class="!p.has_evidence ? 'bg-rose-500/[0.03]' : ''">
                <td class="p-3">
                  <p class="font-semibold text-slate-200">{{ p.employee }}</p>
                  <p class="text-[11px] text-slate-500 font-mono">
                    {{ p.staff_no || '—' }}<span v-if="p.is_mass" class="ml-1 text-[9px] uppercase font-bold px-1 py-0.5 rounded bg-slate-800 text-slate-500 border border-slate-700">batch</span>
                  </p>
                </td>
                <td class="p-3 text-slate-400 text-xs">{{ p.start_date || '—' }}<span v-if="p.end_date"> → {{ p.end_date }}</span></td>
                <td class="p-3 text-center font-mono text-slate-300">{{ p.days }}</td>
                <td class="p-3 text-slate-400 text-xs max-w-[16rem]">{{ p.reason || '—' }}</td>
                <td class="p-3">
                  <button v-if="p.has_evidence" type="button" @click="openPreview(p)"
                          class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-400 hover:text-blue-300 hover:underline cursor-pointer">
                    <Icon name="File" :size="12" /> View
                  </button>
                  <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] rounded-full font-bold border bg-rose-500/10 border-rose-500/25 text-rose-400">
                    <Icon name="AlertTriangle" :size="11" /> No document
                  </span>
                </td>
                <td class="p-3 text-slate-400 text-xs">
                  {{ p.created_by || '—' }}
                  <span v-if="p.approved_by" class="block text-[10px] text-slate-600">✓ {{ p.approved_by }}</span>
                </td>
                <td class="p-3 text-center">
                  <span class="px-2 py-0.5 text-[10px] rounded-full font-bold border" :class="STATUS[p.status]">{{ p.status_label }}</span>
                  <span v-if="p.status === 'rejected' && p.review_notes" class="block text-[10px] text-slate-500 italic mt-1 max-w-[10rem] truncate" :title="p.review_notes">
                    {{ p.review_notes }}
                  </span>
                </td>
                <td class="p-3 text-right whitespace-nowrap">
                  <button v-if="can.create && p.can_edit" @click="openEdit(p)"
                          class="text-[11px] font-bold px-2.5 py-1.5 border border-slate-800 hover:border-blue-700 text-blue-300 bg-slate-900/50 rounded-lg mr-1 cursor-pointer">
                    Edit
                  </button>
                  <button v-if="can.create && p.can_attach" @click="openAttach(p)"
                          class="text-[11px] font-bold px-2.5 py-1.5 border rounded-lg mr-1 cursor-pointer"
                          :class="p.has_evidence
                            ? 'border-slate-800 hover:border-slate-700 text-slate-400 bg-slate-900/50'
                            : 'border-rose-700/40 hover:border-rose-600 text-rose-300 bg-rose-600/10'">
                    {{ p.has_evidence ? 'Replace' : 'Attach' }}
                  </button>
                  <template v-if="can.approve && p.status === 'pending'">
                    <button @click="rejectModal = p.id" class="text-[11px] font-bold px-2.5 py-1.5 border border-slate-800 hover:border-rose-700 text-rose-400 bg-slate-900/50 rounded-lg mr-1 cursor-pointer">Reject</button>
                    <button @click="approve(p)" class="text-[11px] font-bold px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg cursor-pointer">Approve</button>
                  </template>
                </td>
              </tr>
              <tr v-if="!visible.length">
                <td colspan="8" class="p-10 text-center text-slate-500 italic">
                  <template v-if="filter === 'missing_evidence'">Every permission in {{ activePeriod.name }} has a document attached.</template>
                  <template v-else-if="filter === 'pending'">Nothing awaiting approval in {{ activePeriod.name }}.</template>
                  <template v-else>No permissions recorded for {{ activePeriod.name }}.</template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- Create / edit modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-white mb-6">{{ editing ? 'Edit' : 'New' }} Attendance Permission</h3>
        <form @submit.prevent="submit" class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Employee</label>
              <select v-model="form.employee_id" required :disabled="!!editing"
                      class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none disabled:opacity-60">
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.full_name_en }}</option>
              </select>
              <p v-if="editing" class="text-[10px] text-slate-600 mt-1">Reassign by rejecting and re-creating.</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Period</label>
              <select v-model="form.payroll_period_id" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
                <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">From</label>
              <input v-model="form.start_date" type="date" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-3 py-3 text-slate-100 text-sm focus:outline-none" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">To</label>
              <input v-model="form.end_date" type="date" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-3 py-3 text-slate-100 text-sm focus:outline-none" />
              <p v-if="form.errors.end_date" class="text-xs text-rose-400 mt-1">{{ form.errors.end_date }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Days</label>
              <input v-model="form.days" type="number" min="1" max="31" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-3 py-3 text-slate-100 text-sm focus:outline-none" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Reason</label>
            <input v-model="form.reason" type="text" placeholder="e.g. Approved sick leave" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
              Supporting document {{ editing && editing.has_evidence ? '(replace)' : '(optional)' }}
            </label>

            <div v-if="editing && editing.has_evidence && !form.file && !form.remove_file"
                 class="flex items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-3 mb-3">
              <span class="text-xs text-slate-300 inline-flex items-center gap-2 min-w-0">
                <Icon name="File" :size="14" class="text-blue-400 shrink-0" />
                <span class="truncate">A document is already on file.</span>
              </span>
              <button type="button" @click="form.remove_file = true" class="text-[11px] font-bold text-rose-400 hover:text-rose-300 shrink-0 cursor-pointer">Remove</button>
            </div>

            <p v-if="form.remove_file" class="text-xs text-rose-400 mb-3 flex items-center gap-2">
              The existing document will be deleted when you save.
              <button type="button" @click="form.remove_file = false" class="underline font-semibold cursor-pointer">Undo</button>
            </p>

            <div class="relative flex items-center justify-center border border-dashed border-slate-800 hover:border-blue-500/50 rounded-xl p-4 transition-colors bg-slate-950/40">
              <input type="file" @change="form.file = $event.target.files[0]" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
              <div class="text-center pointer-events-none">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 mb-2">
                  <Icon name="UploadCloud" :size="16" />
                </span>
                <p class="text-xs text-slate-400 font-medium">
                  <span class="text-blue-400">Click to upload</span> or drag and drop
                </p>
                <p class="text-[10px] text-slate-500 mt-1">PDF, DOC, DOCX, PNG, JPG up to 10MB</p>
                <p v-if="form.file" class="text-xs text-emerald-400 font-semibold mt-2">Selected: {{ form.file.name }}</p>
              </div>
            </div>
            <p class="text-[10px] text-slate-600 mt-1.5">Leave empty to submit now and attach the document later.</p>
            <p v-if="form.errors.file" class="text-xs text-rose-400 mt-1">{{ form.errors.file }}</p>
          </div>

          <p v-if="form.errors.days" class="text-xs text-rose-400">{{ form.errors.days }}</p>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="modalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl cursor-pointer">Cancel</button>
            <button type="submit" :disabled="form.processing" class="text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50">
              {{ form.processing ? 'Saving…' : (editing ? 'Save changes' : 'Submit') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Attach-later modal -->
    <div v-if="attachTarget" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-2">
          {{ attachTarget.has_evidence ? 'Replace document' : 'Attach document' }}
        </h3>
        <p class="text-sm text-slate-400 mb-5">
          Evidence for <span class="font-semibold text-slate-200">{{ attachTarget.employee }}</span> —
          {{ attachTarget.days }} day(s) in {{ attachTarget.period }}.
          <span v-if="attachTarget.status === 'approved'" class="block text-xs text-slate-500 mt-1">
            Already approved; attaching a document does not change the approval.
          </span>
        </p>

        <div class="relative flex items-center justify-center border border-dashed border-slate-800 hover:border-blue-500/50 rounded-xl p-5 transition-colors bg-slate-950/40">
          <input type="file" @change="attachForm.file = $event.target.files[0]" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
          <div class="text-center pointer-events-none">
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 mb-2">
              <Icon name="UploadCloud" :size="18" />
            </span>
            <p class="text-xs text-slate-400 font-medium"><span class="text-blue-400">Click to upload</span> or drag and drop</p>
            <p class="text-[10px] text-slate-500 mt-1">PDF, DOC, DOCX, PNG, JPG up to 10MB</p>
            <p v-if="attachForm.file" class="text-xs text-emerald-400 font-semibold mt-2">Selected: {{ attachForm.file.name }}</p>
          </div>
        </div>
        <p v-if="attachForm.errors.file" class="text-xs text-rose-400 mt-2">{{ attachForm.errors.file }}</p>

        <div class="flex items-center justify-end gap-3 pt-5">
          <button @click="attachTarget = null" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl cursor-pointer">Cancel</button>
          <button @click="submitAttach" :disabled="!attachForm.file || attachForm.processing"
                  class="text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50">
            {{ attachForm.processing ? 'Uploading…' : 'Upload' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Reject modal -->
    <div v-if="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-4">Reject Permission</h3>
        <textarea v-model="rejectNotes" rows="3" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" placeholder="Reason (optional)…"></textarea>
        <div class="flex items-center justify-end gap-3 pt-5">
          <button @click="rejectModal = null" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl cursor-pointer">Cancel</button>
          <button @click="reject" class="text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white px-5 py-2.5 rounded-xl cursor-pointer">Reject</button>
        </div>
      </div>
    </div>

    <!-- Attachment preview modal -->
    <div v-if="previewOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm" @click.self="previewOpen = false">
      <div class="w-full max-w-3xl max-h-[90vh] flex flex-col rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-slate-900">
          <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
              <Icon name="File" :size="16" />
            </span>
            <div class="min-w-0">
              <p class="text-sm font-bold text-white truncate">{{ previewName }}</p>
              <p class="text-[10px] text-slate-500 uppercase tracking-widest">Attachment Preview</p>
            </div>
          </div>
          <button type="button" @click="previewOpen = false" class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg border border-slate-800 text-slate-400 hover:text-white hover:border-slate-700 transition cursor-pointer">
            <Icon name="X" :size="16" />
          </button>
        </div>

        <div class="flex-1 min-h-0 overflow-auto bg-slate-950/60 flex items-center justify-center p-4">
          <img v-if="previewKind === 'image'" :src="previewSrc" :alt="previewName" class="max-w-full max-h-[70vh] rounded-lg object-contain" />
          <iframe v-else-if="previewKind === 'pdf'" :src="previewSrc" class="w-full h-[70vh] rounded-lg bg-white" title="PDF preview"></iframe>
          <div v-else class="text-center py-12 px-6">
            <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 text-slate-400 mb-4">
              <Icon name="FileText" :size="26" />
            </span>
            <p class="text-sm text-slate-300 font-semibold">Preview not available</p>
            <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">This file type can't be shown in the browser. Download it to view the contents.</p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-900">
          <a :href="previewSrc" target="_blank" rel="noopener" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl inline-flex items-center gap-1.5">
            <Icon name="Link2" :size="14" /> Open in new tab
          </a>
          <a :href="previewSrc" :download="previewName" class="text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl shadow-md inline-flex items-center gap-1.5">
            <Icon name="Download" :size="14" /> Download
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
