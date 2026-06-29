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
  goals: { type: Array, default: () => [] },
  strategies: { type: Array, default: () => [] },
});

// Modal state
const modalOpen = ref(false);
const editingGoal = ref(null);

const form = useForm({
  strategy_id: props.strategies.length > 0 ? props.strategies[0].id : '',
  name: '',
  description: '',
});

const openCreateModal = () => {
  editingGoal.value = null;
  form.reset({
    strategy_id: props.strategies.length > 0 ? props.strategies[0].id : '',
    name: '',
    description: '',
  });
  form.clearErrors();
  modalOpen.value = true;
};

const openEditModal = (goal) => {
  editingGoal.value = goal;
  form.strategy_id = goal.strategy_id;
  form.name = goal.name;
  form.description = goal.description || '';
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  if (editingGoal.value) {
    form.put(`/admin/strategy/goals/${editingGoal.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/strategy/goals', {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteGoal = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Goal',
    message: 'Are you sure you want to delete this goal? All child targets will be deleted.',
  });
  if (confirmed) {
    router.delete(`/admin/strategy/goals/${id}`);
  }
};
</script>

<template>
  <Head title="Strategic Goals Setup — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Activity" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
          </div>
        </div>
        <button 
          @click="openCreateModal"
          :disabled="strategies.length === 0"
          class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white disabled:from-slate-800 disabled:to-slate-800 disabled:text-slate-500 px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
        >
          + New Goal
        </button>
      </div>
    </section>

    <!-- Table of Goals -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Goal Name</th>
              <th class="pb-3">Parent Strategy</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-if="goals.length === 0">
              <td colspan="3" class="py-6 text-center text-slate-500 italic">No goals defined yet. Create one above!</td>
            </tr>
            <tr v-for="goal in goals" :key="goal.id" class="hover:bg-slate-900/40">
              <td class="py-4 font-semibold text-slate-200">
                <span>{{ goal.name }}</span>
                <span class="block text-xs text-slate-500 font-normal mt-0.5">{{ goal.description }}</span>
              </td>
              <td class="py-4 text-slate-455 font-medium max-w-xs truncate">
                {{ goal.strategy?.name }}
              </td>
              <td class="py-4 text-right space-x-2 shrink-0">
                <button 
                  @click="openEditModal(goal)"
                  class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
                >
                  Edit
                </button>
                <button 
                  @click="deleteGoal(goal.id)"
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
          {{ editingGoal ? 'Edit Strategic Goal' : 'Create Strategic Goal' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- Strategy Selection -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Parent Strategy</label>
            <select 
              v-model="form.strategy_id" 
              required
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm border-slate-850"
            >
              <option v-for="strat in strategies" :key="strat.id" :value="strat.id">
                {{ strat.name }}
              </option>
            </select>
            <div v-if="form.errors.strategy_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.strategy_id }}</div>
          </div>

          <!-- Goal Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Goal Name</label>
            <input 
              v-model="form.name" 
              type="text" 
              required
              placeholder="e.g. Complete SITS ERP integration" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.name ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.name" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.name }}</div>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Description / Objectives</label>
            <textarea 
              v-model="form.description" 
              rows="3"
              placeholder="Describe the objective detail..." 
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
              {{ form.processing ? 'Saving...' : 'Save Goal' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
