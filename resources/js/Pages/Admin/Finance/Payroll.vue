<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, required: true },
  routeName: { type: String, required: true },
  periods: { type: Array, default: () => [] },
  payslips: { type: Array, default: () => [] },
});

// Modal state
const periodModalOpen = ref(false);
const runPayrollModalOpen = ref(false);

const moneyFmt = (n) => Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// Total loan repayment withheld across a period's payslips (0 when none).
const periodLoanTotal = (period) =>
  (period.payslips || []).reduce((sum, p) => sum + Number(p.loan_deduction || 0), 0);

const periodForm = useForm({
  name: '',
  start_date: '',
  end_date: '',
  payment_date: '',
});

// Payroll runs monthly: pick a month and derive name + start/end/payment dates.
const selectedMonth = ref('');
const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'];

const applyMonth = () => {
  if (!selectedMonth.value) {
    periodForm.name = periodForm.start_date = periodForm.end_date = periodForm.payment_date = '';
    return;
  }
  const [y, m] = selectedMonth.value.split('-').map(Number);
  const last = new Date(y, m, 0).getDate(); // last day of month
  const pad = (n) => String(n).padStart(2, '0');
  periodForm.name = `${MONTH_NAMES[m - 1]} ${y}`;
  periodForm.start_date = `${y}-${pad(m)}-01`;
  periodForm.end_date = `${y}-${pad(m)}-${pad(last)}`;
  periodForm.payment_date = periodForm.end_date;
};

const runForm = useForm({
  payroll_period_id: '',
});

const openPeriodModal = () => {
  periodForm.reset();
  periodForm.clearErrors();
  selectedMonth.value = '';
  periodModalOpen.value = true;
};

const openRunPayrollModal = () => {
  runForm.reset();
  const firstRunnable = props.periods.find(p => p.status !== 'locked' && p.status !== 'paid');
  if (firstRunnable) {
    runForm.payroll_period_id = firstRunnable.id;
  }
  runForm.clearErrors();
  runPayrollModalOpen.value = true;
};

const submitPeriod = () => {
  periodForm.post('/admin/payroll/periods', {
    onSuccess: () => {
      periodModalOpen.value = false;
    }
  });
};

const submitRun = () => {
  runForm.post('/admin/payroll/run', {
    onSuccess: () => {
      runPayrollModalOpen.value = false;
    }
  });
};

const lockPeriod = async (id) => {
  const confirmed = await confirm({
    title: 'Lock Payroll Period',
    message: 'Lock this payroll period? Payslips and attendance become immutable.',
  });
  if (confirmed) {
    router.post(`/admin/payroll/periods/${id}/lock`, {}, { preserveScroll: true });
  }
};

const markPaid = async (id) => {
  const confirmed = await confirm({
    title: 'Mark Period as Paid',
    message: 'Mark this period as paid? This records the disbursement and finalizes all payslips.',
  });
  if (confirmed) {
    router.post(`/admin/payroll/periods/${id}/pay`, {}, { preserveScroll: true });
  }
};

const STATUS_CLASSES = {
  open: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  processing: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  pending_approval: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
  approved: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  rejected: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
  locked: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
  paid: 'bg-violet-500/10 border-violet-500/20 text-violet-400',
};
const statusClass = (s) => STATUS_CLASSES[s] || 'bg-slate-800/60 border-slate-800 text-slate-500';
const statusLabel = (s) => (s || '').replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
const runnable = (p) => p.status !== 'locked' && p.status !== 'paid';

const approvePeriod = async (id) => {
  const confirmed = await confirm({
    title: 'Approve Payroll',
    message: 'Approve this Finance-submitted payroll draft?',
  });
  if (confirmed) router.post(`/admin/payroll/${id}/approve`, {}, { preserveScroll: true });
};

const rejectPeriodModal = ref(null);
const rejectPeriodNotes = ref('');
const rejectPeriod = () => {
  router.post(`/admin/payroll/${rejectPeriodModal.value}/reject`, { review_notes: rejectPeriodNotes.value }, {
    preserveScroll: true,
    onSuccess: () => { rejectPeriodModal.value = null; rejectPeriodNotes.value = ''; },
  });
};
</script>

<template>
  <Head title="Payroll Runs — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Banknote" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button
            @click="openPeriodModal"
            class="shrink-0 text-sm font-semibold bg-slate-900 hover:bg-slate-800 border border-slate-850 hover:border-slate-700 text-slate-200 px-4 py-2.5 rounded-xl transition-all cursor-pointer"
          >
            + Create Period
          </button>
          <button 
            @click="openRunPayrollModal"
            class="shrink-0 text-sm font-semibold bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer"
          >
            Run Payroll
          </button>
        </div>
      </div>
    </section>

    <!-- Periods List -->
    <div class="space-y-4">
      <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Payroll Periods</h3>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="period in periods" 
          :key="period.id"
          class="p-6 rounded-2xl border border-slate-900 bg-slate-900/10 hover:border-slate-850 transition-all flex flex-col justify-between"
        >
          <div>
            <div class="flex items-center justify-between mb-3">
              <span class="font-bold text-white">{{ period.name }}</span>
              <span class="px-2 py-0.5 text-[9px] rounded-md font-bold uppercase border" :class="statusClass(period.status)">
                {{ statusLabel(period.status) }}
              </span>
            </div>
            <p class="text-xs text-slate-400">
              Dates: {{ new Date(period.start_date).toLocaleDateString() }} - {{ new Date(period.end_date).toLocaleDateString() }}
            </p>
            <p class="text-xs text-slate-500 mt-1" v-if="period.payment_date">
              Payment Date: {{ new Date(period.payment_date).toLocaleDateString() }}
            </p>
            <div v-if="periodLoanTotal(period) > 0" class="mt-3 inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-400 bg-emerald-500/[0.07] border border-emerald-500/15 rounded-lg px-2.5 py-1">
              <Icon name="Landmark" :size="13" />
              <span>Loan deductions: {{ moneyFmt(periodLoanTotal(period)) }}</span>
            </div>
          </div>

          <div class="flex items-center justify-between mt-6 pt-4 border-t border-slate-900">
            <span class="text-xs text-slate-500 font-semibold">{{ period.payslips?.length || 0 }} payslips generated</span>
            <div class="flex items-center gap-2 flex-wrap justify-end">
              <Link
                :href="`/admin/payroll/${period.id}`"
                class="text-[10px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-slate-850 border border-slate-850 text-teal-300 rounded-lg transition-colors cursor-pointer"
              >
                Open
              </Link>
              <template v-if="period.status === 'pending_approval'">
                <button
                  @click="rejectPeriodModal = period.id"
                  class="text-[10px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-slate-850 border border-slate-850 text-rose-400 rounded-lg transition-colors cursor-pointer"
                >
                  Reject
                </button>
                <button
                  @click="approvePeriod(period.id)"
                  class="text-[10px] font-bold px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition-colors cursor-pointer"
                >
                  Approve
                </button>
              </template>
              <button
                v-if="['open', 'processing', 'approved'].includes(period.status)"
                @click="lockPeriod(period.id)"
                class="text-[10px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-slate-850 border border-slate-850 text-amber-400 rounded-lg transition-colors cursor-pointer"
              >
                Lock Period
              </button>
              <button
                v-if="period.status === 'locked'"
                @click="markPaid(period.id)"
                class="text-[10px] font-bold px-3 py-1.5 bg-violet-600 hover:bg-violet-500 text-white rounded-lg transition-colors cursor-pointer"
              >
                Mark Paid
              </button>
              <span v-if="period.status === 'paid'" class="text-[10px] font-bold text-violet-400">✓ Disbursed</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Period Modal -->
    <div v-if="periodModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-6">Create Payroll Period</h3>

        <form @submit.prevent="submitPeriod" class="space-y-5">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Month</label>
            <input
              v-model="selectedMonth"
              @input="applyMonth"
              type="month"
              required
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            />
            <p class="text-[11px] text-slate-500 mt-2">Payroll periods cover a full calendar month.</p>
          </div>

          <div v-if="periodForm.name" class="rounded-xl border border-slate-850 bg-slate-950/40 px-4 py-3 text-xs text-slate-400 space-y-1">
            <p><span class="text-slate-500">Period:</span> <span class="text-slate-200 font-semibold">{{ periodForm.name }}</span></p>
            <p><span class="text-slate-500">Covers:</span> {{ periodForm.start_date }} → {{ periodForm.end_date }}</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Payment Date</label>
            <input
              v-model="periodForm.payment_date"
              type="date"
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            />
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button 
              type="button" 
              @click="periodModalOpen = false"
              class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="periodForm.processing"
              class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
            >
              Save Period
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Run Payroll Modal -->
    <div v-if="runPayrollModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-4">Run Payroll Calculation</h3>
        <p class="text-slate-400 text-sm mb-6 leading-relaxed">
          This will calculate gross salary, overtime pay, pensions (7% employee / 11% employer), and Personal Income Tax (PIT) for all active employees.
        </p>

        <form @submit.prevent="submitRun" class="space-y-5">
          <!-- Period Select -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Payroll Period</label>
            <select 
              v-model="runForm.payroll_period_id"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              required
            >
              <option value="" disabled>Select Period</option>
              <option v-for="p in periods.filter(runnable)" :key="p.id" :value="p.id">{{ p.name }} ({{ p.status }})</option>
            </select>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button 
              type="button" 
              @click="runPayrollModalOpen = false"
              class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="runForm.processing"
              class="text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer"
            >
              {{ runForm.processing ? 'Calculating...' : 'Run Payroll' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Reject Period Modal -->
    <div v-if="rejectPeriodModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-4">Return Payroll to Finance</h3>
        <p class="text-sm text-slate-400 mb-4">Add a note so Finance knows what to revise.</p>
        <textarea v-model="rejectPeriodNotes" rows="4" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" placeholder="Reason for returning…"></textarea>
        <div class="flex items-center justify-end gap-3 pt-5">
          <button @click="rejectPeriodModal = null" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl">Cancel</button>
          <button @click="rejectPeriod" class="text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white px-5 py-2.5 rounded-xl cursor-pointer">Return Payroll</button>
        </div>
      </div>
    </div>

  </div>
</template>
