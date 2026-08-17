<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  module: { type: Object, default: () => ({}) },
  requests: { type: Array, default: () => [] },
  items: { type: Array, default: () => [] },
  availableUnits: { type: Array, default: () => [] },
  locations: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
  currentEmployee: { type: Object, default: null },
  can: { type: Object, default: () => ({}) },
  statuses: { type: Array, default: () => [] },
});

const today = () => new Date().toISOString().slice(0, 10);

const search = ref('');
const activeTab = ref('all'); // all, awaiting, issuable, fulfilled, my

const filteredRequests = computed(() => {
  let list = props.requests;

  if (activeTab.value === 'awaiting') {
    list = list.filter((r) => r.status === 'submitted');
  } else if (activeTab.value === 'issuable') {
    list = list.filter((r) => r.is_issuable);
  } else if (activeTab.value === 'fulfilled') {
    list = list.filter((r) => r.status === 'fulfilled');
  } else if (activeTab.value === 'my') {
    list = list.filter((r) => props.currentEmployee && r.requester_employee_id === props.currentEmployee.id);
  }

  if (!search.value.trim()) return list;
  const q = search.value.toLowerCase();
  return list.filter((r) =>
    r.request_number.toLowerCase().includes(q) ||
    (r.requester_name && r.requester_name.toLowerCase().includes(q)) ||
    (r.purpose && r.purpose.toLowerCase().includes(q)) ||
    (r.department_name && r.department_name.toLowerCase().includes(q)) ||
    r.lines.some((l) => l.item_name && l.item_name.toLowerCase().includes(q))
  );
});

const counts = computed(() => ({
  all: props.requests.length,
  awaiting: props.requests.filter((r) => r.status === 'submitted').length,
  issuable: props.requests.filter((r) => r.is_issuable).length,
  fulfilled: props.requests.filter((r) => r.status === 'fulfilled').length,
  my: props.currentEmployee ? props.requests.filter((r) => r.requester_employee_id === props.currentEmployee.id).length : 0,
}));

// ---- Create Requisition Modal ----
const createOpen = ref(false);
const createForm = useForm({
  purpose: '',
  needed_by: '',
  notes: '',
  department_id: '',
  submit_now: true,
  lines: [
    { item_id: '', quantity_requested: 1, unit_id: '', note: '' }
  ],
});

const addLine = () => {
  createForm.lines.push({ item_id: '', quantity_requested: 1, unit_id: '', note: '' });
};

const removeLine = (idx) => {
  if (createForm.lines.length > 1) {
    createForm.lines.splice(idx, 1);
  }
};

const openCreateModal = () => {
  createForm.reset();
  createForm.clearErrors();
  createForm.lines = [{ item_id: '', quantity_requested: 1, unit_id: '', note: '' }];
  createForm.submit_now = true;
  if (props.currentEmployee?.department_id) {
    createForm.department_id = props.currentEmployee.department_id;
  }
  createOpen.value = true;
};

const submitCreate = () => {
  createForm.post('/store/requests', {
    preserveScroll: true,
    onSuccess: () => { createOpen.value = false; },
  });
};

// ---- View / Approve / Reject Modal ----
const viewOpen = ref(false);
const selectedRequest = ref(null);
const approveForm = useForm({
  lines: [],
});
const rejectForm = useForm({
  rejection_reason: '',
});
const showRejectInput = ref(false);

const openViewModal = (req) => {
  selectedRequest.value = req;
  showRejectInput.value = false;
  rejectForm.reset();
  rejectForm.clearErrors();

  approveForm.reset();
  approveForm.clearErrors();
  approveForm.lines = req.lines.map((l) => ({
    id: l.id,
    quantity_approved: l.quantity_approved !== null ? l.quantity_approved : l.quantity_requested,
  }));

  viewOpen.value = true;
};

const submitApprove = () => {
  if (!selectedRequest.value) return;
  approveForm.post(`/store/requests/${selectedRequest.value.id}/approve`, {
    preserveScroll: true,
    onSuccess: () => { viewOpen.value = false; },
  });
};

const submitReject = () => {
  if (!selectedRequest.value) return;
  rejectForm.post(`/store/requests/${selectedRequest.value.id}/reject`, {
    preserveScroll: true,
    onSuccess: () => { viewOpen.value = false; },
  });
};

const submitCancel = (req) => {
  if (confirm(`Cancel requisition ${req.request_number}?`)) {
    router.post(`/store/requests/${req.id}/cancel`, {}, { preserveScroll: true });
  }
};

const submitDirectSubmit = (req) => {
  router.post(`/store/requests/${req.id}/submit`, {}, { preserveScroll: true });
};

// ---- Issue Modal ----
const issueOpen = ref(false);
const issueRequest = ref(null);
const issueForm = useForm({
  reference: '',
  notes: '',
  issues: [],
});

const openIssueModal = (req) => {
  issueRequest.value = req;
  issueForm.reset();
  issueForm.clearErrors();
  issueForm.issues = req.lines
    .filter((l) => l.outstanding > 0)
    .map((l) => ({
      line_id: l.id,
      item_id: l.item_id,
      item_name: l.item_name,
      tracking_mode: l.tracking_mode,
      unit_of_measure: l.unit_of_measure,
      outstanding: l.outstanding,
      quantity: l.tracking_mode === 'asset' ? 1 : l.outstanding,
      from_location_id: '',
      unit_id: l.unit_id || '',
    }));
  issueOpen.value = true;
};

const submitIssue = () => {
  if (!issueRequest.value) return;
  issueForm.post(`/store/requests/${issueRequest.value.id}/issue`, {
    preserveScroll: true,
    onSuccess: () => { issueOpen.value = false; },
  });
};

const getUnitsForItem = (itemId) => {
  return props.availableUnits.filter((u) => u.item_id === Number(itemId));
};
</script>

<template>
  <Head title="Requisitions — SITS Store" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="ClipboardList" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Store · Stock</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Material Requisitions</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Staff material requests through maker-checker approval. Custodians cannot authorize their own releases;
              Department Heads or Operations approve before Store Keepers issue.
            </p>
          </div>
        </div>
        <button v-if="can.request" @click="openCreateModal" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer flex items-center gap-2">
          <Icon name="Plus" :size="16" /> New Requisition
        </button>
      </div>
    </section>

    <!-- Stat Cards & Tabs -->
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex flex-wrap items-center gap-2 p-1.5 rounded-2xl border border-slate-900 bg-slate-950/40">
        <button
          @click="activeTab = 'all'"
          :class="['px-4 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer flex items-center gap-2', activeTab === 'all' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200']"
        >
          All Requests <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-900 text-slate-300">{{ counts.all }}</span>
        </button>
        <button
          @click="activeTab = 'awaiting'"
          :class="['px-4 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer flex items-center gap-2', activeTab === 'awaiting' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'text-slate-400 hover:text-slate-200']"
        >
          Awaiting Approval <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-500/30 text-amber-200">{{ counts.awaiting }}</span>
        </button>
        <button
          @click="activeTab = 'issuable'"
          :class="['px-4 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer flex items-center gap-2', activeTab === 'issuable' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'text-slate-400 hover:text-slate-200']"
        >
          Ready to Issue <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/30 text-emerald-200">{{ counts.issuable }}</span>
        </button>
        <button
          @click="activeTab = 'fulfilled'"
          :class="['px-4 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer flex items-center gap-2', activeTab === 'fulfilled' ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30' : 'text-slate-400 hover:text-slate-200']"
        >
          Fulfilled <span class="px-2 py-0.5 rounded-full text-[10px] bg-blue-500/30 text-blue-200">{{ counts.fulfilled }}</span>
        </button>
        <button
          v-if="currentEmployee"
          @click="activeTab = 'my'"
          :class="['px-4 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer flex items-center gap-2', activeTab === 'my' ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200']"
        >
          My Requests <span class="px-2 py-0.5 rounded-full text-[10px] bg-indigo-500/30 text-indigo-200">{{ counts.my }}</span>
        </button>
      </div>

      <div class="relative w-72">
        <Icon name="Search" :size="15" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500" />
        <input
          v-model="search"
          type="text"
          placeholder="Search by REQ#, requester, purpose..."
          class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-900 bg-slate-950/60 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-blue-500/50"
        />
      </div>
    </div>

    <!-- Requests Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2 overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm min-w-[1020px]">
        <thead>
          <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
            <th class="p-3">REQ Number</th>
            <th class="p-3">Requester & Dept</th>
            <th class="p-3">Purpose</th>
            <th class="p-3">Items Requested</th>
            <th class="p-3">Needed By</th>
            <th class="p-3">Status</th>
            <th class="p-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-900">
          <tr v-for="r in filteredRequests" :key="r.id" class="hover:bg-slate-900/30 transition-colors">
            <td class="p-3 font-mono text-xs text-blue-400/90 font-semibold">{{ r.request_number }}</td>
            <td class="p-3">
              <p class="font-semibold text-slate-200">{{ r.requester_name || 'Staff' }}</p>
              <p class="text-xs text-slate-500">{{ r.department_name || '—' }}</p>
            </td>
            <td class="p-3 text-xs text-slate-300 max-w-xs truncate">{{ r.purpose }}</td>
            <td class="p-3 text-xs">
              <div class="space-y-1">
                <div v-for="l in r.lines.slice(0, 2)" :key="l.id" class="text-slate-300">
                  <span class="font-semibold text-slate-200">{{ l.item_name }}</span>:
                  {{ l.quantity_requested }} {{ l.unit_of_measure }}
                  <span v-if="l.quantity_approved !== null" class="text-emerald-400 text-[11px]">
                    (appr: {{ l.quantity_approved }} / iss: {{ l.quantity_issued }})
                  </span>
                </div>
                <p v-if="r.lines.length > 2" class="text-[11px] text-slate-500">+{{ r.lines.length - 2 }} more lines</p>
              </div>
            </td>
            <td class="p-3 text-xs font-mono text-slate-400">{{ r.needed_by || '—' }}</td>
            <td class="p-3">
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                r.status === 'approved' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' :
                r.status === 'submitted' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' :
                r.status === 'partially_fulfilled' ? 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20' :
                r.status === 'fulfilled' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' :
                r.status === 'rejected' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' :
                'bg-slate-800 text-slate-400'
              ]">
                {{ r.status_label }}
              </span>
            </td>
            <td class="p-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  @click="openViewModal(r)"
                  class="px-3 py-1.5 text-xs font-medium rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 transition-colors cursor-pointer"
                >
                  Details
                </button>

                <button
                  v-if="r.status === 'draft' && can.request"
                  @click="submitDirectSubmit(r)"
                  class="px-3 py-1.5 text-xs font-medium rounded-lg bg-blue-600/80 hover:bg-blue-600 text-white transition-colors cursor-pointer"
                >
                  Submit
                </button>

                <button
                  v-if="can.issue && r.is_issuable"
                  @click="openIssueModal(r)"
                  class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600/90 hover:bg-emerald-600 text-white transition-colors cursor-pointer flex items-center gap-1"
                >
                  <Icon name="PackageMinus" :size="13" /> Issue
                </button>

                <button
                  v-if="r.is_editable && can.request"
                  @click="submitCancel(r)"
                  class="px-2.5 py-1.5 text-xs font-medium rounded-lg text-rose-400 hover:bg-rose-500/10 transition-colors cursor-pointer"
                  title="Cancel requisition"
                >
                  <Icon name="X" :size="14" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredRequests.length === 0">
            <td colspan="7" class="p-8 text-center text-slate-500 text-sm">
              No material requisitions found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create Requisition Modal -->
    <div v-if="createOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
      <div class="relative w-full max-w-3xl rounded-3xl border border-slate-800 bg-slate-950 p-6 md:p-8 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-900 pb-4">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <Icon name="ClipboardList" :size="20" class="text-blue-400" /> New Material Requisition
            </h3>
            <p class="text-xs text-slate-400 mt-1">Submit a request for consumables or serialized equipment.</p>
          </div>
          <button @click="createOpen = false" class="text-slate-400 hover:text-white p-1 cursor-pointer">
            <Icon name="X" :size="20" />
          </button>
        </div>

        <form @submit.prevent="submitCreate" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Department</label>
              <select v-model="createForm.department_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-blue-500">
                <option value="">Select Department</option>
                <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Needed By Date</label>
              <input v-model="createForm.needed_by" type="date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-blue-500" />
            </div>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-300">Purpose / Justification *</label>
            <textarea v-model="createForm.purpose" required rows="2" placeholder="e.g. Office stationery for Q3 academic review meeting" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-blue-500"></textarea>
            <p v-if="createForm.errors.purpose" class="text-rose-400 text-xs">{{ createForm.errors.purpose }}</p>
          </div>

          <!-- Items Line Builder -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Requisition Items *</label>
              <button type="button" @click="addLine" class="text-xs font-semibold text-blue-400 hover:text-blue-300 cursor-pointer flex items-center gap-1">
                <Icon name="Plus" :size="14" /> Add Item
              </button>
            </div>

            <div v-for="(line, idx) in createForm.lines" :key="idx" class="p-4 rounded-2xl border border-slate-900 bg-slate-900/40 space-y-3">
              <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div class="md:col-span-6 space-y-1">
                  <label class="text-[11px] font-semibold text-slate-400">Item</label>
                  <select v-model="line.item_id" required class="w-full px-3 py-2 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-blue-500">
                    <option value="">Select Catalog Item</option>
                    <option v-for="it in items" :key="it.id" :value="it.id">
                      {{ it.code }} — {{ it.name_en }} (Stock: {{ it.on_hand }} {{ it.unit_of_measure }})
                    </option>
                  </select>
                </div>

                <div class="md:col-span-3 space-y-1">
                  <label class="text-[11px] font-semibold text-slate-400">Quantity</label>
                  <input v-model="line.quantity_requested" type="number" step="any" min="0.001" required class="w-full px-3 py-2 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-blue-500" />
                </div>

                <div class="md:col-span-2 space-y-1">
                  <label class="text-[11px] font-semibold text-slate-400">Note</label>
                  <input v-model="line.note" type="text" placeholder="e.g. Urgent" class="w-full px-3 py-2 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-blue-500" />
                </div>

                <div class="md:col-span-1 flex justify-end">
                  <button type="button" @click="removeLine(idx)" :disabled="createForm.lines.length === 1" class="p-2 text-slate-500 hover:text-rose-400 disabled:opacity-30 cursor-pointer">
                    <Icon name="Trash2" :size="16" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2 pt-2">
            <input id="submit_now" v-model="createForm.submit_now" type="checkbox" class="rounded border-slate-800 bg-slate-900 text-blue-600 focus:ring-0" />
            <label for="submit_now" class="text-xs text-slate-300">Submit immediately for approval (otherwise save as Draft)</label>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="createOpen = false" class="px-5 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:bg-slate-900 transition-all cursor-pointer">
              Cancel
            </button>
            <button type="submit" :disabled="createForm.processing" class="px-6 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white transition-all shadow-md cursor-pointer disabled:opacity-50">
              {{ createForm.submit_now ? 'Submit Requisition' : 'Save Draft' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- View & Approval Modal -->
    <div v-if="viewOpen && selectedRequest" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
      <div class="relative w-full max-w-3xl rounded-3xl border border-slate-800 bg-slate-950 p-6 md:p-8 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-900 pb-4">
          <div>
            <div class="flex items-center gap-3">
              <h3 class="text-lg font-bold text-white font-mono">{{ selectedRequest.request_number }}</h3>
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                selectedRequest.status === 'approved' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' :
                selectedRequest.status === 'submitted' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' :
                selectedRequest.status === 'fulfilled' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' :
                selectedRequest.status === 'rejected' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' :
                'bg-slate-800 text-slate-400'
              ]">
                {{ selectedRequest.status_label }}
              </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Requested by {{ selectedRequest.requester_name }} ({{ selectedRequest.department_name }})</p>
          </div>
          <button @click="viewOpen = false" class="text-slate-400 hover:text-white p-1 cursor-pointer">
            <Icon name="X" :size="20" />
          </button>
        </div>

        <div class="space-y-4 text-xs">
          <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-900 space-y-2">
            <p><strong class="text-slate-400">Purpose:</strong> <span class="text-slate-200">{{ selectedRequest.purpose }}</span></p>
            <p v-if="selectedRequest.needed_by"><strong class="text-slate-400">Needed by:</strong> <span class="text-slate-300 font-mono">{{ selectedRequest.needed_by }}</span></p>
            <p v-if="selectedRequest.approved_by_name"><strong class="text-slate-400">Approved by:</strong> <span class="text-emerald-400">{{ selectedRequest.approved_by_name }} ({{ selectedRequest.approved_at }})</span></p>
            <p v-if="selectedRequest.rejection_reason"><strong class="text-rose-400">Rejection reason:</strong> <span class="text-rose-300">{{ selectedRequest.rejection_reason }}</span></p>
          </div>

          <!-- Lines Table -->
          <div class="border border-slate-900 rounded-2xl overflow-hidden">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-900/60 text-slate-400 font-semibold border-b border-slate-900 uppercase text-[10px]">
                <tr>
                  <th class="p-3">Item</th>
                  <th class="p-3 text-right">Requested</th>
                  <th class="p-3 text-right">Approved</th>
                  <th class="p-3 text-right">Issued</th>
                  <th class="p-3 text-right">Outstanding</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-900">
                <tr v-for="(line, idx) in selectedRequest.lines" :key="line.id">
                  <td class="p-3">
                    <p class="font-semibold text-slate-200">{{ line.item_name }}</p>
                    <p class="font-mono text-[11px] text-slate-500">{{ line.item_code }}</p>
                  </td>
                  <td class="p-3 text-right font-mono text-slate-300">{{ line.quantity_requested }} {{ line.unit_of_measure }}</td>
                  <td class="p-3 text-right font-mono">
                    <span v-if="selectedRequest.status !== 'submitted' || !can.approve" class="text-slate-200">
                      {{ line.quantity_approved !== null ? line.quantity_approved : '—' }}
                    </span>
                    <input
                      v-else
                      v-model="approveForm.lines[idx].quantity_approved"
                      type="number"
                      step="any"
                      :max="line.quantity_requested"
                      min="0"
                      class="w-20 px-2 py-1 rounded bg-slate-900 border border-slate-700 text-right text-xs text-white"
                    />
                  </td>
                  <td class="p-3 text-right font-mono text-emerald-400">{{ line.quantity_issued }}</td>
                  <td class="p-3 text-right font-mono font-semibold text-amber-400">{{ line.outstanding }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Reject Form -->
          <div v-if="showRejectInput" class="p-4 rounded-2xl border border-rose-500/30 bg-rose-500/5 space-y-3">
            <label class="text-xs font-semibold text-rose-400">Reason for Rejection *</label>
            <textarea v-model="rejectForm.rejection_reason" required rows="2" placeholder="State why this requisition cannot be approved..." class="w-full px-3 py-2 rounded-xl border border-rose-500/30 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-rose-500"></textarea>
            <div class="flex justify-end gap-2">
              <button type="button" @click="showRejectInput = false" class="px-3 py-1.5 rounded-lg text-slate-400 hover:text-white">Cancel</button>
              <button type="button" @click="submitReject" :disabled="rejectForm.processing" class="px-4 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-semibold shadow">Confirm Rejection</button>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="flex justify-between items-center pt-4 border-t border-slate-900">
          <div>
            <button
              v-if="can.issue && selectedRequest.is_issuable"
              @click="viewOpen = false; openIssueModal(selectedRequest)"
              class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition-all shadow-md cursor-pointer flex items-center gap-1.5"
            >
              <Icon name="PackageMinus" :size="15" /> Proceed to Issue
            </button>
          </div>

          <div class="flex items-center gap-3">
            <button type="button" @click="viewOpen = false" class="px-5 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:bg-slate-900 transition-all cursor-pointer">
              Close
            </button>

            <template v-if="selectedRequest.status === 'submitted' && can.approve">
              <button
                v-if="!showRejectInput"
                type="button"
                @click="showRejectInput = true"
                class="px-4 py-2 rounded-xl text-xs font-semibold text-rose-400 hover:bg-rose-500/10 border border-rose-500/20 transition-all cursor-pointer"
              >
                Reject Request
              </button>
              <button
                v-if="!showRejectInput"
                type="button"
                @click="submitApprove"
                :disabled="approveForm.processing"
                class="px-6 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition-all shadow-md cursor-pointer disabled:opacity-50"
              >
                Approve Requisition
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Issue Stock Modal -->
    <div v-if="issueOpen && issueRequest" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
      <div class="relative w-full max-w-3xl rounded-3xl border border-slate-800 bg-slate-950 p-6 md:p-8 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-900 pb-4">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <Icon name="PackageMinus" :size="20" class="text-emerald-400" /> Issue Stock — {{ issueRequest.request_number }}
            </h3>
            <p class="text-xs text-slate-400 mt-1">Recipient: {{ issueRequest.requester_name }} ({{ issueRequest.department_name }})</p>
          </div>
          <button @click="issueOpen = false" class="text-slate-400 hover:text-white p-1 cursor-pointer">
            <Icon name="X" :size="20" />
          </button>
        </div>

        <form @submit.prevent="submitIssue" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Voucher Reference (Auto-generated if empty)</label>
              <input v-model="issueForm.reference" type="text" placeholder="e.g. ISV-2026-0012" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs font-mono focus:outline-none focus:border-emerald-500" />
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Issue Notes</label>
              <input v-model="issueForm.notes" type="text" placeholder="Optional handover notes" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-emerald-500" />
            </div>
          </div>

          <div class="space-y-3">
            <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Items to Issue</label>

            <div v-for="(line, idx) in issueForm.issues" :key="line.line_id" class="p-4 rounded-2xl border border-slate-900 bg-slate-900/40 space-y-3">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs font-bold text-slate-200">{{ line.item_name }}</p>
                  <p class="text-[11px] text-amber-400">Outstanding: {{ line.outstanding }} {{ line.unit_of_measure }}</p>
                </div>
                <span class="text-[10px] px-2 py-0.5 rounded font-mono uppercase bg-slate-800 text-slate-400">{{ line.tracking_mode }}</span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="space-y-1">
                  <label class="text-[11px] text-slate-400">Quantity to Release</label>
                  <input
                    v-model="line.quantity"
                    type="number"
                    step="any"
                    min="0.001"
                    :max="line.outstanding"
                    :disabled="line.tracking_mode === 'asset'"
                    class="w-full px-3 py-2 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-emerald-500"
                  />
                </div>

                <div class="space-y-1">
                  <label class="text-[11px] text-slate-400">From Location</label>
                  <select v-model="line.from_location_id" class="w-full px-3 py-2 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-emerald-500">
                    <option value="">Default Store Location</option>
                    <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }} ({{ loc.code }})</option>
                  </select>
                </div>

                <div v-if="line.tracking_mode === 'asset'" class="space-y-1">
                  <label class="text-[11px] text-slate-400">Specific Asset Unit *</label>
                  <select v-model="line.unit_id" required class="w-full px-3 py-2 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-emerald-500 font-mono">
                    <option value="">Select In-Store Asset Tag</option>
                    <option v-for="u in getUnitsForItem(line.item_id)" :key="u.id" :value="u.id">
                      {{ u.asset_tag }} ({{ u.condition }})
                    </option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="issueOpen = false" class="px-5 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:bg-slate-900 transition-all cursor-pointer">
              Cancel
            </button>
            <button type="submit" :disabled="issueForm.processing" class="px-6 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition-all shadow-md cursor-pointer disabled:opacity-50">
              Confirm & Issue Voucher
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
