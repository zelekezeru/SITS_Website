<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, required: true },
  employees: { type: Array, default: () => [] },
  positions: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
  employmentTypes: { type: Array, default: () => [] },
});

// Search and filter state
const search = ref('');
const selectedDept = ref('');
const selectedPos = ref('');

const filteredEmployees = computed(() => {
  return props.employees.filter(emp => {
    const matchesSearch = emp.full_name_en.toLowerCase().includes(search.value.toLowerCase()) || 
                          (emp.full_name_am && emp.full_name_am.includes(search.value)) ||
                          emp.staff_no.toLowerCase().includes(search.value.toLowerCase());
    const matchesDept = !selectedDept.value || emp.department_id === Number(selectedDept.value);
    const matchesPos = !selectedPos.value || emp.position_id === Number(selectedPos.value);
    return matchesSearch && matchesDept && matchesPos;
  });
});

// Modal state
const modalOpen = ref(false);
const editingEmployee = ref(null);

const form = useForm({
  user_id: '',
  staff_no: '',
  full_name_en: '',
  full_name_am: '',
  position_id: '',
  department_id: '',
  reporting_to_id: '',
  employment_type: 'full_time',
  base_salary: 0,
  legal_daily_hour_limit: 8,
  hired_at: '',
  is_active: true,
});

const openCreateModal = () => {
  editingEmployee.value = null;
  form.reset();
  // Pre-fill next staff number format e.g. SITS-2026-X
  const nextNum = props.employees.length + 1;
  form.staff_no = `SITS-2026-${String(nextNum).padStart(3, '0')}`;
  form.clearErrors();
  modalOpen.value = true;
};

const openEditModal = (emp) => {
  editingEmployee.value = emp;
  form.user_id = emp.user_id;
  form.staff_no = emp.staff_no;
  form.full_name_en = emp.full_name_en;
  form.full_name_am = emp.full_name_am || '';
  form.position_id = emp.position_id || '';
  form.department_id = emp.department_id || '';
  form.reporting_to_id = emp.reporting_to_id || '';
  form.employment_type = emp.employment_type || 'full_time';
  form.base_salary = Number(emp.base_salary);
  form.legal_daily_hour_limit = emp.legal_daily_hour_limit || 8;
  form.hired_at = emp.hired_at ? emp.hired_at.substring(0, 10) : '';
  form.is_active = !!emp.is_active;
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  if (editingEmployee.value) {
    form.put(`/admin/employees/${editingEmployee.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/employees', {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteEmployee = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Employee',
    message: 'Are you sure you want to delete this employee? This will soft delete their personnel profile.',
  });
  if (confirmed) {
    router.delete(`/admin/employees/${id}`);
  }
};

const statusClass = (status) => {
  const map = {
    active: 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400',
    on_leave: 'bg-amber-500/10 border-amber-500/25 text-amber-400',
    suspended: 'bg-rose-500/10 border-rose-500/25 text-rose-455',
    terminated: 'bg-red-500/10 border-red-500/25 text-red-400',
    inactive: 'bg-slate-800/60 border-slate-800 text-slate-500',
  };
  return map[status] || 'bg-slate-800/60 border-slate-800 text-slate-500';
};
</script>

<template>
  <Head title="Employees Setup — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Users" :size="26" />
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
          + Add Employee
        </button>
      </div>
    </section>

    <!-- Filters & Search -->
    <div class="flex flex-wrap items-center gap-4 bg-slate-900/10 border border-slate-900 p-5 rounded-2xl">
      <div class="flex-1 min-w-[240px]">
        <input 
          v-model="search" 
          type="text" 
          placeholder="Search by name, staff ID..."
          class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-2 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
        />
      </div>
      <div class="w-48">
        <select 
          v-model="selectedDept"
          class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-2 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
        >
          <option value="">All Departments</option>
          <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name_en }}</option>
        </select>
      </div>
      <div class="w-48">
        <select 
          v-model="selectedPos"
          class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-2 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
        >
          <option value="">All Positions</option>
          <option v-for="p in positions" :key="p.id" :value="p.id">{{ p.title_en }}</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Staff No</th>
              <th class="pb-3">Full Name (EN/AM)</th>
              <th class="pb-3">Department</th>
              <th class="pb-3">Position</th>
              <th class="pb-3">Reports To</th>
              <th class="pb-3">Type</th>
              <th class="pb-3">Salary</th>
              <th class="pb-3">Status</th>
              <th class="pb-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr
              v-for="emp in filteredEmployees"
              :key="emp.id"
              class="hover:bg-slate-900/40 cursor-pointer group"
              @click="router.visit(`/admin/employees/${emp.id}`)"
            >
              <td class="py-4 font-mono text-xs text-blue-400 font-semibold">{{ emp.staff_no }}</td>
              <td class="py-4">
                <div class="font-semibold text-slate-200 group-hover:text-blue-300 transition-colors">{{ emp.full_name_en }}</div>
                <div class="text-xs text-slate-500 mt-0.5">{{ emp.full_name_am || '—' }}</div>
              </td>
              <td class="py-4 text-slate-400">
                <span v-if="emp.department" class="font-medium">{{ emp.department.name_en }}</span>
                <span v-else class="text-slate-650 italic">None</span>
              </td>
              <td class="py-4 text-slate-400">
                <span v-if="emp.position" class="font-medium text-slate-300">{{ emp.position.title_en }}</span>
                <span v-else class="text-slate-650 italic">None</span>
              </td>
              <td class="py-4 text-slate-400">
                <span v-if="emp.reporting_to" class="font-medium text-slate-450">{{ emp.reporting_to.full_name_en }}</span>
                <span v-else class="text-slate-650 italic">None</span>
              </td>
              <td class="py-4 text-slate-400 capitalize">{{ emp.employment_type.replace('_', ' ') }}</td>
              <td class="py-4 text-slate-300 font-mono font-medium">{{ Number(emp.base_salary).toLocaleString() }} ETB</td>
              <td class="py-4">
                <span
                  class="px-2.5 py-0.5 text-xs rounded-full font-bold border capitalize"
                  :class="statusClass(emp.status)"
                >
                  {{ emp.status || (emp.is_active ? 'active' : 'inactive') }}
                </span>
              </td>
              <td class="py-4 text-right space-x-2" @click.stop>
                <button
                  @click="openEditModal(emp)"
                  class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
                >
                  Edit
                </button>
                <button 
                  @click="deleteEmployee(emp.id)"
                  class="text-[11px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-rose-950/20 border border-slate-850 hover:border-rose-900/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                >
                  Delete
                </button>
              </td>
            </tr>
            <tr v-if="!filteredEmployees.length">
              <td colspan="9" class="py-8 text-center text-slate-600 italic">
                No employees matching filter criteria.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-2xl rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-white mb-6">
          {{ editingEmployee ? 'Edit Employee Profile' : 'Add Employee Profile' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- User Login Account -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Login Account</label>
              <select 
                v-model="form.user_id"
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
                required
              >
                <option value="" disabled>Select User Account</option>
                <option v-for="u in users" :key="u.id" :value="u.id">
                  {{ u.name }} ({{ u.email }}) — {{ u.roles?.map(r=>r.name).join(', ') }}
                </option>
              </select>
              <div v-if="form.errors.user_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.user_id }}</div>
            </div>

            <!-- Staff No -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Staff ID / No</label>
              <input 
                v-model="form.staff_no" 
                type="text" 
                required
                placeholder="e.g. SITS-2026-001" 
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
                :class="form.errors.staff_no ? 'border-rose-500/50' : 'border-slate-850'"
              />
              <div v-if="form.errors.staff_no" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.staff_no }}</div>
            </div>

            <!-- Full Name EN -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Full Name (English)</label>
              <input 
                v-model="form.full_name_en" 
                type="text" 
                required
                placeholder="e.g. Elfinesh Dedefa" 
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
                :class="form.errors.full_name_en ? 'border-rose-500/50' : 'border-slate-850'"
              />
              <div v-if="form.errors.full_name_en" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.full_name_en }}</div>
            </div>

            <!-- Full Name AM -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Full Name (Amharic)</label>
              <input 
                v-model="form.full_name_am" 
                type="text" 
                placeholder="e.g. እልፍነሽ ደደፋ" 
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
                :class="form.errors.full_name_am ? 'border-rose-500/50' : 'border-slate-850'"
              />
              <div v-if="form.errors.full_name_am" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.full_name_am }}</div>
            </div>

            <!-- Position -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Position</label>
              <select 
                v-model="form.position_id"
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              >
                <option value="">None / Unassigned</option>
                <option v-for="p in positions" :key="p.id" :value="p.id">{{ p.title_en }}</option>
              </select>
              <div v-if="form.errors.position_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.position_id }}</div>
            </div>

            <!-- Department -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Department</label>
              <select 
                v-model="form.department_id"
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              >
                <option value="">None / Unassigned</option>
                <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name_en }}</option>
              </select>
              <div v-if="form.errors.department_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.department_id }}</div>
            </div>

            <!-- Reports To -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Reports To Manager</label>
              <select 
                v-model="form.reporting_to_id"
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              >
                <option value="">None (Top-Level)</option>
                <option 
                  v-for="e in employees.filter(e => !editingEmployee || e.id !== editingEmployee.id)" 
                  :key="e.id" 
                  :value="e.id"
                >
                  {{ e.full_name_en }} ({{ e.position?.title_en || 'Staff' }})
                </option>
              </select>
              <div v-if="form.errors.reporting_to_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.reporting_to_id }}</div>
            </div>

            <!-- Employment Type -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Employment Type</label>
              <select 
                v-model="form.employment_type"
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
                required
              >
                <option v-for="type in employmentTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
              </select>
              <div v-if="form.errors.employment_type" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.employment_type }}</div>
            </div>

            <!-- Base Salary -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Base Salary (ETB)</label>
              <input 
                v-model="form.base_salary" 
                type="number" 
                step="0.01"
                min="0"
                required
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm font-mono"
                :class="form.errors.base_salary ? 'border-rose-500/50' : 'border-slate-850'"
              />
              <div v-if="form.errors.base_salary" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.base_salary }}</div>
            </div>

            <!-- Legal Daily Hour Limit -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Legal Daily Hour Limit</label>
              <input 
                v-model="form.legal_daily_hour_limit" 
                type="number" 
                min="1"
                max="24"
                required
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
                :class="form.errors.legal_daily_hour_limit ? 'border-rose-500/50' : 'border-slate-850'"
              />
              <div v-if="form.errors.legal_daily_hour_limit" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.legal_daily_hour_limit }}</div>
            </div>

            <!-- Hire Date -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Hired Date</label>
              <input 
                v-model="form.hired_at" 
                type="date" 
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
                :class="form.errors.hired_at ? 'border-rose-500/50' : 'border-slate-850'"
              />
              <div v-if="form.errors.hired_at" class="text-xs text-rose-455 mt-1 font-semibold">{{ form.errors.hired_at }}</div>
            </div>

            <!-- Active Toggle -->
            <div class="flex items-center gap-3 pt-6">
              <input 
                v-model="form.is_active" 
                type="checkbox" 
                id="is_active"
                class="rounded border-slate-850 bg-slate-950 text-blue-500 focus:ring-0"
              />
              <label for="is_active" class="text-sm font-semibold text-slate-300 cursor-pointer">Active / Approved for Payroll</label>
            </div>
          </div>

          <!-- Modal Actions -->
          <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-900">
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
              {{ form.processing ? 'Saving...' : 'Save Employee' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
