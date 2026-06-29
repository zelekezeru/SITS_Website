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
  days: { type: Array, default: () => [] },
  fortnights: { type: Array, default: () => [] },
});

// Modal state
const modalOpen = ref(false);
const editingDay = ref(null);

const form = useForm({
  fortnight_id: '',
});

const openEditModal = (day) => {
  editingDay.value = day;
  form.fortnight_id = day.fortnight_id || '';
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  form.put(`/admin/calendar/days/${editingDay.value.id}`, {
    onSuccess: () => closeModal(),
  });
};

const getDayName = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('en-US', { weekday: 'long' });
};
</script>

<template>
  <Head title="Days Setup — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
          <Icon name="CalendarDays" :size="26" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
        </div>
      </div>
    </section>

    <!-- Days Grid/List -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Date</th>
              <th class="pb-3">Day of Week</th>
              <th class="pb-3">Assigned Fortnight</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="day in days" :key="day.id" class="hover:bg-slate-900/40">
              <td class="py-4 font-semibold text-slate-200">{{ new Date(day.date).toLocaleDateString() }}</td>
              <td class="py-4 text-slate-400">{{ getDayName(day.date) }}</td>
              <td class="py-4 text-slate-350">
                <span 
                  v-if="day.fortnight" 
                  class="px-2.5 py-0.5 text-xs rounded-full font-bold bg-blue-500/10 border border-blue-500/25 text-blue-400"
                >
                  {{ day.fortnight.name }}
                </span>
                <span v-else class="text-slate-600 italic">Unassigned</span>
              </td>
              <td class="py-4 text-right">
                <button 
                  @click="openEditModal(day)"
                  class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
                >
                  Reassign Fortnight
                </button>
              </td>
            </tr>
            <tr v-if="!days.length">
              <td colspan="4" class="py-8 text-center text-slate-600 italic">
                No calendar days generated yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-6">Assign Day to Fortnight</h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <div class="text-sm text-slate-400 mb-4">
            Reassign date <span class="text-white font-semibold">{{ new Date(editingDay.date).toLocaleDateString() }}</span> to a fortnight:
          </div>

          <!-- Fortnight Select -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Select Fortnight</label>
            <select 
              v-model="form.fortnight_id"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            >
              <option value="">None (Unassigned)</option>
              <option v-for="f in fortnights" :key="f.id" :value="f.id">{{ f.name }}</option>
            </select>
            <div v-if="form.errors.fortnight_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.fortnight_id }}</div>
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
              {{ form.processing ? 'Saving...' : 'Save Assignment' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
