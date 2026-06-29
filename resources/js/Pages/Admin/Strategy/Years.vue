<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, required: true },
  years: { type: Array, default: () => [] },
});

// Modal state
const modalOpen = ref(false);
const editingYear = ref(null);

const form = useForm({
  label: '',
  start_date: '',
  end_date: '',
});

const openCreateModal = () => {
  editingYear.value = null;
  form.reset();
  form.clearErrors();
  modalOpen.value = true;
};

const openEditModal = (year) => {
  editingYear.value = year;
  form.label = year.label;
  form.start_date = year.start_date ? year.start_date.substring(0, 10) : '';
  form.end_date = year.end_date ? year.end_date.substring(0, 10) : '';
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  if (editingYear.value) {
    form.put(`/admin/strategy/years/${editingYear.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/strategy/years', {
      onSuccess: () => closeModal(),
    });
  }
};

const activateYear = (id) => {
  router.post(`/admin/strategy/years/${id}/activate`);
};

const deleteYear = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Fiscal Year',
    message: 'Are you sure you want to delete this fiscal year?',
  });
  if (confirmed) {
    router.delete(`/admin/strategy/years/${id}`);
  }
};
</script>

<template>
  <Head title="Fiscal Year Setup — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="CalendarDays" :size="26" />
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
          + New Fiscal Year
        </button>
      </div>
    </section>

    <!-- Table of Years -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Label</th>
              <th class="pb-3">Start Date</th>
              <th class="pb-3">End Date</th>
              <th class="pb-3">Status</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr
              v-for="year in years"
              :key="year.id"
              class="hover:bg-slate-900/30 transition-colors cursor-pointer"
              @click="router.visit(`/admin/strategy/years/${year.id}`)"
            >
              <td class="py-4 font-semibold">
                <Link
                  :href="`/admin/strategy/years/${year.id}`"
                  class="text-blue-400 hover:text-blue-300 hover:underline"
                  @click.stop
                >
                  {{ year.label }}
                </Link>
              </td>
              <td class="py-4 text-slate-400">{{ year.start_date ? new Date(year.start_date).toLocaleDateString() : '—' }}</td>
              <td class="py-4 text-slate-400">{{ year.end_date ? new Date(year.end_date).toLocaleDateString() : '—' }}</td>
              <td class="py-4">
                <span 
                  class="px-2.5 py-0.5 text-xs rounded-full font-bold border"
                  :class="year.active 
                    ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400' 
                    : 'bg-slate-800/60 border-slate-800 text-slate-500'"
                >
                  {{ year.active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="py-4 text-right space-x-2">
                <button 
                  v-if="!year.active"
                  @click.stop="activateYear(year.id)"
                  class="text-[11px] font-bold px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors cursor-pointer"
                >
                  Activate
                </button>
                <button 
                  @click.stop="openEditModal(year)"
                  class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
                >
                  Edit
                </button>
                <button 
                  v-if="!year.active"
                  @click.stop="deleteYear(year.id)"
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

    <!-- Create/Edit Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-6">
          {{ editingYear ? 'Edit Fiscal Year' : 'Create Fiscal Year' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- Year Label -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Year Label</label>
            <input 
              v-model="form.label" 
              type="text" 
              required
              placeholder="e.g. FY2026" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.label ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.label" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.label }}</div>
          </div>

          <!-- Start Date -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Start Date</label>
            <input 
              v-model="form.start_date" 
              type="date" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.start_date ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.start_date" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.start_date }}</div>
          </div>

          <!-- End Date -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">End Date</label>
            <input 
              v-model="form.end_date" 
              type="date" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.end_date ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.end_date" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.end_date }}</div>
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
              {{ form.processing ? 'Saving...' : 'Save Year' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
