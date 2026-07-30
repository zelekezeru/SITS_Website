<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, default: () => ({}) },
  claims: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  periods: { type: Array, default: () => [] },
  settings: { type: Object, default: () => ({}) },
  summary: { type: Object, default: () => ({}) },
  can: { type: Object, default: () => ({}) },
});

const STATUS = {
  pending_review: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
  approved: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  paid: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  rejected: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
  cancelled: 'bg-slate-500/10 border-slate-500/20 text-slate-400',
};

const money = (n) => Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatBytes = (b) => {
  if (!b) return '—';
  const kb = b / 1024;
  return kb < 1024 ? `${kb.toFixed(0)} KB` : `${(kb / 1024).toFixed(1)} MB`;
};

/** Mirrors App\Services\MedicalAllowanceCalculator::split() for a live preview only — the server locks in the real split on approval. */
const previewSplit = (priorReserved, billAmount) => {
  const full = Number(props.settings.full_coverage_limit || 0);
  const max = Number(props.settings.max_coverage_limit || 0);
  const rate = Number(props.settings.coinsurance_rate || 0);
  priorReserved = Math.max(priorReserved || 0, 0);
  billAmount = Math.max(billAmount || 0, 0);

  const remainingFull = Math.max(full - priorReserved, 0);
  const inFull = Math.min(billAmount, remainingFull);
  const afterFull = billAmount - inFull;
  const coinsuranceBase = Math.max(priorReserved, full);
  const remainingCoinsurance = Math.max(max - coinsuranceBase, 0);
  const inCoinsurance = Math.min(afterFull, remainingCoinsurance);

  let covered = inFull + inCoinsurance * (rate / 100);
  covered = Math.min(covered, billAmount);
  return { covered_amount: Math.round(covered * 100) / 100, employee_amount: Math.round((billAmount - covered) * 100) / 100 };
};

// ---- Filters ---------------------------------------------------------------
const TABS = [
  { key: 'all', label: 'All' },
  { key: 'pending_review', label: 'Pending' },
  { key: 'approved', label: 'Awaiting Payment' },
  { key: 'paid', label: 'Paid' },
  { key: 'rejected', label: 'Rejected' },
  { key: 'cancelled', label: 'Cancelled' },
];
const activeTab = ref('all');
const search = ref('');

const tabCount = (key) => key === 'all' ? props.claims.length : props.claims.filter((c) => c.status === key).length;

const filteredClaims = computed(() => {
  let rows = props.claims;
  if (activeTab.value !== 'all') rows = rows.filter((c) => c.status === activeTab.value);
  if (search.value) {
    const q = search.value.toLowerCase();
    rows = rows.filter((c) =>
      (c.employee || '').toLowerCase().includes(q) ||
      (c.reference || '').toLowerCase().includes(q) ||
      (c.staff_no || '').toLowerCase().includes(q)
    );
  }
  return rows;
});

// ---- Create claim ------------------------------------------------------------
const createOpen = ref(false);
const createForm = useForm({
  employee_id: '',
  bill_amount: '',
  incident_date: '',
  notes: '',
  files: [],
});

const openCreate = () => {
  createForm.reset();
  createForm.clearErrors();
  createOpen.value = true;
};

const selectedEmployee = computed(() =>
  props.employees.find((e) => String(e.id) === String(createForm.employee_id)) || null);

const createPreview = computed(() => {
  if (!selectedEmployee.value || !createForm.bill_amount) return null;
  return previewSplit(selectedEmployee.value.reserved_this_year, Number(createForm.bill_amount));
});

const onFilesChange = (e) => {
  createForm.files = Array.from(e.target.files || []);
};
const removeFile = (idx) => {
  createForm.files = createForm.files.filter((_, i) => i !== idx);
};

const submitCreate = () => {
  createForm.post('/admin/medical-allowance', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => { createOpen.value = false; },
  });
};

// ---- Settings (dynamic coverage tiers) ---------------------------------------
const settingsOpen = ref(false);
const settingsForm = useForm({
  full_coverage_limit: props.settings.full_coverage_limit ?? 5000,
  max_coverage_limit: props.settings.max_coverage_limit ?? 10000,
  coinsurance_rate: props.settings.coinsurance_rate ?? 50,
});
const submitSettings = () => {
  settingsForm.post('/admin/medical-allowance/settings', {
    preserveScroll: true,
    onSuccess: () => { settingsOpen.value = false; },
  });
};

// ---- Detail / review / payment -----------------------------------------------
const detailId = ref(null);
const detailClaim = computed(() => props.claims.find((c) => c.id === detailId.value) || null);

const approveForm = useForm({ notes: '' });
const rejectForm = useForm({ rejection_reason: '' });
const paymentForm = useForm({ paid_on: '', payroll_period_id: '', payment_reference: '' });
const addFilesForm = useForm({ files: [] });
const showReject = ref(false);

const openDetail = (claim) => {
  detailId.value = claim.id;
  approveForm.reset(); approveForm.clearErrors();
  rejectForm.reset(); rejectForm.clearErrors();
  paymentForm.reset(); paymentForm.clearErrors();
  addFilesForm.reset(); addFilesForm.clearErrors();
  showReject.value = false;
};
const closeDetail = () => { detailId.value = null; };

const submitApprove = () => {
  approveForm.post(`/admin/medical-allowance/${detailId.value}/approve`, { preserveScroll: true });
};
const submitReject = () => {
  rejectForm.post(`/admin/medical-allowance/${detailId.value}/reject`, {
    preserveScroll: true,
    onSuccess: () => { showReject.value = false; },
  });
};
const submitPayment = () => {
  paymentForm.post(`/admin/medical-allowance/${detailId.value}/payment`, { preserveScroll: true });
};

const onAddFilesChange = (e) => {
  addFilesForm.files = Array.from(e.target.files || []);
};
const submitAddFiles = () => {
  addFilesForm.post(`/admin/medical-allowance/${detailId.value}/documents`, {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => { addFilesForm.reset(); },
  });
};

const cancelClaim = async (claim) => {
  const ok = await confirm({
    title: 'Cancel Claim',
    message: `Withdraw claim ${claim.reference} for ${claim.employee}? This cannot be undone.`,
  });
  if (ok) router.post(`/admin/medical-allowance/${claim.id}/cancel`, {}, { preserveScroll: true, onSuccess: closeDetail });
};

const deleteDocument = async (claim, doc) => {
  const ok = await confirm({
    title: 'Remove Document',
    message: `Remove "${doc.title}" from claim ${claim.reference}?`,
  });
  if (ok) router.delete(`/admin/medical-allowance/${claim.id}/documents/${doc.id}`, { preserveScroll: true });
};
</script>

<template>
  <Head title="Medical Allowance — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-rose-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 shrink-0">
            <Icon name="HeartPulse" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Finance · Payroll</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Medical Allowance</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Non-taxable medical bill reimbursement for eligible full-time staff. Bills up to
              <span class="text-slate-200 font-semibold">{{ money(settings.full_coverage_limit) }} ETB</span> a year are fully covered;
              the next band up to <span class="text-slate-200 font-semibold">{{ money(settings.max_coverage_limit) }} ETB</span> is shared
              at <span class="text-slate-200 font-semibold">{{ settings.coinsurance_rate }}%</span> institution / {{ 100 - Number(settings.coinsurance_rate || 0) }}% employee.
            </p>
          </div>
        </div>
        <div class="flex items-center gap-3 shrink-0">
          <button v-if="can.configure" @click="settingsOpen = true" class="text-sm font-semibold border border-slate-800 hover:border-slate-700 text-slate-300 px-4 py-2.5 rounded-xl bg-slate-900/50 cursor-pointer flex items-center gap-2">
            <Icon name="SlidersHorizontal" :size="16" /> Policy
          </button>
          <button v-if="can.request" @click="openCreate" class="text-sm font-semibold bg-gradient-to-r from-rose-600 to-orange-600 hover:from-rose-500 hover:to-orange-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
            + New Claim
          </button>
        </div>
      </div>
    </section>

    <!-- Summary tiles (the "payments report" at a glance) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="rounded-2xl border border-amber-500/15 bg-amber-500/[0.04] p-5">
        <p class="text-[11px] text-amber-500/80 font-semibold uppercase tracking-wider">Pending Review</p>
        <p class="text-2xl font-extrabold text-amber-300 mt-1">{{ summary.pending_count ?? 0 }}</p>
      </div>
      <div class="rounded-2xl border border-blue-500/15 bg-blue-500/[0.04] p-5">
        <p class="text-[11px] text-blue-400/80 font-semibold uppercase tracking-wider">Awaiting Payment</p>
        <p class="text-2xl font-extrabold text-blue-300 mt-1">{{ summary.approved_awaiting_payment ?? 0 }}</p>
        <p class="text-[11px] text-slate-500 mt-0.5">{{ money(summary.awaiting_payment_total) }} ETB</p>
      </div>
      <div class="rounded-2xl border border-emerald-500/15 bg-emerald-500/[0.04] p-5">
        <p class="text-[11px] text-emerald-500/80 font-semibold uppercase tracking-wider">Paid This Year</p>
        <p class="text-2xl font-extrabold text-emerald-300 mt-1">{{ money(summary.paid_this_year) }}</p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Policy Year</p>
        <p class="text-2xl font-extrabold text-white mt-1">{{ settings.policy_year }}</p>
      </div>
    </div>

    <!-- Tabs + search -->
    <div class="space-y-4">
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-1 flex-wrap">
          <button v-for="t in TABS" :key="t.key" @click="activeTab = t.key"
                  class="px-3.5 py-2 text-xs font-semibold rounded-lg border transition-colors"
                  :class="activeTab === t.key ? 'bg-slate-800 border-slate-700 text-white' : 'border-transparent text-slate-500 hover:text-slate-300'">
            {{ t.label }} <span class="text-slate-500">({{ tabCount(t.key) }})</span>
          </button>
        </div>
        <div class="relative w-full max-w-xs">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"><Icon name="Search" :size="16" /></span>
          <input v-model="search" type="text" placeholder="Search by name, ref, staff no…" class="w-full bg-slate-900/40 border border-slate-900 rounded-xl pl-10 pr-4 py-2.5 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-rose-500" />
        </div>
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2 overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm min-w-[920px]">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="p-3">Reference</th>
              <th class="p-3">Employee</th>
              <th class="p-3 text-right">Bill</th>
              <th class="p-3 text-right">Covered</th>
              <th class="p-3 text-right">Employee Share</th>
              <th class="p-3">Docs</th>
              <th class="p-3 text-center">Status</th>
              <th class="p-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-900">
            <tr v-for="claim in filteredClaims" :key="claim.id" class="hover:bg-slate-900/30 transition-colors cursor-pointer" @click="openDetail(claim)">
              <td class="p-3 font-mono text-xs font-semibold text-slate-300">{{ claim.reference }}</td>
              <td class="p-3">
                <p class="font-semibold text-slate-200">{{ claim.employee || '—' }}</p>
                <p class="text-[10px] text-slate-500">{{ claim.staff_no }}</p>
              </td>
              <td class="p-3 text-right font-mono text-slate-300">{{ money(claim.bill_amount) }}</td>
              <td class="p-3 text-right font-mono text-emerald-400">{{ claim.covered_amount !== null ? money(claim.covered_amount) : '—' }}</td>
              <td class="p-3 text-right font-mono text-slate-400">{{ claim.employee_amount !== null ? money(claim.employee_amount) : '—' }}</td>
              <td class="p-3 text-slate-400 text-xs">{{ claim.documents.length }} file(s)</td>
              <td class="p-3 text-center">
                <span class="px-2 py-0.5 text-[10px] rounded-full font-bold border" :class="STATUS[claim.status]">{{ claim.status_label }}</span>
              </td>
              <td class="p-3 text-right whitespace-nowrap" @click.stop>
                <button @click="openDetail(claim)" class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-rose-500/50 text-rose-400 hover:text-rose-300 bg-slate-900/50 rounded-lg">Review</button>
              </td>
            </tr>
            <tr v-if="!filteredClaims.length">
              <td colspan="8" class="p-8 text-center text-slate-500 italic">No claims found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create modal -->
    <div v-if="createOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm" @click.self="createOpen = false">
      <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-6">Submit Medical Claim</h3>
        <form @submit.prevent="submitCreate" class="space-y-5">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Employee</label>
            <select v-model="createForm.employee_id" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-rose-500/50">
              <option value="" disabled>Select an eligible employee…</option>
              <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.full_name_en }} <template v-if="e.staff_no">({{ e.staff_no }})</template></option>
            </select>
            <p v-if="employees.length === 0" class="text-[11px] text-amber-400 mt-1">No employees are enrolled yet — enable "Medical Allowance" for full-time staff on their payroll profile.</p>
            <p v-if="selectedEmployee" class="text-[11px] text-slate-500 mt-1">Used so far this year: {{ money(selectedEmployee.reserved_this_year) }} ETB</p>
            <p v-if="createForm.errors.employee_id" class="text-xs text-rose-400 mt-1">{{ createForm.errors.employee_id }}</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Bill Amount (ETB)</label>
            <input v-model="createForm.bill_amount" type="number" min="1" step="0.01" required placeholder="e.g. 8000" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-rose-500/50" />
            <p v-if="createForm.errors.bill_amount" class="text-xs text-rose-400 mt-1">{{ createForm.errors.bill_amount }}</p>
          </div>
          <div v-if="createPreview" class="rounded-xl border border-emerald-500/15 bg-emerald-500/[0.05] px-4 py-3 text-xs space-y-1">
            <p class="text-emerald-400 font-semibold flex justify-between"><span>Institution covers</span><span>{{ money(createPreview.covered_amount) }} ETB</span></p>
            <p class="text-slate-400 flex justify-between"><span>Employee share</span><span>{{ money(createPreview.employee_amount) }} ETB</span></p>
            <p class="text-slate-600 text-[10px] pt-1">Estimate only — the final split is locked in when the admin approves.</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Incident / Treatment Date (optional)</label>
            <input v-model="createForm.incident_date" type="date" :max="new Date().toISOString().slice(0,10)" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-rose-500/50" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Bill Documents</label>
            <input type="file" multiple accept=".pdf,.jpg,.jpeg,.png" @change="onFilesChange" class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-rose-600 file:text-white file:text-xs file:font-semibold file:cursor-pointer" />
            <ul v-if="createForm.files.length" class="mt-2 space-y-1">
              <li v-for="(f, i) in createForm.files" :key="i" class="flex items-center justify-between text-[11px] text-slate-400 bg-slate-950/50 rounded-lg px-3 py-1.5">
                <span class="truncate">{{ f.name }} <span class="text-slate-600">({{ formatBytes(f.size) }})</span></span>
                <button type="button" @click="removeFile(i)" class="text-rose-400 hover:text-rose-300 shrink-0 ml-2"><Icon name="X" :size="14" /></button>
              </li>
            </ul>
            <p v-if="createForm.errors.files" class="text-xs text-rose-400 mt-1">{{ createForm.errors.files }}</p>
            <p class="text-[10px] text-slate-600 mt-1">PDF, JPG or PNG · up to 10 files, 10MB each.</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Notes (optional)</label>
            <textarea v-model="createForm.notes" rows="2" placeholder="Context for the reviewer…" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-rose-500/50"></textarea>
          </div>
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="createOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl">Cancel</button>
            <button type="submit" :disabled="createForm.processing" class="text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white px-5 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50">Submit Claim</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Policy settings modal -->
    <div v-if="settingsOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm" @click.self="settingsOpen = false">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-2">Coverage Policy</h3>
        <p class="text-xs text-slate-500 mb-6">Applies to every claim approved from now on. Existing approved/paid claims keep the split they were locked in with.</p>
        <form @submit.prevent="submitSettings" class="space-y-5">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Full-coverage limit (ETB / year)</label>
            <input v-model="settingsForm.full_coverage_limit" type="number" min="0" step="0.01" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-rose-500/50" />
            <p v-if="settingsForm.errors.full_coverage_limit" class="text-xs text-rose-400 mt-1">{{ settingsForm.errors.full_coverage_limit }}</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Max coverage limit (ETB / year)</label>
            <input v-model="settingsForm.max_coverage_limit" type="number" min="0" step="0.01" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-rose-500/50" />
            <p class="text-[10px] text-slate-600 mt-1">Nothing is covered above this amount.</p>
            <p v-if="settingsForm.errors.max_coverage_limit" class="text-xs text-rose-400 mt-1">{{ settingsForm.errors.max_coverage_limit }}</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Coinsurance rate (%)</label>
            <input v-model="settingsForm.coinsurance_rate" type="number" min="0" max="100" step="0.1" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-rose-500/50" />
            <p class="text-[10px] text-slate-600 mt-1">Institution's share of the band between the two limits above.</p>
            <p v-if="settingsForm.errors.coinsurance_rate" class="text-xs text-rose-400 mt-1">{{ settingsForm.errors.coinsurance_rate }}</p>
          </div>
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="settingsOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl">Cancel</button>
            <button type="submit" :disabled="settingsForm.processing" class="text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white px-5 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50">Save Policy</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Detail modal -->
    <div v-if="detailClaim" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm" @click.self="closeDetail">
      <div class="w-full max-w-2xl max-h-[90vh] flex flex-col rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-slate-900">
          <div class="min-w-0">
            <div class="flex items-center gap-2.5">
              <span class="font-mono text-xs font-semibold text-slate-400">{{ detailClaim.reference }}</span>
              <span class="px-2 py-0.5 text-[10px] rounded-full font-bold border" :class="STATUS[detailClaim.status]">{{ detailClaim.status_label }}</span>
            </div>
            <p class="text-lg font-bold text-white truncate mt-0.5">{{ detailClaim.employee }}</p>
          </div>
          <button @click="closeDetail" class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg border border-slate-800 text-slate-400 hover:text-white hover:border-slate-700"><Icon name="X" :size="16" /></button>
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-6">
          <!-- amounts -->
          <div class="grid grid-cols-3 gap-3">
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-3">
              <p class="text-[10px] text-slate-500 uppercase tracking-wider">Bill</p>
              <p class="text-sm font-bold text-slate-200 mt-0.5">{{ money(detailClaim.bill_amount) }}</p>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-3">
              <p class="text-[10px] text-slate-500 uppercase tracking-wider">Covered</p>
              <p class="text-sm font-bold text-emerald-400 mt-0.5">{{ detailClaim.covered_amount !== null ? money(detailClaim.covered_amount) : '—' }}</p>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-3">
              <p class="text-[10px] text-slate-500 uppercase tracking-wider">Employee Share</p>
              <p class="text-sm font-bold text-slate-300 mt-0.5">{{ detailClaim.employee_amount !== null ? money(detailClaim.employee_amount) : '—' }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 text-xs">
            <p class="text-slate-500">Policy year <span class="text-slate-300 font-semibold">{{ detailClaim.policy_year }}</span></p>
            <p class="text-slate-500">Incident date <span class="text-slate-300 font-semibold">{{ detailClaim.incident_date || '—' }}</span></p>
            <p class="text-slate-500">Submitted by <span class="text-slate-300 font-semibold">{{ detailClaim.created_by || '—' }}</span></p>
            <p class="text-slate-500">On <span class="text-slate-300 font-semibold">{{ detailClaim.created_at }}</span></p>
          </div>
          <p v-if="detailClaim.notes" class="text-xs text-slate-400 bg-slate-950/40 border border-slate-800 rounded-xl px-4 py-3">{{ detailClaim.notes }}</p>
          <p v-if="detailClaim.rejection_reason" class="text-xs text-rose-400 bg-rose-500/5 border border-rose-500/20 rounded-xl px-4 py-3">
            <strong>Rejected:</strong> {{ detailClaim.rejection_reason }}
          </p>

          <!-- documents -->
          <div>
            <p class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Bill Documents</p>
            <div class="space-y-1.5">
              <div v-for="doc in detailClaim.documents" :key="doc.id" class="flex items-center justify-between gap-2 rounded-xl border border-slate-800 bg-slate-950/40 px-3 py-2 text-xs">
                <a :href="doc.url" target="_blank" class="flex items-center gap-2 text-slate-300 hover:text-rose-300 truncate">
                  <Icon name="File" :size="14" class="shrink-0" />
                  <span class="truncate">{{ doc.title }}</span>
                  <span class="text-slate-600 shrink-0">({{ formatBytes(doc.size) }})</span>
                </a>
                <button v-if="can.request && detailClaim.is_pending" @click="deleteDocument(detailClaim, doc)" class="text-rose-400 hover:text-rose-300 shrink-0"><Icon name="Trash2" :size="14" /></button>
              </div>
              <p v-if="!detailClaim.documents.length" class="text-slate-500 italic text-xs">No documents attached.</p>
            </div>

            <form v-if="can.request && detailClaim.accepts_documents" @submit.prevent="submitAddFiles" class="mt-3 flex items-center gap-2">
              <input type="file" multiple accept=".pdf,.jpg,.jpeg,.png" @change="onAddFilesChange" class="flex-1 text-[11px] text-slate-400 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:bg-slate-800 file:text-slate-200 file:text-[11px] file:cursor-pointer" />
              <button type="submit" :disabled="!addFilesForm.files.length || addFilesForm.processing" class="text-[11px] font-semibold px-3 py-2 border border-slate-800 hover:border-rose-500/50 text-rose-400 rounded-lg disabled:opacity-40 shrink-0">Attach</button>
            </form>
          </div>

          <!-- review actions -->
          <div v-if="can.approve && detailClaim.status === 'pending_review'" class="rounded-2xl border border-slate-800 bg-slate-950/40 p-4 space-y-3">
            <p class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Review</p>
            <div v-if="!showReject" class="space-y-3">
              <textarea v-model="approveForm.notes" rows="2" placeholder="Approval note (optional)…" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-100 text-sm focus:outline-none focus:border-emerald-500/50"></textarea>
              <div class="flex items-center gap-3">
                <button @click="submitApprove" :disabled="approveForm.processing" class="flex-1 text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50">Approve Claim</button>
                <button @click="showReject = true" class="text-xs font-semibold px-4 py-2.5 border border-rose-500/30 text-rose-400 hover:bg-rose-500/10 rounded-xl">Reject</button>
              </div>
            </div>
            <div v-else class="space-y-3">
              <textarea v-model="rejectForm.rejection_reason" rows="2" required placeholder="Reason for rejection (required)…" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-100 text-sm focus:outline-none focus:border-rose-500/50"></textarea>
              <p v-if="rejectForm.errors.rejection_reason" class="text-xs text-rose-400">{{ rejectForm.errors.rejection_reason }}</p>
              <div class="flex items-center gap-3">
                <button @click="submitReject" :disabled="rejectForm.processing" class="flex-1 text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white px-4 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50">Confirm Rejection</button>
                <button @click="showReject = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-800 hover:border-slate-700 rounded-xl">Back</button>
              </div>
            </div>
          </div>

          <!-- record payment -->
          <div v-if="can.request && detailClaim.status === 'approved'" class="rounded-2xl border border-blue-500/20 bg-blue-500/[0.04] p-4 space-y-3">
            <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Record Disbursement</p>
            <p class="text-[11px] text-slate-500">Reimbursement is paid outside payroll — record it here once disbursed so it's reflected on the payslip for the period you choose.</p>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Paid On</label>
                <input v-model="paymentForm.paid_on" type="date" required :max="new Date().toISOString().slice(0,10)" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-100 text-sm focus:outline-none focus:border-blue-500/50" />
              </div>
              <div>
                <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Payslip Period</label>
                <select v-model="paymentForm.payroll_period_id" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-100 text-sm focus:outline-none focus:border-blue-500/50">
                  <option value="">— none —</option>
                  <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
              </div>
            </div>
            <input v-model="paymentForm.payment_reference" type="text" maxlength="255" placeholder="Reference (e.g. bank transfer no.)…" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-100 text-sm focus:outline-none focus:border-blue-500/50" />
            <p v-if="paymentForm.errors.paid_on" class="text-xs text-rose-400">{{ paymentForm.errors.paid_on }}</p>
            <button @click="submitPayment" :disabled="paymentForm.processing" class="w-full text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white px-4 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50">Record Payment</button>
          </div>

          <div v-if="detailClaim.status === 'paid'" class="rounded-2xl border border-emerald-500/20 bg-emerald-500/[0.06] p-4 space-y-1">
            <p class="text-sm font-semibold text-emerald-400 flex items-center gap-2"><Icon name="CheckCircle2" :size="18" /> Paid on {{ detailClaim.paid_on }}</p>
            <p class="text-[11px] text-slate-500">By {{ detailClaim.paid_by }} <template v-if="detailClaim.payroll_period">· reported on {{ detailClaim.payroll_period }}'s payslip</template></p>
            <p v-if="detailClaim.payment_reference" class="text-[11px] text-slate-500">Ref: {{ detailClaim.payment_reference }}</p>
          </div>
        </div>

        <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-slate-900">
          <button v-if="can.request && detailClaim.is_pending" @click="cancelClaim(detailClaim)" class="text-xs font-semibold px-4 py-2.5 border border-rose-500/30 text-rose-400 hover:bg-rose-500/10 rounded-xl">Withdraw Claim</button>
          <span v-else></span>
          <button @click="closeDetail" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>
