<script>
import PortalLayout from '@/Layouts/PortalLayout.vue';
export default { layout: PortalLayout };
</script>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  tasks: { type: Array, default: () => [] },
  targets: { type: Array, default: () => [] },
  fortnight: { type: Object, default: null },
  cadences: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({ create: false }) },
});

const summary = computed(() => ({
  total: props.tasks.length,
  open: props.tasks.filter((t) => ['pending', 'in_progress'].includes(t.status)).length,
  completed: props.tasks.filter((t) => t.status === 'completed').length,
}));

const modalOpen = ref(false);
const editing = ref(null);

const form = useForm({
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

const openCreate = () => {
  editing.value = null;
  form.reset();
  form.clearErrors();
  modalOpen.value = true;
};

const openEdit = (task) => {
  editing.value = task;
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

const submit = () => {
  if (editing.value) {
    form.put(`/dashboard/tasks/${editing.value.id}`, { onSuccess: () => (modalOpen.value = false) });
  } else {
    form.post('/dashboard/tasks', { onSuccess: () => (modalOpen.value = false) });
  }
};

const remove = async (task) => {
  if (await confirm({ title: 'Delete Task', message: `Remove “${task.title}” from your plan?` })) {
    router.delete(`/dashboard/tasks/${task.id}`);
  }
};

const TASK_BADGE = {
  completed: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  in_progress: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  submitted: 'bg-violet-500/10 border-violet-500/20 text-violet-400',
  pending: 'bg-slate-800/60 border-slate-800 text-slate-400',
  missed: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
};
const badge = (s) => TASK_BADGE[s] ?? TASK_BADGE.pending;
const label = (s) => (s ?? '').replace(/_/g, ' ');
const fmtDate = (d) => (d ? new Date(d).toLocaleDateString() : '—');
</script>

<template>
  <Head title="My Tasks — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0"><Icon name="ListChecks" :size="26" /></span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">My Work</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">My Tasks</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Plan and progress your work<span v-if="fortnight"> for <span class="text-slate-300 font-semibold">{{ fortnight.name }}</span></span>.
            </p>
          </div>
        </div>
        <button v-if="can.create" @click="openCreate"
                class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer">
          + Add Task
        </button>
      </div>
    </section>

    <!-- Summary -->
    <div class="grid grid-cols-3 gap-5">
      <div class="p-5 rounded-2xl border border-slate-900 bg-slate-900/35"><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Open</span><p class="text-2xl font-extrabold text-white mt-1">{{ summary.open }}</p></div>
      <div class="p-5 rounded-2xl border border-slate-900 bg-slate-900/35"><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Completed</span><p class="text-2xl font-extrabold text-emerald-400 mt-1">{{ summary.completed }}</p></div>
      <div class="p-5 rounded-2xl border border-slate-900 bg-slate-900/35"><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total</span><p class="text-2xl font-extrabold text-white mt-1">{{ summary.total }}</p></div>
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Task</th>
              <th class="pb-3">Target</th>
              <th class="pb-3">Due</th>
              <th class="pb-3">Progress</th>
              <th class="pb-3">Status</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="t in tasks" :key="t.id" class="hover:bg-slate-900/40">
              <td class="py-4">
                <div class="font-semibold text-slate-200">{{ t.title }}</div>
                <div v-if="t.description" class="text-xs text-slate-500 mt-0.5 line-clamp-1 max-w-xs">{{ t.description }}</div>
              </td>
              <td class="py-4">
                <span v-if="t.target" class="text-xs font-semibold text-slate-300 bg-slate-950/40 border border-slate-900 px-2 py-0.5 rounded-md">{{ t.target.name }}</span>
                <span v-else class="text-slate-600 italic text-xs">Standalone</span>
              </td>
              <td class="py-4 text-slate-400">{{ fmtDate(t.due_date) }}</td>
              <td class="py-4">
                <div class="flex items-center gap-2">
                  <div class="w-24 bg-slate-950/80 rounded-full h-1.5 overflow-hidden"><div class="bg-blue-500 h-full rounded-full" :style="`width:${t.completion_pct}%`"></div></div>
                  <span class="text-xs font-mono font-bold text-slate-300">{{ Number(t.completion_pct) }}%</span>
                </div>
              </td>
              <td class="py-4">
                <span class="px-2 py-0.5 text-[10px] rounded-md font-bold uppercase tracking-wider border capitalize" :class="badge(t.status)">{{ label(t.status) }}</span>
              </td>
              <td class="py-4 text-right space-x-2 whitespace-nowrap">
                <button @click="openEdit(t)" class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer">Edit</button>
                <button @click="remove(t)" class="text-[11px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-rose-950/20 border border-slate-800 hover:border-rose-900/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors cursor-pointer">Delete</button>
              </td>
            </tr>
            <tr v-if="!tasks.length"><td colspan="6" class="py-12 text-center text-slate-600 italic">No tasks yet. Click “Add Task” to plan your work.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-white mb-6">{{ editing ? 'Edit Task' : 'Add Task' }}</h3>
        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Title</label>
            <input v-model="form.title" type="text" required placeholder="e.g. Draft fortnight progress report"
                   class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 text-sm" />
            <p v-if="form.errors.title" class="text-xs text-rose-400 mt-1">{{ form.errors.title }}</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Strategic Target (optional)</label>
            <select v-model="form.target_id" class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 text-sm">
              <option value="">None (Standalone)</option>
              <option v-for="t in targets" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Description</label>
            <textarea v-model="form.description" rows="3" placeholder="What does “done” look like?"
                      class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 text-sm"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Cadence</label>
              <select v-model="form.cadence" class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 text-sm">
                <option v-for="c in cadences" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Weight</label>
              <input v-model="form.weight" type="number" step="0.1" min="0" required class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 text-sm" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Start Date</label>
              <input v-model="form.starting_date" type="date" class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 text-sm" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Due Date</label>
              <input v-model="form.due_date" type="date" class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 text-sm" />
              <p v-if="form.errors.due_date" class="text-xs text-rose-400 mt-1">{{ form.errors.due_date }}</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Status</label>
              <select v-model="form.status" class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 text-sm">
                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Completion %</label>
              <input v-model="form.completion_pct" type="number" min="0" max="100" required class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 text-sm font-mono" />
            </div>
          </div>
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="modalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer">Cancel</button>
            <button type="submit" :disabled="form.processing" class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 cursor-pointer">{{ form.processing ? 'Saving…' : 'Save Task' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
