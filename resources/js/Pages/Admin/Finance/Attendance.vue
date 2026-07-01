<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  module: { type: Object, required: true },
  attendanceRecords: { type: Array, default: () => [] },
  attendanceLogs: { type: Array, default: () => [] },
  payrollPeriods: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({ duration_type: 'this_month', start_date: '', end_date: '' }) },
});

const activeTab = ref('summaries'); // 'summaries' or 'logs'
const showSyncModal = ref(false);

const filterForm = ref({
  duration_type: props.filters.duration_type,
  start_date: props.filters.start_date,
  end_date: props.filters.end_date,
});

const setDurationType = (type) => {
  filterForm.value.duration_type = type;
  if (type !== 'custom') {
    applyFilter();
  }
};

const applyFilter = () => {
  router.get(window.location.pathname, {
    duration_type: filterForm.value.duration_type,
    start_date: filterForm.value.start_date,
    end_date: filterForm.value.end_date,
  }, {
    preserveState: true,
    replace: true,
  });
};

const syncForm = useForm({
  payroll_period_id: '',
});

const submitSync = () => {
  syncForm.post('/admin/attendance-logs/sync', {
    onSuccess: () => {
      showSyncModal.value = false;
      syncForm.reset();
    },
  });
};
</script>

<template>
  <Head title="Attendance — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Clock" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Attendance summaries are calculated from either imported Excel device sheets or synced directly from real-time biometric terminal webhooks.
            </p>
          </div>
        </div>
        <Link
          href="/admin/attendance-imports/create"
          class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
        >
          Import Excel Sheet
        </Link>
      </div>
    </section>

    <!-- Filters Panel -->
    <div class="rounded-2xl border border-slate-900 bg-slate-950/40 p-4 flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Duration:</span>
        <div class="flex bg-slate-900 p-0.5 rounded-lg border border-slate-850">
          <button 
            @click="setDurationType('this_month')"
            class="px-3 py-1.5 text-xs font-medium rounded-md transition cursor-pointer"
            :class="filterForm.duration_type === 'this_month' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white'"
          >
            This Month
          </button>
          <button 
            @click="setDurationType('last_month')"
            class="px-3 py-1.5 text-xs font-medium rounded-md transition cursor-pointer"
            :class="filterForm.duration_type === 'last_month' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white'"
          >
            Last Month
          </button>
          <button 
            @click="setDurationType('custom')"
            class="px-3 py-1.5 text-xs font-medium rounded-md transition cursor-pointer"
            :class="filterForm.duration_type === 'custom' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white'"
          >
            Custom Range
          </button>
        </div>
      </div>

      <!-- Custom Date Inputs -->
      <div v-if="filterForm.duration_type === 'custom'" class="flex items-center gap-3 flex-wrap">
        <div class="flex items-center gap-2">
          <span class="text-xs text-slate-500">From</span>
          <input 
            type="date" 
            v-model="filterForm.start_date"
            class="px-3 py-1.5 text-xs rounded-lg bg-slate-900 border border-slate-850 text-white focus:outline-none focus:border-blue-500"
          />
        </div>
        <div class="flex items-center gap-2">
          <span class="text-xs text-slate-500">To</span>
          <input 
            type="date" 
            v-model="filterForm.end_date"
            class="px-3 py-1.5 text-xs rounded-lg bg-slate-900 border border-slate-850 text-white focus:outline-none focus:border-blue-500"
          />
        </div>
        <button 
          @click="applyFilter"
          class="px-4 py-1.5 text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition shadow-md shadow-blue-500/10 cursor-pointer"
        >
          Apply
        </button>
      </div>

      <!-- Date Range Display -->
      <div v-else class="text-xs text-slate-500">
        Range: <strong class="text-slate-300 font-mono">{{ filters.start_date }}</strong> to <strong class="text-slate-300 font-mono">{{ filters.end_date }}</strong>
      </div>
    </div>

    <!-- Tab Selector -->
    <div class="flex border-b border-slate-900 gap-6 text-sm mb-2">
      <button 
        @click="activeTab = 'summaries'" 
        class="pb-3 font-semibold transition-all cursor-pointer relative"
        :class="activeTab === 'summaries' ? 'text-blue-400' : 'text-slate-500 hover:text-slate-350'"
      >
        Monthly Summaries
        <span v-if="activeTab === 'summaries'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-500 rounded-full"></span>
      </button>
      <button 
        @click="activeTab = 'logs'" 
        class="pb-3 font-semibold transition-all cursor-pointer relative"
        :class="activeTab === 'logs' ? 'text-blue-400' : 'text-slate-500 hover:text-slate-350'"
      >
        Real-time Swipes
        <span v-if="activeTab === 'logs'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-500 rounded-full"></span>
      </button>
    </div>

    <!-- Summaries Tab -->
    <div v-if="activeTab === 'summaries'" class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Employee</th>
              <th class="pb-3">Period</th>
              <th class="pb-3">Regular Hours</th>
              <th class="pb-3">Late Min</th>
              <th class="pb-3">Absences</th>
              <th class="pb-3">OT (Normal / Night)</th>
              <th class="pb-3">OT (Rest / Holiday)</th>
              <th class="pb-3">Status</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="rec in attendanceRecords" :key="rec.id" class="hover:bg-slate-900/40">
              <td class="py-4 font-semibold text-slate-200">{{ rec.employee?.full_name_en }}</td>
              <td class="py-4 text-slate-450">{{ rec.payroll_period?.name }}</td>
              <td class="py-4 font-mono text-slate-300">{{ Number(rec.work_hours).toFixed(1) }}h</td>
              <td class="py-4 font-mono text-amber-500 font-semibold">{{ rec.late_minutes }}m</td>
              <td class="py-4 text-slate-400">
                <span class="text-rose-400 font-bold" v-if="rec.absent_days > 0">{{ rec.absent_days }}d absent</span>
                <span v-else>0 absences</span>
              </td>
              <td class="py-4 font-mono text-slate-400">
                <span>{{ Number(rec.overtime_normal).toFixed(1) }}h</span> /
                <span class="text-blue-400">{{ Number(rec.ot_night).toFixed(1) }}h</span>
              </td>
              <td class="py-4 font-mono text-slate-400">
                <span>{{ Number(rec.ot_rest).toFixed(1) }}h</span> /
                <span class="text-purple-400">{{ Number(rec.ot_holiday).toFixed(1) }}h</span>
              </td>
              <td class="py-4 capitalize">
                <span
                  class="px-2 py-0.5 text-[10px] rounded-full font-bold border"
                  :class="rec.status === 'verified'
                    ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400'
                    : 'bg-slate-800/60 border-slate-800 text-slate-500'"
                >
                  {{ rec.status }}
                </span>
              </td>
            </tr>
            <tr v-if="!attendanceRecords.length">
              <td colspan="8" class="py-8 text-center text-slate-650 italic">
                No attendance summaries found for the selected period.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Real-time Logs Tab -->
    <div v-if="activeTab === 'logs'" class="space-y-6">
      <div class="flex justify-between items-center flex-wrap gap-4">
        <div>
          <h3 class="text-lg font-bold text-slate-200">Biometric Live Swipes</h3>
          <p class="text-xs text-slate-500">Real-time attendance logs pushed directly from internet-connected HikVision terminals.</p>
        </div>
        <button 
          @click="showSyncModal = true"
          class="text-sm font-semibold bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-emerald-500/10 hover:shadow-emerald-500/20 cursor-pointer"
        >
          Sync Biometric Logs
        </button>
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
                <th class="pb-3">Timestamp</th>
                <th class="pb-3">Employee</th>
                <th class="pb-3">Device Code</th>
                <th class="pb-3">Device Name</th>
                <th class="pb-3">Direction</th>
              </tr>
            </thead>
            <tbody class="text-sm divide-y divide-slate-900">
              <tr v-for="log in attendanceLogs" :key="log.id" class="hover:bg-slate-900/40">
                <td class="py-4 font-mono text-slate-300">
                  {{ new Date(log.swipe_time).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' }) }}
                </td>
                <td class="py-4 font-semibold text-slate-200">{{ log.employee?.full_name_en || 'Unmatched Code' }}</td>
                <td class="py-4 font-mono text-slate-550">{{ log.device_employee_code }}</td>
                <td class="py-4 text-slate-450">{{ log.device_name || 'Hikvision Device' }}</td>
                <td class="py-4">
                  <span 
                    class="px-2.5 py-0.5 text-[10px] rounded-full font-bold uppercase tracking-wider border"
                    :class="log.direction === 'in' 
                      ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400' 
                      : (log.direction === 'out' ? 'bg-amber-500/10 border-amber-500/25 text-amber-400' : 'bg-slate-800/60 border-slate-800 text-slate-500')"
                  >
                    {{ log.direction }}
                  </span>
                </td>
              </tr>
              <tr v-if="!attendanceLogs.length">
                <td colspan="5" class="py-8 text-center text-slate-650 italic">
                  No live punches found for the selected period. Make sure your HikVision terminal is connected and configured to listen at `/hikvision/webhook`.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Sync Modal -->
    <div v-if="showSyncModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showSyncModal = false"></div>
      <div class="relative w-full max-w-md rounded-3xl border border-slate-900 bg-slate-950 p-6 shadow-2xl space-y-6">
        <div>
          <h3 class="text-xl font-bold text-white">Sync Biometric Swipes</h3>
          <p class="text-xs text-slate-400 mt-1">This will process all raw check-ins/outs for the selected month and compile them into employee payroll summaries.</p>
        </div>

        <form @submit.prevent="submitSync" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Select Payroll Period</label>
            <select 
              v-model="syncForm.payroll_period_id" 
              required
              class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-850 text-white text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            >
              <option value="" disabled>Choose monthly payroll period</option>
              <option v-for="period in payrollPeriods" :key="period.id" :value="period.id">
                {{ period.name }} ({{ period.start_date.split('T')[0] }} to {{ period.end_date.split('T')[0] }})
              </option>
            </select>
            <span v-if="syncForm.errors.payroll_period_id" class="text-xs text-rose-500 mt-1.5 block">
              {{ syncForm.errors.payroll_period_id }}
            </span>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button 
              type="button" 
              @click="showSyncModal = false"
              class="px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white transition cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="syncForm.processing"
              class="px-5 py-2.5 text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white rounded-xl transition shadow-md shadow-blue-500/10 cursor-pointer"
            >
              {{ syncForm.processing ? 'Syncing...' : 'Start Aggregation' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
