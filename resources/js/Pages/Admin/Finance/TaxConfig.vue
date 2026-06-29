<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  module: { type: Object, required: true },
  brackets: { type: Array, default: () => [] },
});

// Modal state
const modalOpen = ref(false);
const editingBracket = ref(null);

const form = useForm({
  min_income: '',
  max_income: '',
  rate: '',
  deduction: '',
  effective_from: '',
});

const openCreateModal = () => {
  editingBracket.value = null;
  form.reset();
  form.clearErrors();
  modalOpen.value = true;
};

const openEditModal = (bracket) => {
  editingBracket.value = bracket;
  form.min_income = Number(bracket.min_income);
  form.max_income = bracket.max_income ? Number(bracket.max_income) : '';
  form.rate = Number(bracket.rate);
  form.deduction = Number(bracket.deduction);
  form.effective_from = bracket.effective_from ? bracket.effective_from.substring(0, 10) : '';
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  if (editingBracket.value) {
    form.put(`/admin/tax/brackets/${editingBracket.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/tax/brackets', {
      onSuccess: () => closeModal(),
    });
  }
};
</script>

<template>
  <Head title="Tax Brackets Config — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Percent" :size="26" />
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
          + Add Bracket
        </button>
      </div>
    </section>

    <!-- Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Monthly Income Range (ETB)</th>
              <th class="pb-3">Tax Rate (%)</th>
              <th class="pb-3">Deduction constant (ETB)</th>
              <th class="pb-3">Effective From</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="bracket in brackets" :key="bracket.id" class="hover:bg-slate-900/40">
              <td class="py-4 font-semibold text-slate-200">
                <span v-if="bracket.max_income">
                  {{ Number(bracket.min_income).toLocaleString() }} - {{ Number(bracket.max_income).toLocaleString() }}
                </span>
                <span v-else>
                  Over {{ Number(bracket.min_income).toLocaleString() }}
                </span>
              </td>
              <td class="py-4 font-mono text-slate-300">{{ Number(bracket.rate) }}%</td>
              <td class="py-4 font-mono text-slate-400">{{ Number(bracket.deduction).toLocaleString() }} ETB</td>
              <td class="py-4 text-slate-450">{{ bracket.effective_from ? new Date(bracket.effective_from).toLocaleDateString() : '—' }}</td>
              <td class="py-4 text-right">
                <button 
                  @click="openEditModal(bracket)"
                  class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
                >
                  Edit
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
          {{ editingBracket ? 'Edit Tax Bracket' : 'Add Tax Bracket' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <!-- Min income -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Min Income</label>
              <input v-model="form.min_income" type="number" step="0.01" required class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm font-mono" />
            </div>

            <!-- Max income -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Max Income (or blank)</label>
              <input v-model="form.max_income" type="number" step="0.01" class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm font-mono" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <!-- Rate -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Rate (%)</label>
              <input v-model="form.rate" type="number" step="0.1" required class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm font-mono" />
            </div>

            <!-- Deduction -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Deduction constant</label>
              <input v-model="form.deduction" type="number" step="0.01" required class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm font-mono" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Effective From Date</label>
            <input v-model="form.effective_from" type="date" class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 text-sm" />
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
              Save Bracket
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
