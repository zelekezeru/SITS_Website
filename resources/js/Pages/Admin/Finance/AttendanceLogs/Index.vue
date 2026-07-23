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
  attendanceLogs: { type: Object, required: true }, // paginated response
  stats: { type: Object, required: true },
  employees: { type: Array, default: () => [] },
  payrollPeriods: { type: Array, default: () => [] },
  filters: {
    type: Object,
    default: () => ({ search: '', direction: '', match: '', start_date: '', end_date: '' }),
  },
});

const filterForm = ref({
  search: props.filters.search || '',
  direction: props.filters.direction || '',
  match: props.filters.match || '',
  start_date: props.filters.start_date || '',
  end_date: props.filters.end_date || '',
});

const activePayloadLog = ref(null);
const showSyncModal = ref(false);
const copiedEndpoint = ref(false);

const applyFilters = () => {
  router.get('/admin/attendance-logs', {
    search: filterForm.value.search,
    direction: filterForm.value.direction,
    match: filterForm.value.match,
    start_date: filterForm.value.start_date,
    end_date: filterForm.value.end_date,
  }, {
    preserveState: true,
    replace: true,
  });
};

const clearFilters = () => {
  filterForm.value = { search: '', direction: '', match: '', start_date: '', end_date: '' };
  applyFilters();
};

const copyWebhookUrl = () => {
  navigator.clipboard.writeText(props.stats.webhook_url);
  copiedEndpoint.value = true;
  setTimeout(() => { copiedEndpoint.value = false; }, 2000);
};

const syncForm = useForm({
  payroll_period_id: '',
});

// Manual reconciliation of an unmatched device code to an employee.
const reconcileLog = ref(null);
const reconcileForm = useForm({ device_employee_code: '', employee_id: '' });

const openReconcile = (log) => {
  reconcileLog.value = log;
  reconcileForm.clearErrors();
  reconcileForm.device_employee_code = log.device_employee_code;
  reconcileForm.employee_id = '';
};

const submitReconcile = () => {
  reconcileForm.post('/admin/attendance-logs/reconcile', {
    preserveScroll: true,
    onSuccess: () => {
      reconcileLog.value = null;
      reconcileForm.reset();
    },
  });
};

const submitSync = () => {
  syncForm.post('/admin/attendance-logs/sync', {
    onSuccess: () => {
      showSyncModal.value = false;
      syncForm.reset();
    },
  });
};

const formatJson = (data) => {
  if (!data) return '{}';
  if (typeof data === 'string') {
    try {
      return JSON.stringify(JSON.parse(data), null, 2);
    } catch (e) {
      return data;
    }
  }
  return JSON.stringify(data, null, 2);
};
</script>

<template>
  <Head title="Biometric & Webhook Logs — SITS ERP" />

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
              Real-time attendance swipes and raw event payloads pushed directly from internet-connected HikVision biometric access terminals.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3 shrink-0">
          <Link
            href="/admin/attendance"
            class="text-sm font-semibold bg-slate-900 hover:bg-slate-850 border border-slate-800 text-slate-300 px-4 py-2.5 rounded-xl transition cursor-pointer"
          >
            Attendance Summaries
          </Link>
          <button
            @click="showSyncModal = true"
            class="text-sm font-semibold bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-emerald-500/10 hover:shadow-emerald-500/20 cursor-pointer"
          >
            Sync to Payroll
          </button>
        </div>
      </div>
    </section>

    <!-- Webhook Endpoint Status Bar -->
    <div class="rounded-2xl border border-slate-900 bg-slate-950/60 p-5 flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-400 shrink-0">
          <Icon name="Globe" :size="20" />
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">HikVision Listener Endpoint:</span>
            <span
              class="px-2.5 py-0.5 text-[10px] rounded-full font-bold uppercase tracking-wider border flex items-center gap-1"
              :class="stats.is_authenticated ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400' : 'bg-amber-500/10 border-amber-500/25 text-amber-400'"
            >
              <Icon :name="stats.is_authenticated ? 'ShieldCheck' : 'AlertTriangle'" :size="12" />
              {{ stats.is_authenticated ? 'Authenticated' : 'Unauthenticated Warning' }}
            </span>
          </div>
          <p class="text-xs font-mono text-slate-300 mt-1 select-all">
            {{ stats.webhook_url }}
          </p>
        </div>
      </div>

      <button
        @click="copyWebhookUrl"
        class="text-xs font-semibold px-3 py-2 rounded-lg border border-slate-800 bg-slate-900 hover:bg-slate-850 text-slate-300 transition flex items-center gap-2 cursor-pointer"
      >
        <Icon :name="copiedEndpoint ? 'CheckCircle2' : 'Link2'" :size="14" />
        {{ copiedEndpoint ? 'Copied Endpoint!' : 'Copy Endpoint URL' }}
      </button>
    </div>

    <!-- Stats Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5 space-y-1">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Recorded Swipes</p>
        <p class="text-2xl font-extrabold text-white font-mono">{{ stats.total_swipes.toLocaleString() }}</p>
        <p class="text-[11px] text-slate-500">Punches logged in database</p>
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5 space-y-1">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Today's Swipes</p>
        <p class="text-2xl font-extrabold text-blue-400 font-mono">{{ stats.today_swipes.toLocaleString() }}</p>
        <p class="text-[11px] text-slate-500">Received today</p>
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5 space-y-1">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Matched Swipes</p>
        <p class="text-2xl font-extrabold text-emerald-400 font-mono">{{ stats.matched_swipes.toLocaleString() }}</p>
        <p class="text-[11px] text-slate-500">Linked to an employee</p>
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5 space-y-1">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Unmatched Device Codes</p>
        <p class="text-2xl font-extrabold text-amber-400 font-mono">{{ stats.unmatched_swipes.toLocaleString() }}</p>
        <p class="text-[11px] text-slate-500">Requires employee matching</p>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="rounded-2xl border border-slate-900 bg-slate-950/40 p-4 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
        <!-- Search Input -->
        <div class="relative md:col-span-2">
          <input
            type="text"
            v-model="filterForm.search"
            @keyup.enter="applyFilters"
            placeholder="Search employee, device code, or device name..."
            class="w-full pl-9 pr-4 py-2 text-xs rounded-xl bg-slate-900 border border-slate-850 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500"
          />
          <span class="absolute left-3 top-2.5 text-slate-500">
            <Icon name="Search" :size="14" />
          </span>
        </div>

        <!-- Direction Dropdown -->
        <div>
          <select
            v-model="filterForm.direction"
            @change="applyFilters"
            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-900 border border-slate-850 text-white focus:outline-none focus:border-blue-500"
          >
            <option value="">All Directions</option>
            <option value="in">Check-IN</option>
            <option value="out">Check-OUT</option>
            <option value="unknown">Unknown</option>
          </select>
        </div>

        <!-- Employee Match Dropdown -->
        <div>
          <select
            v-model="filterForm.match"
            @change="applyFilters"
            class="w-full px-3 py-2 text-xs rounded-xl bg-slate-900 border border-slate-850 text-white focus:outline-none focus:border-blue-500"
          >
            <option value="">All Swipes</option>
            <option value="matched">Matched Employees Only</option>
            <option value="unmatched">Unmatched Codes Only</option>
          </select>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2">
          <button
            @click="applyFilters"
            class="w-full py-2 text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white rounded-xl transition shadow-md shadow-blue-500/10 cursor-pointer"
          >
            Filter
          </button>
          <button
            @click="clearFilters"
            title="Reset filters"
            class="p-2 text-xs font-semibold bg-slate-900 hover:bg-slate-850 border border-slate-800 text-slate-400 hover:text-white rounded-xl transition cursor-pointer"
          >
            <Icon name="RotateCcw" :size="14" />
          </button>
        </div>
      </div>

      <!-- Date Range Inputs -->
      <div class="flex items-center gap-3 pt-1 text-xs border-t border-slate-900 flex-wrap">
        <span class="text-slate-500 font-semibold uppercase tracking-wider text-[10px]">Filter Date Range:</span>
        <div class="flex items-center gap-2">
          <span class="text-slate-500">From</span>
          <input
            type="date"
            v-model="filterForm.start_date"
            @change="applyFilters"
            class="px-3 py-1 text-xs rounded-lg bg-slate-900 border border-slate-850 text-white focus:outline-none focus:border-blue-500"
          />
        </div>
        <div class="flex items-center gap-2">
          <span class="text-slate-500">To</span>
          <input
            type="date"
            v-model="filterForm.end_date"
            @change="applyFilters"
            class="px-3 py-1 text-xs rounded-lg bg-slate-900 border border-slate-850 text-white focus:outline-none focus:border-blue-500"
          />
        </div>
      </div>
    </div>

    <!-- Attendance Logs Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase tracking-wider">
              <th class="pb-3">Swipe Time</th>
              <th class="pb-3">Employee</th>
              <th class="pb-3">Device Code</th>
              <th class="pb-3">Device Name</th>
              <th class="pb-3">Direction</th>
              <th class="pb-3 text-right">Raw Event Payload</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="log in attendanceLogs.data" :key="log.id" class="hover:bg-slate-900/40 transition-colors">
              <!-- Swipe Time -->
              <td class="py-4 font-mono text-slate-300 text-xs">
                {{ new Date(log.swipe_time).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'medium' }) }}
              </td>

              <!-- Employee Name -->
              <td class="py-4">
                <div v-if="log.employee" class="flex items-center gap-2">
                  <span class="font-semibold text-slate-200">{{ log.employee.full_name_en }}</span>
                  <span v-if="log.employee.staff_no" class="text-[10px] font-mono text-slate-500 bg-slate-900 px-1.5 py-0.5 rounded border border-slate-800">
                    {{ log.employee.staff_no }}
                  </span>
                </div>
                <div v-else class="flex items-center gap-2">
                  <span class="flex items-center gap-1.5 text-amber-400 text-xs font-semibold">
                    <Icon name="AlertTriangle" :size="13" />
                    <span>Unmatched Code</span>
                  </span>
                  <button
                    @click="openReconcile(log)"
                    class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-md border border-blue-500/30 bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 transition cursor-pointer"
                  >
                    Assign
                  </button>
                </div>
              </td>

              <!-- Device Employee Code -->
              <td class="py-4 font-mono text-xs text-slate-400">
                {{ log.device_employee_code }}
              </td>

              <!-- Device Name -->
              <td class="py-4 text-slate-400 text-xs">
                {{ log.device_name || 'Hikvision Terminal' }}
              </td>

              <!-- Direction -->
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

              <!-- View Payload Action -->
              <td class="py-4 text-right">
                <button
                  @click="activePayloadLog = log"
                  class="px-3 py-1 text-xs font-medium rounded-lg bg-slate-900 hover:bg-slate-850 border border-slate-800 text-blue-400 hover:text-blue-300 transition cursor-pointer"
                >
                  View Payload
                </button>
              </td>
            </tr>

            <!-- Empty State -->
            <tr v-if="!attendanceLogs.data || !attendanceLogs.data.length">
              <td colspan="6" class="py-12 text-center text-slate-500 italic space-y-2">
                <p>No biometric attendance logs found for the selected filters.</p>
                <p class="text-xs text-slate-600">Ensure your HikVision device is pointing to <code class="text-slate-400 font-mono">{{ stats.webhook_url }}</code></p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Controls -->
      <div v-if="attendanceLogs.links && attendanceLogs.links.length > 3" class="flex items-center justify-between pt-6 border-t border-slate-900 text-xs text-slate-500">
        <p>Showing {{ attendanceLogs.from || 0 }} to {{ attendanceLogs.to || 0 }} of {{ attendanceLogs.total || 0 }} log entries</p>
        <div class="flex items-center gap-1">
          <Link
            v-for="(link, idx) in attendanceLogs.links"
            :key="idx"
            :href="link.url || '#'"
            v-html="link.label"
            class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition"
            :class="link.active
              ? 'bg-blue-600 border-blue-500 text-white'
              : (link.url ? 'bg-slate-900 border-slate-850 text-slate-400 hover:text-white' : 'bg-slate-950 border-slate-900 text-slate-650 cursor-not-allowed')"
          />
        </div>
      </div>
    </div>

    <!-- View Payload Modal -->
    <div v-if="activePayloadLog" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="activePayloadLog = null"></div>
      <div class="relative w-full max-w-2xl rounded-3xl border border-slate-900 bg-slate-950 p-6 shadow-2xl space-y-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h3 class="text-lg font-bold text-white">HikVision Raw Event Payload</h3>
            <p class="text-xs text-slate-400 mt-1">
              Log #{{ activePayloadLog.id }} · Swipe at {{ new Date(activePayloadLog.swipe_time).toLocaleString() }}
            </p>
          </div>
          <button
            @click="activePayloadLog = null"
            class="p-2 text-slate-500 hover:text-white transition rounded-lg bg-slate-900 border border-slate-850 cursor-pointer"
          >
            <Icon name="X" :size="16" />
          </button>
        </div>

        <div class="rounded-xl border border-slate-900 bg-slate-900/60 p-4 max-h-96 overflow-y-auto font-mono text-xs text-emerald-400 select-all">
          <pre>{{ formatJson(activePayloadLog.raw_payload) }}</pre>
        </div>

        <div class="flex justify-between items-center pt-2">
          <div class="text-xs text-slate-500 font-mono">
            Code: {{ activePayloadLog.device_employee_code }} | Direction: {{ activePayloadLog.direction }}
          </div>
          <button
            @click="activePayloadLog = null"
            class="px-5 py-2 text-xs font-semibold bg-slate-900 hover:bg-slate-850 border border-slate-800 text-slate-200 rounded-xl transition cursor-pointer"
          >
            Close
          </button>
        </div>
      </div>
    </div>

    <!-- Reconcile / Assign Employee Modal -->
    <div v-if="reconcileLog" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="reconcileLog = null"></div>
      <div class="relative w-full max-w-md rounded-3xl border border-slate-900 bg-slate-950 p-6 shadow-2xl space-y-6">
        <div>
          <h3 class="text-xl font-bold text-white">Link Device Code to Employee</h3>
          <p class="text-xs text-slate-400 mt-1">
            Assign device code
            <code class="text-blue-400 font-mono bg-slate-900 px-1.5 py-0.5 rounded border border-slate-800">{{ reconcileLog.device_employee_code }}</code>
            to an employee. This updates all past logs with this code and links future punches automatically.
          </p>
        </div>

        <form @submit.prevent="submitReconcile" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Employee</label>
            <select
              v-model="reconcileForm.employee_id"
              required
              class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-850 text-white text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            >
              <option value="" disabled>Choose an employee…</option>
              <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                {{ emp.full_name_en }}<template v-if="emp.staff_no"> ({{ emp.staff_no }})</template><template v-if="emp.device_employee_code"> — already code {{ emp.device_employee_code }}</template>
              </option>
            </select>
            <span v-if="reconcileForm.errors.employee_id" class="text-xs text-rose-500 mt-1.5 block">
              {{ reconcileForm.errors.employee_id }}
            </span>
            <span v-if="reconcileForm.errors.device_employee_code" class="text-xs text-rose-500 mt-1.5 block">
              {{ reconcileForm.errors.device_employee_code }}
            </span>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button
              type="button"
              @click="reconcileLog = null"
              class="px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white transition cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="reconcileForm.processing || !reconcileForm.employee_id"
              class="px-5 py-2.5 text-xs font-semibold bg-blue-600 hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl transition shadow-md shadow-blue-500/10 cursor-pointer"
            >
              {{ reconcileForm.processing ? 'Linking…' : 'Link Employee' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Sync to Payroll Modal -->
    <div v-if="showSyncModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showSyncModal = false"></div>
      <div class="relative w-full max-w-md rounded-3xl border border-slate-900 bg-slate-950 p-6 shadow-2xl space-y-6">
        <div>
          <h3 class="text-xl font-bold text-white">Sync Biometric Swipes</h3>
          <p class="text-xs text-slate-400 mt-1">This will process raw check-ins/outs for the selected period and compile them into employee payroll summaries.</p>
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
