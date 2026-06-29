<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import JdItemsEditor from '@/Components/JdItemsEditor.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, required: true },
  jobDescriptions: { type: Array, default: () => [] },
  positions: { type: Array, default: () => [] },
});

const CATEGORY_LABELS = {
  responsibility: 'Responsibility',
  authority: 'Authority',
  qualification: 'Qualification',
  relationship: 'Reporting / Relationship',
};

const blankItem = () => ({
  category: 'responsibility', title_en: '', title_am: '',
  is_kpi: false, measure_type: 'qualitative', target_value: '', unit: '', weight: 1,
});

// Selection for viewing version history
const selectedJd = ref(null);

// Unassigned positions (positions that don't have a JD yet)
const unassignedPositions = computed(() => {
  return props.positions.filter(pos => {
    return !props.jobDescriptions.some(jd => jd.position_id === pos.id);
  });
});

// Modal state for creating a new JD
const createJdOpen = ref(false);
const createJdForm = useForm({
  position_id: '',
  title_en: '',
  title_am: '',
  effective_from: '',
  items: [blankItem()],
});

// Modal state for adding a new version
const addVersionOpen = ref(false);
const addVersionForm = useForm({
  effective_from: '',
  items: [blankItem()],
});

const openCreateJdModal = () => {
  createJdForm.reset();
  createJdForm.items = [blankItem()];
  if (unassignedPositions.value.length) {
    createJdForm.position_id = unassignedPositions.value[0].id;
  }
  createJdForm.clearErrors();
  createJdOpen.value = true;
};

const submitCreateJd = () => {
  const selectedPos = props.positions.find(p => p.id === Number(createJdForm.position_id));
  if (selectedPos) {
    createJdForm.title_en = selectedPos.title_en;
    createJdForm.title_am = selectedPos.title_am || '';
  }
  createJdForm.post('/admin/job-descriptions', {
    onSuccess: () => {
      createJdOpen.value = false;
      selectedJd.value = null;
    }
  });
};

const openAddVersionModal = (jd) => {
  selectedJd.value = jd;
  addVersionForm.reset();
  // Pre-fill the editor with the current version's items, so a new version
  // starts from the existing JD and can be tweaked.
  const current = jd.current_version;
  addVersionForm.items = current && Array.isArray(current.items) && current.items.length
    ? current.items.map(it => ({ ...blankItem(), ...it }))
    : [blankItem()];
  addVersionForm.clearErrors();
  addVersionOpen.value = true;
};

const submitAddVersion = () => {
  addVersionForm.post(`/admin/job-descriptions/${selectedJd.value.id}/versions`, {
    onSuccess: () => {
      addVersionOpen.value = false;
      // Refresh selected JD reference to show updated versions list
      const updated = props.jobDescriptions.find(j => j.id === selectedJd.value.id);
      selectedJd.value = updated || null;
    }
  });
};

const activateVersion = (jd, version) => {
  router.post(`/admin/job-descriptions/${jd.id}/versions/${version.id}/activate`, {}, {
    onSuccess: () => {
      const updated = props.jobDescriptions.find(j => j.id === jd.id);
      selectedJd.value = updated || null;
    }
  });
};

const deleteJd = async (id) => {
  const confirmed = await confirm({
    title: 'Delete Job Description',
    message: 'Are you sure you want to delete this job description? This will delete all versions.',
  });
  if (confirmed) {
    router.delete(`/admin/job-descriptions/${id}`, {
      onSuccess: () => {
        selectedJd.value = null;
      }
    });
  }
};
</script>

<template>
  <Head title="Job Descriptions — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="FileText" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
          </div>
        </div>
        <button 
          @click="openCreateJdModal"
          class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
        >
          + Create JD
        </button>
      </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left side: Job Descriptions List -->
      <div class="lg:col-span-1 space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Positions</h3>
        <div class="space-y-3">
          <div 
            v-for="jd in jobDescriptions" 
            :key="jd.id"
            @click="selectedJd = jd"
            class="p-4 rounded-xl border transition-all cursor-pointer select-none"
            :class="selectedJd && selectedJd.id === jd.id 
              ? 'border-blue-500/40 bg-blue-500/5' 
              : 'border-slate-900 bg-slate-900/10 hover:bg-slate-900/30 hover:border-slate-800'"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <h4 class="font-bold text-white text-sm">{{ jd.title_en }}</h4>
                <p class="text-[11px] text-slate-500 font-mono mt-0.5">{{ jd.position?.code || 'NO-CODE' }}</p>
              </div>
              <span class="text-[10px] bg-blue-500/10 text-blue-400 px-2 py-0.5 rounded font-semibold border border-blue-500/20 shrink-0">
                v{{ jd.current_version?.version_no || 1 }} Active
              </span>
            </div>
          </div>

          <div v-if="!jobDescriptions.length" class="py-8 text-center text-slate-600 italic border border-dashed border-slate-900 rounded-xl">
            No job descriptions created yet.
          </div>
        </div>
      </div>

      <!-- Right side: Detail View & Version Control -->
      <div class="lg:col-span-2">
        <div v-if="selectedJd" class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 space-y-6">
          <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
              <span class="text-xs font-mono text-blue-400 font-semibold uppercase tracking-wider">{{ selectedJd.position?.code }}</span>
              <h3 class="text-xl font-bold text-white mt-1">{{ selectedJd.title_en }}</h3>
              <p class="text-xs text-slate-500 mt-1" v-if="selectedJd.title_am">{{ selectedJd.title_am }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button 
                @click="openAddVersionModal(selectedJd)"
                class="text-xs font-semibold bg-slate-900 hover:bg-slate-800 border border-slate-850 hover:border-slate-700 text-slate-200 px-3.5 py-2 rounded-xl transition-all cursor-pointer"
              >
                Draft New Version
              </button>
              <button 
                @click="deleteJd(selectedJd.id)"
                class="text-xs font-semibold bg-rose-950/20 hover:bg-rose-900/30 border border-rose-900/20 text-rose-400 px-3.5 py-2 rounded-xl transition-all cursor-pointer"
              >
                Delete
              </button>
            </div>
          </div>

          <!-- Active Version Detail -->
          <div class="p-5 rounded-xl border border-slate-850 bg-slate-950/50">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-4 flex items-center gap-2">
              <Icon name="FileText" :size="14" class="text-emerald-400" />
              Active Job Description (Version {{ selectedJd.current_version?.version_no || 1 }})
            </h4>

            <ul v-if="selectedJd.current_version?.items?.length" class="space-y-2">
              <li v-for="(item, idx) in selectedJd.current_version.items" :key="idx"
                  class="flex items-start gap-3 p-3 rounded-lg border border-slate-900 bg-slate-900/20">
                <span class="text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded mt-0.5 shrink-0 bg-slate-800 text-slate-400 border border-slate-700">
                  {{ CATEGORY_LABELS[item.category] || item.category }}
                </span>
                <div class="min-w-0 flex-1">
                  <p class="text-sm text-slate-200">{{ item.title_en }}</p>
                  <p v-if="item.title_am" class="text-xs text-slate-500">{{ item.title_am }}</p>
                </div>
                <span v-if="item.is_kpi" class="text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-blue-500/15 text-blue-400 border border-blue-500/20 shrink-0">
                  KPI<template v-if="item.target_value"> · {{ item.target_value }}{{ item.unit }}</template>
                </span>
              </li>
            </ul>
            <p v-else class="text-sm text-slate-500 italic whitespace-pre-wrap">
              {{ selectedJd.current_version?.body || 'No items defined for this version.' }}
            </p>

            <div class="text-[11px] text-slate-500 mt-4 pt-3 border-t border-slate-900 flex justify-between">
              <span>Effective From: {{ selectedJd.current_version?.effective_from ? new Date(selectedJd.current_version.effective_from).toLocaleDateString() : 'Immediate' }}</span>
              <span>Created By: {{ selectedJd.current_version?.created_by_user?.name || 'System' }}</span>
            </div>
          </div>

          <!-- All Versions list -->
          <div class="space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Version History</h4>
            <div class="divide-y divide-slate-900 border border-slate-900 rounded-xl overflow-hidden">
              <div 
                v-for="version in selectedJd.versions" 
                :key="version.id"
                class="p-4 bg-slate-900/5 flex items-center justify-between gap-4"
              >
                <div>
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-sm text-slate-200">Version {{ version.version_no }}</span>
                    <span 
                      v-if="selectedJd.current_version_id === version.id"
                      class="text-[9px] bg-emerald-500/10 text-emerald-400 px-1.5 py-0.5 rounded font-semibold border border-emerald-500/20"
                    >
                      Active
                    </span>
                  </div>
                  <p class="text-xs text-slate-500 mt-1">
                    Created {{ new Date(version.created_at).toLocaleDateString() }} 
                    <span v-if="version.created_by_user">by {{ version.created_by_user.name }}</span>
                  </p>
                </div>
                
                <button 
                  v-if="selectedJd.current_version_id !== version.id"
                  @click="activateVersion(selectedJd, version)"
                  class="text-[10px] font-bold px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors cursor-pointer"
                >
                  Activate
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="h-64 flex flex-col items-center justify-center border border-dashed border-slate-900 rounded-2xl text-slate-500">
          <Icon name="FileText" :size="32" class="text-slate-700 mb-2" />
          <p class="text-sm">Select a position from the left to view and manage its versioned job description.</p>
        </div>
      </div>
    </div>

    <!-- Create JD Modal -->
    <div v-if="createJdOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-white mb-6">Create Job Description</h3>

        <form @submit.prevent="submitCreateJd" class="space-y-5">
          <!-- Position Select -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Target Position</label>
            <select 
              v-model="createJdForm.position_id"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              required
            >
              <option value="" disabled>Select Position</option>
              <option v-for="pos in unassignedPositions" :key="pos.id" :value="pos.id">
                [{{ pos.code }}] {{ pos.title_en }}
              </option>
            </select>
            <div v-if="createJdForm.errors.position_id" class="text-xs text-rose-455 mt-1 font-semibold">{{ createJdForm.errors.position_id }}</div>
          </div>

          <!-- Structured JD items -->
          <JdItemsEditor v-model="createJdForm.items" :errors="createJdForm.errors" />

          <!-- Effective From -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Effective From Date</label>
            <input 
              v-model="createJdForm.effective_from" 
              type="date" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="createJdForm.errors.effective_from ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="createJdForm.errors.effective_from" class="text-xs text-rose-455 mt-1 font-semibold">{{ createJdForm.errors.effective_from }}</div>
          </div>

          <!-- Modal Actions -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button 
              type="button" 
              @click="createJdOpen = false"
              class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="createJdForm.processing"
              class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
            >
              {{ createJdForm.processing ? 'Creating...' : 'Create JD' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Draft New Version Modal -->
    <div v-if="addVersionOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-white mb-6">Draft Job Description Version</h3>

        <form @submit.prevent="submitAddVersion" class="space-y-5">
          <!-- Structured JD items -->
          <JdItemsEditor v-model="addVersionForm.items" :errors="addVersionForm.errors" />

          <!-- Effective From -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Effective From Date</label>
            <input 
              v-model="addVersionForm.effective_from" 
              type="date" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              :class="addVersionForm.errors.effective_from ? 'border-rose-500/50' : 'border-slate-850'"
            />
            <div v-if="addVersionForm.errors.effective_from" class="text-xs text-rose-455 mt-1 font-semibold">{{ addVersionForm.errors.effective_from }}</div>
          </div>

          <!-- Modal Actions -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button 
              type="button" 
              @click="addVersionOpen = false"
              class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="addVersionForm.processing"
              class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
            >
              {{ addVersionForm.processing ? 'Drafting...' : 'Save Draft' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
