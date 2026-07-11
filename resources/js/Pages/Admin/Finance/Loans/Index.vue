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
  loans: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  summary: { type: Object, default: () => ({}) },
  can: { type: Object, default: () => ({}) },
});

const STATUS = {
  active: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  paid: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  cancelled: 'bg-slate-500/10 border-slate-500/20 text-slate-400',
};

const money = (n) => Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const search = ref('');

const filteredLoans = computed(() => {
  if (!search.value) return props.loans;
  const q = search.value.toLowerCase();
  return props.loans.filter(
    (l) =>
      (l.employee || '').toLowerCase().includes(q) ||
      (l.reference || '').toLowerCase().includes(q) ||
      (l.staff_no || '').toLowerCase().includes(q) ||
      l.status_label.toLowerCase().includes(q)
  );
});

// ---- Create loan ----------------------------------------------------------
const createOpen = ref(false);
const createForm = useForm({
  employee_id: '',
  principal_amount: '',
  monthly_amount: '',
  start_date: '',
  notes: '',
});

const derivedDuration = computed(() => {
  const p = Number(createForm.principal_amount);
  const m = Number(createForm.monthly_amount);
  if (!p || !m || m <= 0) return null;
  return Math.ceil(p / m);
});

const openCreate = () => {
  createForm.reset();
  createForm.clearErrors();
  createOpen.value = true;
};

const submitCreate = () => {
  createForm.post('/admin/loans', {
    preserveScroll: true,
    onSuccess: () => { createOpen.value = false; },
  });
};

// ---- Loan detail + payments ----------------------------------------------
const detailId = ref(null);
const detailLoan = computed(() => props.loans.find((l) => l.id === detailId.value) || null);

const payForm = useForm({ amount: '', note: '' });

const openDetail = (loan) => {
  detailId.value = loan.id;
  payForm.reset();
  payForm.clearErrors();
};
const closeDetail = () => { detailId.value = null; };

const payRemaining = () => {
  if (detailLoan.value) payForm.amount = detailLoan.value.balance;
};

const submitPayment = () => {
  payForm.post(`/admin/loans/${detailId.value}/payments`, {
    preserveScroll: true,
    onSuccess: () => { payForm.reset(); payForm.clearErrors(); },
  });
};

const cancelLoan = async (loan) => {
  const ok = await confirm({
    title: 'Cancel Loan',
    message: `Cancel loan ${loan.reference} for ${loan.employee}? Payroll will stop deducting it. The outstanding balance of ${money(loan.balance)} will be written off.`,
  });
  if (ok) router.post(`/admin/loans/${loan.id}/cancel`, {}, { preserveScroll: true, onSuccess: closeDetail });
};

const selectedEmployee = computed(() =>
  props.employees.find((e) => String(e.id) === String(createForm.employee_id)) || null);
</script>

<template>
  <Head title="Employee Loans — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-emerald-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
            <Icon name="Landmark" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Finance · Payroll</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Employee Loans</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Issue salary loans repaid at a fixed monthly amount. Each payroll run auto-deducts the
              instalment until the balance clears; employees can also settle early or pay extra any time.
            </p>
          </div>
        </div>
        <button v-if="can.manage" @click="openCreate" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
          + New Loan
        </button>
      </div>
    </section>

    <!-- Summary tiles -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Active Loans</p>
        <p class="text-2xl font-extrabold text-white mt-1">{{ summary.active_count ?? 0 }}</p>
      </div>
      <div class="rounded-2xl border border-amber-500/15 bg-amber-500/[0.04] p-5">
        <p class="text-[11px] text-amber-500/80 font-semibold uppercase tracking-wider">Total Outstanding</p>
        <p class="text-2xl font-extrabold text-amber-300 mt-1">{{ money(summary.total_outstanding) }}</p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Monthly Commitment</p>
        <p class="text-2xl font-extrabold text-white mt-1">{{ money(summary.monthly_commitment) }}</p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Total Disbursed</p>
        <p class="text-2xl font-extrabold text-white mt-1">{{ money(summary.total_disbursed) }}</p>
      </div>
    </div>

    <!-- Search + table -->
    <div class="space-y-4">
      <div class="relative w-full max-w-xs">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"><Icon name="Search" :size="16" /></span>
        <input v-model="search" type="text" placeholder="Search by name, ref, staff no…" class="w-full bg-slate-900/40 border border-slate-900 rounded-xl pl-10 pr-4 py-2.5 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-emerald-500" />
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2 overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm min-w-[880px]">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="p-3">Reference</th>
              <th class="p-3">Employee</th>
              <th class="p-3 text-right">Principal</th>
              <th class="p-3 text-right">Monthly</th>
              <th class="p-3 text-right">Balance</th>
              <th class="p-3 w-48">Progress</th>
              <th class="p-3 text-center">Status</th>
              <th class="p-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-900">
            <tr v-for="loan in filteredLoans" :key="loan.id" class="hover:bg-slate-900/30 transition-colors cursor-pointer" @click="openDetail(loan)">
              <td class="p-3 font-mono text-xs font-semibold text-slate-300">{{ loan.reference }}</td>
              <td class="p-3">
                <p class="font-semibold text-slate-200">{{ loan.employee || '—' }}</p>
                <p class="text-[10px] text-slate-500">{{ loan.staff_no }}</p>
              </td>
              <td class="p-3 text-right font-mono text-slate-300">{{ money(loan.principal_amount) }}</td>
              <td class="p-3 text-right font-mono text-slate-400">{{ money(loan.monthly_amount) }}</td>
              <td class="p-3 text-right font-mono font-semibold" :class="loan.balance > 0 ? 'text-amber-300' : 'text-emerald-400'">{{ money(loan.balance) }}</td>
              <td class="p-3">
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-1.5 rounded-full bg-slate-800 overflow-hidden">
                    <div class="h-full rounded-full" :class="loan.balance > 0 ? 'bg-emerald-500' : 'bg-emerald-400'" :style="{ width: Math.min(loan.progress_percent, 100) + '%' }"></div>
                  </div>
                  <span class="text-[10px] text-slate-500 font-mono w-16 text-right">{{ loan.months_paid }}/{{ loan.duration_months }} mo</span>
                </div>
              </td>
              <td class="p-3 text-center">
                <span class="px-2 py-0.5 text-[10px] rounded-full font-bold border" :class="STATUS[loan.status]">{{ loan.status_label }}</span>
              </td>
              <td class="p-3 text-right whitespace-nowrap" @click.stop>
                <button @click="openDetail(loan)" class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-emerald-500/50 text-emerald-400 hover:text-emerald-300 bg-slate-900/50 rounded-lg">Manage</button>
              </td>
            </tr>
            <tr v-if="!filteredLoans.length">
              <td colspan="8" class="p-8 text-center text-slate-500 italic">No loans yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create modal -->
    <div v-if="createOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm" @click.self="createOpen = false">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-6">Issue New Loan</h3>
        <form @submit.prevent="submitCreate" class="space-y-5">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Employee</label>
            <select v-model="createForm.employee_id" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-emerald-500/50">
              <option value="" disabled>Select an employee…</option>
              <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.full_name_en }} <template v-if="e.staff_no">({{ e.staff_no }})</template></option>
            </select>
            <p v-if="selectedEmployee" class="text-[11px] text-slate-500 mt-1">Base salary: {{ money(selectedEmployee.base_salary) }}</p>
            <p v-if="createForm.errors.employee_id" class="text-xs text-rose-400 mt-1">{{ createForm.errors.employee_id }}</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Principal Amount</label>
              <input v-model="createForm.principal_amount" type="number" min="1" step="0.01" required placeholder="e.g. 20000" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-emerald-500/50" />
              <p v-if="createForm.errors.principal_amount" class="text-xs text-rose-400 mt-1">{{ createForm.errors.principal_amount }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Monthly Deduction</label>
              <input v-model="createForm.monthly_amount" type="number" min="1" step="0.01" required placeholder="e.g. 2000" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-emerald-500/50" />
              <p v-if="createForm.errors.monthly_amount" class="text-xs text-rose-400 mt-1">{{ createForm.errors.monthly_amount }}</p>
            </div>
          </div>
          <div v-if="derivedDuration" class="flex items-center gap-2 text-xs text-emerald-400 bg-emerald-500/5 border border-emerald-500/15 rounded-xl px-4 py-3">
            <Icon name="HandCoins" :size="16" />
            <span>Repaid over <span class="font-bold">{{ derivedDuration }}</span> month(s) — the final instalment covers the remainder.</span>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">First Deduction Date</label>
            <input v-model="createForm.start_date" type="date" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-emerald-500/50" />
            <p v-if="createForm.errors.start_date" class="text-xs text-rose-400 mt-1">{{ createForm.errors.start_date }}</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Notes (optional)</label>
            <textarea v-model="createForm.notes" rows="2" placeholder="Purpose or terms…" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-emerald-500/50"></textarea>
          </div>
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="createOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl">Cancel</button>
            <button type="submit" :disabled="createForm.processing" class="text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50">Issue Loan</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Detail modal -->
    <div v-if="detailLoan" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm" @click.self="closeDetail">
      <div class="w-full max-w-2xl max-h-[90vh] flex flex-col rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 shadow-2xl overflow-hidden">
        <!-- header -->
        <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-slate-900">
          <div class="min-w-0">
            <div class="flex items-center gap-2.5">
              <span class="font-mono text-xs font-semibold text-slate-400">{{ detailLoan.reference }}</span>
              <span class="px-2 py-0.5 text-[10px] rounded-full font-bold border" :class="STATUS[detailLoan.status]">{{ detailLoan.status_label }}</span>
            </div>
            <p class="text-lg font-bold text-white truncate mt-0.5">{{ detailLoan.employee }}</p>
          </div>
          <button @click="closeDetail" class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg border border-slate-800 text-slate-400 hover:text-white hover:border-slate-700"><Icon name="X" :size="16" /></button>
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto p-6 space-y-6">
          <!-- amounts -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-3">
              <p class="text-[10px] text-slate-500 uppercase tracking-wider">Principal</p>
              <p class="text-sm font-bold text-slate-200 mt-0.5">{{ money(detailLoan.principal_amount) }}</p>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-3">
              <p class="text-[10px] text-slate-500 uppercase tracking-wider">Paid</p>
              <p class="text-sm font-bold text-emerald-400 mt-0.5">{{ money(detailLoan.amount_paid) }}</p>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-3">
              <p class="text-[10px] text-slate-500 uppercase tracking-wider">Balance</p>
              <p class="text-sm font-bold mt-0.5" :class="detailLoan.balance > 0 ? 'text-amber-300' : 'text-emerald-400'">{{ money(detailLoan.balance) }}</p>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-3">
              <p class="text-[10px] text-slate-500 uppercase tracking-wider">Monthly</p>
              <p class="text-sm font-bold text-slate-200 mt-0.5">{{ money(detailLoan.monthly_amount) }}</p>
            </div>
          </div>

          <!-- progress -->
          <div>
            <div class="flex items-center justify-between text-xs mb-1.5">
              <span class="text-slate-400 font-medium">{{ detailLoan.months_paid }} of {{ detailLoan.duration_months }} months · {{ detailLoan.months_remaining }} remaining</span>
              <span class="text-emerald-400 font-bold">{{ detailLoan.progress_percent }}%</span>
            </div>
            <div class="h-2 rounded-full bg-slate-800 overflow-hidden">
              <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400" :style="{ width: Math.min(detailLoan.progress_percent, 100) + '%' }"></div>
            </div>
          </div>

          <!-- payment form -->
          <div v-if="can.manage && detailLoan.status === 'active' && detailLoan.balance > 0" class="rounded-2xl border border-slate-800 bg-slate-950/40 p-4">
            <p class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-3">Record a Payment</p>
            <form @submit.prevent="submitPayment" class="space-y-3">
              <div class="flex items-end gap-3">
                <div class="flex-1">
                  <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Amount</label>
                  <input v-model="payForm.amount" type="number" min="1" :max="detailLoan.balance" step="0.01" required placeholder="0.00" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-100 text-sm focus:outline-none focus:border-emerald-500/50" />
                </div>
                <button type="button" @click="payRemaining" class="text-[11px] font-semibold px-3 py-2.5 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10 rounded-xl whitespace-nowrap">Pay off ({{ money(detailLoan.balance) }})</button>
              </div>
              <input v-model="payForm.note" type="text" maxlength="255" placeholder="Note (e.g. cash settlement, bonus deduction)…" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-100 text-sm focus:outline-none focus:border-emerald-500/50" />
              <p v-if="payForm.errors.amount" class="text-xs text-rose-400">{{ payForm.errors.amount }}</p>
              <div class="flex justify-end">
                <button type="submit" :disabled="payForm.processing" class="text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50">Record Payment</button>
              </div>
            </form>
          </div>
          <div v-else-if="detailLoan.balance <= 0" class="rounded-2xl border border-emerald-500/20 bg-emerald-500/[0.06] p-4 flex items-center gap-2.5 text-emerald-400">
            <Icon name="CheckCircle2" :size="18" />
            <span class="text-sm font-semibold">This loan is fully paid — the employee is clear.</span>
          </div>

          <!-- ledger -->
          <div>
            <p class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Payment History</p>
            <div class="rounded-xl border border-slate-800 overflow-hidden">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="bg-slate-900/60 text-[10px] uppercase text-slate-500">
                    <th class="px-3 py-2">Date</th>
                    <th class="px-3 py-2">Type</th>
                    <th class="px-3 py-2">Period</th>
                    <th class="px-3 py-2 text-right">Amount</th>
                    <th class="px-3 py-2">Note</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-900">
                  <tr v-for="p in detailLoan.payments" :key="p.id">
                    <td class="px-3 py-2 font-mono text-slate-400">{{ p.paid_on }}</td>
                    <td class="px-3 py-2">
                      <span class="px-1.5 py-0.5 rounded text-[9px] font-bold border" :class="p.type === 'payroll' ? 'border-blue-500/20 bg-blue-500/10 text-blue-400' : 'border-violet-500/20 bg-violet-500/10 text-violet-400'">{{ p.type_label }}</span>
                    </td>
                    <td class="px-3 py-2 text-slate-500">{{ p.period || '—' }}</td>
                    <td class="px-3 py-2 text-right font-mono font-semibold text-slate-300">{{ money(p.amount) }}</td>
                    <td class="px-3 py-2 text-slate-500 truncate max-w-[140px]" :title="p.note">{{ p.note || '—' }}</td>
                  </tr>
                  <tr v-if="!detailLoan.payments.length">
                    <td colspan="5" class="px-3 py-6 text-center text-slate-500 italic">No payments recorded yet.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- footer -->
        <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-slate-900">
          <button v-if="can.manage && detailLoan.status === 'active'" @click="cancelLoan(detailLoan)" class="text-xs font-semibold px-4 py-2.5 border border-rose-500/30 text-rose-400 hover:bg-rose-500/10 rounded-xl">Cancel Loan</button>
          <span v-else></span>
          <button @click="closeDetail" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>
