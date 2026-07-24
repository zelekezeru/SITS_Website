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
  stats: { type: Object, default: () => ({ total: 0, exempt: 0, tracked: 0 }) },
});

const search = ref('');
const filter = ref('all'); // all | exempt | tracked

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase();
  return props.employees.filter((e) => {
    if (filter.value === 'exempt' && !e.attendance_exempt) return false;
    if (filter.value === 'tracked' && e.attendance_exempt) return false;
    if (!q) return true;
    return [e.name, e.staff_no, e.department, e.grade]
      .filter(Boolean)
      .some((v) => String(v).toLowerCase().includes(q));
  });
});

// ---- Toggle exemption ----------------------------------------------------
const busyId = ref(null);
const exemptModal = ref(null); // the employee being exempted
const reason = ref('');

const openExemptModal = (employee) => {
  exemptModal.value = employee;
  reason.value = '';
};

const submitExempt = () => {
  const emp = exemptModal.value;
  if (!emp) return;
  busyId.value = emp.id;
  router.post(`/admin/attendance-exemptions/${emp.id}/toggle`,
    { attendance_exempt: true, attendance_exempt_reason: reason.value },
    {
      preserveScroll: true,
      onSuccess: () => { exemptModal.value = null; reason.value = ''; },
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
    <div class="grid sm:grid-cols-3 gap-4">
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
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] rounded-full font-bold border bg-amber-500/10 border-amber-500/25 text-amber-400">
                    <Icon name="ShieldCheck" :size="12" /> Exempt
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
                <button v-else @click="returnToTracking(e)" :disabled="busyId === e.id"
                        class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-emerald-700 bg-slate-900/60 text-emerald-300 rounded-lg transition-colors cursor-pointer disabled:opacity-50">
                  Return to Tracking
                </button>
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
          <span class="font-semibold text-slate-200">{{ exemptModal.name }}</span> will no longer be synced from
          biometric logs and won't be marked absent. Add a reason for the record.
        </p>
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Reason (optional)</label>
        <textarea v-model="reason" rows="3" placeholder="e.g. Senior management — not required to badge in"
                  class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50"></textarea>
        <div class="flex items-center justify-end gap-3 pt-5">
          <button @click="exemptModal = null" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl cursor-pointer">Cancel</button>
          <button @click="submitExempt" :disabled="busyId === exemptModal.id"
                  class="text-xs font-semibold bg-amber-600 hover:bg-amber-500 text-white px-5 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50">
            Confirm Exemption
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
