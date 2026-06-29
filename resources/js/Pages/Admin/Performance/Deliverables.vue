<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, required: true },
  deliverables: { type: Array, default: () => [] },
  fortnights: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
});

// Modal state
const modalOpen = ref(false);
const editingDeliverable = ref(null);

const form = useForm({
  fortnight_id: '',
  user_id: '',
  name: '',
  deadline: '',
  is_completed: false,
});

const openCreateModal = () => {
  editingDeliverable.value = null;
  form.reset();
  if (props.fortnights.length) {
    form.fortnight_id = props.fortnights[0].id;
  }
  if (props.users.length) {
    form.user_id = props.users[0].id;
  }
  form.clearErrors();
  modalOpen.value = true;
};

const openEditModal = (deliv) => {
  editingDeliverable.value = deliv;
  form.fortnight_id = deliv.fortnight_id;
  form.user_id = deliv.user_id;
  form.name = deliv.name;
  form.deadline = deliv.deadline ? deliv.deadline.substring(0, 10) : '';
  form.is_completed = !!deliv.is_completed;
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  if (editingDeliverable.value) {
    form.put(`/admin/deliverables/${editingDeliverable.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/deliverables', {
      onSuccess: () => closeModal(),
    });
  }
};

const toggleDeliverable = (id) => {
  router.post(`/admin/deliverables/${id}/toggle`);
};

const deleteDeliverable = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Deliverable',
    message: 'Are you sure you want to delete this deliverable?',
  });
  if (confirmed) {
    router.delete(`/admin/deliverables/${id}`);
  }
};
</script>

<template>
  <Head title="Fortnight Deliverables — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Flag" :size="26" />
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
          + Add Deliverable
        </button>
      </div>
    </section>

    <!-- Deliverables List -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Name</th>
              <th class="pb-3">Fortnight Sprint</th>
              <th class="pb-3">Owner</th>
              <th class="pb-3">Deadline</th>
              <th class="pb-3">Status</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="deliv in deliverables" :key="deliv.id" class="hover:bg-slate-900/40">
              <td class="py-4 font-semibold text-slate-200">{{ deliv.name }}</td>
              <td class="py-4 text-slate-400">
                <span v-if="deliv.fortnight" class="text-xs font-mono font-bold text-blue-400 bg-blue-500/10 border border-blue-500/20 px-2 py-0.5 rounded">
                  {{ deliv.fortnight.name }}
                </span>
              </td>
              <td class="py-4 text-slate-450">{{ deliv.user?.name }}</td>
              <td class="py-4 text-slate-400">{{ deliv.deadline ? new Date(deliv.deadline).toLocaleDateString() : '—' }}</td>
              <td class="py-4">
                <div class="flex items-center gap-2">
                  <button 
                    @click="toggleDeliverable(deliv.id)"
                    class="px-2.5 py-0.5 text-xs rounded-full font-bold border cursor-pointer hover:scale-102 transition-transform"
                    :class="deliv.is_completed 
                      ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400' 
                      : 'bg-slate-800/60 border-slate-800 text-slate-500'"
                  >
                    {{ deliv.is_completed ? 'Completed' : 'Pending Review' }}
                  </button>
                  <span v-if="deliv.reviewed_by" class="text-[10px] text-slate-500">
                    Reviewed
                  </span>
                </div>
              </td>
              <td class="py-4 text-right space-x-2">
                <button 
                  @click="openEditModal(deliv)"
                  class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
                >
                  Edit
                </button>
                <button 
                  @click="deleteDeliverable(deliv.id)"
                  class="text-[11px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-rose-950/20 border border-slate-850 hover:border-rose-900/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                >
                  Delete
                </button>
              </td>
            </tr>
            <tr v-if="!deliverables.length">
              <td colspan="6" class="py-8 text-center text-slate-600 italic">
                No deliverables listed for this period.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-6">
          {{ editingDeliverable ? 'Edit Deliverable' : 'Add Deliverable' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- Fortnight Select -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Fortnight Sprint</label>
            <select 
              v-model="form.fortnight_id"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              required
            >
              <option value="" disabled>Select Fortnight</option>
              <option v-for="f in fortnights" :key="f.id" :value="f.id">{{ f.name }}</option>
            </select>
          </div>

          <!-- Owner Select -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Owner (User)</label>
            <select 
              v-model="form.user_id"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              required
            >
              <option value="" disabled>Select Owner</option>
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
            </select>
          </div>

          <!-- Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Deliverable Name</label>
            <input 
              v-model="form.name" 
              type="text" 
              required
              placeholder="e.g. Draft financial report file" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            />
          </div>

          <!-- Deadline -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Deadline</label>
            <input 
              v-model="form.deadline" 
              type="date" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            />
          </div>

          <!-- Complete Toggle -->
          <div class="flex items-center gap-3">
            <input 
              v-model="form.is_completed" 
              type="checkbox" 
              id="is_completed"
              class="rounded border-slate-850 bg-slate-950 text-blue-500 focus:ring-0"
            />
            <label for="is_completed" class="text-sm font-semibold text-slate-300 cursor-pointer">Mark as Completed</label>
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
              {{ form.processing ? 'Saving...' : 'Save Deliverable' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
