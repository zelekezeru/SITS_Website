<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, reactive } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  policy: { type: Object, default: () => ({}) },
  components: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  periods: { type: Array, default: () => [] },
  scheduleTypes: { type: Array, default: () => [] },
});

const TABS = [
  { key: 'absence', label: 'Attendance & Absence', icon: 'CalendarX2' },
  { key: 'rates', label: 'Deductions & Tax', icon: 'Percent' },
  { key: 'people', label: 'Per Employee', icon: 'Users' },
];
const activeTab = ref('absence');

const money = (n) => Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// ─── Policy form ─────────────────────────────────────────────────────────────
// One form across every tab: the endpoint validates the whole policy set, and a
// single save keeps the constants internally consistent.
const groups = computed(() => props.policy ?? {});
const allFields = computed(() => Object.values(groups.value).flat());

const policyForm = useForm(
  Object.fromEntries(allFields.value.map(f => [f.key, f.value])),
);

const savePolicy = () => policyForm.post('/admin/payroll/config/policy', { preserveScroll: true });
const resetPolicy = () => policyForm.reset();

// ─── Live worked example (absence tab) ───────────────────────────────────────
const sample = reactive({ salary: 10000, allowances: 1500, days: 2 });

const example = computed(() => {
  const workingDays = Number(policyForm.working_days_per_month) || 26;
  const grace = Number(policyForm.absence_grace_days) || 0;
  const rate = Number(policyForm.absence_deduction_rate) || 0;
  const chargeable = policyForm.absence_deduction_enabled
    ? Math.max(sample.days - grace, 0)
    : 0;
  const basis = policyForm.absence_deduction_basis === 'gross'
    ? sample.salary + sample.allowances
    : sample.salary;
  const daily = basis / workingDays;

  return {
    workingDays,
    grace,
    chargeable,
    forgiven: Math.min(sample.days, grace),
    basis,
    daily,
    perDay: daily * rate,
    total: chargeable * daily * rate,
  };
});

// ─── Per-employee ────────────────────────────────────────────────────────────
const search = ref('');
const filter = ref('all');

const FILTERS = [
  { key: 'all', label: 'Everyone' },
  { key: 'pension', label: 'Pension scheme' },
  { key: 'pf', label: 'Provident Fund' },
  { key: 'statutory_exempt', label: 'Statutory exempt' },
  { key: 'attendance_exempt', label: 'Attendance exempt' },
  { key: 'assigned', label: 'Has assignments' },
];

const visibleEmployees = computed(() => {
  const q = search.value.trim().toLowerCase();

  return props.employees.filter((e) => {
    if (q && !(`${e.name} ${e.staff_no ?? ''} ${e.department ?? ''}`.toLowerCase().includes(q))) return false;

    switch (filter.value) {
      case 'pension': return !e.has_provident_fund && !e.statutory_exempt && !e.scheme_excluded_by_type;
      case 'pf': return e.has_provident_fund && !e.statutory_exempt && !e.scheme_excluded_by_type;
      case 'statutory_exempt': return e.statutory_exempt || e.scheme_excluded_by_type;
      case 'attendance_exempt': return e.attendance_exempt;
      case 'assigned': return e.assignments.length > 0;
      default: return true;
    }
  });
});

const schemeOf = (e) => {
  if (e.statutory_exempt) return { label: 'Exempt', tone: 'slate' };
  if (e.scheme_excluded_by_type) return { label: 'Excluded (type)', tone: 'slate' };
  return e.has_provident_fund
    ? { label: 'Provident Fund', tone: 'violet' }
    : { label: 'Pension', tone: 'blue' };
};

const toneClass = (tone) => ({
  blue: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  violet: 'bg-violet-500/10 border-violet-500/20 text-violet-400',
  amber: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
  emerald: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  rose: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
  slate: 'bg-slate-800/60 border-slate-800 text-slate-500',
}[tone]);

/** What one unpermitted absent day currently costs this employee, post-tax. */
const dailyAbsenceCost = (e) => {
  if (!policyForm.absence_deduction_enabled || e.attendance_exempt) return 0;
  const workingDays = Number(policyForm.working_days_per_month) || 26;
  const standing = e.assignments.filter(a => a.is_earning).reduce((s, a) => s + a.amount, 0);
  const basis = policyForm.absence_deduction_basis === 'gross' ? e.base_salary + standing : e.base_salary;

  return (basis / workingDays) * (Number(policyForm.absence_deduction_rate) || 0);
};

const standingTotal = (e, earning) => e.assignments
  .filter(a => a.is_earning === earning)
  .reduce((s, a) => s + a.amount, 0);

const expandedId = ref(null);

const profileForm = useForm({
  grade: '', has_provident_fund: false, statutory_exempt: false,
  attendance_exempt: false, attendance_exempt_reason: '',
});

const assignForm = useForm({
  payroll_component_id: '', amount: 0, schedule_type: 'monthly',
  start_period_id: '', end_period_id: '', note: '',
});

const toggleRow = (e) => {
  if (expandedId.value === e.id) {
    expandedId.value = null;
    return;
  }
  expandedId.value = e.id;
  profileForm.defaults({
    grade: e.grade ?? '',
    has_provident_fund: e.has_provident_fund,
    statutory_exempt: e.statutory_exempt,
    attendance_exempt: e.attendance_exempt,
    attendance_exempt_reason: e.attendance_exempt_reason ?? '',
  });
  profileForm.reset();
  profileForm.clearErrors();
  assignForm.reset();
  assignForm.clearErrors();
  assignFormOpen.value = false;
};

const saveProfile = (e) => profileForm.post(`/admin/payroll/config/employees/${e.id}`, {
  preserveScroll: true, preserveState: true,
});

const assignFormOpen = ref(false);

const submitAssignment = (e) => assignForm.post(`/admin/payroll/config/employees/${e.id}/assignments`, {
  preserveScroll: true,
  preserveState: true,
  onSuccess: () => { assignForm.reset(); assignFormOpen.value = false; },
});

const removeAssignment = async (e, a) => {
  const ok = await confirm({
    title: 'Remove assignment',
    message: `Remove "${a.component}" from ${e.name}? Payslips already computed keep their figures until the period is recomputed.`,
  });
  if (ok) {
    router.delete(`/admin/payroll/config/employees/${e.id}/assignments/${a.id}`, {
      preserveScroll: true, preserveState: true,
    });
  }
};

const needsStartPeriod = computed(() => assignForm.schedule_type !== 'monthly');
const needsEndPeriod = computed(() => assignForm.schedule_type === 'range');
</script>

<template>
  <Head title="Payroll Config — SITS ERP" />

  <div class="space-y-8 pb-24">
    <!-- Hero -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-emerald-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
          <Icon name="SlidersHorizontal" :size="26" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Finance · Payroll</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Payroll Config</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-3xl">
            The rules the calculator runs on. Set how unpermitted absence is charged, how deductions and tax are
            treated, and each employee's own payroll profile. Changes take effect the next time a period is
            recomputed — an already-approved or locked period keeps the figures it was approved with.
          </p>
          <div class="flex flex-wrap gap-2 mt-4">
            <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg border" :class="toneClass('emerald')">
              {{ employees.length }} active employees
            </span>
            <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg border" :class="toneClass('blue')">
              {{ components.length }} assignable components
            </span>
            <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg border"
                  :class="toneClass(policyForm.absence_deduction_enabled ? 'amber' : 'slate')">
              Absence deduction {{ policyForm.absence_deduction_enabled ? 'on' : 'off' }}
            </span>
          </div>
        </div>
      </div>
    </section>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-slate-900 overflow-x-auto">
      <button v-for="t in TABS" :key="t.key" @click="activeTab = t.key"
              class="px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors -mb-px flex items-center gap-2 whitespace-nowrap cursor-pointer"
              :class="activeTab === t.key ? 'border-emerald-500 text-white' : 'border-transparent text-slate-500 hover:text-slate-300'">
        <Icon :name="t.icon" :size="15" /> {{ t.label }}
      </button>
    </div>

    <!-- ════════════════ ATTENDANCE & ABSENCE ════════════════ -->
    <div v-show="activeTab === 'absence'" class="grid lg:grid-cols-5 gap-6">
      <!-- Policy controls -->
      <div class="lg:col-span-3 space-y-6">
        <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 space-y-5">
          <div class="flex items-center gap-2">
            <Icon name="CalendarX2" :size="16" class="text-amber-400" />
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Unpaid absence policy</h3>
          </div>

          <div v-for="f in groups.absence" :key="f.key" class="pt-1">
            <!-- Boolean -->
            <label v-if="f.type === 'boolean'" class="flex items-start gap-3 p-4 rounded-xl border border-slate-800 bg-slate-950/50 cursor-pointer hover:border-slate-700 transition-colors">
              <input type="checkbox" v-model="policyForm[f.key]" class="mt-0.5 rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-emerald-500/50" />
              <span>
                <span class="block text-sm font-bold text-slate-200">{{ f.label }}</span>
                <span class="block text-xs text-slate-500 mt-0.5">{{ f.help }}</span>
              </span>
            </label>

            <!-- Choice -->
            <div v-else-if="f.type === 'choice'">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ f.label }}</label>
              <div class="grid sm:grid-cols-2 gap-3">
                <label v-for="c in f.choices" :key="c.value"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl border cursor-pointer transition-colors"
                       :class="policyForm[f.key] === c.value ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">
                  <input type="radio" :value="c.value" v-model="policyForm[f.key]" class="bg-slate-900 border-slate-700 text-emerald-500 focus:ring-emerald-500/50" />
                  <span class="text-sm font-semibold text-slate-200">{{ c.label }}</span>
                </label>
              </div>
              <p class="text-xs text-slate-500 mt-2">{{ f.help }}</p>
            </div>

            <!-- Numeric -->
            <div v-else>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ f.label }}</label>
              <input v-model="policyForm[f.key]" type="number" :step="f.type === 'integer' ? 1 : 0.01" min="0"
                     class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-emerald-500/50" />
              <p class="text-xs text-slate-500 mt-1.5">{{ f.help }}</p>
            </div>
            <p v-if="policyForm.errors[f.key]" class="text-xs text-rose-400 mt-1">{{ policyForm.errors[f.key] }}</p>
          </div>
        </div>

        <!-- Where the deduction lands -->
        <div class="rounded-2xl border border-amber-500/20 bg-amber-500/[0.04] p-6">
          <div class="flex items-start gap-3">
            <Icon name="Info" :size="18" class="text-amber-400 shrink-0 mt-0.5" />
            <div class="space-y-2 text-sm">
              <p class="font-bold text-amber-300">Absence is withheld after tax</p>
              <p class="text-slate-400 leading-relaxed">
                Income tax is computed on earnings <em>before</em> any absence is considered — an absent day never
                reduces taxable income. The amount is then withheld from the taxed pay along with the other
                post-tax deductions, so one absent day costs the employee the full day's gross, not its net.
              </p>
              <p class="text-slate-500 text-xs">
                Only <strong class="text-slate-400">unpermitted</strong> days are charged: approved attendance
                permissions and mass permissions for closed days are subtracted first, then the grace allowance
                below. Employees flagged attendance-exempt are skipped entirely.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Live worked example -->
      <div class="lg:col-span-2">
        <div class="rounded-2xl border border-slate-900 bg-gradient-to-b from-slate-900/40 to-slate-950 p-6 space-y-5 lg:sticky lg:top-6">
          <div class="flex items-center gap-2">
            <Icon name="Calculator" :size="16" class="text-emerald-400" />
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">What it costs</h3>
          </div>
          <p class="text-xs text-slate-500 -mt-2">Recalculates as you edit the policy — nothing is saved until you do.</p>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Basic salary</label>
              <input v-model.number="sample.salary" type="number" min="0" step="100" class="w-full bg-slate-950/60 border border-slate-800 rounded-lg px-3 py-2 text-slate-100 text-sm focus:outline-none" />
            </div>
            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Allowances</label>
              <input v-model.number="sample.allowances" type="number" min="0" step="100" class="w-full bg-slate-950/60 border border-slate-800 rounded-lg px-3 py-2 text-slate-100 text-sm focus:outline-none" />
            </div>
            <div class="col-span-2">
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Unpermitted absent days</label>
              <input v-model.number="sample.days" type="number" min="0" max="31" step="1" class="w-full bg-slate-950/60 border border-slate-800 rounded-lg px-3 py-2 text-slate-100 text-sm focus:outline-none" />
            </div>
          </div>

          <div class="space-y-2.5 pt-4 border-t border-slate-900 text-sm">
            <div class="flex justify-between"><span class="text-slate-500">Deduction basis</span><span class="font-mono text-slate-300">{{ money(example.basis) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">÷ working days</span><span class="font-mono text-slate-300">{{ example.workingDays }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Daily rate</span><span class="font-mono text-slate-300">{{ money(example.daily) }}</span></div>
            <div class="flex justify-between" v-if="example.forgiven > 0">
              <span class="text-slate-500">Forgiven (grace)</span>
              <span class="font-mono text-emerald-400">−{{ example.forgiven }} day(s)</span>
            </div>
            <div class="flex justify-between"><span class="text-slate-500">Chargeable days</span><span class="font-mono text-slate-300">{{ example.chargeable }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Cost per absent day</span><span class="font-mono text-slate-300">{{ money(example.perDay) }}</span></div>
          </div>

          <div class="rounded-xl border p-4 flex items-center justify-between"
               :class="example.total > 0 ? 'border-rose-500/20 bg-rose-500/5' : 'border-slate-800 bg-slate-950/50'">
            <div>
              <p class="text-[10px] font-bold uppercase tracking-wider" :class="example.total > 0 ? 'text-rose-400' : 'text-slate-500'">Withheld post-tax</p>
              <p class="text-[10px] text-slate-600 mt-0.5">ETB, this month</p>
            </div>
            <p class="text-xl font-extrabold font-mono" :class="example.total > 0 ? 'text-rose-300' : 'text-slate-500'">
              {{ money(example.total) }}
            </p>
          </div>

          <p v-if="!policyForm.absence_deduction_enabled" class="text-xs text-slate-500 italic text-center">
            Absence deduction is switched off — absent days are recorded but cost nothing.
          </p>
        </div>
      </div>
    </div>

    <!-- ════════════════ DEDUCTIONS & TAX ════════════════ -->
    <div v-show="activeTab === 'rates'" class="grid lg:grid-cols-2 gap-6">
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 space-y-5">
        <div class="flex items-center gap-2">
          <Icon name="Percent" :size="16" class="text-blue-400" />
          <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Tax treatment & caps</h3>
        </div>

        <div v-for="f in groups.deductions" :key="f.key">
          <label v-if="f.type === 'boolean'" class="flex items-start gap-3 p-4 rounded-xl border border-slate-800 bg-slate-950/50 cursor-pointer hover:border-slate-700 transition-colors">
            <input type="checkbox" v-model="policyForm[f.key]" class="mt-0.5 rounded bg-slate-900 border-slate-700 text-blue-500 focus:ring-blue-500/50" />
            <span>
              <span class="block text-sm font-bold text-slate-200">{{ f.label }}</span>
              <span class="block text-xs text-slate-500 mt-0.5">{{ f.help }}</span>
            </span>
          </label>
          <div v-else>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ f.label }}</label>
            <input v-model="policyForm[f.key]" type="number" step="0.01" min="0"
                   class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-blue-500/50" />
            <p class="text-xs text-slate-500 mt-1.5">{{ f.help }}</p>
          </div>
          <p v-if="policyForm.errors[f.key]" class="text-xs text-rose-400 mt-1">{{ policyForm.errors[f.key] }}</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-4 text-xs text-slate-500 leading-relaxed">
          <strong class="text-slate-400">Deduction order.</strong> Income tax is computed first, on gross earnings
          less any pre-tax pension. Everything else — provident fund, salary advances, other deductions, loan
          repayments and unpaid absence — is withheld from the taxed pay.
          <a href="/admin/payroll/components" class="text-blue-400 hover:text-blue-300 font-semibold">Payroll Setup</a>
          defines the deduction titles themselves;
          <a href="/admin/tax" class="text-blue-400 hover:text-blue-300 font-semibold">Tax Configuration</a>
          holds the PIT bands.
        </div>
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 space-y-5">
        <div class="flex items-center gap-2">
          <Icon name="Clock" :size="16" class="text-violet-400" />
          <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Overtime multipliers</h3>
        </div>
        <p class="text-xs text-slate-500 -mt-2">
          Applied to the hourly rate, itself derived from the same working-days divisor as absence.
          Overtime pay is fully taxable.
        </p>

        <div v-for="f in groups.overtime" :key="f.key">
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ f.label }}</label>
          <input v-model="policyForm[f.key]" type="number" step="0.01" min="1" max="5"
                 class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-violet-500/50" />
          <p class="text-xs text-slate-500 mt-1.5">{{ f.help }}</p>
          <p v-if="policyForm.errors[f.key]" class="text-xs text-rose-400 mt-1">{{ policyForm.errors[f.key] }}</p>
        </div>
      </div>
    </div>

    <!-- ════════════════ PER EMPLOYEE ════════════════ -->
    <div v-show="activeTab === 'people'" class="space-y-5">
      <div class="flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[220px]">
          <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-600"><Icon name="Search" :size="16" /></span>
          <input v-model="search" type="search" placeholder="Search by name, staff no. or department…"
                 class="w-full bg-slate-950/60 border border-slate-800 rounded-xl pl-10 pr-4 py-2.5 text-slate-100 text-sm focus:outline-none focus:border-emerald-500/50" />
        </div>
        <div class="flex gap-1.5 flex-wrap">
          <button v-for="f in FILTERS" :key="f.key" @click="filter = f.key"
                  class="text-[11px] font-bold px-3 py-2 rounded-lg border transition-colors cursor-pointer"
                  :class="filter === f.key ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400' : 'border-slate-800 bg-slate-900/40 text-slate-500 hover:text-slate-300'">
            {{ f.label }}
          </button>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 overflow-hidden">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="border-b border-slate-900 text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-slate-950/40">
              <th class="p-3.5">Employee</th>
              <th class="p-3.5">Scheme</th>
              <th class="p-3.5 text-right">Basic salary</th>
              <th class="p-3.5 text-right">Standing +/−</th>
              <th class="p-3.5 text-right">Cost / absent day</th>
              <th class="p-3.5 text-center">Flags</th>
              <th class="p-3.5 w-10"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-900">
            <template v-for="e in visibleEmployees" :key="e.id">
              <tr class="hover:bg-slate-900/30 cursor-pointer" @click="toggleRow(e)">
                <td class="p-3.5">
                  <span class="block font-semibold text-slate-200">{{ e.name }}</span>
                  <span class="block text-[11px] text-slate-500">
                    {{ e.staff_no || '—' }} · {{ e.position || 'No position' }}<template v-if="e.grade"> · {{ e.grade }}</template>
                  </span>
                </td>
                <td class="p-3.5">
                  <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded border" :class="toneClass(schemeOf(e).tone)">
                    {{ schemeOf(e).label }}
                  </span>
                </td>
                <td class="p-3.5 text-right font-mono text-slate-300">{{ money(e.base_salary) }}</td>
                <td class="p-3.5 text-right font-mono text-xs">
                  <span v-if="standingTotal(e, true)" class="text-emerald-400">+{{ money(standingTotal(e, true)) }}</span>
                  <span v-if="standingTotal(e, true) && standingTotal(e, false)" class="text-slate-700 mx-1">/</span>
                  <span v-if="standingTotal(e, false)" class="text-rose-400">−{{ money(standingTotal(e, false)) }}</span>
                  <span v-if="!e.assignments.length" class="text-slate-600">—</span>
                </td>
                <td class="p-3.5 text-right font-mono text-xs" :class="dailyAbsenceCost(e) ? 'text-amber-400' : 'text-slate-600'">
                  {{ dailyAbsenceCost(e) ? money(dailyAbsenceCost(e)) : 'exempt' }}
                </td>
                <td class="p-3.5 text-center">
                  <span v-if="e.attendance_exempt" class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded border mr-1" :class="toneClass('emerald')">No absence ded.</span>
                  <span v-if="e.statutory_exempt" class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded border" :class="toneClass('amber')">No statutory</span>
                  <span v-if="!e.attendance_exempt && !e.statutory_exempt" class="text-slate-700 text-xs">—</span>
                </td>
                <td class="p-3.5 text-right text-slate-600">
                  <Icon :name="expandedId === e.id ? 'ChevronDown' : 'ChevronRight'" :size="16" />
                </td>
              </tr>

              <!-- Editor -->
              <tr v-if="expandedId === e.id" class="bg-slate-950/60">
                <td colspan="7" class="p-6">
                  <div class="grid lg:grid-cols-2 gap-6">
                    <!-- Profile flags -->
                    <div class="space-y-4">
                      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                        <Icon name="ShieldAlert" :size="14" /> Payroll profile
                      </h4>

                      <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Salary grade</label>
                        <input v-model="profileForm.grade" type="text" placeholder="e.g. G13-L5"
                               class="w-full bg-slate-950/60 border border-slate-800 rounded-lg px-3 py-2 text-slate-100 text-sm focus:outline-none" />
                      </div>

                      <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-800 bg-slate-950/50 cursor-pointer hover:border-slate-700 transition-colors">
                        <input type="checkbox" v-model="profileForm.has_provident_fund" class="mt-0.5 rounded bg-slate-900 border-slate-700 text-violet-500 focus:ring-violet-500/50" />
                        <span>
                          <span class="block text-sm font-bold text-slate-200">Provident Fund member</span>
                          <span class="block text-xs text-slate-500 mt-0.5">Contributes 5% / 12.5% to the PF instead of the 7% / 11% public pension.</span>
                        </span>
                      </label>

                      <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-800 bg-slate-950/50 cursor-pointer hover:border-slate-700 transition-colors">
                        <input type="checkbox" v-model="profileForm.statutory_exempt" class="mt-0.5 rounded bg-slate-900 border-slate-700 text-amber-500 focus:ring-amber-500/50" />
                        <span>
                          <span class="block text-sm font-bold text-slate-200">Statutory exempt</span>
                          <span class="block text-xs text-slate-500 mt-0.5">No pension and no provident fund on either side. Income tax still applies.</span>
                        </span>
                      </label>

                      <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-800 bg-slate-950/50 cursor-pointer hover:border-slate-700 transition-colors">
                        <input type="checkbox" v-model="profileForm.attendance_exempt" class="mt-0.5 rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-emerald-500/50" />
                        <span>
                          <span class="block text-sm font-bold text-slate-200">Attendance exempt</span>
                          <span class="block text-xs text-slate-500 mt-0.5">Skipped by attendance tracking and never charged for absent days.</span>
                        </span>
                      </label>

                      <div v-if="profileForm.attendance_exempt">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Reason (kept for audit)</label>
                        <input v-model="profileForm.attendance_exempt_reason" type="text" placeholder="e.g. Executive management"
                               class="w-full bg-slate-950/60 border border-slate-800 rounded-lg px-3 py-2 text-slate-100 text-sm focus:outline-none" />
                        <p v-if="profileForm.errors.attendance_exempt_reason" class="text-xs text-rose-400 mt-1">{{ profileForm.errors.attendance_exempt_reason }}</p>
                      </div>

                      <p v-if="e.scheme_excluded_by_type" class="text-xs text-amber-400/80 flex items-start gap-2">
                        <Icon name="Info" :size="14" class="shrink-0 mt-0.5" />
                        Part-time and contract staff are excluded from pension and provident fund regardless of these flags.
                      </p>

                      <button @click="saveProfile(e)" :disabled="profileForm.processing"
                              class="text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50">
                        {{ profileForm.processing ? 'Saving…' : 'Save profile' }}
                      </button>
                    </div>

                    <!-- Assignments -->
                    <div class="space-y-4">
                      <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                          <Icon name="Banknote" :size="14" /> Allowances & deductions
                        </h4>
                        <button @click="assignFormOpen = !assignFormOpen"
                                class="text-[11px] font-bold px-3 py-1.5 rounded-lg border cursor-pointer transition-colors" :class="toneClass('emerald')">
                          {{ assignFormOpen ? 'Cancel' : '+ Assign' }}
                        </button>
                      </div>

                      <div v-if="assignFormOpen" class="p-4 rounded-xl border border-slate-800 bg-slate-950/50 space-y-3">
                        <div class="grid sm:grid-cols-2 gap-3">
                          <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Component</label>
                            <select v-model="assignForm.payroll_component_id" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-slate-200 text-sm focus:outline-none">
                              <option value="" disabled>Select…</option>
                              <optgroup label="Allowances">
                                <option v-for="c in components.filter(x => x.is_earning)" :key="c.id" :value="c.id">{{ c.name }}</option>
                              </optgroup>
                              <optgroup label="Deductions">
                                <option v-for="c in components.filter(x => !x.is_earning)" :key="c.id" :value="c.id">{{ c.name }}</option>
                              </optgroup>
                            </select>
                          </div>
                          <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Amount (ETB)</label>
                            <input v-model="assignForm.amount" type="number" step="0.01" min="0" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-slate-200 text-sm focus:outline-none" />
                          </div>
                          <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Schedule</label>
                            <select v-model="assignForm.schedule_type" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-slate-200 text-sm focus:outline-none">
                              <option v-for="st in scheduleTypes" :key="st.value" :value="st.value">{{ st.label }}</option>
                            </select>
                          </div>
                          <div v-if="needsStartPeriod">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Start period</label>
                            <select v-model="assignForm.start_period_id" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-slate-200 text-sm focus:outline-none">
                              <option value="">— select —</option>
                              <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                          </div>
                          <div v-if="needsEndPeriod">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">End period</label>
                            <select v-model="assignForm.end_period_id" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-slate-200 text-sm focus:outline-none">
                              <option value="">— open-ended —</option>
                              <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                          </div>
                          <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Note (optional)</label>
                            <input v-model="assignForm.note" type="text" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-slate-200 text-sm focus:outline-none" />
                          </div>
                        </div>
                        <div class="flex justify-end">
                          <button @click="submitAssignment(e)" :disabled="assignForm.processing"
                                  class="text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl cursor-pointer disabled:opacity-50">
                            Save assignment
                          </button>
                        </div>
                      </div>

                      <div class="rounded-xl border border-slate-800 overflow-hidden">
                        <table class="w-full text-left text-sm">
                          <tbody class="divide-y divide-slate-800">
                            <tr v-if="!e.assignments.length">
                              <td class="px-4 py-6 text-center text-slate-600 text-xs italic">
                                Nothing assigned — this employee is paid basic salary plus statutory only.
                              </td>
                            </tr>
                            <tr v-for="a in e.assignments" :key="a.id" class="hover:bg-slate-900/30">
                              <td class="px-4 py-3">
                                <span class="font-medium text-slate-200">{{ a.component }}</span>
                                <span class="ml-2 text-[9px] uppercase px-1.5 py-0.5 rounded border font-bold tracking-wider"
                                      :class="toneClass(a.is_earning ? 'emerald' : 'rose')">{{ a.kind }}</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5">
                                  {{ a.schedule_label }}<template v-if="a.start_period"> · from {{ a.start_period }}</template><template v-if="a.end_period"> to {{ a.end_period }}</template>
                                  <template v-if="a.note"> · {{ a.note }}</template>
                                </span>
                              </td>
                              <td class="px-4 py-3 text-right font-mono" :class="a.is_earning ? 'text-emerald-400' : 'text-rose-400'">
                                {{ a.is_earning ? '+' : '−' }}{{ money(a.amount) }}
                              </td>
                              <td class="px-4 py-3 text-right w-10">
                                <button @click.stop="removeAssignment(e, a)" class="text-slate-600 hover:text-rose-400 transition-colors cursor-pointer">
                                  <Icon name="Trash2" :size="14" />
                                </button>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>

                      <p class="text-[11px] text-slate-600 leading-relaxed">
                        Assignments feed the next recompute. Statutory pension and provident-fund contributions are
                        not listed here — they apply automatically from the scheme flags above.
                      </p>
                    </div>
                  </div>
                </td>
              </tr>
            </template>

            <tr v-if="!visibleEmployees.length">
              <td colspan="7" class="p-10 text-center text-slate-500 italic">No employees match this filter.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Sticky policy save bar -->
    <div v-if="policyForm.isDirty && activeTab !== 'people'"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 flex items-center gap-4 px-5 py-3.5 rounded-2xl border border-emerald-500/25 bg-slate-900/95 backdrop-blur shadow-2xl">
      <span class="text-sm text-slate-300 font-semibold">Unsaved policy changes</span>
      <button @click="resetPolicy" class="text-xs font-semibold px-3 py-2 border border-slate-800 hover:border-slate-700 text-slate-400 rounded-xl cursor-pointer">Discard</button>
      <button @click="savePolicy" :disabled="policyForm.processing"
              class="text-xs font-bold bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-5 py-2 rounded-xl shadow-md cursor-pointer disabled:opacity-50">
        {{ policyForm.processing ? 'Saving…' : 'Save policy' }}
      </button>
    </div>
  </div>
</template>
