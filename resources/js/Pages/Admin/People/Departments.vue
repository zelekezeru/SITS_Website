<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, required: true },
  departments: { type: Array, default: () => [] },
  campuses: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
});

// Modal state
const modalOpen = ref(false);
const editingDepartment = ref(null);

const form = useForm({
  name_en: '',
  name_am: '',
  parent_id: '',
  campus_id: '',
  head_user_id: '',
  is_active: true,
});

const openCreateModal = () => {
  editingDepartment.value = null;
  form.reset();
  form.clearErrors();
  modalOpen.value = true;
};

const openEditModal = (department) => {
  editingDepartment.value = department;
  form.name_en = department.name_en;
  form.name_am = department.name_am || '';
  form.parent_id = department.parent_id || '';
  form.campus_id = department.campus_id || '';
  form.head_user_id = department.head_user_id || '';
  form.is_active = !!department.is_active;
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  if (editingDepartment.value) {
    form.put(`/admin/departments/${editingDepartment.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/departments', {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteDepartment = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Department',
    message: 'Are you sure you want to delete this department? This will set parent references of sub-departments to null.',
  });
  if (confirmed) {
    router.delete(`/admin/departments/${id}`);
  }
};
</script>

<template>
  <Head title="Departments — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Building2" :size="26" />
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
          + Add Department
        </button>
      </div>
    </section>

    <!-- Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">English Name</th>
              <th class="pb-3">Amharic Name</th>
              <th class="pb-3">Campus</th>
              <th class="pb-3">Parent Department</th>
              <th class="pb-3">Department Head</th>
              <th class="pb-3">Status</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="dept in departments" :key="dept.id" class="hover:bg-slate-900/40">
              <td class="py-4 font-semibold text-slate-200">
                <Link :href="'/admin/organization/departments/' + dept.id" class="hover:underline text-blue-450 font-semibold">{{ dept.name_en }}</Link>
              </td>
              <td class="py-4 text-slate-400">{{ dept.name_am || '—' }}</td>
              <td class="py-4 text-slate-400">
                <span v-if="dept.campus" class="text-slate-300 font-medium">{{ dept.campus.name_en }}</span>
                <span v-else class="text-slate-650 italic">None</span>
              </td>
              <td class="py-4 text-slate-400">
                <span v-if="dept.parent" class="text-slate-400 font-medium">{{ dept.parent.name_en }}</span>
                <span v-else class="text-slate-650 italic">None</span>
              </td>
              <td class="py-4 text-slate-400">
                <span v-if="dept.head" class="text-blue-400 font-medium">{{ dept.head.name }}</span>
                <span v-else class="text-slate-650 italic">Unassigned</span>
              </td>
              <td class="py-4">
                <span 
                  class="px-2.5 py-0.5 text-xs rounded-full font-bold border"
                  :class="dept.is_active 
                    ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400' 
                    : 'bg-slate-800/60 border-slate-800 text-slate-500'"
                >
                  {{ dept.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="py-4 text-right space-x-2">
                <button 
                  @click="openEditModal(dept)"
                  class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
                >
                  Edit
                </button>
                <button 
                  @click="deleteDepartment(dept.id)"
                  class="text-[11px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-rose-950/20 border border-slate-850 hover:border-rose-900/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                >
                  Delete
                </button>
              </td>
            </tr>
            <tr v-if="!departments.length">
              <td colspan="7" class="py-8 text-center text-slate-600 italic">
                No departments defined.
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
          {{ editingDepartment ? 'Edit Department' : 'Add Department' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- English Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">English Name</label>
            <input 
              v-model="form.name_en" 
              type="text" 
              required
              placeholder="e.g. Finance Department" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.name_en ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.name_en" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.name_en }}</div>
          </div>

          <!-- Amharic Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Amharic Name</label>
            <input 
              v-model="form.name_am" 
              type="text" 
              placeholder="e.g. የሂሳብ ክፍል" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.name_am ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.name_am" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.name_am }}</div>
          </div>

          <!-- Campus -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Campus Location</label>
            <select 
              v-model="form.campus_id"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            >
              <option value="">None / Remote</option>
              <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name_en }}</option>
            </select>
            <div v-if="form.errors.campus_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.campus_id }}</div>
          </div>

          <!-- Parent Department -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Parent Department</label>
            <select 
              v-model="form.parent_id"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            >
              <option value="">None (Top-Level)</option>
              <option 
                v-for="d in departments.filter(d => !editingDepartment || d.id !== editingDepartment.id)" 
                :key="d.id" 
                :value="d.id"
              >
                {{ d.name_en }}
              </option>
            </select>
            <div v-if="form.errors.parent_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.parent_id }}</div>
          </div>

          <!-- Department Head -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Department Head</label>
            <select 
              v-model="form.head_user_id"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            >
              <option value="">None / Unassigned</option>
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
            </select>
            <div v-if="form.errors.head_user_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.head_user_id }}</div>
          </div>

          <!-- Active Toggle -->
          <div class="flex items-center gap-3">
            <input 
              v-model="form.is_active" 
              type="checkbox" 
              id="is_active"
              class="rounded border-slate-850 bg-slate-950 text-blue-500 focus:ring-0"
            />
            <label for="is_active" class="text-sm font-semibold text-slate-300 cursor-pointer">Active</label>
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
              {{ form.processing ? 'Saving...' : 'Save Department' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
