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
  positions: { type: Array, default: () => [] },
});

// Modal state
const modalOpen = ref(false);
const editingPosition = ref(null);

const form = useForm({
  title_en: '',
  title_am: '',
  code: '',
});

const openCreateModal = () => {
  editingPosition.value = null;
  form.reset();
  form.clearErrors();
  modalOpen.value = true;
};

const openEditModal = (position) => {
  editingPosition.value = position;
  form.title_en = position.title_en;
  form.title_am = position.title_am || '';
  form.code = position.code || '';
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  if (editingPosition.value) {
    form.put(`/admin/positions/${editingPosition.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/positions', {
      onSuccess: () => closeModal(),
    });
  }
};

const deletePosition = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Position',
    message: 'Are you sure you want to delete this position? This will delete associated employee records and job descriptions.',
  });
  if (confirmed) {
    router.delete(`/admin/positions/${id}`);
  }
};
</script>

<template>
  <Head title="Positions — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Briefcase" :size="26" />
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
          + Add Position
        </button>
      </div>
    </section>

    <!-- Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Code</th>
              <th class="pb-3">English Title</th>
              <th class="pb-3">Amharic Title</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="position in positions" :key="position.id" class="hover:bg-slate-900/40">
              <td class="py-4 font-mono text-xs text-blue-400 font-semibold">{{ position.code || '—' }}</td>
              <td class="py-4 font-semibold text-slate-200">{{ position.title_en }}</td>
              <td class="py-4 text-slate-400">{{ position.title_am || '—' }}</td>
              <td class="py-4 text-right space-x-2">
                <button 
                  @click="openEditModal(position)"
                  class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
                >
                  Edit
                </button>
                <button 
                  @click="deletePosition(position.id)"
                  class="text-[11px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-rose-950/20 border border-slate-850 hover:border-rose-900/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                >
                  Delete
                </button>
              </td>
            </tr>
            <tr v-if="!positions.length">
              <td colspan="4" class="py-8 text-center text-slate-600 italic">
                No positions defined.
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
          {{ editingPosition ? 'Edit Position' : 'Add Position' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- Position Code -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Position Code</label>
            <input 
              v-model="form.code" 
              type="text" 
              placeholder="e.g. REG-01" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.code ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.code" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.code }}</div>
          </div>

          <!-- English Title -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">English Title</label>
            <input 
              v-model="form.title_en" 
              type="text" 
              required
              placeholder="e.g. Registrar" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.title_en ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.title_en" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.title_en }}</div>
          </div>

          <!-- Amharic Title -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Amharic Title</label>
            <input 
              v-model="form.title_am" 
              type="text" 
              placeholder="e.g. ሬጅስትራር" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.title_am ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.title_am" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.title_am }}</div>
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
              {{ form.processing ? 'Saving...' : 'Save Position' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
