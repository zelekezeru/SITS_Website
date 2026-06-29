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
  module: { type: Object, required: true },
  routeName: { type: String, required: true },
  tasks: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  targets: { type: Array, default: () => [] },
  fortnights: { type: Array, default: () => [] },
});

// Modal state
const modalOpen = ref(false);
const editingTask = ref(null);

const form = useForm({
  employee_id: '',
  target_id: '',
  title: '',
  description: '',
  cadence: 'fortnightly',
  starting_date: '',
  due_date: '',
  weight: 1.0,
  status: 'pending',
  completion_pct: 0,
});

const openCreateModal = () => {
  editingTask.value = null;
  form.reset();
  if (props.employees.length) {
    form.employee_id = props.employees[0].id;
  }
  form.clearErrors();
  modalOpen.value = true;
};

const openEditModal = (task) => {
  editingTask.value = task;
  form.employee_id = task.employee_id;
  form.target_id = task.target_id || '';
  form.title = task.title;
  form.description = task.description || '';
  form.cadence = task.cadence;
  form.starting_date = task.starting_date ? task.starting_date.substring(0, 10) : '';
  form.due_date = task.due_date ? task.due_date.substring(0, 10) : '';
  form.weight = Number(task.weight);
  form.status = task.status;
  form.completion_pct = Number(task.completion_pct);
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  if (editingTask.value) {
    form.put(`/admin/tasks/${editingTask.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/tasks', {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteTask = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Task',
    message: 'Are you sure you want to delete this task?',
  });
  if (confirmed) {
    router.delete(`/admin/tasks/${id}`);
  }
};
</script>

<template>
  <Head title="Tasks Tracking — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="ListChecks" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
          </div>
        </div>
        <button 
          @click="openCreateModal"
          class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
        >
          + Add Task
        </button>
      </div>
    </section>

    <!-- Task List -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Title & Owner</th>
              <th class="pb-3">Strategic Target</th>
              <th class="pb-3">Due Date</th>
              <th class="pb-3">Weight</th>
              <th class="pb-3">Completion</th>
              <th class="pb-3">Status</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="task in tasks" :key="task.id" class="hover:bg-slate-900/40">
              <td class="py-4">
                <div class="font-semibold text-slate-200">{{ task.title }}</div>
                <div class="text-xs text-slate-500 mt-0.5">Assigned to: {{ task.employee?.full_name_en }}</div>
              </td>
              <td class="py-4 text-slate-400">
                <span v-if="task.target" class="text-xs font-semibold text-slate-350 bg-slate-950/40 border border-slate-900 px-2 py-0.5 rounded-md">
                  {{ task.target.name }}
                </span>
                <span v-else class="text-slate-650 italic">None</span>
              </td>
              <td class="py-4 text-slate-400">{{ task.due_date ? new Date(task.due_date).toLocaleDateString() : '—' }}</td>
              <td class="py-4 text-slate-400 font-mono">{{ task.weight }}</td>
              <td class="py-4">
                <div class="flex items-center gap-2">
                  <div class="w-24 bg-slate-950/80 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-blue-500 h-full rounded-full transition-all" :style="`width: ${task.completion_pct}%`"></div>
                  </div>
                  <span class="text-xs font-mono font-bold text-slate-300">{{ Number(task.completion_pct) }}%</span>
                </div>
              </td>
              <td class="py-4 capitalize">
                <span 
                  class="px-2 py-0.5 text-[10px] rounded-md font-bold uppercase tracking-wider border"
                  :class="task.status === 'completed' 
                    ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' 
                    : task.status === 'in_progress' 
                      ? 'bg-blue-500/10 border-blue-500/20 text-blue-400' 
                      : 'bg-slate-800/60 border-slate-800 text-slate-500'"
                >
                  {{ task.status.replace('_', ' ') }}
                </span>
              </td>
              <td class="py-4 text-right space-x-2">
                <button 
                  @click="openEditModal(task)"
                  class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
                >
                  Edit
                </button>
                <button 
                  @click="deleteTask(task.id)"
                  class="text-[11px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-rose-950/20 border border-slate-850 hover:border-rose-900/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                >
                  Delete
                </button>
              </td>
            </tr>
            <tr v-if="!tasks.length">
              <td colspan="7" class="py-8 text-center text-slate-600 italic">
                No tasks logged.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-white mb-6">
          {{ editingTask ? 'Edit Task' : 'Add Task' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- Employee Select -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Task Owner (Employee)</label>
            <select 
              v-model="form.employee_id"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              required
            >
              <option value="" disabled>Select Employee</option>
              <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                {{ emp.full_name_en }} ({{ emp.position?.title_en || 'Staff' }})
              </option>
            </select>
          </div>

          <!-- Target Select -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Strategic Plan Link (Target)</label>
            <select 
              v-model="form.target_id"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            >
              <option value="">None (Standalone)</option>
              <option v-for="t in targets" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>

          <!-- Title -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Task Title</label>
            <input 
              v-model="form.title" 
              type="text" 
              required
              placeholder="e.g. Audit documentation compilation" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            />
          </div>

          <!-- Description -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Description</label>
            <textarea 
              v-model="form.description" 
              rows="3"
              placeholder="Describe the details and deliverables of this task..."
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-650 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <!-- Starting Date -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Start Date</label>
              <input 
                v-model="form.starting_date" 
                type="date" 
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              />
            </div>

            <!-- Due Date -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Due Date</label>
              <input 
                v-model="form.due_date" 
                type="date" 
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <!-- Weight -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Weight</label>
              <input 
                v-model="form.weight" 
                type="number" 
                step="0.1"
                min="0"
                required
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              />
            </div>

            <!-- Completion % -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Completion %</label>
              <input 
                v-model="form.completion_pct" 
                type="number" 
                min="0"
                max="100"
                required
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm font-mono"
              />
            </div>

            <!-- Status -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Status</label>
              <select 
                v-model="form.status"
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              >
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
              </select>
            </div>
          </div>

          <!-- Modal Actions -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button 
              type="button" 
              @click="closeModal"
              class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="form.processing"
              class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
            >
              {{ form.processing ? 'Saving...' : 'Save Task' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
