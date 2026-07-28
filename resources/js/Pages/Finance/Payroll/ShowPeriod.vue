<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import EmployeePayrollConfigModal from '@/Components/EmployeePayrollConfigModal.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  isAdmin: { type: Boolean, default: false },
  period: { type: Object, required: true },
  rows: { type: Array, default: () => [] },
  totals: { type: Object, default: () => ({}) },
  columns: { type: Object, default: () => ({}) },
  assignments: { type: Object, default: () => ({}) },
  employees: { type: Array, default: () => [] },
  components: { type: Array, default: () => [] },
  periods: { type: Array, default: () => [] },
  scheduleTypes: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
});

const money = (n) => Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const columnKeys = computed(() => Object.keys(props.columns));

const STATUS = {
  open: 'bg-slate-700/40 border-slate-700 text-slate-300',
  processing: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  pending_approval: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
  approved: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  rejected: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
  locked: 'bg-violet-500/10 border-violet-500/20 text-violet-400',
  paid: 'bg-teal-500/10 border-teal-500/20 text-teal-400',
};
const statusClass = (s) => STATUS[s] || STATUS.open;

const financeBase = `/finance/payroll/${props.period.id}`;

// ---- Row selection (drives scoped export) --------------------------------
const selected = reactive(new Set());
const allSelected = computed(() => props.rows.length > 0 && selected.size === props.rows.length);
const toggleAll = () => {
  if (allSelected.value) selected.clear();
  else props.rows.forEach((r) => selected.add(r.employee_id));
};
const toggleRow = (id) => { selected.has(id) ? selected.delete(id) : selected.add(id); };

const exportUrl = (format) => {
  const params = selected.size ? `?scope=selected&ids=${[...selected].join(',')}` : '';
  return `${financeBase}/export/${format}${params}`;
};

// ---- Actions -------------------------------------------------------------
const busy = ref(false);
const recompute = () => {
  busy.value = true;
  router.post(`${financeBase}/run`, {}, { preserveScroll: true, onFinish: () => (busy.value = false) });
};

/**
 * President-side run, offered only when Finance has not prepared this period and
 * it is not yet submitted or approved. Goes through the admin endpoint, which
 * takes the period in the body rather than the URL.
 */
const runPayroll = async () => {
  const ok = await confirm({
    title: 'Run Payroll',
    message: `Finance has not prepared ${props.period.name} yet. Generate payslips for every active employee now from salaries, attendance and the standing component assignments?`,
  });
  if (!ok) return;

  busy.value = true;
  router.post('/admin/payroll/run', { payroll_period_id: props.period.id }, {
    preserveScroll: true, onFinish: () => (busy.value = false),
  });
};

const recalculateAdmin = async () => {
  const ok = await confirm({
    title: 'Recalculate Payroll',
    message: `Recalculate ${props.period.name} payroll? This will regenerate payslips based on current employee data and assignments.`,
  });
  if (!ok) return;

  busy.value = true;
  router.post('/admin/payroll/run', { payroll_period_id: props.period.id }, {
    preserveScroll: true, onFinish: () => (busy.value = false),
  });
};

const submitForApproval = async () => {
  const ok = await confirm({
    title: 'Submit for Approval',
    message: `Send ${props.period.name} payroll to the President for approval? You won't be able to edit it until it's reviewed.`,
  });
  if (ok) router.post(`${financeBase}/submit`, {}, { preserveScroll: true });
};

const approve = async () => {
  const ok = await confirm({ title: 'Approve Payroll', message: `Approve ${props.period.name} payroll?` });
  if (ok) router.post(`/admin/payroll/${props.period.id}/approve`, {}, { preserveScroll: true });
};

const revert = async () => {
  const ok = await confirm({
    title: 'Revert Payroll Run',
    message: `Revert ${props.period.name} back to open? Payslips are kept as drafts and the submit/approve trail is cleared, so Finance can recompute and resubmit.`,
  });
  if (ok) router.post(`/admin/payroll/${props.period.id}/revert`, {}, { preserveScroll: true });
};

const rejectModalOpen = ref(false);
const rejectNotes = ref('');
const reject = () => {
  router.post(`/admin/payroll/${props.period.id}/reject`, { review_notes: rejectNotes.value }, {
    preserveScroll: true, onSuccess: () => (rejectModalOpen.value = false),
  });
};

// ---- Component assignments -----------------------------------------------
const adjModalOpen = ref(false);
const adjForm = useForm({
  employee_id: '', payroll_component_id: '', amount: 0,
  schedule_type: 'monthly', start_period_id: '', end_period_id: '', note: '',
});
const openAdjModal = () => {
  adjForm.reset();
  adjForm.employee_id = props.employees.length ? props.employees[0].id : '';
  adjForm.payroll_component_id = props.components.length ? props.components[0].id : '';
  adjForm.start_period_id = props.period.id;
  adjForm.clearErrors();
  adjModalOpen.value = true;
};
const submitAdj = () => {
  adjForm.post(`${financeBase}/assignments`, {
    preserveScroll: true,
    onSuccess: () => { adjModalOpen.value = false; },
  });
};
const removeAdj = async (id) => {
  const ok = await confirm({ title: 'Remove Assignment', message: 'Remove this component assignment? Recompute to apply.' });
  if (ok) router.delete(`${financeBase}/assignments/${id}`, { preserveScroll: true });
};

const employeeName = (id) => props.employees.find((e) => e.id === id)?.full_name_en ?? '—';
const flatAssignments = computed(() => Object.values(props.assignments).flat());

// ---- Allowances & deductions, grouped per employee -----------------------
// A flat grid of every assignment across all staff is unreadable once the roster
// grows, so the list collapses by employee and filters by which month applies.
const adjMonthFilter = ref('this_month'); // this_month | other_months | all
const adjSearch = ref('');
const expandedEmployees = ref(new Set());

const ADJ_MONTH_FILTERS = [
  { key: 'this_month', label: 'Applies this month' },
  { key: 'other_months', label: 'Other months' },
  { key: 'all', label: 'All' },
];

const matchesMonthFilter = (adj) => {
  if (adjMonthFilter.value === 'this_month') return adj.applies_now;
  if (adjMonthFilter.value === 'other_months') return !adj.applies_now;
  return true;
};

/** [{ employee_id, name, items, earnings, deductions, appliesNow }] sorted by name. */
const assignmentGroups = computed(() => {
  const q = adjSearch.value.trim().toLowerCase();
  const groups = new Map();

  for (const adj of flatAssignments.value) {
    if (!matchesMonthFilter(adj)) continue;

    const name = employeeName(adj.employee_id);
    if (q && !`${name} ${adj.component ?? ''}`.toLowerCase().includes(q)) continue;

    if (!groups.has(adj.employee_id)) {
      groups.set(adj.employee_id, {
        employee_id: adj.employee_id, name, items: [], earnings: 0, deductions: 0, appliesNow: 0,
      });
    }

    const group = groups.get(adj.employee_id);
    group.items.push(adj);
    if (adj.is_earning) group.earnings += Number(adj.amount || 0);
    else group.deductions += Number(adj.amount || 0);
    if (adj.applies_now) group.appliesNow += 1;
  }

  return [...groups.values()].sort((a, b) => a.name.localeCompare(b.name));
});

const visibleAdjCount = computed(() =>
  assignmentGroups.value.reduce((n, g) => n + g.items.length, 0));

const isExpanded = (id) => expandedEmployees.value.has(id);
const toggleEmployee = (id) => {
  const next = new Set(expandedEmployees.value);
  next.has(id) ? next.delete(id) : next.add(id);
  expandedEmployees.value = next;
};
const expandAll = () => {
  expandedEmployees.value = new Set(assignmentGroups.value.map((g) => g.employee_id));
};
const collapseAll = () => { expandedEmployees.value = new Set(); };

// ---- Per-Employee Config -------------------------------------------------
const employeeConfigOpen = ref(false);
const activeEmployeeForConfig = ref(null);
const openEmployeeConfig = (row) => {
  activeEmployeeForConfig.value = {
    id: row.employee_id,
    full_name_en: row.name,
  };
  employeeConfigOpen.value = true;
};
const onEmployeeConfigUpdated = () => {
  recompute();
};
</script>

<template>
  <Head :title="`${period.name} Payroll — SITS ERP`" />

  <div class="space-y-6">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-6 md:p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-teal-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
          <Link :href="isAdmin ? '/admin/payroll' : '/finance/payroll'"
                class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest inline-flex items-center gap-1 hover:text-slate-300">
            <Icon name="ArrowLeft" :size="12" /> Payroll Periods
          </Link>
          <div class="flex items-center gap-3 mt-1">
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight">{{ period.name }}</h2>
            <span class="px-2.5 py-0.5 text-[11px] rounded-full font-bold border" :class="statusClass(period.status)">{{ period.statusLabel }}</span>
          </div>
          <p class="text-slate-400 text-sm mt-1">{{ period.start_date }} → {{ period.end_date }} · {{ rows.length }} employee(s)</p>
        </div>

        <!-- Action bar -->
        <div class="flex flex-wrap items-center gap-2">
          <button v-if="can.prepare" @click="recompute" :disabled="busy"
                  class="text-xs font-semibold px-4 py-2.5 border border-slate-800 hover:border-blue-700 bg-slate-900/60 text-blue-300 rounded-xl transition-colors cursor-pointer disabled:opacity-50">
            <Icon name="RefreshCw" :size="14" class="inline -mt-0.5 mr-1" />{{ busy ? 'Computing…' : 'Recompute' }}
          </button>
          <button v-if="can.recalculateAdmin" @click="recalculateAdmin" :disabled="busy"
                  class="text-xs font-semibold px-4 py-2.5 border border-slate-800 hover:border-blue-700 bg-slate-900/60 text-blue-300 rounded-xl transition-colors cursor-pointer disabled:opacity-50"
                  title="Recalculate the payroll based on current data.">
            <Icon name="RefreshCw" :size="14" class="inline -mt-0.5 mr-1" />{{ busy ? 'Recalculating…' : 'Recalculate Payroll' }}
          </button>
          <button v-if="can.run" @click="runPayroll" :disabled="busy"
                  class="text-xs font-semibold px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl transition-all shadow-md cursor-pointer disabled:opacity-50"
                  title="Finance has not prepared this period — generate the payslips from here.">
            <Icon name="Play" :size="14" class="inline -mt-0.5 mr-1" />{{ busy ? 'Running…' : 'Run Payroll' }}
          </button>
          <button v-if="can.manageDeductions" @click="openAdjModal"
                  class="text-xs font-semibold px-4 py-2.5 border border-slate-800 hover:border-slate-700 bg-slate-900/60 text-slate-200 rounded-xl transition-colors cursor-pointer">
            + Deduction / Allowance
          </button>

          <a v-if="can.export" :href="exportUrl('excel')"
             class="text-xs font-semibold px-4 py-2.5 border border-emerald-700/40 hover:border-emerald-600 bg-emerald-600/10 text-emerald-300 rounded-xl transition-colors cursor-pointer">
            <Icon name="Sheet" :size="14" class="inline -mt-0.5 mr-1" />Excel
          </a>
          <a v-if="can.export" :href="exportUrl('pdf')"
             class="text-xs font-semibold px-4 py-2.5 border border-rose-700/40 hover:border-rose-600 bg-rose-600/10 text-rose-300 rounded-xl transition-colors cursor-pointer">
            <Icon name="FileText" :size="14" class="inline -mt-0.5 mr-1" />PDF
          </a>

          <button v-if="can.submit" @click="submitForApproval"
                  class="text-xs font-semibold px-5 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white rounded-xl transition-all shadow-md cursor-pointer">
            Submit for Approval
          </button>
          <template v-if="can.approve">
            <button @click="rejectModalOpen = true"
                    class="text-xs font-semibold px-4 py-2.5 border border-slate-800 hover:border-rose-700 text-rose-400 bg-slate-900/60 rounded-xl transition-colors cursor-pointer">
              Reject
            </button>
            <button @click="approve"
                    class="text-xs font-semibold px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl transition-all shadow-md cursor-pointer">
              Approve
            </button>
          </template>
          <button v-if="can.revert" @click="revert"
                  class="text-xs font-semibold px-4 py-2.5 border border-slate-800 hover:border-orange-700 text-orange-400 bg-slate-900/60 rounded-xl transition-colors cursor-pointer inline-flex items-center gap-1.5"
                  title="Revert this run back to open so Finance can recompute and resubmit.">
            <Icon name="Undo2" :size="14" /> Revert
          </button>
        </div>
      </div>

      <!-- Approval / status banners -->
      <div v-if="period.status === 'rejected' && period.review_notes"
           class="relative z-10 mt-4 rounded-xl border border-rose-500/30 bg-rose-950/30 px-4 py-3 text-sm text-rose-200">
        <span class="font-semibold">Returned by the President:</span> {{ period.review_notes }}
      </div>
      <div v-else-if="period.status === 'pending_approval'"
           class="relative z-10 mt-4 rounded-xl border border-amber-500/30 bg-amber-950/20 px-4 py-3 text-sm text-amber-200 flex flex-wrap items-center justify-between gap-3">
        <span>Submitted{{ period.submitted_at ? ' on ' + period.submitted_at : '' }} — awaiting the President's approval.</span>
        <Link v-if="can.approveHint" :href="`/admin/payroll/${period.id}`"
              class="font-semibold text-amber-100 underline underline-offset-2 hover:text-white shrink-0">
          Approve it on the Payroll admin page →
        </Link>
      </div>
      <div v-else-if="isAdmin && period.status === 'processing'"
           class="relative z-10 mt-4 rounded-xl border border-slate-700/50 bg-slate-900/40 px-4 py-3 text-sm text-slate-300">
        Prepared by Finance — the <span class="font-semibold text-slate-100">Approve</span> action appears once Finance submits this period for approval.
      </div>
      <div v-else-if="period.approved_by"
           class="relative z-10 mt-4 rounded-xl border border-emerald-500/30 bg-emerald-950/20 px-4 py-3 text-sm text-emerald-200">
        Approved by {{ period.approved_by }}{{ period.approved_at ? ' on ' + period.approved_at : '' }}.
      </div>
      <p v-if="selected.size" class="relative z-10 mt-3 text-xs text-slate-400">
        Exporting {{ selected.size }} selected employee(s). Clear the checkboxes to export everyone.
      </p>
    </section>

    <!-- Collective summary -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-right text-[13px] border-collapse whitespace-nowrap">
          <thead>
            <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-slate-950/40">
              <th class="px-3 py-3 text-center sticky left-0 bg-slate-950/40">
                <input type="checkbox" :checked="allSelected" @change="toggleAll" class="rounded border-slate-700 bg-slate-950 text-teal-500 focus:ring-0" />
              </th>
              <th class="px-3 py-3 text-left sticky left-10 bg-slate-950/40">Employee</th>

              <th v-for="key in columnKeys" :key="key" class="px-3 py-3">{{ columns[key] }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-900/70">
            <tr v-for="row in rows" :key="row.payslip_id" class="hover:bg-slate-900/30">
              <td class="px-3 py-2.5 text-center sticky left-0 bg-slate-950/20">
                <input type="checkbox" :checked="selected.has(row.employee_id)" @change="toggleRow(row.employee_id)" class="rounded border-slate-700 bg-slate-950 text-teal-500 focus:ring-0" />
              </td>
              <td class="px-3 py-2.5 text-left font-semibold text-slate-200 sticky left-10 bg-slate-950/20 group">
                <div class="flex items-center justify-between">
                  <div>
                    {{ row.name }}
                    <span v-if="row.has_provident_fund" class="ml-1 text-[9px] font-bold uppercase px-1 py-0.5 rounded bg-violet-500/15 text-violet-300 border border-violet-500/20">PF</span>
                  </div>
                  <button @click="openEmployeeConfig(row)" class="text-slate-500 hover:text-blue-400 opacity-0 group-hover:opacity-100 transition-all focus:opacity-100" title="Payroll Config">
                    <Icon name="Settings" :size="14" />
                  </button>
                </div>
              </td>

              <td v-for="key in columnKeys" :key="key" class="px-3 py-2.5 font-mono"
                  :class="key === 'net_pay' ? 'text-emerald-300 font-bold' : key === 'gross' ? 'text-slate-100' : key === 'absence_deduction' ? 'text-rose-400' : key === 'absent_days' ? 'text-amber-300' : 'text-slate-400'">
                {{ key === 'absent_days' ? (row[key] || 0) : money(row[key]) }}
              </td>
            </tr>
            <tr v-if="!rows.length">
              <td :colspan="columnKeys.length + 2" class="px-3 py-10 text-center text-slate-500 italic">
                No payslips yet.
                <button v-if="can.prepare" @click="recompute" class="text-teal-400 underline cursor-pointer">Recompute</button>
                <button v-else-if="can.run" @click="runPayroll" class="text-blue-400 underline cursor-pointer">Run payroll</button>
                <template v-if="can.prepare || can.run"> to generate them.</template>
              </td>
            </tr>
          </tbody>
          <tfoot v-if="rows.length">
            <tr class="border-t-2 border-slate-700 bg-slate-950/50 font-bold text-slate-100">
              <td class="px-3 py-3 sticky left-0 bg-slate-950/50"></td>
              <td class="px-3 py-3 text-left sticky left-10 bg-slate-950/50">TOTAL</td>

              <td v-for="key in columnKeys" :key="key" class="px-3 py-3 font-mono"
                  :class="key === 'net_pay' ? 'text-emerald-300' : key === 'absence_deduction' ? 'text-rose-400' : key === 'absent_days' ? 'text-amber-300' : ''">
                {{ key === 'absent_days' ? (totals[key] || 0) : money(totals[key]) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Component assignments -->
    <div v-if="flatAssignments.length || can.manageDeductions" class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Allowances & Deductions</h3>
        <button v-if="can.manageDeductions" @click="openAdjModal" class="text-[11px] font-bold text-teal-400 hover:text-teal-300">+ Assign Component</button>
      </div>
      <div v-if="!flatAssignments.length" class="text-sm text-slate-500 italic">No component assignments. Transport, housing, salary advances and custom items appear here.</div>

      <template v-else>
        <!-- Month filter + search -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
          <div class="flex gap-1.5">
            <button v-for="f in ADJ_MONTH_FILTERS" :key="f.key" @click="adjMonthFilter = f.key"
                    class="text-[11px] font-bold px-3 py-1.5 rounded-lg border transition-colors cursor-pointer"
                    :class="adjMonthFilter === f.key
                      ? 'border-teal-500/40 bg-teal-500/10 text-teal-300'
                      : 'border-slate-850 bg-slate-950/40 text-slate-500 hover:text-slate-300'">
              {{ f.label }}
            </button>
          </div>
          <div class="flex items-center gap-2 flex-1 min-w-[180px] rounded-lg border border-slate-850 bg-slate-950/40 px-3 py-1.5">
            <Icon name="Search" :size="14" class="text-slate-600" />
            <input v-model="adjSearch" type="text" placeholder="Employee or component…"
                   class="flex-1 bg-transparent text-xs text-slate-100 placeholder-slate-600 focus:outline-none" />
          </div>
          <div class="flex items-center gap-2 text-[11px] text-slate-600">
            <span>{{ visibleAdjCount }} item(s)</span>
            <button @click="expandAll" class="font-bold text-slate-400 hover:text-slate-200 cursor-pointer">Expand all</button>
            <span class="text-slate-800">·</span>
            <button @click="collapseAll" class="font-bold text-slate-400 hover:text-slate-200 cursor-pointer">Collapse</button>
          </div>
        </div>

        <!-- One collapsible row per employee -->
        <div v-if="!assignmentGroups.length" class="text-sm text-slate-500 italic py-6 text-center">
          Nothing matches this filter.
        </div>
        <div v-else class="space-y-2">
          <div v-for="g in assignmentGroups" :key="g.employee_id" class="rounded-xl border border-slate-900 bg-slate-950/30 overflow-hidden">
            <button @click="toggleEmployee(g.employee_id)"
                    class="w-full flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-900/40 transition-colors cursor-pointer text-left">
              <span class="flex items-center gap-2.5 min-w-0">
                <Icon :name="isExpanded(g.employee_id) ? 'ChevronDown' : 'ChevronRight'" :size="15" class="text-slate-600 shrink-0" />
                <span class="text-sm font-semibold text-slate-200 truncate">{{ g.name }}</span>
                <span class="text-[10px] font-bold text-slate-600 shrink-0">{{ g.items.length }}</span>
              </span>
              <span class="flex items-center gap-3 shrink-0 font-mono text-xs">
                <span v-if="g.earnings" class="text-emerald-400">+{{ money(g.earnings) }}</span>
                <span v-if="g.deductions" class="text-rose-400">−{{ money(g.deductions) }}</span>
                <span v-if="!g.appliesNow" class="text-[10px] font-sans font-bold uppercase px-1.5 py-0.5 rounded border border-slate-800 text-slate-600">
                  not this month
                </span>
              </span>
            </button>

            <div v-if="isExpanded(g.employee_id)" class="border-t border-slate-900 divide-y divide-slate-900/70">
              <div v-for="adj in g.items" :key="adj.id"
                   class="flex items-center justify-between gap-3 px-4 py-2.5"
                   :class="adj.applies_now ? '' : 'opacity-60'">
                <div class="min-w-0">
                  <p class="text-sm text-slate-300 truncate">
                    {{ adj.component }}
                    <span :class="adj.is_earning ? 'text-emerald-400' : 'text-rose-400'" class="font-mono ml-1">
                      {{ adj.is_earning ? '+' : '−' }}{{ money(adj.amount) }}
                    </span>
                  </p>
                  <p class="text-[10px] text-slate-600 mt-0.5">
                    {{ adj.schedule_label }}
                    <template v-if="adj.start_period"> · from {{ adj.start_period }}</template>
                    <template v-if="adj.end_period"> to {{ adj.end_period }}</template>
                    <span v-if="!adj.applies_now"> · does not apply to {{ period.name }}</span>
                  </p>
                </div>
                <button v-if="can.manageDeductions" @click="removeAdj(adj.id)" class="text-slate-500 hover:text-rose-400 shrink-0 cursor-pointer">
                  <Icon name="X" :size="15" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Assign component modal -->
    <div v-if="adjModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-white mb-6">Assign Component</h3>
        <form @submit.prevent="submitAdj" class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Employee</label>
              <select v-model="adjForm.employee_id" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.full_name_en }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Component</label>
              <select v-model="adjForm.payroll_component_id" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
                <option v-for="c in components" :key="c.id" :value="c.id">{{ c.name }} ({{ c.is_earning ? '+' : '−' }})</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Amount (ETB)</label>
              <input v-model="adjForm.amount" type="number" step="0.01" min="0" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Schedule</label>
              <select v-model="adjForm.schedule_type" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
                <option v-for="s in scheduleTypes" :key="s.value" :value="s.value">{{ s.label }}</option>
              </select>
            </div>
          </div>
          <div v-if="adjForm.schedule_type !== 'one_time'" class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">From month</label>
              <select v-model="adjForm.start_period_id" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
                <option value="">— start of year —</option>
                <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
            <div v-if="adjForm.schedule_type === 'range'">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">To month</label>
              <select v-model="adjForm.end_period_id" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
                <option value="">— open-ended —</option>
                <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
          </div>
          <p class="text-[11px] text-slate-500">One-time applies to this month only; monthly recurs (optionally from a start month); range applies between two months.</p>
          <p v-if="adjForm.errors.amount" class="text-xs text-rose-400">{{ adjForm.errors.amount }}</p>
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="adjModalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl">Cancel</button>
            <button type="submit" :disabled="adjForm.processing" class="text-xs font-semibold bg-teal-600 hover:bg-teal-500 text-white px-5 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50">Assign</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Reject modal (admin) -->
    <div v-if="rejectModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-4">Return to Finance</h3>
        <p class="text-sm text-slate-400 mb-4">Add a note so Finance knows what to revise.</p>
        <textarea v-model="rejectNotes" rows="4" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" placeholder="Reason for returning…"></textarea>
        <div class="flex items-center justify-end gap-3 pt-5">
          <button @click="rejectModalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl">Cancel</button>
          <button @click="reject" class="text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white px-5 py-2.5 rounded-xl cursor-pointer">Return Payroll</button>
        </div>
      </div>
    </div>

    <!-- Employee Config Modal -->
    <EmployeePayrollConfigModal
      v-if="employeeConfigOpen"
      :employee="activeEmployeeForConfig"
      :components="components"
      :periods="periods"
      :scheduleTypes="scheduleTypes"
      apiPrefix="finance"
      @close="employeeConfigOpen = false"
      @updated="onEmployeeConfigUpdated"
    />
  </div>
</template>
