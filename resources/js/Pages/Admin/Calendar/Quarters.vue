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
  quarters: { type: Array, default: () => [] },
  years: { type: Array, default: () => [] },
  activeYear: { type: Object, default: null },
});

// Modal state
const modalOpen = ref(false);
const editingQuarter = ref(null);
const generateOpen = ref(false);

const form = useForm({
  year_id: props.activeYear ? props.activeYear.id : '',
  name: '',
  start_date: '',
  end_date: '',
});

const genForm = useForm({
  year_id: props.activeYear ? props.activeYear.id : '',
});

const openCreateModal = () => {
  editingQuarter.value = null;
  form.reset();
  if (props.activeYear) {
    form.year_id = props.activeYear.id;
  }
  form.clearErrors();
  modalOpen.value = true;
};

const openEditModal = (quarter) => {
  editingQuarter.value = quarter;
  form.year_id = quarter.year_id;
  form.name = quarter.name;
  form.start_date = quarter.start_date ? quarter.start_date.substring(0, 10) : '';
  form.end_date = quarter.end_date ? quarter.end_date.substring(0, 10) : '';
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  if (editingQuarter.value) {
    form.put(`/admin/calendar/quarters/${editingQuarter.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/calendar/quarters', {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteQuarter = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Quarter',
    message: 'Are you sure you want to delete this quarter? This will delete all its fortnights and day associations.',
  });
  if (confirmed) {
    router.delete(`/admin/calendar/quarters/${id}`);
  }
};

const runGeneration = () => {
  genForm.post('/admin/calendar/generate', {
    onSuccess: () => {
      generateOpen.value = false;
    }
  });
};
</script>

<template>
  <Head title="Quarters Setup — SITS ERP" />

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
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              {{ module.description }}
              <span v-if="activeYear" class="text-blue-400 font-bold block mt-1">
                Active Year: {{ activeYear.label }} ({{ new Date(activeYear.start_date).toLocaleDateString() }} - {{ new Date(activeYear.end_date).toLocaleDateString() }})
              </span>
              <span v-else class="text-amber-500 font-semibold block mt-1">
                No active Fiscal Year. Please activate one in Strategic Plan -> Fiscal Years.
              </span>
            </p>
          </div>
        </div>
        <div class="flex items-center gap-3 shrink-0">
          <button 
            @click="generateOpen = true"
            class="text-sm font-semibold bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-emerald-500/10 hover:shadow-emerald-500/20 cursor-pointer"
          >
            Generate Calendar
          </button>
          <button 
            @click="openCreateModal"
            class="text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
          >
            + New Quarter
          </button>
        </div>
      </div>
    </section>

    <!-- Quarters List -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div 
        v-for="quarter in quarters" 
        :key="quarter.id"
        class="rounded-2xl border border-slate-900 bg-slate-900/15 p-6 hover:border-slate-800 transition-all relative overflow-hidden group"
      >
        <div class="absolute top-0 right-0 w-32 h-32 rounded-full bg-blue-500/5 blur-3xl pointer-events-none group-hover:bg-blue-500/10 transition-colors"></div>
        <div class="flex items-center justify-between mb-4">
          <h4 class="text-lg font-bold text-white tracking-tight">{{ quarter.name }}</h4>
          <span class="text-xs text-slate-500 bg-slate-950/40 px-2.5 py-1 rounded-md border border-slate-900">
            {{ quarter.fortnights?.length || 0 }} Fortnights
          </span>
        </div>
        <div class="space-y-2 text-xs text-slate-400">
          <div class="flex justify-between">
            <span>Start Date:</span>
            <span class="font-medium text-slate-300">{{ new Date(quarter.start_date).toLocaleDateString() }}</span>
          </div>
          <div class="flex justify-between">
            <span>End Date:</span>
            <span class="font-medium text-slate-300">{{ new Date(quarter.end_date).toLocaleDateString() }}</span>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-slate-900">
          <button 
            @click="openEditModal(quarter)"
            class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
          >
            Edit
          </button>
          <button 
            @click="deleteQuarter(quarter.id)"
            class="text-[11px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-rose-950/20 border border-slate-850 hover:border-rose-900/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
          >
            Delete
          </button>
        </div>
      </div>

      <div 
        v-if="!quarters.length" 
        class="col-span-full py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl"
      >
        No quarters generated yet. Click "Generate Calendar" to automatically generate quarters and fortnights for the active year.
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-6">
          {{ editingQuarter ? 'Edit Quarter' : 'Create Quarter' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- Year Select -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Fiscal Year</label>
            <select 
              v-model="form.year_id"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              required
            >
              <option value="" disabled>Select Year</option>
              <option v-for="year in years" :key="year.id" :value="year.id">{{ year.label }}</option>
            </select>
            <div v-if="form.errors.year_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.year_id }}</div>
          </div>

          <!-- Quarter Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Quarter Name</label>
            <input 
              v-model="form.name" 
              type="text" 
              required
              placeholder="e.g. Q1" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.name ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.name" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.name }}</div>
          </div>

          <!-- Start Date -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Start Date</label>
            <input 
              v-model="form.start_date" 
              type="date" 
              required
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
              required
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
              {{ form.processing ? 'Saving...' : 'Save Quarter' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Generate Modal -->
    <div v-if="generateOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-4">Generate Calendar Structure</h3>
        <p class="text-slate-400 text-sm mb-6 leading-relaxed">
          This will generate 4 quarters, 2-week fortnights, and daily working calendars.
          <br />
          <span class="text-rose-455 font-semibold">Warning: This will clear any existing quarters, fortnights, and day structures for the selected year.</span>
        </p>

        <form @submit.prevent="runGeneration" class="space-y-5">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Select Year</label>
            <select 
              v-model="genForm.year_id"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              required
            >
              <option value="" disabled>Select Year</option>
              <option v-for="year in years" :key="year.id" :value="year.id">{{ year.label }}</option>
            </select>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button 
              type="button" 
              @click="generateOpen = false"
              class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="genForm.processing"
              class="text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer"
            >
              {{ genForm.processing ? 'Generating...' : 'Start Generation' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
