<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  fortnight: { type: Object, required: true },
  tasks: { type: Array, default: () => [] },
  deliverables: { type: Array, default: () => [] },
  days: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  selectedEmployee: { type: Object, default: null },
});

// ─── Active tab ───────────────────────────────────────────────────────────────
const activeTab = ref('tasks');
const tabs = [
  { key: 'tasks', label: 'Tasks', icon: 'ListChecks' },
  { key: 'deliverables', label: 'Deliverables', icon: 'Flag' },
  { key: 'daily', label: 'Daily Tasks', icon: 'CalendarDays' },
];

// ─── Employee filter ──────────────────────────────────────────────────────────
const employeeFilter = ref(props.selectedEmployee?.id || '');

const applyFilter = () => {
  router.get(`/admin/calendar/fortnights/${props.fortnight.id}`, 
    employeeFilter.value ? { employee_id: employeeFilter.value } : {},
    { preserveScroll: true, preserveState: true }
  );
};

// ─── Filtered data ───────────────────────────────────────────────────────────
const filteredTasks = computed(() => {
  if (!employeeFilter.value) return props.tasks;
  return props.tasks.filter(t => t.employee_id === Number(employeeFilter.value));
});

const filteredDeliverables = computed(() => {
  if (!employeeFilter.value) return props.deliverables;
  // Deliverables link to user_id, map through employee→user
  const emp = props.employees.find(e => e.id === Number(employeeFilter.value));
  if (!emp) return props.deliverables;
  return props.deliverables.filter(d => d.user_id === emp.user_id);
});

// ─── Helpers ─────────────────────────────────────────────────────────────────
const fmt = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) : '—';

const statusColor = (status) => {
  const map = {
    done: 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400',
    in_progress: 'bg-blue-500/10 border-blue-500/25 text-blue-400',
    overdue: 'bg-rose-500/10 border-rose-500/25 text-rose-400',
    pending: 'bg-slate-800/60 border-slate-800 text-slate-500',
  };
  return map[status] || map.pending;
};

const dayTypeColor = (type) => {
  const map = {
    working: 'bg-emerald-500/10 text-emerald-400',
    weekend: 'bg-slate-900/60 text-slate-600',
    holiday: 'bg-amber-500/10 text-amber-400',
    leave: 'bg-purple-500/10 text-purple-400',
  };
  return map[type] || 'bg-slate-900/60 text-slate-500';
};
</script>

<template>
  <Head :title="`${fortnight.name} — Fortnight Detail`" />

  <div class="space-y-8">

    <!-- ── Back navigation ─────────────────────────────────────────────────── -->
    <div class="flex items-center gap-3">
      <Link
        href="/admin/calendar/fortnights"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-400 hover:text-white bg-slate-900/60 hover:bg-slate-900 border border-slate-900 hover:border-slate-700 px-4 py-2 rounded-xl transition-all"
      >
        <Icon name="ArrowLeft" :size="15" />
        Back to Fortnights
      </Link>
      <div class="flex items-center gap-2 text-xs text-slate-600 font-semibold">
        <Icon name="ChevronRight" :size="13" />
        <span class="text-slate-400">{{ fortnight.name }}</span>
      </div>
    </div>
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-violet-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 space-y-4">
        <div class="flex items-start justify-between gap-6 flex-wrap">
          <div class="flex items-start gap-4">
            <span class="w-14 h-14 rounded-2xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-400 shrink-0">
              <Icon name="CalendarRange" :size="26" />
            </span>
            <div>
              <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight">{{ fortnight.name }}</h2>
              <p class="text-slate-400 text-sm mt-1">
                Sprint period:
                <span class="text-slate-300 font-semibold">
                  {{ fmt(fortnight.start_date) }} → {{ fmt(fortnight.end_date) }}
                </span>
                <span v-if="fortnight.quarter" class="ml-3 text-xs text-slate-500">
                  ({{ fortnight.quarter.name }})
                </span>
              </p>
            </div>
          </div>

          <!-- Stats bar -->
          <div class="flex gap-5 flex-wrap">
            <div class="text-center">
              <div class="text-2xl font-black text-blue-400">{{ filteredTasks.length }}</div>
              <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Tasks</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-black text-violet-400">{{ filteredDeliverables.length }}</div>
              <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Deliverables</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-black text-emerald-400">{{ days.length }}</div>
              <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Days</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Employee Filter ────────────────────────────────────────────────── -->
    <div class="flex flex-wrap items-center gap-4 bg-slate-900/10 border border-slate-900 p-4 rounded-2xl">
      <Icon name="UserCircle" :size="18" class="text-blue-400 shrink-0" />
      <span class="text-sm font-semibold text-slate-400">Filter by Employee:</span>
      <select
        v-model="employeeFilter"
        @change="applyFilter"
        class="flex-1 min-w-[220px] max-w-xs bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
      >
        <option value="">All Employees</option>
        <option v-for="emp in employees" :key="emp.id" :value="emp.id">
          {{ emp.full_name_en }} ({{ emp.position?.title_en || 'Staff' }})
        </option>
      </select>
      <span v-if="selectedEmployee" class="text-xs text-blue-400 font-semibold">
        Showing data for: {{ selectedEmployee.full_name_en }}
      </span>
    </div>

    <!-- ── Tab Navigation ────────────────────────────────────────────────── -->
    <div class="flex border-b border-slate-900 gap-1">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="activeTab = tab.key"
        class="flex items-center gap-2 pb-3 px-4 text-sm font-bold tracking-wide transition-colors cursor-pointer"
        :class="activeTab === tab.key ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-300'"
      >
        <Icon :name="tab.icon" :size="15" />
        {{ tab.label }}
      </button>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         TAB: TASKS
    ════════════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'tasks'" class="space-y-4">
      <div v-if="!filteredTasks.length" class="py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
        No tasks for this sprint{{ employeeFilter ? ' for the selected employee' : '' }}.
      </div>

      <div
        v-for="task in filteredTasks"
        :key="task.id"
        class="rounded-2xl border border-slate-900 bg-slate-900/10 p-5 hover:border-slate-800 transition-all"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-2">
              <span
                class="px-2 py-0.5 text-[10px] rounded-md font-bold uppercase tracking-wider border"
                :class="statusColor(task.status)"
              >
                {{ task.status?.replace('_', ' ') || 'pending' }}
              </span>
              <span class="text-[10px] text-slate-500 bg-slate-950/40 border border-slate-900 px-2 py-0.5 rounded-md font-semibold">
                {{ task.cadence || 'fortnight' }}
              </span>
            </div>
            <h3 class="font-bold text-white text-base">{{ task.title }}</h3>
            <p v-if="task.description" class="text-xs text-slate-400 mt-1 line-clamp-2">{{ task.description }}</p>
            <div class="flex items-center gap-4 mt-2 text-xs text-slate-500">
              <span v-if="task.employee" class="flex items-center gap-1">
                <Icon name="User" :size="12" />
                {{ task.employee.full_name_en }}
              </span>
              <span v-if="task.due_date" class="flex items-center gap-1">
                <Icon name="Calendar" :size="12" />
                Due {{ fmt(task.due_date) }}
              </span>
              <span v-if="task.weight" class="flex items-center gap-1">
                <Icon name="Weight" :size="12" />
                Weight: {{ task.weight }}
              </span>
            </div>
          </div>

          <!-- Progress ring -->
          <div class="shrink-0 flex flex-col items-center gap-1">
            <div class="relative w-14 h-14">
              <svg class="w-14 h-14 -rotate-90" viewBox="0 0 48 48">
                <circle cx="24" cy="24" r="20" stroke="#1e293b" stroke-width="4" fill="none" />
                <circle
                  cx="24" cy="24" r="20"
                  stroke="currentColor"
                  stroke-width="4"
                  fill="none"
                  class="text-blue-500"
                  :stroke-dasharray="`${(task.completion_pct || 0) * 1.256} 125.6`"
                  stroke-linecap="round"
                />
              </svg>
              <span class="absolute inset-0 flex items-center justify-center text-xs font-black text-slate-200">
                {{ Math.round(task.completion_pct || 0) }}%
              </span>
            </div>
            <span class="text-[10px] text-slate-500 font-semibold">Complete</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         TAB: DELIVERABLES
    ════════════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'deliverables'" class="space-y-4">
      <div v-if="!filteredDeliverables.length" class="py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
        No deliverables for this sprint{{ employeeFilter ? ' for the selected employee' : '' }}.
      </div>

      <div class="grid sm:grid-cols-2 gap-5">
        <div
          v-for="del in filteredDeliverables"
          :key="del.id"
          class="rounded-2xl border p-5 transition-all"
          :class="del.is_completed
            ? 'border-emerald-900/40 bg-emerald-950/10'
            : 'border-slate-900 bg-slate-900/10 hover:border-slate-800'"
        >
          <div class="flex items-start gap-3">
            <!-- Completion checkbox indicator -->
            <div
              class="w-5 h-5 rounded-full shrink-0 mt-0.5 flex items-center justify-center border-2 transition-all"
              :class="del.is_completed
                ? 'bg-emerald-500 border-emerald-400'
                : 'border-slate-700 bg-transparent'"
            >
              <Icon v-if="del.is_completed" name="Check" :size="10" class="text-white" />
            </div>

            <div class="flex-1 min-w-0">
              <h3
                class="font-bold text-sm"
                :class="del.is_completed ? 'text-slate-500 line-through' : 'text-white'"
              >
                {{ del.name }}
              </h3>
              <div class="flex items-center gap-4 mt-2 text-xs text-slate-500">
                <span v-if="del.deadline" class="flex items-center gap-1">
                  <Icon name="Clock" :size="11" />
                  Due {{ fmt(del.deadline) }}
                </span>
                <span v-if="del.user" class="flex items-center gap-1">
                  <Icon name="User" :size="11" />
                  {{ del.user.name }}
                </span>
              </div>
              <div v-if="del.is_completed && del.reviewed_by" class="mt-2 text-[10px] text-emerald-500 font-semibold flex items-center gap-1">
                <Icon name="BadgeCheck" :size="11" />
                Reviewed
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════
         TAB: DAILY TASKS
    ════════════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'daily'">
      <div v-if="!days.length" class="py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl">
        No days defined for this fortnight yet.
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-7 gap-3">
        <div
          v-for="day in days"
          :key="day.id"
          class="rounded-xl border border-slate-900 p-3 space-y-2"
          :class="day.type !== 'working' ? 'opacity-50' : 'hover:border-slate-800 transition-all'"
        >
          <!-- Date -->
          <div class="text-xs font-bold text-slate-300">
            {{ new Date(day.date).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) }}
          </div>

          <!-- Day type badge -->
          <span
            class="inline-block text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-md"
            :class="dayTypeColor(day.type)"
          >
            {{ day.type || 'working' }}
          </span>

          <!-- Tasks on this day -->
          <div v-if="day.tasks && day.tasks.length" class="space-y-1 mt-1">
            <div
              v-for="t in day.tasks.slice(0, 3)"
              :key="t.id"
              class="text-[10px] text-slate-400 truncate bg-slate-950/40 px-1.5 py-0.5 rounded"
            >
              {{ t.title }}
            </div>
            <div v-if="day.tasks.length > 3" class="text-[9px] text-slate-600 font-semibold">
              +{{ day.tasks.length - 3 }} more
            </div>
          </div>

          <div v-else class="text-[10px] text-slate-700 italic">No tasks</div>
        </div>
      </div>
    </div>
  </div>
</template>
