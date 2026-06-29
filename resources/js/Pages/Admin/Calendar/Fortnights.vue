<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, required: true },
  fortnights: { type: Array, default: () => [] },
  quarters: { type: Array, default: () => [] },
  activeYear: { type: Object, default: null },
});

// ─── Collapse state ───────────────────────────────────────────────────────────
// Quarters start expanded; click the header to toggle.
const collapsedQuarters = ref(new Set());

const toggleQuarter = (qName) => {
  if (collapsedQuarters.value.has(qName)) {
    collapsedQuarters.value.delete(qName);
  } else {
    collapsedQuarters.value.add(qName);
  }
  // Force Vue reactivity on the Set
  collapsedQuarters.value = new Set(collapsedQuarters.value);
};

const isCollapsed = (qName) => collapsedQuarters.value.has(qName);

// ─── Group fortnights by Quarter ─────────────────────────────────────────────
const groupedFortnights = computed(() => {
  const groups = {};
  props.fortnights.forEach(f => {
    const qName = f.quarter ? f.quarter.name : 'Unassigned';
    if (!groups[qName]) groups[qName] = { label: qName, items: [] };
    groups[qName].items.push(f);
  });
  return Object.values(groups);
});

// ─── Modal state ──────────────────────────────────────────────────────────────
const modalOpen = ref(false);
const editingFortnight = ref(null);

const form = useForm({
  quarter_id: '',
  name: '',
  start_date: '',
  end_date: '',
});

const openCreateModal = () => {
  editingFortnight.value = null;
  form.reset();
  if (props.quarters.length) {
    form.quarter_id = props.quarters[0].id;
  }
  form.clearErrors();
  modalOpen.value = true;
};

const openEditModal = (fortnight) => {
  editingFortnight.value = fortnight;
  form.quarter_id = fortnight.quarter_id;
  form.name = fortnight.name;
  form.start_date = fortnight.start_date ? fortnight.start_date.substring(0, 10) : '';
  form.end_date = fortnight.end_date ? fortnight.end_date.substring(0, 10) : '';
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  if (editingFortnight.value) {
    form.put(`/admin/calendar/fortnights/${editingFortnight.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/calendar/fortnights', {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteFortnight = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Fortnight',
    message: 'Are you sure you want to delete this fortnight? This will dissociate any days assigned to it.',
  });
  if (confirmed) {
    router.delete(`/admin/calendar/fortnights/${id}`);
  }
};

// Date display helper
const fmt = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
  <Head title="Fortnights Setup — SITS ERP" />

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
                Viewing Sprints for Year: {{ activeYear.label }}
              </span>
            </p>
          </div>
        </div>
        <button 
          @click="openCreateModal"
          class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
        >
          + New Fortnight
        </button>
      </div>
    </section>

    <!-- Fortnights by Quarter -->
    <div class="space-y-4">
      <div v-for="group in groupedFortnights" :key="group.label">

        <!-- ── Quarter header (clickable to collapse) ── -->
        <button
          type="button"
          @click="toggleQuarter(group.label)"
          class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-2xl border border-slate-900 bg-slate-900/20 hover:bg-slate-900/40 transition-colors cursor-pointer group"
        >
          <div class="flex items-center gap-2 text-slate-300">
            <span
              class="transition-transform duration-200"
              :class="isCollapsed(group.label) ? 'rotate-0' : 'rotate-90'"
            >
              <Icon name="ChevronRight" :size="18" class="text-blue-400" />
            </span>
            <span class="text-base font-bold tracking-wide">{{ group.label }}</span>
            <span class="ml-2 text-xs font-semibold text-slate-500 bg-slate-950/60 border border-slate-900 px-2 py-0.5 rounded-md">
              {{ group.items.length }} sprint{{ group.items.length !== 1 ? 's' : '' }}
            </span>
          </div>
          <span class="text-[11px] text-slate-600 font-semibold group-hover:text-slate-500 transition-colors">
            {{ isCollapsed(group.label) ? 'Expand ▼' : 'Collapse ▲' }}
          </span>
        </button>

        <!-- ── Fortnight cards (collapsible body) ── -->
        <div
          v-show="!isCollapsed(group.label)"
          class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 pt-2 pl-2"
        >
          <div
            v-for="fortnight in group.items"
            :key="fortnight.id"
            class="rounded-2xl border border-slate-900 bg-slate-900/10 p-5 hover:border-blue-900/50 hover:bg-slate-900/20 transition-all relative group cursor-pointer"
            @click="router.visit(`/admin/calendar/fortnights/${fortnight.id}`)"
          >
            <!-- Name + days badge -->
            <div class="flex items-center justify-between mb-3">
              <h4 class="font-bold text-white">{{ fortnight.name }}</h4>
              <span class="text-xs text-slate-500 bg-slate-950/40 px-2 py-0.5 rounded-md border border-slate-900">
                {{ fortnight.days?.length || 0 }} days
              </span>
            </div>
            
            <!-- Dates -->
            <div class="space-y-1.5 text-xs text-slate-400">
              <div class="flex justify-between">
                <span>Start:</span>
                <span class="text-slate-300">{{ fmt(fortnight.start_date) }}</span>
              </div>
              <div class="flex justify-between">
                <span>End:</span>
                <span class="text-slate-300">{{ fmt(fortnight.end_date) }}</span>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between gap-2 mt-4 pt-3 border-t border-slate-900">
              <span class="text-[10px] font-semibold text-blue-400 flex items-center gap-1">
                <Icon name="ExternalLink" :size="11" /> Click to view
              </span>
              <div class="flex gap-2" @click.stop>
                <button
                  @click="openEditModal(fortnight)"
                  class="text-[10px] font-bold px-2.5 py-1.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-md transition-colors cursor-pointer"
                >
                  Edit
                </button>
                <button
                  @click="deleteFortnight(fortnight.id)"
                  class="text-[10px] font-bold px-2.5 py-1.5 bg-slate-900 hover:bg-rose-950/20 border border-slate-850 hover:border-rose-900/20 text-slate-400 hover:text-rose-400 rounded-md transition-colors cursor-pointer"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div 
        v-if="!fortnights.length" 
        class="py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl"
      >
        No fortnights defined. Generate the calendar in the Quarters screen first.
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-6">
          {{ editingFortnight ? 'Edit Fortnight' : 'Create Fortnight' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- Quarter Select -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Quarter</label>
            <select 
              v-model="form.quarter_id"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              required
            >
              <option value="" disabled>Select Quarter</option>
              <option v-for="q in quarters" :key="q.id" :value="q.id">{{ q.name }}</option>
            </select>
            <div v-if="form.errors.quarter_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.quarter_id }}</div>
          </div>

          <!-- Fortnight Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Sprint / Fortnight Name</label>
            <input 
              v-model="form.name" 
              type="text" 
              required
              placeholder="e.g. Q1-F1" 
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
              {{ form.processing ? 'Saving...' : 'Save Fortnight' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
