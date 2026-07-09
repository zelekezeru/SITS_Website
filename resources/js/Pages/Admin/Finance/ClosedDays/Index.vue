<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, default: () => ({}) },
  closedDays: { type: Array, default: () => [] },
  types: { type: Object, default: () => ({}) },
  can: { type: Object, default: () => ({}) },
});

const TYPE_COLORS = {
  holiday: 'bg-indigo-500/10 border-indigo-500/20 text-indigo-400',
  special_closure: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
  official: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
};

const modalOpen = ref(false);
const editingDay = ref(null);
const search = ref('');

const form = useForm({
  start_date: '',
  end_date: '',
  name: '',
  type: 'holiday',
  description: '',
  is_active: true,
});

// Keep end_date in step with start_date until the user sets a later end date,
// so a single-day closure just needs the one field.
const onStartDateChange = () => {
  if (!form.end_date || form.end_date < form.start_date) {
    form.end_date = form.start_date;
  }
};

const openCreate = () => {
  editingDay.value = null;
  form.reset();
  form.clearErrors();
  modalOpen.value = true;
};

const openEdit = (day) => {
  editingDay.value = day;
  form.start_date = day.start_date;
  form.end_date = day.end_date;
  form.name = day.name;
  form.type = day.type;
  form.description = day.description || '';
  form.is_active = day.is_active;
  form.clearErrors();
  modalOpen.value = true;
};

const submit = () => {
  if (editingDay.value) {
    form.put(`/admin/closed-days/${editingDay.value.id}`, {
      preserveScroll: true,
      onSuccess: () => { modalOpen.value = false; },
    });
  } else {
    form.post('/admin/closed-days', {
      preserveScroll: true,
      onSuccess: () => { modalOpen.value = false; },
    });
  }
};

// "2026-01-07" for a single day, or "2026-01-07 → 2026-01-09" for a range.
const formatRange = (day) =>
  day.start_date === day.end_date ? day.start_date : `${day.start_date} → ${day.end_date}`;

const remove = async (day) => {
  const ok = await confirm({
    title: 'Remove Closed Day',
    message: `Are you sure you want to remove "${day.name}" (${formatRange(day)})?`,
  });
  if (ok) {
    router.delete(`/admin/closed-days/${day.id}`, { preserveScroll: true });
  }
};

const filteredClosedDays = computed(() => {
  if (!search.value) return props.closedDays;
  const q = search.value.toLowerCase();
  return props.closedDays.filter(
    (d) =>
      d.name.toLowerCase().includes(q) ||
      d.start_date.includes(q) ||
      d.end_date.includes(q) ||
      d.type_label.toLowerCase().includes(q)
  );
});
</script>

<template>
  <Head title="Closed Days & Holidays — SITS ERP" />

  <div class="space-y-8">
    <!-- Header Section -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-indigo-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
            <Icon name="CalendarX" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Finance · Attendance</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Closed Days & Holidays</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Register national holidays, religious festivals, and official closures.
              These days are used in Mass Permissions to automatically permit absences and prevent salary deductions.
            </p>
          </div>
        </div>
        <button v-if="can.manage" @click="openCreate" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
          + Add Closed Day
        </button>
      </div>
    </section>

    <!-- Search and Table -->
    <div class="space-y-4">
      <div class="flex items-center justify-between gap-4">
        <div class="relative w-full max-w-xs">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
            <Icon name="Search" :size="16" />
          </span>
          <input v-model="search" type="text" placeholder="Search holidays..." class="w-full bg-slate-900/40 border border-slate-900 rounded-xl pl-10 pr-4 py-2.5 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500" />
        </div>
      </div>

      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="p-3">Date(s)</th>
              <th class="p-3">Holiday / Closure Name</th>
              <th class="p-3">Type</th>
              <th class="p-3">Description</th>
              <th class="p-3">Status</th>
              <th class="p-3 text-right" v-if="can.manage">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-900">
            <tr v-for="day in filteredClosedDays" :key="day.id" class="hover:bg-slate-900/30 transition-colors">
              <td class="p-3 whitespace-nowrap">
                <span class="font-mono font-semibold text-slate-200">{{ formatRange(day) }}</span>
                <span v-if="day.days_count > 1" class="ml-2 px-2 py-0.5 text-[10px] rounded-full font-bold border border-indigo-500/20 bg-indigo-500/10 text-indigo-400">
                  {{ day.days_count }} days
                </span>
              </td>
              <td class="p-3 font-semibold text-slate-300">{{ day.name }}</td>
              <td class="p-3">
                <span class="px-2 py-0.5 text-[10px] rounded-full font-bold border" :class="TYPE_COLORS[day.type] || 'text-slate-400 border-slate-800'">
                  {{ day.type_label }}
                </span>
              </td>
              <td class="p-3 text-slate-400 text-xs max-w-xs truncate" :title="day.description">{{ day.description || '—' }}</td>
              <td class="p-3">
                <span v-if="day.is_active" class="text-xs text-emerald-400 flex items-center gap-1.5 font-medium">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Active
                </span>
                <span v-else class="text-xs text-slate-500 flex items-center gap-1.5 font-medium">
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span> Inactive
                </span>
              </td>
              <td class="p-3 text-right whitespace-nowrap" v-if="can.manage">
                <button @click="openEdit(day)" class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-indigo-500/50 text-indigo-400 hover:text-indigo-300 bg-slate-900/40 rounded-lg mr-2 transition-colors">
                  Edit
                </button>
                <button @click="remove(day)" class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-rose-500/50 text-rose-400 hover:text-rose-300 bg-slate-900/40 rounded-lg transition-colors">
                  Remove
                </button>
              </td>
            </tr>
            <tr v-if="!filteredClosedDays.length">
              <td colspan="6" class="p-8 text-center text-slate-500 italic">No closed days found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-6">
          {{ editingDay ? 'Edit Closed Day' : 'Add Closed Day' }}
        </h3>
        <form @submit.prevent="submit" class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">From</label>
              <input v-model="form.start_date" @change="onStartDateChange" type="date" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-indigo-500/50" />
              <p v-if="form.errors.start_date" class="text-xs text-rose-400 mt-1">{{ form.errors.start_date }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">To</label>
              <input v-model="form.end_date" type="date" required :min="form.start_date" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-indigo-500/50" />
              <p v-if="form.errors.end_date" class="text-xs text-rose-400 mt-1">{{ form.errors.end_date }}</p>
            </div>
          </div>
          <p class="-mt-1 text-[11px] text-slate-500">Set <span class="text-slate-400 font-medium">To</span> the same as <span class="text-slate-400 font-medium">From</span> for a single-day closure, or a later date for a multi-day one.</p>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Type</label>
            <select v-model="form.type" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-indigo-500/50">
              <option v-for="(label, val) in types" :key="val" :value="val">{{ label }}</option>
            </select>
            <p v-if="form.errors.type" class="text-xs text-rose-400 mt-1">{{ form.errors.type }}</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Name / Title</label>
            <input v-model="form.name" type="text" placeholder="e.g. Ethiopian Christmas" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-indigo-500/50" />
            <p v-if="form.errors.name" class="text-xs text-rose-400 mt-1">{{ form.errors.name }}</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Description</label>
            <textarea v-model="form.description" rows="3" placeholder="Additional details or remarks..." class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-indigo-500/50"></textarea>
            <p v-if="form.errors.description" class="text-xs text-rose-400 mt-1">{{ form.errors.description }}</p>
          </div>
          <div v-if="editingDay" class="flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded border-slate-800 bg-slate-950 text-indigo-600 focus:ring-indigo-500" />
            <label for="is_active" class="text-sm text-slate-300 select-none">Active / Enabled</label>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="modalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-855 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors">Cancel</button>
            <button type="submit" :disabled="form.processing" class="text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50 transition-all">
              {{ editingDay ? 'Save Changes' : 'Add Day' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
