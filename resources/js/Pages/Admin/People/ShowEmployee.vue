<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
  employee:          { type: Object, required: true },
  jobDescriptions:   { type: Array, default: () => [] },
  deliverables:      { type: Array, default: () => [] },
  fortnights:        { type: Array, default: () => [] },
  targets:           { type: Array, default: () => [] },
  evaluationPeriods: { type: Array, default: () => [] },
  credentials:       { type: Object, default: null },
});

// ─── Tabs ─────────────────────────────────────────────────────────────────────
const activeTab = ref('details');
const tabs = [
  { key: 'details',       label: 'Details',          icon: 'User' },
  { key: 'tasks',         label: 'Tasks',             icon: 'ListChecks' },
  { key: 'deliverables',  label: 'Deliverables',      icon: 'Flag' },
  { key: 'payrolls',      label: 'Payrolls',          icon: 'Banknote' },
  { key: 'jd',            label: 'Job Descriptions',  icon: 'FileText' },
  { key: 'evaluations',   label: 'Evaluations',       icon: 'Star' },
  { key: 'kpis',          label: 'KPIs',              icon: 'Gauge' },
  { key: 'attendance',    label: 'Attendance',        icon: 'Clock' },
  { key: 'payslips',      label: 'Payslips',          icon: 'Receipt' },
  { key: 'reports',       label: 'Reports',           icon: 'BarChart2' },
  { key: 'documents',     label: 'Documents',         icon: 'FolderOpen' },
];

// ─── Helpers ──────────────────────────────────────────────────────────────────
const fmt = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
const fmtCurrency = (n) => `ETB ${Number(n || 0).toLocaleString('en-GB', { minimumFractionDigits: 2 })}`;

const emp = props.employee;

const initials = computed(() =>
  (emp.full_name_en || 'E').split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase()
);

const statusBadge = computed(() => {
  const status = emp.status || (emp.is_active ? 'active' : 'inactive');
  const map = {
    active: 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400',
    on_leave: 'bg-amber-500/10 border-amber-500/25 text-amber-400',
    suspended: 'bg-rose-500/10 border-rose-500/25 text-rose-455',
    terminated: 'bg-red-500/10 border-red-500/25 text-red-400',
    inactive: 'bg-slate-800/60 border-slate-800 text-slate-500',
  };
  return map[status] || 'bg-slate-800/60 border-slate-800 text-slate-500';
});

const scoreColor = (score) => {
  const n = Number(score || 0);
  if (n >= 90) return 'text-emerald-400';
  if (n >= 75) return 'text-blue-400';
  if (n >= 60) return 'text-amber-400';
  return 'text-rose-400';
};

const scoreBg = (score) => {
  const n = Number(score || 0);
  if (n >= 90) return 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400';
  if (n >= 75) return 'bg-blue-500/10 border-blue-500/25 text-blue-400';
  if (n >= 60) return 'bg-amber-500/10 border-amber-500/25 text-amber-400';
  return 'bg-rose-500/10 border-rose-500/25 text-rose-400';
};

const taskStatusBg = (status) => ({
  completed: 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400',
  in_progress: 'bg-blue-500/10 border-blue-500/25 text-blue-400',
  overdue: 'bg-rose-500/10 border-rose-500/25 text-rose-400',
  pending: 'bg-slate-800/60 border-slate-800 text-slate-500',
}[status] || 'bg-slate-800/60 border-slate-800 text-slate-500');

const avgScore = computed(() => {
  if (!emp.evaluations?.length) return null;
  const total = emp.evaluations.reduce((s, e) => s + Number(e.final_score || 0), 0);
  return (total / emp.evaluations.length).toFixed(1);
});

const completedTasks = computed(() =>
  (emp.tasks || []).filter(t => t.status === 'completed' || t.status === 'done').length
);

const totalAttendanceHours = computed(() =>
  (emp.attendance_records || []).reduce((s, a) => s + Number(a.hours_worked || 0), 0).toFixed(1)
);

// ─── Credentials / password recovery ──────────────────────────────────────────
const showDefaultPassword = ref(false);

const sendResetLink = async () => {
  const confirmed = await confirm({
    title: 'Send Password Reset Link',
    message: `Email a password recovery link to ${emp.user?.email}?`,
  });
  if (confirmed) {
    router.post(`/admin/employees/${emp.id}/send-reset-link`);
  }
};

const fmtDateTime = (d) => d ? new Date(d).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : null;

// ─── inline forms ─────────────────────────────────────────────────────────────

// Task form
const taskForm = useForm({
  employee_id: emp.id,
  target_id: '',
  title: '',
  description: '',
  cadence: 'fortnight',
  starting_date: '',
  due_date: '',
  weight: 5,
  status: 'pending',
  completion_pct: 0,
});
const taskModalOpen = ref(false);

const submitTask = () => {
  taskForm.post('/admin/tasks', {
    onSuccess: () => {
      taskModalOpen.value = false;
      taskForm.reset();
    }
  });
};

// Deliverable form
const deliverableForm = useForm({
  fortnight_id: '',
  user_id: emp.user_id || '',
  name: '',
  deadline: '',
  is_completed: false,
});
const deliverableModalOpen = ref(false);

const submitDeliverable = () => {
  deliverableForm.post('/admin/deliverables', {
    onSuccess: () => {
      deliverableModalOpen.value = false;
      deliverableForm.reset();
    }
  });
};

// Job Description form
const jdForm = useForm({
  position_id: emp.position_id || '',
  title_en: emp.position ? emp.position.title_en : '',
  title_am: emp.position ? emp.position.title_am || '' : '',
  body: '',
  items: [
    { category: 'responsibility', title_en: 'Core Job Duties', is_kpi: false }
  ]
});
const jdModalOpen = ref(false);

const addJdItem = () => {
  jdForm.items.push({ category: 'responsibility', title_en: '', is_kpi: false });
};
const removeJdItem = (idx) => {
  if (jdForm.items.length > 1) jdForm.items.splice(idx, 1);
};

const submitJd = () => {
  jdForm.post('/admin/job-descriptions', {
    onSuccess: () => {
      jdModalOpen.value = false;
      jdForm.reset();
    }
  });
};

// Document form
const { confirm } = useConfirm();

const documentForm = useForm({
  name: '',
  documentable_type: 'App\\Models\\Employee',
  documentable_id: emp.id,
  file_path: '',
  file: null,
  upload_type: 'file',
});
const documentModalOpen = ref(false);

const onDocFileChange = (e) => {
  documentForm.file = e.target.files[0];
};

const submitDocument = () => {
  documentForm.post('/admin/documents', {
    onSuccess: () => {
      documentModalOpen.value = false;
      documentForm.reset();
    }
  });
};

const deleteDoc = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Document',
    message: 'Are you sure you want to delete this document?',
  });
  if (confirmed) {
    router.delete(`/admin/documents/${id}`);
  }
};

// Narrative Report form
const reportForm = useForm({
  employee_id: emp.id,
  evaluation_period_id: '',
  language: 'en',
  body: '',
});
const reportModalOpen = ref(false);

const submitReport = () => {
  reportForm.post('/admin/ai-analysis/reports', {
    onSuccess: () => {
      reportModalOpen.value = false;
      reportForm.reset();
    }
  });
};

// Lifecycle Status Form
const statusModalOpen = ref(false);
const statusForm = useForm({
  status: emp.status || 'active',
  reason: '',
  notes: '',
  effective_date: new Date().toISOString().substring(0, 10),
});

const submitStatusChange = () => {
  statusForm.post(`/admin/employees/${emp.id}/status`, {
    onSuccess: () => {
      statusModalOpen.value = false;
      statusForm.reset('reason', 'notes');
    }
  });
};

const activePreviewImage = ref(null);

const isImage = (path) => {
  if (!path) return false;
  const ext = path.split('.').pop().toLowerCase();
  return ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'bmp'].includes(ext);
};

const isWebLink = (path) => {
  if (!path) return false;
  return path.startsWith('http://') || path.startsWith('https://');
};

const handleOpenDocument = (path) => {
  if (isWebLink(path)) {
    window.open(path, '_blank');
  } else if (isImage(path)) {
    activePreviewImage.value = '/' + path;
  } else {
    // It's a document: download it
    const link = document.createElement('a');
    link.href = '/' + path;
    link.download = path.split('/').pop() || 'document';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
};
</script>

<template>
  <Head :title="`${employee.full_name_en} — Employee Profile`" />

  <div class="space-y-6">

    <!-- ── Back navigation ─────────────────────────────────────────────────── -->
    <div class="flex items-center gap-3">
      <Link
        href="/admin/employees"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-400 hover:text-white bg-slate-900/60 hover:bg-slate-900 border border-slate-900 hover:border-slate-700 px-4 py-2 rounded-xl transition-all"
      >
        <Icon name="ArrowLeft" :size="15" />
        Back to Employees
      </Link>
    </div>
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-96 h-96 rounded-full bg-blue-600/8 blur-[120px] pointer-events-none"></div>

      <div class="relative z-10 space-y-6">
        <!-- Main profile row -->
        <div class="flex items-start gap-6 flex-wrap">
          <!-- Avatar -->
          <div class="shrink-0 w-20 h-20 rounded-2xl bg-gradient-to-tr from-blue-500/30 to-purple-500/30 border border-slate-800 flex items-center justify-center text-2xl font-black text-white">
            {{ initials }}
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
              <h2 class="text-2xl font-extrabold tracking-tight text-white">{{ employee.full_name_en }}</h2>
              <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border capitalize" :class="statusBadge">
                {{ employee.status || (employee.is_active ? 'active' : 'inactive') }}
              </span>
              <button
                @click="statusModalOpen = true"
                class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border border-blue-500/25 bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 transition-all cursor-pointer"
              >
                Manage Status
              </button>
            </div>
            <p v-if="employee.full_name_am" class="text-slate-400 mt-0.5">{{ employee.full_name_am }}</p>
            <div class="flex items-center gap-4 mt-2 flex-wrap text-sm text-slate-400">
              <span v-if="employee.position" class="flex items-center gap-1.5">
                <Icon name="Briefcase" :size="14" class="text-slate-600" />
                {{ employee.position.title_en }}
              </span>
              <span v-if="employee.department" class="flex items-center gap-1.5">
                <Icon name="Building2" :size="14" class="text-slate-600" />
                {{ employee.department.name_en }}
              </span>
              <span class="flex items-center gap-1.5">
                <Icon name="Hash" :size="14" class="text-slate-600" />
                {{ employee.staff_no }}
              </span>
              <span class="flex items-center gap-1.5">
                <Icon name="Mail" :size="14" class="text-slate-600" />
                {{ employee.user?.email || '—' }}
              </span>
            </div>
          </div>

          <!-- Quick stats -->
          <div class="flex gap-4 flex-wrap shrink-0">
            <div class="text-center px-4 py-3 rounded-2xl border border-slate-900 bg-slate-950/50">
              <div class="text-xl font-black text-blue-400">{{ (employee.tasks || []).length }}</div>
              <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Tasks</div>
            </div>
            <div class="text-center px-4 py-3 rounded-2xl border border-slate-900 bg-slate-950/50">
              <div class="text-xl font-black" :class="avgScore ? scoreColor(avgScore) : 'text-slate-600'">
                {{ avgScore ? `${avgScore}%` : '—' }}
              </div>
              <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Avg Score</div>
            </div>
            <div class="text-center px-4 py-3 rounded-2xl border border-slate-900 bg-slate-950/50">
              <div class="text-xl font-black text-emerald-400">{{ fmtCurrency(employee.base_salary) }}</div>
              <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Base Salary</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════════════
         TOP NAVIGATION TABS
    ═══════════════════════════════════════════════════════════ -->
    <div class="overflow-x-auto">
      <div class="flex border-b border-slate-900 gap-0.5 min-w-max">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          class="flex items-center gap-1.5 pb-3 px-3 text-xs font-bold tracking-wide transition-colors cursor-pointer whitespace-nowrap"
          :class="activeTab === tab.key
            ? 'text-blue-400 border-b-2 border-blue-500'
            : 'text-slate-500 hover:text-slate-300'"
        >
          <Icon :name="tab.icon" :size="13" />
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB: DETAILS
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'details'" class="space-y-6">
      <div class="grid md:grid-cols-2 gap-6">
        <!-- Personal / Employment Info -->
        <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 space-y-4">
          <h3 class="text-sm font-bold text-slate-300 flex items-center gap-2">
            <Icon name="User" :size="15" class="text-blue-400" /> Employment Details
          </h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between py-2 border-b border-slate-900/60">
              <span class="text-slate-500">Staff Number</span>
              <span class="font-mono font-bold text-slate-200">{{ employee.staff_no }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-slate-900/60">
              <span class="text-slate-500">Employment Type</span>
              <span class="capitalize text-slate-200">{{ employee.employment_type?.replace('_', ' ') || '—' }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-slate-900/60">
              <span class="text-slate-500">Hired</span>
              <span class="text-slate-200">{{ fmt(employee.hired_at) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-slate-900/60">
              <span class="text-slate-500">Daily Hour Limit</span>
              <span class="text-slate-200">{{ employee.legal_daily_hour_limit }}h</span>
            </div>
            <div class="flex justify-between py-2">
              <span class="text-slate-500">Base Salary</span>
              <span class="font-bold text-emerald-400">{{ fmtCurrency(employee.base_salary) }}</span>
            </div>
          </div>
        </div>

        <!-- Reporting structure -->
        <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 space-y-4">
          <h3 class="text-sm font-bold text-slate-300 flex items-center gap-2">
            <Icon name="Users" :size="15" class="text-purple-400" /> Reporting Structure
          </h3>
          <div class="space-y-3 text-sm">
            <div v-if="employee.reporting_to" class="flex items-center gap-3 py-2 border-b border-slate-900/60">
              <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-slate-700 to-slate-800 border border-slate-700 flex items-center justify-center text-[10px] font-bold text-slate-300 shrink-0">
                {{ (employee.reporting_to.full_name_en || 'M').charAt(0) }}
              </div>
              <div>
                <p class="text-slate-200 font-semibold">{{ employee.reporting_to.full_name_en }}</p>
                <p class="text-[11px] text-slate-500">{{ employee.reporting_to.position?.title_en || 'Manager' }}</p>
              </div>
              <span class="ml-auto text-[10px] text-slate-600 font-semibold">Reports To</span>
            </div>
            <div v-else class="py-2 border-b border-slate-900/60 text-slate-600 italic text-xs">No direct manager assigned.</div>

            <div v-if="employee.subordinates?.length">
              <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600 mb-2">Direct Reports ({{ employee.subordinates.length }})</p>
              <div class="space-y-2">
                <div v-for="sub in employee.subordinates" :key="sub.id" class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center text-[9px] font-bold text-slate-400 shrink-0">
                    {{ (sub.full_name_en || 'S').charAt(0) }}
                  </div>
                  <span class="text-xs text-slate-300">{{ sub.full_name_en }}</span>
                  <span class="text-[10px] text-slate-600 ml-auto">{{ sub.position?.title_en }}</span>
                </div>
              </div>
            </div>
            <div v-else class="text-xs text-slate-600 italic">No direct reports.</div>
          </div>
        </div>
      </div>

      <!-- Account & Security -->
      <div v-if="credentials" class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 space-y-4">
        <h3 class="text-sm font-bold text-slate-300 flex items-center gap-2">
          <Icon name="KeyRound" :size="15" class="text-rose-400" /> Account & Security
        </h3>
        <div class="space-y-3 text-sm">
          <div class="flex justify-between items-center py-2 border-b border-slate-900/60">
            <span class="text-slate-500">Password Status</span>
            <span
              class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border"
              :class="credentials.password_changed
                ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400'
                : 'bg-amber-500/10 border-amber-500/25 text-amber-400'"
            >
              {{ credentials.password_changed ? 'Changed by employee' : 'Default password active' }}
            </span>
          </div>

          <div v-if="!credentials.password_changed" class="flex justify-between items-center py-2 border-b border-slate-900/60 gap-3">
            <span class="text-slate-500 shrink-0">Default Password</span>
            <div class="flex items-center gap-2">
              <span class="font-mono text-slate-200">
                {{ showDefaultPassword ? (credentials.default_password || '—') : '••••••••' }}
              </span>
              <button
                @click="showDefaultPassword = !showDefaultPassword"
                class="text-slate-500 hover:text-slate-300 transition-colors cursor-pointer"
                :title="showDefaultPassword ? 'Hide' : 'Show'"
              >
                <Icon :name="showDefaultPassword ? 'EyeOff' : 'Eye'" :size="15" />
              </button>
            </div>
          </div>

          <div class="flex justify-between items-center py-2">
            <div>
              <span class="text-slate-500">Password Recovery</span>
              <p v-if="credentials.reset_link_requested_at" class="text-[11px] text-slate-600 mt-0.5">
                Last link sent {{ fmtDateTime(credentials.reset_link_requested_at) }}
              </p>
            </div>
            <button
              @click="sendResetLink"
              class="text-xs font-semibold bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/25 px-3 py-1.5 rounded-xl transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <Icon name="Mail" :size="13" /> Send Reset Link
            </button>
          </div>
        </div>
      </div>

      <!-- Lifecycle History Timeline -->
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 space-y-4">
        <h3 class="text-sm font-bold text-slate-300 flex items-center gap-2">
          <Icon name="History" :size="15" class="text-amber-450" /> Status & Lifecycle History
        </h3>
        
        <div v-if="!employee.status_changes || !employee.status_changes.length" class="py-6 text-center text-slate-600 italic text-xs border border-dashed border-slate-900 rounded-xl">
          No historical status transitions recorded.
        </div>

        <div v-else class="space-y-4 relative before:absolute before:left-3.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-900">
          <div v-for="change in employee.status_changes" :key="change.id" class="flex gap-4 relative pl-8">
            <div class="absolute left-1.5 top-1.5 w-4.5 h-4.5 rounded-full border-2 border-slate-950 bg-slate-900 flex items-center justify-center">
              <div class="w-1.5 h-1.5 rounded-full bg-amber-450"></div>
            </div>
            <div class="flex-1 min-w-0 bg-slate-950/40 border border-slate-900 rounded-xl p-4 space-y-2">
              <div class="flex items-center justify-between gap-3 flex-wrap">
                <p class="text-xs font-semibold text-slate-305">
                  Transitioned from 
                  <span class="text-slate-500 capitalize">{{ change.from_status || 'none' }}</span> 
                  to 
                  <span class="text-emerald-400 capitalize font-bold">{{ change.to_status }}</span>
                </p>
                <span class="text-[10px] text-slate-550 font-mono">{{ fmt(change.effective_date) }}</span>
              </div>
              <p v-if="change.reason" class="text-xs text-slate-400">
                <span class="text-slate-550 font-semibold">Reason / Trigger:</span> {{ change.reason }}
              </p>
              <p v-if="change.notes" class="text-xs text-slate-500 italic">
                "{{ change.notes }}"
              </p>
              <div class="flex items-center gap-1.5 text-[10px] text-slate-550 pt-1 border-t border-slate-900/60" v-if="change.changed_by">
                <Icon name="User" :size="10" />
                <span>Changed by: {{ change.changed_by?.name || 'System' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB: TASKS
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'tasks'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Tasks Assigned</h3>
        <button
          @click="taskModalOpen = true"
          class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-3.5 py-2 rounded-xl transition-all cursor-pointer inline-flex items-center gap-1.5"
        >
          + Create Task
        </button>
      </div>
      <div v-if="!(employee.tasks || []).length" class="py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
        No tasks assigned to this employee yet.
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 overflow-hidden" v-else>
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-slate-900 bg-slate-950/40">
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Task</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Target Link</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Due</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Progress</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-900/60 text-sm">
              <tr v-for="task in employee.tasks" :key="task.id" class="hover:bg-slate-900/30 transition-colors">
                <td class="px-5 py-3.5">
                  <p class="font-semibold text-slate-200">{{ task.title }}</p>
                  <p v-if="task.description" class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">{{ task.description }}</p>
                </td>
                <td class="px-5 py-3.5">
                  <span v-if="task.target" class="text-[11px] bg-slate-900/60 border border-slate-800 px-2 py-1 rounded-lg text-slate-400">
                    {{ task.target.name }}
                  </span>
                  <span v-else class="text-slate-700 italic text-xs">—</span>
                </td>
                <td class="px-5 py-3.5 text-slate-400 text-xs">{{ fmt(task.due_date) }}</td>
                <td class="px-5 py-3.5">
                  <div class="flex flex-col items-center gap-1">
                    <div class="w-20 h-1.5 bg-slate-900 rounded-full overflow-hidden">
                      <div
                        class="h-full rounded-full transition-all"
                        :class="Number(task.completion_pct||0)===100 ? 'bg-emerald-500'
                              : Number(task.completion_pct||0)>=50   ? 'bg-blue-500'
                              : 'bg-amber-500'"
                        :style="`width:${task.completion_pct||0}%`"
                      ></div>
                    </div>
                    <span class="text-[10px] font-mono text-slate-500">{{ Math.round(task.completion_pct||0) }}%</span>
                  </div>
                </td>
                <td class="px-5 py-3.5">
                  <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-lg border" :class="taskStatusBg(task.status)">
                    {{ (task.status || 'pending').replace('_', ' ') }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="border-t border-slate-900 px-5 py-3 bg-slate-950/30 text-xs text-slate-500">
          {{ (employee.tasks || []).length }} task(s) · {{ completedTasks }} completed
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB: DELIVERABLES
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'deliverables'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Deliverables List</h3>
        <button
          @click="deliverableModalOpen = true"
          class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-3.5 py-2 rounded-xl transition-all cursor-pointer inline-flex items-center gap-1.5"
        >
          + Create Deliverable
        </button>
      </div>
      <div v-if="!deliverables.length" class="py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
        No deliverables assigned to this employee.
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4" v-else>
        <div
          v-for="del in deliverables"
          :key="del.id"
          class="rounded-2xl border p-5 transition-all"
          :class="del.is_completed
            ? 'border-emerald-900/40 bg-emerald-950/10'
            : 'border-slate-900 bg-slate-900/10 hover:border-slate-800'"
        >
          <div class="flex items-start gap-3">
            <div
              class="w-5 h-5 rounded-full shrink-0 mt-0.5 flex items-center justify-center border-2 transition-all"
              :class="del.is_completed ? 'bg-emerald-500 border-emerald-400' : 'border-slate-700'"
            >
              <Icon v-if="del.is_completed" name="Check" :size="10" class="text-white" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-sm" :class="del.is_completed ? 'text-slate-500 line-through' : 'text-white'">
                {{ del.name }}
              </p>
              <div class="flex items-center gap-3 mt-2 text-xs text-slate-500 flex-wrap">
                <span v-if="del.deadline" class="flex items-center gap-1">
                  <Icon name="Clock" :size="11" /> {{ fmt(del.deadline) }}
                </span>
                <span v-if="del.fortnight" class="flex items-center gap-1">
                  <Icon name="CalendarRange" :size="11" /> {{ del.fortnight.name }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB: PAYROLLS
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'payrolls'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Payrolls Log</h3>
        <Link
          href="/admin/payroll"
          class="text-xs font-semibold bg-slate-900 hover:bg-slate-800 border border-slate-850 hover:border-slate-700 text-slate-200 px-3.5 py-2 rounded-xl transition-all cursor-pointer inline-flex items-center gap-1.5"
        >
          <Icon name="ArrowRight" :size="14" /> Go to Payroll Page
        </Link>
      </div>

      <div v-if="!employee.payslips || !employee.payslips.length"
        class="py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
        No payroll records for this employee.
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 overflow-hidden" v-else>
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-slate-900 bg-slate-950/40">
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Period</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Gross</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Tax Deducted</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Pension</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Net</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-900/60 text-sm">
              <tr v-for="slip in employee.payslips" :key="slip.id" class="hover:bg-slate-900/30 transition-colors">
                <td class="px-5 py-3 text-slate-300 font-semibold">{{ slip.payroll_period?.name || '—' }}</td>
                <td class="px-5 py-3 text-right font-mono text-slate-300">{{ fmtCurrency(slip.gross) }}</td>
                <td class="px-5 py-3 text-right font-mono text-rose-400">-{{ fmtCurrency(slip.income_tax) }}</td>
                <td class="px-5 py-3 text-right font-mono text-slate-400">{{ fmtCurrency(slip.employee_pension) }}</td>
                <td class="px-5 py-3 text-right font-mono font-bold text-emerald-400">{{ fmtCurrency(slip.net_pay) }}</td>
                <td class="px-5 py-3">
                  <span
                    class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-lg border"
                    :class="slip.status === 'paid'
                      ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400'
                      : slip.status === 'locked'
                        ? 'bg-blue-500/10 border-blue-500/25 text-blue-400'
                        : 'bg-amber-500/10 border-amber-500/25 text-amber-400'"
                  >
                    {{ slip.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB: JOB DESCRIPTIONS
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'jd'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Job Descriptions</h3>
        <button
          @click="jdModalOpen = true"
          class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-3.5 py-2 rounded-xl transition-all cursor-pointer inline-flex items-center gap-1.5"
        >
          + Create Job Description
        </button>
      </div>
      <div v-if="!jobDescriptions.length" class="py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
        No job description linked to {{ employee.position?.title_en || 'this position' }}.
      </div>

      <div v-for="jd in jobDescriptions" :key="jd.id" class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 space-y-4">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h3 class="font-bold text-white">{{ jd.title_en }}</h3>
            <p v-if="jd.title_am" class="text-sm text-slate-500 mt-0.5">{{ jd.title_am }}</p>
          </div>
          <span class="text-[10px] font-bold text-slate-500 bg-slate-900/60 border border-slate-800 px-2.5 py-1 rounded-lg">
            v{{ jd.current_version?.version_no || '—' }}
          </span>
        </div>

        <div v-if="jd.current_version?.items?.length" class="space-y-2">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600">JD Items</p>
          <div class="space-y-1.5">
            <div
              v-for="(item, idx) in jd.current_version.items"
              :key="idx"
              class="flex items-start gap-3 text-sm py-2 border-b border-slate-900/40 last:border-0"
            >
              <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border mt-0.5 shrink-0"
                :class="item.is_kpi
                  ? 'bg-amber-500/10 border-amber-500/25 text-amber-400'
                  : 'bg-slate-800/60 border-slate-800 text-slate-500'">
                {{ item.category }}
              </span>
              <span class="text-slate-300 flex-1">{{ item.title_en }}</span>
              <span v-if="item.weight" class="text-xs text-slate-600 shrink-0">{{ item.weight }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB: EVALUATIONS
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'evaluations'" class="space-y-4">
      <div v-if="!(employee.evaluations || []).length" class="py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
        No evaluation scorecards recorded for this employee.
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4" v-else>
        <div
          v-for="ev in employee.evaluations"
          :key="ev.id"
          class="rounded-2xl border border-slate-900 bg-slate-900/10 p-5 hover:border-slate-800 transition-all"
        >
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-slate-400 bg-slate-900/60 border border-slate-800 px-2 py-1 rounded-lg">
              {{ ev.period?.name || 'No period' }}
            </span>
            <span v-if="ev.grade_band" class="text-[10px] font-bold px-2 py-0.5 rounded-lg border" :class="scoreBg(ev.final_score)">
              {{ ev.grade_band.label_en }}
            </span>
          </div>

          <!-- Score breakdown -->
          <div class="space-y-2 mt-4">
            <div class="flex justify-between text-xs text-slate-500">
              <span>Auto (40%)</span>
              <span class="font-mono font-bold" :class="scoreColor(ev.auto_score)">{{ Number(ev.auto_score||0).toFixed(1) }}</span>
            </div>
            <div class="flex justify-between text-xs text-slate-500">
              <span>Manager (40%)</span>
              <span class="font-mono font-bold" :class="scoreColor(ev.manager_score)">{{ Number(ev.manager_score||0).toFixed(1) }}</span>
            </div>
            <div class="flex justify-between text-xs text-slate-500">
              <span>Executive (20%)</span>
              <span class="font-mono font-bold" :class="scoreColor(ev.executive_score)">{{ Number(ev.executive_score||0).toFixed(1) }}</span>
            </div>
          </div>

          <!-- Final score bar -->
          <div class="mt-4 pt-3 border-t border-slate-900">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600">Final Score</span>
              <span class="font-mono text-sm font-black" :class="scoreColor(ev.final_score)">
                {{ Number(ev.final_score||0).toFixed(1) }}%
              </span>
            </div>
            <div class="w-full h-1.5 bg-slate-900 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all"
                :class="Number(ev.final_score||0) >= 90 ? 'bg-emerald-500'
                      : Number(ev.final_score||0) >= 75 ? 'bg-blue-500'
                      : Number(ev.final_score||0) >= 60 ? 'bg-amber-500' : 'bg-rose-500'"
                :style="`width:${ev.final_score||0}%`"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB: KPIS
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'kpis'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Assigned KPIs</h3>
        <Link
          href="/admin/kpis"
          class="text-xs font-semibold bg-slate-900 hover:bg-slate-800 border border-slate-850 hover:border-slate-700 text-slate-200 px-3.5 py-2 rounded-xl transition-all cursor-pointer inline-flex items-center gap-1.5"
        >
          <Icon name="ArrowRight" :size="14" />
          Manage KPIs
        </Link>
      </div>

      <div v-if="!employee.kpis || !employee.kpis.length" class="py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
        No KPIs assigned to this employee.
      </div>

      <div class="grid sm:grid-cols-2 gap-5" v-else>
        <div 
          v-for="kpi in employee.kpis" 
          :key="kpi.id" 
          class="rounded-2xl border border-slate-900 bg-slate-900/10 p-5 hover:border-slate-850 transition-all flex flex-col justify-between"
        >
          <div class="space-y-3">
            <div class="flex justify-between items-start gap-3">
              <span 
                class="px-2 py-0.5 text-[9px] rounded font-bold uppercase tracking-wider border"
                :class="kpi.confirmed_by 
                  ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' 
                  : kpi.approved_by 
                    ? 'bg-blue-500/10 border-blue-500/20 text-blue-400' 
                    : 'bg-slate-800/60 border-slate-800 text-slate-500'"
              >
                {{ kpi.confirmed_by ? 'Confirmed' : kpi.approved_by ? 'Approved' : 'Draft / Created' }}
              </span>
              <span class="text-xs font-mono font-bold text-slate-500">Weight: {{ kpi.weight }}</span>
            </div>

            <h4 class="font-bold text-white leading-snug">{{ kpi.title_en }}</h4>
            <p v-if="kpi.title_am" class="text-xs text-slate-500 mt-0.5">{{ kpi.title_am }}</p>

            <div class="space-y-2 pt-2 border-t border-slate-900 text-xs text-slate-450">
              <div class="flex justify-between">
                <span>Target Value:</span>
                <span class="text-slate-300 font-medium">{{ kpi.target_value }} {{ kpi.unit }} ({{ kpi.measure_type }})</span>
              </div>
              <div class="flex justify-between">
                <span>Association:</span>
                <span class="text-slate-300 font-medium flex items-center gap-1">
                  <template v-if="kpi.kpiable_type === 'App\\Models\\Target'">
                    🎯 Target: <span class="text-blue-400 font-semibold">{{ kpi.kpiable?.name || '—' }}</span>
                  </template>
                  <template v-else-if="kpi.kpiable_type === 'App\\Models\\JobDescriptionVersion'">
                    📄 JD: <span class="text-purple-400 font-semibold">{{ kpi.kpiable?.job_description?.title_en || '—' }} (v{{ kpi.kpiable?.version_no }})</span>
                  </template>
                  <template v-else>
                    <span class="text-slate-500 italic">Standalone KPI</span>
                  </template>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB: ATTENDANCE
    ═══════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'attendance'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Attendance Log</h3>
        <span class="text-[11px] text-slate-600 italic">Sourced from imported device exports</span>
      </div>
      <div v-if="!(employee.attendance_records || []).length" class="py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
        No attendance records for this employee.
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 overflow-hidden" v-else>
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-slate-900 bg-slate-950/40">
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Period</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Days</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Hours Worked</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Overtime</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Absent</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-900/60 text-sm">
              <tr v-for="att in employee.attendance_records" :key="att.id" class="hover:bg-slate-900/30 transition-colors">
                <td class="px-5 py-3 text-slate-300">{{ att.payroll_period?.name || '—' }}</td>
                <td class="px-5 py-3 text-center font-mono text-slate-300">{{ att.days_worked ?? '—' }}</td>
                <td class="px-5 py-3 text-center font-mono text-slate-300">{{ att.hours_worked ? `${att.hours_worked}h` : '—' }}</td>
                <td class="px-5 py-3 text-center font-mono text-amber-400">{{ att.overtime_hours ? `${att.overtime_hours}h` : '—' }}</td>
                <td class="px-5 py-3 text-center font-mono text-rose-400">{{ att.absent_days ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB: PAYSLIPS
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'payslips'" class="space-y-4">
      <div v-if="!(employee.payslips || []).length" class="py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
        No payslips generated for this employee.
      </div>

      <div class="grid sm:grid-cols-2 gap-4" v-else>
        <div
          v-for="slip in employee.payslips"
          :key="slip.id"
          class="rounded-2xl border border-slate-900 bg-slate-900/10 hover:border-slate-800 transition-all p-5"
        >
          <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-bold text-slate-200">{{ slip.payroll_period?.label || 'Payslip' }}</span>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border"
              :class="slip.is_paid
                ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400'
                : 'bg-amber-500/10 border-amber-500/25 text-amber-400'">
              {{ slip.is_paid ? 'Paid' : 'Pending' }}
            </span>
          </div>

          <div class="space-y-2 text-xs">
            <div class="flex justify-between text-slate-400">
              <span>Gross Pay</span>
              <span class="font-mono text-slate-300">{{ fmtCurrency(slip.gross_pay) }}</span>
            </div>
            <div class="flex justify-between text-slate-400">
              <span>Deductions</span>
              <span class="font-mono text-rose-400">- {{ fmtCurrency(slip.total_deductions) }}</span>
            </div>
            <div class="flex justify-between font-bold pt-2 border-t border-slate-900">
              <span class="text-slate-300">Net Pay</span>
              <span class="font-mono text-emerald-400 text-sm">{{ fmtCurrency(slip.net_pay) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB: REPORTS
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'reports'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Narrative Reports</h3>
        <button
          @click="reportModalOpen = true"
          class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-3.5 py-2 rounded-xl transition-all cursor-pointer inline-flex items-center gap-1.5"
        >
          + Submit Narrative Report
        </button>
      </div>
      <div v-if="!(employee.narrative_reports || []).length" class="py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
        No narrative reports submitted by this employee.
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 overflow-hidden" v-else>
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-slate-900 bg-slate-950/40">
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Report</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Period</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Submitted</th>
                <th class="px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-900/60 text-sm">
              <tr v-for="rpt in employee.narrative_reports" :key="rpt.id" class="hover:bg-slate-900/30 transition-colors">
                <td class="px-5 py-3">
                  <p class="font-semibold text-slate-200">{{ rpt.title || 'Report' }}</p>
                </td>
                <td class="px-5 py-3 text-slate-400">{{ rpt.period?.name || '—' }}</td>
                <td class="px-5 py-3 text-slate-400 text-xs">{{ fmt(rpt.submitted_at) }}</td>
                <td class="px-5 py-3">
                  <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-lg border"
                    :class="rpt.status === 'approved'
                      ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400'
                      : rpt.status === 'submitted'
                        ? 'bg-blue-500/10 border-blue-500/25 text-blue-400'
                        : 'bg-slate-800/60 border-slate-800 text-slate-500'">
                    {{ rpt.status || 'draft' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB: DOCUMENTS
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'documents'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Secure Document Vault</h3>
        <button
          @click="documentModalOpen = true"
          class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-3.5 py-2 rounded-xl transition-all cursor-pointer inline-flex items-center gap-1.5"
        >
          + Upload Document
        </button>
      </div>

      <div v-if="!employee.documents || !employee.documents.length"
        class="py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
        No documents uploaded for this employee.
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 overflow-hidden" v-else>
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-slate-900 bg-slate-950/40 text-xs text-slate-500 uppercase">
                <th class="px-5 py-3">Name</th>
                <th class="px-5 py-3">Path / Link</th>
                <th class="px-5 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-900/60 text-sm">
              <tr v-for="doc in employee.documents" :key="doc.id" class="hover:bg-slate-900/30 transition-colors">
                <td class="px-5 py-3 text-slate-200 font-semibold">{{ doc.title }}</td>
                <td class="px-5 py-3 text-slate-400 font-mono text-xs">{{ doc.path }}</td>
                <td class="px-5 py-3 text-right space-x-2">
                  <button
                    @click="handleOpenDocument(doc.path)"
                    class="text-[11px] font-bold px-3 py-1.5 bg-blue-600/10 hover:bg-blue-600/20 border border-blue-500/20 text-blue-400 rounded-lg transition-colors cursor-pointer"
                  >
                    Open
                  </button>
                  <button
                    @click="deleteDoc(doc.id)"
                    class="text-[11px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-rose-950/20 border border-slate-850 hover:border-rose-900/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         MODAL: CREATE TASK
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="taskModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-white mb-6">Create Strategic Task</h3>

        <form @submit.prevent="submitTask" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Task Title</label>
            <input v-model="taskForm.title" type="text" required placeholder="Task title..." class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Description</label>
            <textarea v-model="taskForm.description" placeholder="Description details..." class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none h-20 resize-none"></textarea>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Link Strategic Target</label>
            <select v-model="taskForm.target_id" required class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
              <option value="" disabled>Select Target</option>
              <option v-for="target in targets" :key="target.id" :value="target.id">{{ target.name }}</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Starting Date</label>
              <input v-model="taskForm.starting_date" type="date" required class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Due Date</label>
              <input v-model="taskForm.due_date" type="date" required class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Cadence</label>
              <select v-model="taskForm.cadence" class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
                <option value="fortnight">Fortnightly</option>
                <option value="daily">Daily</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Weight (1-10)</label>
              <input v-model="taskForm.weight" type="number" min="1" max="10" required class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="taskModalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer">Cancel</button>
            <button type="submit" :disabled="taskForm.processing" class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-650 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 cursor-pointer">Save Task</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         MODAL: CREATE DELIVERABLE
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="deliverableModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-6">Create Deliverable</h3>

        <form @submit.prevent="submitDeliverable" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Deliverable Name</label>
            <input v-model="deliverableForm.name" type="text" required placeholder="Deliverable name..." class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Link Fortnight Sprint</label>
            <select v-model="deliverableForm.fortnight_id" required class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
              <option value="" disabled>Select Fortnight</option>
              <option v-for="fn in fortnights" :key="fn.id" :value="fn.id">{{ fn.name }} ({{ fmt(fn.start_date) }} - {{ fmt(fn.end_date) }})</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Deadline</label>
            <input v-model="deliverableForm.deadline" type="date" required class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="deliverableModalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer">Cancel</button>
            <button type="submit" :disabled="deliverableForm.processing" class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-650 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 cursor-pointer">Save Deliverable</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         MODAL: CREATE JOB DESCRIPTION
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="jdModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-white mb-6">Create Job Description</h3>

        <form @submit.prevent="submitJd" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Title (English)</label>
              <input v-model="jdForm.title_en" type="text" required placeholder="e.g. Dean of Students" class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Title (Amharic)</label>
              <input v-model="jdForm.title_am" type="text" placeholder="በአማርኛ..." class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">JD Summary / Body</label>
            <textarea v-model="jdForm.body" placeholder="Position overview and details..." class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none h-20 resize-none"></textarea>
          </div>

          <!-- JD Items Builder -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Duties, Responsibilities & Qualifications</label>
              <button type="button" @click="addJdItem" class="text-[10px] font-bold text-blue-400 hover:text-blue-300 flex items-center gap-1">
                + Add Item
              </button>
            </div>

            <div class="space-y-2">
              <div v-for="(item, idx) in jdForm.items" :key="idx" class="flex gap-2 items-center p-3 rounded-xl border border-slate-900 bg-slate-950/40 relative">
                <div class="flex-1 space-y-2">
                  <div class="grid grid-cols-2 gap-2">
                    <select v-model="item.category" class="bg-slate-950/60 border border-slate-850 rounded-lg px-2.5 py-1 text-slate-200 text-xs focus:outline-none">
                      <option value="responsibility">Responsibility</option>
                      <option value="authority">Authority</option>
                      <option value="qualification">Qualification</option>
                    </select>
                    <label class="flex items-center gap-1.5 text-xs text-slate-450 cursor-pointer select-none">
                      <input type="checkbox" v-model="item.is_kpi" class="accent-blue-500" />
                      <span>Promote to KPI</span>
                    </label>
                  </div>
                  <input v-model="item.title_en" type="text" required placeholder="Duty / Responsibility text..." class="w-full bg-slate-950/60 border border-slate-850 rounded-lg px-3 py-1.5 text-slate-200 text-xs focus:outline-none" />
                </div>
                <button v-if="jdForm.items.length > 1" type="button" @click="removeJdItem(idx)" class="text-rose-500 hover:text-rose-400 p-1">
                  ✕
                </button>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="jdModalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer">Cancel</button>
            <button type="submit" :disabled="jdForm.processing" class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-650 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 cursor-pointer">Save JD</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         MODAL: UPLOAD DOCUMENT
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="documentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-6">Upload Secure Document</h3>

        <form @submit.prevent="submitDocument" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Document Name</label>
            <input v-model="documentForm.name" type="text" required placeholder="e.g. Identity Card Scan..." class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
          </div>

          <!-- Source Type Toggle -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Source Type</label>
            <div class="flex items-center gap-4">
              <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
                <input type="radio" v-model="documentForm.upload_type" value="file" class="accent-blue-500" />
                <span>Upload File</span>
              </label>
              <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
                <input type="radio" v-model="documentForm.upload_type" value="link" class="accent-blue-500" />
                <span>Web Link</span>
              </label>
            </div>
          </div>

          <!-- File Input -->
          <div v-if="documentForm.upload_type === 'file'">
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Choose File</label>
            <input type="file" @change="onDocFileChange" required class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip" />
            <p class="text-[10px] text-slate-500 mt-1">Supports image, PDF, Word, Excel, ZIP (Max 50MB)</p>
          </div>

          <!-- Web Link Input -->
          <div v-else>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Web Link / URL</label>
            <input v-model="documentForm.file_path" type="url" required placeholder="https://example.com/document.pdf" class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm font-mono focus:outline-none" />
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="documentModalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer">Cancel</button>
            <button type="submit" :disabled="documentForm.processing" class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-650 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 cursor-pointer">Upload</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         MODAL: SUBMIT NARRATIVE REPORT
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="reportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-6">Submit Narrative Performance Report</h3>

        <form @submit.prevent="submitReport" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Evaluation Period</label>
            <select v-model="reportForm.evaluation_period_id" required class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
              <option value="" disabled>Select Period</option>
              <option v-for="period in evaluationPeriods" :key="period.id" :value="period.id">{{ period.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Language</label>
            <select v-model="reportForm.language" class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
              <option value="en">English</option>
              <option value="am">Amharic</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Narrative Body</label>
            <textarea v-model="reportForm.body" required placeholder="Write details about the performance, achievements, and challenges during this period..." class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none h-32 resize-none"></textarea>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="reportModalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer">Cancel</button>
            <button type="submit" :disabled="reportForm.processing" class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-650 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 cursor-pointer">Submit Report</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         MODAL: MANAGE EMPLOYEE STATUS
    ═══════════════════════════════════════════════════════════ -->
    <div v-if="statusModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-6">Manage Employee Status</h3>

        <form @submit.prevent="submitStatusChange" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">New Status</label>
            <select v-model="statusForm.status" required class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
              <option value="active">Active</option>
              <option value="on_leave">On Leave</option>
              <option value="suspended">Suspended</option>
              <option value="terminated">Terminated</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Reason / Trigger</label>
            <input v-model="statusForm.reason" type="text" required placeholder="e.g. Parental Leave, Disciplinary, Resigned" class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Detailed Notes</label>
            <textarea v-model="statusForm.notes" placeholder="Provide additional details regarding this status change..." class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none h-24 resize-none"></textarea>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Effective Date</label>
            <input v-model="statusForm.effective_date" type="date" required class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="statusModalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer text-slate-350">Cancel</button>
            <button type="submit" :disabled="statusForm.processing" class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-650 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 cursor-pointer">Update Status</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Image Preview Modal / Lightbox -->
    <div v-if="activePreviewImage" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/90 backdrop-blur-md" @click="activePreviewImage = null">
      <div class="relative max-w-4xl max-h-[90vh] flex flex-col items-center justify-center bg-slate-900 border border-slate-800 rounded-3xl p-4 shadow-2xl" @click.stop>
        <button 
          @click="activePreviewImage = null"
          class="absolute top-4 right-4 text-slate-400 hover:text-white bg-slate-950/80 p-2 rounded-full border border-slate-800 hover:border-slate-700 transition-all cursor-pointer z-10"
        >
          <Icon name="X" :size="20" />
        </button>
        <img :src="activePreviewImage" class="max-w-full max-h-[75vh] object-contain rounded-2xl border border-slate-950/50 shadow-inner" alt="Document Preview" />
        <div class="mt-4 text-slate-300 font-semibold text-sm text-center">
          Document Preview
        </div>
      </div>
    </div>

  </div>
</template>
