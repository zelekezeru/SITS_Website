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
  module: { type: Object, required: true },
  routeName: { type: String, required: true },
  kpis: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  targets: { type: Array, default: () => [] },
  jobDescriptionVersions: { type: Array, default: () => [] },
});

// Tab/View filtering
const activeTab = ref(
  props.routeName === 'admin.kpis.approvals' ? 'approvals' :
  props.routeName === 'admin.kpis.confirmations' ? 'confirmations' : 'all'
);

const filteredKpis = computed(() => {
  return props.kpis.filter(kpi => {
    if (activeTab.value === 'approvals') {
      return !kpi.approved_by;
    }
    if (activeTab.value === 'confirmations') {
      return kpi.approved_by && !kpi.confirmed_by;
    }
    return true;
  });
});

// Modal state
const modalOpen = ref(false);
const editingKpi = ref(null);

const form = useForm({
  title_en: '',
  title_am: '',
  measure_type: 'quantitative',
  target_value: '',
  unit: '',
  weight: 1.00,
  kpiable_type: '',
  kpiable_id: '',
  employee_ids: [],
});

const openCreateModal = () => {
  editingKpi.value = null;
  form.reset();
  form.clearErrors();
  modalOpen.value = true;
};

const openEditModal = (kpi) => {
  editingKpi.value = kpi;
  form.title_en = kpi.title_en;
  form.title_am = kpi.title_am || '';
  form.measure_type = kpi.measure_type;
  form.target_value = kpi.target_value ? Number(kpi.target_value) : '';
  form.unit = kpi.unit || '';
  form.weight = Number(kpi.weight);
  form.kpiable_type = kpi.kpiable_type || '';
  form.kpiable_id = kpi.kpiable_id || '';
  form.employee_ids = kpi.employees ? kpi.employees.map(e => e.id) : [];
  form.clearErrors();
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const submitForm = () => {
  if (editingKpi.value) {
    form.put(`/admin/kpis/${editingKpi.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/admin/kpis', {
      onSuccess: () => closeModal(),
    });
  }
};

const approveKpi = (id) => {
  router.post(`/admin/kpis/${id}/approve`);
};

const confirmKpi = (id) => {
  router.post(`/admin/kpis/${id}/confirm`);
};

const deleteKpi = async (id) => {
  const confirmed = await confirm({
    title: 'Delete KPI',
    message: 'Are you sure you want to delete this KPI?',
  });
  if (confirmed) {
    router.delete(`/admin/kpis/${id}`);
  }
};
</script>

<template>
  <Head title="Polymorphic KPIs — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Gauge" :size="26" />
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
          + Create KPI
        </button>
      </div>
    </section>

    <!-- Tab selection -->
    <div class="flex border-b border-slate-900 gap-6">
      <button 
        @click="activeTab = 'all'"
        class="pb-3 text-sm font-bold tracking-wide transition-colors cursor-pointer"
        :class="activeTab === 'all' ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-300'"
      >
        All KPIs
      </button>
      <button 
        @click="activeTab = 'approvals'"
        class="pb-3 text-sm font-bold tracking-wide transition-colors cursor-pointer"
        :class="activeTab === 'approvals' ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-300'"
      >
        Pending Approval (Maker)
      </button>
      <button 
        @click="activeTab = 'confirmations'"
        class="pb-3 text-sm font-bold tracking-wide transition-colors cursor-pointer"
        :class="activeTab === 'confirmations' ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-300'"
      >
        Pending Confirmation (Checker)
      </button>
    </div>

    <!-- KPI Cards List -->
    <div class="grid md:grid-cols-2 gap-6">
      <div 
        v-for="kpi in filteredKpis" 
        :key="kpi.id"
        class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 hover:border-slate-850 transition-all flex flex-col justify-between"
      >
        <div class="space-y-4">
          <div class="flex items-start justify-between gap-4">
            <div>
              <span 
                class="px-2 py-0.5 text-[10px] rounded-md font-bold uppercase tracking-wider border"
                :class="kpi.confirmed_by 
                  ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' 
                  : kpi.approved_by 
                    ? 'bg-blue-500/10 border-blue-500/20 text-blue-400' 
                    : 'bg-slate-800/60 border-slate-800 text-slate-500'"
              >
                {{ kpi.confirmed_by ? 'Confirmed' : kpi.approved_by ? 'Approved' : 'Draft / Created' }}
              </span>
            </div>
            <span class="text-xs font-mono font-bold text-slate-500">Weight: {{ kpi.weight }}</span>
          </div>

          <div>
            <h3 class="text-lg font-bold text-white tracking-tight">{{ kpi.title_en }}</h3>
            <p class="text-xs text-slate-500 mt-1" v-if="kpi.title_am">{{ kpi.title_am }}</p>
          </div>

          <div class="space-y-2 pt-2 border-t border-slate-900 text-xs text-slate-400">
            <div class="flex justify-between">
              <span>Target:</span>
              <span class="text-slate-200 font-medium">{{ kpi.target_value }} {{ kpi.unit }} ({{ kpi.measure_type }})</span>
            </div>
            <div class="flex justify-between">
              <span>Assigned Employees:</span>
              <span class="text-slate-300 font-semibold">{{ kpi.employees?.length || 0 }}</span>
            </div>
            <div class="flex justify-between">
              <span>Association:</span>
              <span class="text-slate-300 font-medium">
                <template v-if="kpi.kpiable_type === 'App\\Models\\Target'">
                  🎯 Target: <span class="text-blue-400 font-semibold">{{ kpi.kpiable?.name || '—' }}</span>
                </template>
                <template v-else-if="kpi.kpiable_type === 'App\\Models\\JobDescriptionVersion'">
                  📄 JD: <span class="text-purple-400 font-semibold">{{ kpi.kpiable?.job_description?.title_en || '—' }} (v{{ kpi.kpiable?.version_no }})</span>
                </template>
                <template v-else>
                  <span class="text-slate-500 italic">Standalone KPI</span>
                </template>
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-slate-900">
          <button 
            v-if="!kpi.approved_by"
            @click="approveKpi(kpi.id)"
            class="text-[11px] font-bold px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition-colors cursor-pointer"
          >
            Approve (Maker)
          </button>
          <button 
            v-if="kpi.approved_by && !kpi.confirmed_by"
            @click="confirmKpi(kpi.id)"
            class="text-[11px] font-bold px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors cursor-pointer"
          >
            Confirm (Checker)
          </button>
          <button 
            @click="openEditModal(kpi)"
            class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg transition-colors cursor-pointer"
          >
            Edit
          </button>
          <button 
            @click="deleteKpi(kpi.id)"
            class="text-[11px] font-bold px-3 py-1.5 bg-slate-900 hover:bg-rose-950/20 border border-slate-850 hover:border-rose-900/20 text-slate-400 hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
          >
            Delete
          </button>
        </div>
      </div>

      <div 
        v-if="!filteredKpis.length" 
        class="col-span-full py-12 text-center text-slate-500 border border-dashed border-slate-900 rounded-2xl"
      >
        No KPIs found.
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-white mb-6">
          {{ editingKpi ? 'Edit KPI' : 'Create KPI' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-5">
          <!-- Title EN -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">KPI Title (English)</label>
            <input 
              v-model="form.title_en" 
              type="text" 
              required
              placeholder="e.g. Clean Audit Opinion" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            />
          </div>

          <!-- Title AM -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">KPI Title (Amharic)</label>
            <input 
              v-model="form.title_am" 
              type="text" 
              placeholder="e.g. ንጹህ የሂሳብ ምርመራ አስተያየት" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <!-- Measure Type -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Measure Type</label>
              <select 
                v-model="form.measure_type"
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              >
                <option value="quantitative">Quantitative</option>
                <option value="qualitative">Qualitative</option>
              </select>
            </div>

            <!-- Weight -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Weight</label>
              <input 
                v-model="form.weight" 
                type="number" 
                step="0.01" 
                min="0"
                required
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <!-- Target Value -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Target Value</label>
              <input 
                v-model="form.target_value" 
                type="number" 
                step="0.01"
                placeholder="e.g. 100" 
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-655 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              />
            </div>

            <!-- Unit -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Measurement Unit</label>
              <input 
                v-model="form.unit" 
                type="text" 
                placeholder="e.g. % or counts" 
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-655 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              />
            </div>
          </div>

          <!-- Association / Ownership Type -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Ownership Type</label>
              <select 
                v-model="form.kpiable_type"
                @change="form.kpiable_id = ''"
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              >
                <option value="">Standalone / None</option>
                <option value="App\Models\Target">Strategic Target</option>
                <option value="App\Models\JobDescriptionVersion">Role Job Description</option>
              </select>
            </div>

            <div v-if="form.kpiable_type">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Associate With</label>
              
              <!-- Target select -->
              <select 
                v-if="form.kpiable_type === 'App\Models\Target'"
                v-model="form.kpiable_id"
                required
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              >
                <option value="" disabled>Select Target</option>
                <option v-for="t in targets" :key="t.id" :value="t.id">{{ t.name }}</option>
              </select>

              <!-- Job Description version select -->
              <select 
                v-if="form.kpiable_type === 'App\Models\JobDescriptionVersion'"
                v-model="form.kpiable_id"
                required
                class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              >
                <option value="" disabled>Select Job Description</option>
                <option v-for="jdv in jobDescriptionVersions" :key="jdv.id" :value="jdv.id">
                  {{ jdv.job_description?.title_en || 'JD' }} (v{{ jdv.version_no }})
                </option>
              </select>
            </div>
          </div>

          <!-- Employee multi-select checklist -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Assign Employees</label>
            <div class="max-h-40 overflow-y-auto border border-slate-900 rounded-xl p-3 space-y-2 bg-slate-950/40">
              <div v-for="emp in employees" :key="emp.id" class="flex items-center gap-2">
                <input 
                  type="checkbox" 
                  :id="`emp-${emp.id}`" 
                  :value="emp.id"
                  v-model="form.employee_ids"
                  class="rounded border-slate-850 bg-slate-950 text-blue-500 focus:ring-0"
                />
                <label :for="`emp-${emp.id}`" class="text-xs text-slate-300 cursor-pointer">
                  {{ emp.full_name_en }} ({{ emp.position?.title_en || 'Staff' }})
                </label>
              </div>
            </div>
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
              {{ form.processing ? 'Saving...' : 'Save KPI' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
