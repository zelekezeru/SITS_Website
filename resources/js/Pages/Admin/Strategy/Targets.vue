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
  targets: { type: Array, default: () => [] },
  goals: { type: Array, default: () => [] },
  years: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
});

// Modal state
const modalOpen = ref(false);
const editingTarget = ref(null);

const activeYear = props.years.find(y => y.active);

const form = useForm({
  goal_id: props.goals.length > 0 ? props.goals[0].id : '',
  year_id: activeYear ? activeYear.id : '',
  name: '',
  budget: 0,
  value: 0,
  unit: '',
  department_ids: [],
});

const openCreateModal = () => {
  editingTarget.value = null;
  form.reset({
    goal_id: props.goals.length > 0 ? props.goals[0].id : '',
    year_id: activeYear ? activeYear.id : '',
    name: '',
    budget: 0,
    value: 0,
    unit: '',
    department_ids: [],
  });
  form.clearErrors();
  modalOpen.value = true;
};

const openEditModal = (target) => {
  editingTarget.value = target;
  form.goal_id = target.goal_id;
  form.year_id = target.year_id;
  form.name = target.name;
  form.budget = parseFloat(target.budget);
  form.value = parseFloat(target.value);
  form.unit = target.unit || '';
  form.department_ids = target.departments ? target.departments.map(d => d.id) : [];
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  if (editingTarget.value) {
    form.put(`/admin/strategy/targets/${editingTarget.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/strategy/targets', {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteTarget = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Target',
    message: 'Are you sure you want to delete this target?',
  });
  if (confirmed) {
    router.delete(`/admin/strategy/targets/${id}`);
  }
};

const toggleDepartment = (deptId) => {
  const idx = form.department_ids.indexOf(deptId);
  if (idx > -1) {
    form.department_ids.splice(idx, 1);
  } else {
    form.department_ids.push(deptId);
  }
};
</script>

<template>
  <Head title="Strategic Targets Setup — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Target" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
          </div>
        </div>
        <button 
          @click="openCreateModal"
          :disabled="goals.length === 0"
          class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white disabled:from-slate-800 disabled:to-slate-800 disabled:text-slate-500 px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
        >
          + New Target
        </button>
      </div>
    </section>

    <!-- Table of Targets -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Target Name</th>
              <th class="pb-3">Goal</th>
              <th class="pb-3">Budget</th>
              <th class="pb-3">Metric Target</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-if="targets.length === 0">
              <td colspan="5" class="py-6 text-center text-slate-500 italic">No targets defined yet. Create one above!</td>
            </tr>
            <tr v-for="t in targets" :key="t.id" class="hover:bg-slate-900/40">
              <td class="py-4 font-semibold text-slate-200">
                <span>{{ t.name }}</span>
                <div class="flex flex-wrap gap-1.5 mt-2">
                  <span v-for="d in t.departments" :key="d.id" class="px-1.5 py-0.5 rounded bg-blue-500/10 border border-blue-500/25 text-blue-400 text-[10px]">
                    {{ d.name_en }}
                  </span>
                </div>
              </td>
              <td class="py-4 text-slate-400 max-w-xs truncate">{{ t.goal?.name }}</td>
              <td class="py-4 font-medium text-slate-350">ETB {{ parseFloat(t.budget).toLocaleString() }}</td>
              <td class="py-4 font-bold text-indigo-400">{{ parseFloat(t.value) }} {{ t.unit }}</td>
              <td class="py-4 text-right space-x-2 shrink-0">
                <button 
                  @click="openEditModal(t)"
                  class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
                >
                  Edit
                </button>
                <button 
                  @click="deleteTarget(t.id)"
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
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative my-8">
        <h3 class="text-xl font-bold text-white mb-6">
          {{ editingTarget ? 'Edit Strategic Target' : 'Create Strategic Target' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Goal Selection -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Strategic Goal</label>
              <select 
                v-model="form.goal_id" 
                required
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm border-slate-850"
              >
                <option v-for="goal in goals" :key="goal.id" :value="goal.id">
                  {{ goal.name }}
                </option>
              </select>
              <div v-if="form.errors.goal_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.goal_id }}</div>
            </div>

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
          </div>

          <!-- Target Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Target Metric Title</label>
            <input 
              v-model="form.name" 
              type="text" 
              required
              placeholder="e.g. Relocate academic databases to cloud" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="form.errors.name ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="form.errors.name" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.name }}</div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Budget -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Allocated Budget</label>
              <input 
                v-model="form.budget" 
                type="number" 
                required
                min="0"
                step="0.01"
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm border-slate-850"
                :class="form.errors.budget ? 'border-rose-500/50' : ''"
              />
              <div v-if="form.errors.budget" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.budget }}</div>
            </div>

            <!-- Value -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Target Value</label>
              <input 
                v-model="form.value" 
                type="number" 
                required
                min="0"
                step="0.01"
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm border-slate-850"
                :class="form.errors.value ? 'border-rose-500/50' : ''"
              />
              <div v-if="form.errors.value" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.value }}</div>
            </div>

            <!-- Unit -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Metric Unit</label>
              <input 
                v-model="form.unit" 
                type="text" 
                placeholder="e.g. Systems" 
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm border-slate-850"
                :class="form.errors.unit ? 'border-rose-500/50' : ''"
              />
              <div v-if="form.errors.unit" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.unit }}</div>
            </div>
          </div>

          <!-- Departments Tagging (Multi-select Grid) -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Responsible Departments</label>
            <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto p-3 rounded-xl border border-slate-900 bg-slate-950/40">
              <div 
                v-for="dept in departments" 
                :key="dept.id"
                @click="toggleDepartment(dept.id)"
                class="flex items-center gap-2 p-2 rounded-lg border text-xs cursor-pointer select-none transition-all"
                :class="form.department_ids.includes(dept.id) 
                  ? 'border-blue-500/40 bg-blue-500/5 text-blue-300' 
                  : 'border-slate-850 hover:border-slate-800 text-slate-400'"
              >
                <div class="w-4 h-4 rounded border flex items-center justify-center shrink-0" :class="form.department_ids.includes(dept.id) ? 'bg-blue-600 border-blue-500 text-white' : 'border-slate-700'">
                  <span v-if="form.department_ids.includes(dept.id)" class="text-[9px] font-bold">✓</span>
                </div>
                <span class="truncate">{{ dept.name_en }}</span>
              </div>
            </div>
            <div v-if="form.errors.department_ids" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.department_ids }}</div>
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
              {{ form.processing ? 'Saving...' : 'Save Target' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
