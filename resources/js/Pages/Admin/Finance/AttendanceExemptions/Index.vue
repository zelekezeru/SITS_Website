<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, default: () => ({}) },
  employees: { type: Array, default: () => [] },
  periods: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({ total: 0, exempt: 0, tracked: 0, oneMonth: 0 }) },
});

const search = ref('');
const filter = ref('all'); // all | exempt | one_month | tracked

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase();
  return props.employees.filter((e) => {
    if (filter.value === 'exempt' && !e.attendance_exempt) return false;
    if (filter.value === 'one_month' && !e.is_one_month) return false;
    if (filter.value === 'tracked' && e.attendance_exempt) return false;
    if (!q) return true;
    return [e.name, e.staff_no, e.department, e.grade]
      .filter(Boolean)
      .some((v) => String(v).toLowerCase().includes(q));
  });
});

// ---- Grant exemption -----------------------------------------------------
const busyId = ref(null);
const exemptModal = ref(null); // the employee being exempted
const reason = ref('');
const scope = ref('permanent'); // permanent | period
const periodId = ref('');
const formError = ref('');

const openExemptModal = (employee) => {
  exemptModal.value = employee;
  reason.value = employee.attendance_exempt_reason ?? '';
  scope.value = employee.is_one_month ? 'period' : 'permanent';
  periodId.value = employee.exempt_period_id ?? (props.periods[0]?.id ?? '');
  formError.value = '';
};

// A one-month exemption must say which month, and why.
const oneMonth = computed(() => scope.value === 'period');
const canSubmit = computed(() => !oneMonth.value || (periodId.value && reason.value.trim()));

const submitExempt = () => {
  const emp = exemptModal.value;
  if (!emp) return;

  if (!canSubmit.value) {
    formError.value = 'A one-month exemption needs both the month and a reason.';
    return;
  }

  busyId.value = emp.id;
  formError.value = '';
  router.post(`/admin/attendance-exemptions/${emp.id}/toggle`,
    {
      attendance_exempt: true,
      attendance_exempt_reason: reason.value,
      scope: scope.value,
      payroll_period_id: oneMonth.value ? periodId.value : null,
    },
    {
      preserveScroll: true,
      onSuccess: () => { exemptModal.value = null; reason.value = ''; },
      onError: (errors) => { formError.value = Object.values(errors)[0] ?? 'Could not save this exemption.'; },
      onFinish: () => { busyId.value = null; },
    });
};

const returnToTracking = async (employee) => {
  const ok = await confirm({
    title: 'Return to Attendance Tracking',
    message: `${employee.name} will be included in attendance sync again and can incur absence deductions. Continue?`,
  });
  if (!ok) return;
  busyId.value = employee.id;
  router.post(`/admin/attendance-exemptions/${employee.id}/toggle`,
    { attendance_exempt: false },
    { preserveScroll: true, onFinish: () => { busyId.value = null; } });
};
</script>

<template>
  <Head title="Attendance Exemptions — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-amber-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
          <Icon name="ShieldCheck" :size="26" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Finance &amp; HR</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Attendance Exemptions</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">
            Exempt employees are skipped by the biometric attendance sync and never incur unpaid-absence
            deductions — use it for management, remote or field staff who don't badge in.
          </p>
        </div>
      </div>
    </section>

    <!-- Stat tiles -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <button @click="filter = 'all'" class="text-left p-5 rounded-2xl border transition-all cursor-pointer"
              :class="filter === 'all' ? 'border-slate-700 bg-slate-900/40' : 'border-slate-900 bg-slate-900/10 hover:border-slate-850'">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Employees</p>
        <p class="text-3xl font-extrabold text-slate-100 mt-1">{{ stats.total }}</p>
      </button>
      <button @click="filter = 'exempt'" class="text-left p-5 rounded-2xl border transition-all cursor-pointer"
              :class="filter === 'exempt' ? 'border-amber-500/40 bg-amber-500/[0.07]' : 'border-slate-900 bg-slate-900/10 hover:border-slate-850'">
        <p class="text-xs font-semibold uppercase tracking-wider text-amber-500/80">Exempt</p>
        <p class="text-3xl font-extrabold text-amber-400 mt-1">{{ stats.exempt }}</p>
      </button>
      <button @click="filter = 'one_month'" class="text-left p-5 rounded-2xl border transition-all cursor-pointer"
              :class="filter === 'one_month' ? 'border-blue-500/40 bg-blue-500/[0.07]' : 'border-slate-900 bg-slate-900/10 hover:border-slate-850'">
        <p class="text-xs font-semibold uppercase tracking-wider text-blue-500/80">One month only</p>
        <p class="text-3xl font-extrabold text-blue-400 mt-1">{{ stats.oneMonth }}</p>
      </button>
      <button @click="filter = 'tracked'" class="text-left p-5 rounded-2xl border transition-all cursor-pointer"
              :class="filter === 'tracked' ? 'border-emerald-500/40 bg-emerald-500/[0.07]' : 'border-slate-900 bg-slate-900/10 hover:border-slate-850'">
        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-500/80">Tracked</p>
        <p class="text-3xl font-extrabold text-emerald-400 mt-1">{{ stats.tracked }}</p>
      </button>
    </div>

    <!-- Search -->
    <div class="flex items-center gap-3 rounded-2xl border border-slate-900 bg-slate-950/40 px-4 py-3">
      <Icon name="Search" :size="16" class="text-slate-500" />
      <input v-model="search" type="text" placeholder="Search by name, staff no, department…"
             class="flex-1 bg-transparent text-sm text-slate-100 placeholder-slate-600 focus:outline-none" />
      <span class="text-xs text-slate-600">{{ filtered.length }} shown</span>
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase bg-slate-950/40">
              <th class="px-5 py-3">Employee</th>
              <th class="px-5 py-3">Department</th>
              <th class="px-5 py-3">Grade</th>
              <th class="px-5 py-3">Attendance</th>
              <th class="px-5 py-3 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900/70">
            <tr v-for="e in filtered" :key="e.id" class="hover:bg-slate-900/30"
                :class="!e.is_active ? 'opacity-50' : ''">
              <td class="px-5 py-4">
                <p class="font-semibold text-slate-200">{{ e.name }}</p>
                <p class="text-[11px] text-slate-500 font-mono">{{ e.staff_no || '—' }}</p>
              </td>
              <td class="px-5 py-4 text-slate-400">{{ e.department || '—' }}</td>
              <td class="px-5 py-4 text-slate-400">{{ e.grade || '—' }}</td>
              <td class="px-5 py-4">
                <div v-if="e.attendance_exempt">
                  <span v-if="e.is_one_month" class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] rounded-full font-bold border bg-blue-500/10 border-blue-500/25 text-blue-400">
                    <Icon name="CalendarX2" :size="12" /> {{ e.exempt_period || 'One month' }} only
                  </span>
                  <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] rounded-full font-bold border bg-amber-500/10 border-amber-500/25 text-amber-400">
                    <Icon name="ShieldCheck" :size="12" /> Exempt — all periods
                  </span>
                  <p v-if="e.attendance_exempt_reason" class="text-[11px] text-slate-500 mt-1 max-w-xs truncate" :title="e.attendance_exempt_reason">
                    {{ e.attendance_exempt_reason }}
                  </p>
                </div>
                <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] rounded-full font-bold border bg-emerald-500/10 border-emerald-500/25 text-emerald-400">
                  <Icon name="Clock" :size="12" /> Tracked
                </span>
              </td>
              <td class="px-5 py-4 text-right">
                <button v-if="!e.attendance_exempt" @click="openExemptModal(e)" :disabled="busyId === e.id"
                        class="text-[11px] font-bold px-3 py-1.5 border border-amber-700/40 hover:border-amber-600 bg-amber-600/10 text-amber-300 rounded-lg transition-colors cursor-pointer disabled:opacity-50">
                  Make Exempt
                </button>
                <template v-else>
                  <button @click="openExemptModal(e)" :disabled="busyId === e.id"
                          class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-blue-700 bg-slate-900/60 text-blue-300 rounded-lg transition-colors cursor-pointer disabled:opacity-50 mr-1">
                    Edit
                  </button>
                  <button @click="returnToTracking(e)" :disabled="busyId === e.id"
                          class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-emerald-700 bg-slate-900/60 text-emerald-300 rounded-lg transition-colors cursor-pointer disabled:opacity-50">
                    Return to Tracking
                  </button>
                </template>
              </td>
            </tr>
            <tr v-if="!filtered.length">
              <td colspan="5" class="px-5 py-10 text-center text-slate-500 italic">No employees match this filter.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Exempt reason modal -->
    <div v-if="exemptModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-2">Exempt from Attendance</h3>
        <p class="text-sm text-slate-400 mb-5">
          <span class="font-semibold text-slate-200">{{ exemptModal.name }}</span> will not be marked absent for
          the periods this exemption covers.
        </p>

        <!-- Scope -->
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">How long?</label>
        <div class="grid sm:grid-cols-2 gap-3 mb-5">
          <label class="flex items-start gap-3 px-4 py-3 rounded-xl border cursor-pointer transition-colors"
                 :class="scope === 'permanent' ? 'border-amber-500/40 bg-amber-500/5' : 'border-slate-850 bg-slate-950/50 hover:border-slate-700'">
            <input type="radio" value="permanent" v-model="scope" class="mt-0.5 bg-slate-900 border-slate-700 text-amber-500 focus:ring-amber-500/50" />
            <span>
              <span class="block text-sm font-bold text-slate-200">Ongoing</span>
              <span class="block text-[11px] text-slate-500 mt-0.5">Every period, until returned to tracking.</span>
            </span>
          </label>
          <label class="flex items-start gap-3 px-4 py-3 rounded-xl border cursor-pointer transition-colors"
                 :class="scope === 'period' ? 'border-blue-500/40 bg-blue-500/5' : 'border-slate-850 bg-slate-950/50 hover:border-slate-700'">
            <input type="radio" value="period" v-model="scope" class="mt-0.5 bg-slate-900 border-slate-700 text-blue-500 focus:ring-blue-500/50" />
            <span>
              <span class="block text-sm font-bold text-slate-200">One month only</span>
              <span class="block text-[11px] text-slate-500 mt-0.5">A single payroll period; every other month is charged as normal.</span>
            </span>
          </label>
        </div>

        <div v-if="scope === 'period'" class="mb-5">
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
            Month <span class="text-rose-400">*</span>
          </label>
          <select v-model="periodId" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-blue-500/50">
            <option value="" disabled>— select a payroll period —</option>
            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
        </div>

        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
          Reason <span v-if="scope === 'period'" class="text-rose-400">*</span>
          <span v-else class="text-slate-600 normal-case font-normal">(optional)</span>
        </label>
        <textarea v-model="reason" rows="3"
                  :placeholder="scope === 'period' ? 'e.g. Field assignment for the whole of this month' : 'e.g. Senior management — not required to badge in'"
                  class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50"></textarea>

        <p v-if="formError" class="text-xs text-rose-400 bg-rose-500/10 border border-rose-500/20 rounded-lg px-3 py-2 mt-3">
          {{ formError }}
        </p>

        <div class="flex items-center justify-end gap-3 pt-5">
          <button @click="exemptModal = null" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl cursor-pointer">Cancel</button>
          <button @click="submitExempt" :disabled="busyId === exemptModal.id || !canSubmit"
                  class="text-xs font-semibold text-white px-5 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50"
                  :class="scope === 'period' ? 'bg-blue-600 hover:bg-blue-500' : 'bg-amber-600 hover:bg-amber-500'">
            {{ scope === 'period' ? 'Exempt this month' : 'Confirm Exemption' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
