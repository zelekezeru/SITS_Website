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
  scales: { type: Array, default: () => [] },
  increments: { type: Array, default: () => [] },
});

// Modal state
const scaleModalOpen = ref(false);
const bandModalOpen = ref(false);

const scaleForm = useForm({
  name: '',
  is_active: true,
});

const bandForm = useForm({
  grade_scale_id: '',
  label_en: '',
  label_am: '',
  min_score: 0,
  max_score: 100,
  triggers_increment: false,
  increment_pct: 0,
});

const openScaleModal = () => {
  scaleForm.reset();
  scaleForm.clearErrors();
  scaleModalOpen.value = true;
};

const openBandModal = (scaleId) => {
  bandForm.reset();
  bandForm.grade_scale_id = scaleId;
  bandForm.clearErrors();
  bandModalOpen.value = true;
};

const submitScale = () => {
  scaleForm.post('/admin/grading/scales', {
    onSuccess: () => {
      scaleModalOpen.value = false;
    }
  });
};

const submitBand = () => {
  bandForm.post('/admin/grading/bands', {
    onSuccess: () => {
      bandModalOpen.value = false;
    }
  });
};

const approveIncrement = async (id) => {
  const confirmed = await confirm({
    title: 'Approve Salary Increment',
    message: "Are you sure you want to approve this salary increment? This will update the employee's base salary immediately.",
  });
  if (confirmed) {
    router.post(`/admin/grading/increments/${id}/approve`);
  }
};
</script>

<template>
  <Head title="Grading & Increments — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Award" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
          </div>
        </div>
        <button 
          @click="openScaleModal"
          class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
        >
          + Add Scale
        </button>
      </div>
    </section>

    <!-- Scales & Bands List -->
    <div class="space-y-8">
      <div v-for="scale in scales" :key="scale.id" class="p-6 rounded-2xl border border-slate-900 bg-slate-900/10 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-900 pb-3">
          <div>
            <h3 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
              {{ scale.name }}
              <span v-if="scale.is_active" class="text-[9px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-1.5 py-0.5 rounded font-bold uppercase">
                Active
              </span>
            </h3>
          </div>
          <button 
            @click="openBandModal(scale.id)"
            class="text-xs font-semibold bg-slate-900 hover:bg-slate-850 border border-slate-850 hover:border-slate-700 text-slate-200 px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
          >
            + Add Band
          </button>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div 
            v-for="band in scale.bands"
            :key="band.id"
            class="p-4 rounded-xl border border-slate-900 bg-slate-950/40 space-y-2 relative group"
          >
            <div class="flex items-start justify-between">
              <div>
                <h4 class="font-bold text-white text-sm">{{ band.label_en }}</h4>
                <p class="text-[10px] text-slate-500 mt-0.5" v-if="band.label_am">{{ band.label_am }}</p>
              </div>
              <span class="text-xs font-mono font-bold text-blue-400">#{{ band.sort_order }}</span>
            </div>
            <div class="text-xs text-slate-400 space-y-1">
              <div class="flex justify-between">
                <span>Scores:</span>
                <span class="text-slate-300 font-semibold">{{ Number(band.min_score).toFixed(0) }} - {{ Number(band.max_score).toFixed(0) }}%</span>
              </div>
              <div class="flex justify-between" v-if="band.triggers_increment">
                <span>Salary Inc:</span>
                <span class="text-emerald-400 font-bold">+{{ Number(band.increment_pct) }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Increment Recommendations List -->
    <div class="space-y-4 pt-6">
      <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Recommended Salary Increments</h3>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
                <th class="pb-3">Employee</th>
                <th class="pb-3">Current Salary</th>
                <th class="pb-3">Proposed Salary</th>
                <th class="pb-3">Status</th>
                <th class="pb-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="text-sm divide-y divide-slate-900">
              <tr v-for="inc in increments" :key="inc.id" class="hover:bg-slate-900/40">
                <td class="py-4 font-semibold text-slate-200">{{ inc.evaluation?.employee?.full_name_en }}</td>
                <td class="py-4 font-mono text-slate-400">{{ Number(inc.current_salary).toLocaleString() }} ETB</td>
                <td class="py-4 font-mono text-emerald-400 font-bold">{{ Number(inc.proposed_salary).toLocaleString() }} ETB</td>
                <td class="py-4 capitalize">
                  <span 
                    class="px-2 py-0.5 text-[10px] rounded-full font-bold border"
                    :class="inc.status === 'approved' 
                      ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400' 
                      : 'bg-slate-800/60 border-slate-800 text-slate-500'"
                  >
                    {{ inc.status }}
                  </span>
                </td>
                <td class="py-4 text-right">
                  <button 
                    v-if="inc.status === 'pending'"
                    @click="approveIncrement(inc.id)"
                    class="text-[11px] font-bold px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors cursor-pointer"
                  >
                    Approve & Apply
                  </button>
                  <span v-else class="text-xs text-slate-500 italic">
                    Applied by {{ inc.approved_by?.name || 'Admin' }}
                  </span>
                </td>
              </tr>
              <tr v-if="!increments.length">
                <td colspan="5" class="py-8 text-center text-slate-650 italic">
                  No salary increment recommendations generated yet.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Scale Modal -->
    <div v-if="scaleModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-6">Create Grade Scale</h3>

        <form @submit.prevent="submitScale" class="space-y-5">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Scale Name</label>
            <input 
              v-model="scaleForm.name" 
              type="text" 
              required
              placeholder="e.g. Standard 5-Band Scale" 
              class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            />
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button 
              type="button" 
              @click="scaleModalOpen = false"
              class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="scaleForm.processing"
              class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
            >
              Save Scale
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Band Modal -->
    <div v-if="bandModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl relative">
        <h3 class="text-xl font-bold text-white mb-6">Create Grade Band</h3>

        <form @submit.prevent="submitBand" class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">English Label</label>
              <input 
                v-model="bandForm.label_en" 
                type="text" 
                required
                placeholder="e.g. Excellent" 
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Amharic Label</label>
              <input 
                v-model="bandForm.label_am" 
                type="text" 
                placeholder="e.g. በጣም ጥሩ" 
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Min Score (%)</label>
              <input 
                v-model="bandForm.min_score" 
                type="number" 
                min="0"
                max="100"
                required
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Max Score (%)</label>
              <input 
                v-model="bandForm.max_score" 
                type="number" 
                min="0"
                max="100"
                required
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center gap-2 pt-6">
              <input 
                v-model="bandForm.triggers_increment" 
                type="checkbox" 
                id="triggers_increment"
                class="rounded border-slate-850 bg-slate-950 text-blue-500 focus:ring-0"
              />
              <label for="triggers_increment" class="text-xs font-semibold text-slate-300 cursor-pointer">Salary Increment</label>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Increment (%)</label>
              <input 
                v-model="bandForm.increment_pct" 
                type="number" 
                step="0.1"
                min="0"
                max="100"
                class="w-full bg-slate-950/60 border focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
              />
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button 
              type="button" 
              @click="bandModalOpen = false"
              class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="bandForm.processing"
              class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
            >
              Save Band
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
