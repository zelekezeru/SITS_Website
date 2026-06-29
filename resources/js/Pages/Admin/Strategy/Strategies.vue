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
  strategies: { type: Array, default: () => [] },
  years: { type: Array, default: () => [] },
  pillars: { type: Array, default: () => [] },
});

// Modal state
const modalOpen = ref(false);
const editingStrategy = ref(null);

const activeYear = props.years.find(y => y.active);

const form = useForm({
  year_id: activeYear ? activeYear.id : '',
  pillar: '',
  name: '',
  description: '',
});

const openCreateModal = () => {
  editingStrategy.value = null;
  form.reset({
    year_id: activeYear ? activeYear.id : '',
    pillar: '',
    name: '',
    description: '',
  });
  form.clearErrors();
  modalOpen.value = true;
};

const openEditModal = (strategy) => {
  editingStrategy.value = strategy;
  form.year_id = strategy.year_id;
  form.pillar = strategy.pillar;
  form.name = strategy.name;
  form.description = strategy.description || '';
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  if (editingStrategy.value) {
    form.put(`/admin/strategy/strategies/${editingStrategy.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/strategy/strategies', {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteStrategy = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Strategy',
    message: 'Are you sure you want to delete this strategy? All child goals and targets will be cascade deleted.',
  });
  if (confirmed) {
    router.delete(`/admin/strategy/strategies/${id}`);
  }
};

const getPillarLabel = (val) => {
  const pillar = props.pillars.find(p => p.value === val);
  return pillar ? pillar.label : val;
};
</script>

<template>
  <Head title="Pillars &amp; Strategies Setup — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Compass" :size="26" />
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
          + New Strategy
        </button>
      </div>
    </section>

    <!-- Table of Strategies -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Name</th>
              <th class="pb-3">Strategic Pillar</th>
              <th class="pb-3">Fiscal Year</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-if="strategies.length === 0">
              <td colspan="4" class="py-6 text-center text-slate-500 italic">No strategies defined yet. Create one above!</td>
            </tr>
            <tr v-for="strat in strategies" :key="strat.id" class="hover:bg-slate-900/40">
              <td class="py-4 font-semibold text-slate-200">
                <span>{{ strat.name }}</span>
                <span class="block text-xs text-slate-500 font-normal mt-0.5">{{ strat.description }}</span>
              </td>
              <td class="py-4 text-slate-400 font-medium">
                <span class="px-2 py-1 bg-slate-900 border border-slate-800 rounded-lg text-xs">
                  {{ getPillarLabel(strat.pillar) }}
                </span>
              </td>
              <td class="py-4 text-slate-400">{{ strat.year?.label }}</td>
              <td class="py-4 text-right space-x-2 shrink-0">
                <button 
                  @click="openEditModal(strat)"
                  class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
                >
                  Edit
                </button>
                <button 
                  @click="deleteStrategy(strat.id)"
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
          {{ editingStrategy ? 'Edit Strategy' : 'Create Strategy' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- Fiscal Year Selection -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Fiscal Year</label>
            <select 
              v-model="form.year_id" 
              required
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm border-slate-850"
            >
              <option v-for="year in years" :key="year.id" :value="year.id">
                {{ year.label }} {{ year.active ? '(Active)' : '' }}
              </option>
            </select>
            <div v-if="form.errors.year_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.year_id }}</div>
          </div>

          <!-- Strategic Pillar Selection -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Strategic Pillar</label>
            <select 
              v-model="form.pillar" 
              required
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm border-slate-850"
            >
              <option value="" disabled>Select Strategic Pillar</option>
              <option v-for="pillar in pillars" :key="pillar.value" :value="pillar.value">
                {{ pillar.label }}
              </option>
            </select>
            <div v-if="form.errors.pillar" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.pillar }}</div>
          </div>

          <!-- Strategy Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Strategy Name</label>
            <input 
              v-model="form.name" 
              type="text" 
              required
              placeholder="e.g. Implement modern payroll protocols" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.name ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.name" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.name }}</div>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Description</label>
            <textarea 
              v-model="form.description" 
              rows="3"
              placeholder="Provide strategic context..." 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.description ? 'border-rose-500/50' : 'border-slate-850'"
            ></textarea>
            <div v-if="form.errors.description" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.description }}</div>
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
              {{ form.processing ? 'Saving...' : 'Save Strategy' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
