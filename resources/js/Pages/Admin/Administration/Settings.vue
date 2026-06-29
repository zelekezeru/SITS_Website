<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  module: { type: Object, required: true },
  routeName: { type: String, required: true },
  settings: { type: Array, default: () => [] },
});

const activeTab = computed(() => {
  if (props.routeName === 'admin.settings.scoring') return 'scoring';
  if (props.routeName === 'admin.settings.localization') return 'localization';
  if (props.routeName === 'admin.settings.ai') return 'ai';
  return 'general';
});

const filteredSettings = computed(() => {
  return props.settings.filter(s => s.group === activeTab.value);
});

const form = useForm({
  key: '',
  value: '',
});

const updateVal = (key, value) => {
  form.key = key;
  form.value = value;
  form.post('/admin/settings', {
    preserveScroll: true,
  });
};

// --- Custom scoring weights reactive state ---
const scoringWeights = ref({
  weight_auto_score: 40,
  weight_manager_score: 40,
  weight_executive_score: 20,
  auto_score_weight_tasks: 40,
  auto_score_weight_deliverables: 25,
  auto_score_weight_kpis: 25,
  auto_score_weight_attendance: 10,
  scoring_formula_version: 'v1',
  auto_score_overdue_penalty: 10,
});

const loadWeights = () => {
  props.settings.forEach(s => {
    if (s.group === 'scoring') {
      if (s.key === 'scoring_formula_version') {
        scoringWeights.value[s.key] = s.value;
      } else if (s.key === 'auto_score_overdue_penalty') {
        scoringWeights.value[s.key] = Number(s.value);
      } else {
        scoringWeights.value[s.key] = Math.round(Number(s.value) * 100);
      }
    }
  });
};

watch(() => props.settings, loadWeights, { immediate: true });

const blendTotal = computed(() => {
  return (
    Number(scoringWeights.value.weight_auto_score || 0) +
    Number(scoringWeights.value.weight_manager_score || 0) +
    Number(scoringWeights.value.weight_executive_score || 0)
  );
});

const autoTotal = computed(() => {
  return (
    Number(scoringWeights.value.auto_score_weight_tasks || 0) +
    Number(scoringWeights.value.auto_score_weight_deliverables || 0) +
    Number(scoringWeights.value.auto_score_weight_kpis || 0) +
    Number(scoringWeights.value.auto_score_weight_attendance || 0)
  );
});

const adjustWeights = (group, changedKey, newValue) => {
  const keys = group === 'blend' 
    ? ['weight_auto_score', 'weight_manager_score', 'weight_executive_score']
    : ['auto_score_weight_tasks', 'auto_score_weight_deliverables', 'auto_score_weight_kpis', 'auto_score_weight_attendance'];

  let val = Math.min(100, Math.max(0, Math.round(Number(newValue) || 0)));
  scoringWeights.value[changedKey] = val;

  const otherKeys = keys.filter(k => k !== changedKey);
  const otherSumBefore = otherKeys.reduce((s, k) => s + (scoringWeights.value[k] || 0), 0);
  const currentTotal = val + otherSumBefore;

  if (currentTotal > 100) {
    const excess = currentTotal - 100;
    if (otherSumBefore > 0) {
      let distributed = 0;
      otherKeys.forEach((k, idx) => {
        let reduction = 0;
        if (idx === otherKeys.length - 1) {
          reduction = excess - distributed;
        } else {
          reduction = Math.round(excess * (scoringWeights.value[k] / otherSumBefore));
          distributed += reduction;
        }
        scoringWeights.value[k] = Math.max(0, scoringWeights.value[k] - reduction);
      });
    } else {
      scoringWeights.value[changedKey] = 100;
    }
  }
};

const autoBalanceBlend = () => {
  const keys = ['weight_auto_score', 'weight_manager_score', 'weight_executive_score'];
  const currentSum = keys.reduce((s, k) => s + (scoringWeights.value[k] || 0), 0);
  if (currentSum === 100) return;
  if (currentSum === 0) {
    scoringWeights.value.weight_auto_score = 40;
    scoringWeights.value.weight_manager_score = 40;
    scoringWeights.value.weight_executive_score = 20;
    return;
  }
  let distributed = 0;
  keys.forEach((k, idx) => {
    if (idx === keys.length - 1) {
      scoringWeights.value[k] = 100 - distributed;
    } else {
      const balanced = Math.round((scoringWeights.value[k] / currentSum) * 100);
      scoringWeights.value[k] = balanced;
      distributed += balanced;
    }
  });
};

const autoBalanceAuto = () => {
  const keys = ['auto_score_weight_tasks', 'auto_score_weight_deliverables', 'auto_score_weight_kpis', 'auto_score_weight_attendance'];
  const currentSum = keys.reduce((s, k) => s + (scoringWeights.value[k] || 0), 0);
  if (currentSum === 100) return;
  if (currentSum === 0) {
    scoringWeights.value.auto_score_weight_tasks = 40;
    scoringWeights.value.auto_score_weight_deliverables = 20;
    scoringWeights.value.auto_score_weight_kpis = 20;
    scoringWeights.value.auto_score_weight_attendance = 20;
    return;
  }
  let distributed = 0;
  keys.forEach((k, idx) => {
    if (idx === keys.length - 1) {
      scoringWeights.value[k] = 100 - distributed;
    } else {
      const balanced = Math.round((scoringWeights.value[k] / currentSum) * 100);
      scoringWeights.value[k] = balanced;
      distributed += balanced;
    }
  });
};

const saveWeights = () => {
  if (blendTotal.value > 100 || autoTotal.value > 100) return;

  const payload = [
    { key: 'weight_auto_score', value: String(scoringWeights.value.weight_auto_score / 100) },
    { key: 'weight_manager_score', value: String(scoringWeights.value.weight_manager_score / 100) },
    { key: 'weight_executive_score', value: String(scoringWeights.value.weight_executive_score / 100) },
    { key: 'auto_score_weight_tasks', value: String(scoringWeights.value.auto_score_weight_tasks / 100) },
    { key: 'auto_score_weight_deliverables', value: String(scoringWeights.value.auto_score_weight_deliverables / 100) },
    { key: 'auto_score_weight_kpis', value: String(scoringWeights.value.auto_score_weight_kpis / 100) },
    { key: 'auto_score_weight_attendance', value: String(scoringWeights.value.auto_score_weight_attendance / 100) },
    { key: 'scoring_formula_version', value: String(scoringWeights.value.scoring_formula_version) },
    { key: 'auto_score_overdue_penalty', value: String(scoringWeights.value.auto_score_overdue_penalty) },
  ];

  router.post('/admin/settings/batch', {
    settings: payload,
  }, {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="System Configuration Settings — SITS ERP" />

  <div class="space-y-6">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
          <Icon name="Settings" :size="26" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
        </div>
      </div>
    </section>

    <!-- Sub-tab Navigation -->
    <div class="flex border-b border-slate-900 gap-1">
      <Link
        href="/admin/settings/general"
        class="pb-3 px-4 text-xs font-bold tracking-wide transition-colors whitespace-nowrap cursor-pointer"
        :class="activeTab === 'general' ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-350'"
      >
        General Configuration
      </Link>
      <Link
        href="/admin/settings/scoring"
        class="pb-3 px-4 text-xs font-bold tracking-wide transition-colors whitespace-nowrap cursor-pointer"
        :class="activeTab === 'scoring' ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-350'"
      >
        Scoring Weights
      </Link>
      <Link
        href="/admin/settings/localization"
        class="pb-3 px-4 text-xs font-bold tracking-wide transition-colors whitespace-nowrap cursor-pointer"
        :class="activeTab === 'localization' ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-350'"
      >
        Localization & Bilingual
      </Link>
      <Link
        href="/admin/settings/ai"
        class="pb-3 px-4 text-xs font-bold tracking-wide transition-colors whitespace-nowrap cursor-pointer"
        :class="activeTab === 'ai' ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-350'"
      >
        AI Integration
      </Link>
    </div>

    <!-- Settings Forms List (General / Localization / AI) -->
    <div v-if="activeTab !== 'scoring'" class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6 space-y-6">
      <div class="grid gap-6">
        <div 
          v-for="setting in filteredSettings" 
          :key="setting.id"
          class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border border-slate-900 bg-slate-950/40"
        >
          <div class="space-y-1 max-w-xl">
            <span class="text-[10px] bg-blue-500/10 text-blue-400 px-2 py-0.5 rounded font-mono border border-blue-500/20 uppercase">
              {{ setting.group }}
            </span>
            <h4 class="font-bold text-white text-sm mt-1 capitalize">{{ setting.key.replace(/_/g, ' ') }}</h4>
            <p class="text-xs text-slate-500 leading-relaxed">{{ setting.description }}</p>
          </div>

          <div class="flex items-center gap-2">
            <!-- Language Dropdown selection -->
            <select
              v-if="setting.key === 'default_language' || setting.key === 'primary_locale'"
              :value="setting.value"
              @change="updateVal(setting.key, $event.target.value)"
              class="bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-2 text-slate-100 text-xs w-48 focus:outline-none"
            >
              <option value="en">English (en)</option>
              <option value="am">Amharic (am)</option>
            </select>

            <!-- Boolean Toggle -->
            <select
              v-else-if="setting.type === 'boolean'"
              :value="setting.value"
              @change="updateVal(setting.key, $event.target.value)"
              class="bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-2 text-slate-100 text-xs w-48 focus:outline-none"
            >
              <option value="true">Enabled / True</option>
              <option value="false">Disabled / False</option>
            </select>

            <!-- Standard Input -->
            <input 
              v-else
              :value="setting.value"
              @change="updateVal(setting.key, $event.target.value)"
              :type="setting.key.includes('api_key') || setting.key.includes('password') ? 'password' : 'text'"
              class="bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-2 text-slate-100 text-xs font-mono w-48 focus:outline-none"
            />
          </div>
        </div>

        <div v-if="!filteredSettings.length" class="py-8 text-center text-slate-650 italic text-sm">
          No settings found in this group.
        </div>
      </div>
    </div>

    <!-- Scoring Weights Custom Percentage Editor -->
    <div v-else class="space-y-6">
      <div class="grid md:grid-cols-2 gap-6">
        <!-- Blend Weights Section -->
        <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6 space-y-6">
          <div>
            <h3 class="text-sm font-extrabold text-white tracking-wide uppercase">Evaluation Blend Weights</h3>
            <p class="text-xs text-slate-500 mt-1">Distribute weighting between system auto scores, manager ratings, and executive calibrations.</p>
          </div>

          <!-- Stacked bar visualization -->
          <div class="space-y-2">
            <div class="flex justify-between text-xs font-semibold">
              <span class="text-slate-400">Weight Allocation</span>
              <span :class="blendTotal > 100 ? 'text-rose-400 font-bold' : 'text-emerald-400'">Total: {{ blendTotal }}%</span>
            </div>
            <div class="w-full h-3 bg-slate-950 rounded-full overflow-hidden flex">
              <div 
                class="h-full bg-blue-500 transition-all duration-300"
                :style="`width: ${scoringWeights.weight_auto_score}%`"
                title="System / Auto"
              ></div>
              <div 
                class="h-full bg-purple-500 transition-all duration-300"
                :style="`width: ${scoringWeights.weight_manager_score}%`"
                title="Manager / Dept Head"
              ></div>
              <div 
                class="h-full bg-pink-500 transition-all duration-300"
                :style="`width: ${scoringWeights.weight_executive_score}%`"
                title="Executive"
              ></div>
            </div>
            <div class="flex gap-4 text-[10px] text-slate-500 font-mono mt-1">
              <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-blue-500"></span> System: {{ scoringWeights.weight_auto_score }}%</span>
              <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-purple-500"></span> Manager: {{ scoringWeights.weight_manager_score }}%</span>
              <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-pink-500"></span> Exec: {{ scoringWeights.weight_executive_score }}%</span>
            </div>
          </div>

          <!-- Form inputs -->
          <div class="space-y-4">
            <!-- Auto score -->
            <div class="space-y-1">
              <div class="flex justify-between text-xs font-semibold">
                <label class="text-slate-350">System / Auto Score Weight</label>
                <span class="font-mono text-slate-200">{{ scoringWeights.weight_auto_score }}%</span>
              </div>
              <div class="flex items-center gap-4">
                <input 
                  type="range" min="0" max="100" 
                  :value="scoringWeights.weight_auto_score"
                  @input="adjustWeights('blend', 'weight_auto_score', $event.target.value)"
                  class="flex-1 accent-blue-500 h-1 bg-slate-955 rounded-lg cursor-pointer"
                />
                <input 
                  type="number" min="0" max="100" 
                  :value="scoringWeights.weight_auto_score"
                  @input="adjustWeights('blend', 'weight_auto_score', $event.target.value)"
                  class="w-16 bg-slate-950/60 border border-slate-850 rounded-lg px-2 py-1 text-slate-100 text-xs text-center focus:outline-none"
                />
              </div>
            </div>

            <!-- Manager score -->
            <div class="space-y-1">
              <div class="flex justify-between text-xs font-semibold">
                <label class="text-slate-350">Manager / Head Rating Weight</label>
                <span class="font-mono text-slate-200">{{ scoringWeights.weight_manager_score }}%</span>
              </div>
              <div class="flex items-center gap-4">
                <input 
                  type="range" min="0" max="100" 
                  :value="scoringWeights.weight_manager_score"
                  @input="adjustWeights('blend', 'weight_manager_score', $event.target.value)"
                  class="flex-1 accent-purple-500 h-1 bg-slate-955 rounded-lg cursor-pointer"
                />
                <input 
                  type="number" min="0" max="100" 
                  :value="scoringWeights.weight_manager_score"
                  @input="adjustWeights('blend', 'weight_manager_score', $event.target.value)"
                  class="w-16 bg-slate-950/60 border border-slate-850 rounded-lg px-2 py-1 text-slate-100 text-xs text-center focus:outline-none"
                />
              </div>
            </div>

            <!-- Executive score -->
            <div class="space-y-1">
              <div class="flex justify-between text-xs font-semibold">
                <label class="text-slate-350">Executive Calibration Weight</label>
                <span class="font-mono text-slate-200">{{ scoringWeights.weight_executive_score }}%</span>
              </div>
              <div class="flex items-center gap-4">
                <input 
                  type="range" min="0" max="100" 
                  :value="scoringWeights.weight_executive_score"
                  @input="adjustWeights('blend', 'weight_executive_score', $event.target.value)"
                  class="flex-1 accent-pink-500 h-1 bg-slate-955 rounded-lg cursor-pointer"
                />
                <input 
                  type="number" min="0" max="100" 
                  :value="scoringWeights.weight_executive_score"
                  @input="adjustWeights('blend', 'weight_executive_score', $event.target.value)"
                  class="w-16 bg-slate-950/60 border border-slate-850 rounded-lg px-2 py-1 text-slate-100 text-xs text-center focus:outline-none"
                />
              </div>
            </div>
          </div>

          <!-- Dynamic auto-balancer card -->
          <div class="flex items-center justify-between text-xs text-slate-400 bg-slate-955 border border-slate-900 px-4 py-3 rounded-2xl">
            <span class="flex items-center gap-1.5">
              <Icon name="Info" :size="14" class="text-blue-400" />
              Total is <span class="font-mono font-bold" :class="blendTotal === 100 ? 'text-emerald-400' : 'text-blue-400'">{{ blendTotal }}%</span>.
            </span>
            <button
              v-if="blendTotal !== 100"
              @click="autoBalanceBlend"
              type="button"
              class="text-[10px] font-bold uppercase tracking-wider text-blue-400 hover:text-blue-300 transition-colors bg-blue-500/10 border border-blue-500/25 px-2.5 py-1 rounded-lg cursor-pointer"
            >
              Auto-Balance to 100%
            </button>
          </div>
        </div>

        <!-- Auto-Score Sub-weights Section -->
        <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6 space-y-6">
          <div>
            <h3 class="text-sm font-extrabold text-white tracking-wide uppercase">Auto-Score Sub-weights</h3>
            <p class="text-xs text-slate-500 mt-1">Determine how system scores are computed from tasks, deliverables, KPIs, and attendance.</p>
          </div>

          <!-- Stacked bar visualization -->
          <div class="space-y-2">
            <div class="flex justify-between text-xs font-semibold">
              <span class="text-slate-400">Auto-Score Breakdown</span>
              <span :class="autoTotal > 100 ? 'text-rose-400 font-bold' : 'text-emerald-400'">Total: {{ autoTotal }}%</span>
            </div>
            <div class="w-full h-3 bg-slate-950 rounded-full overflow-hidden flex">
              <div 
                class="h-full bg-blue-500 transition-all duration-300"
                :style="`width: ${scoringWeights.auto_score_weight_tasks}%`"
                title="Tasks"
              ></div>
              <div 
                class="h-full bg-emerald-500 transition-all duration-300"
                :style="`width: ${scoringWeights.auto_score_weight_deliverables}%`"
                title="Deliverables"
              ></div>
              <div 
                class="h-full bg-amber-500 transition-all duration-300"
                :style="`width: ${scoringWeights.auto_score_weight_kpis}%`"
                title="KPIs"
              ></div>
              <div 
                class="h-full bg-purple-500 transition-all duration-300"
                :style="`width: ${scoringWeights.auto_score_weight_attendance}%`"
                title="Attendance"
              ></div>
            </div>
            <div class="flex gap-3 text-[9px] text-slate-500 font-mono mt-1 flex-wrap">
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-blue-500"></span> Tasks: {{ scoringWeights.auto_score_weight_tasks }}%</span>
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-emerald-500"></span> Deliv: {{ scoringWeights.auto_score_weight_deliverables }}%</span>
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-amber-500"></span> KPIs: {{ scoringWeights.auto_score_weight_kpis }}%</span>
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-purple-500"></span> Attend: {{ scoringWeights.auto_score_weight_attendance }}%</span>
            </div>
          </div>

          <!-- Form inputs -->
          <div class="space-y-4">
            <!-- Tasks -->
            <div class="space-y-1">
              <div class="flex justify-between text-xs font-semibold">
                <label class="text-slate-350">Task Completion Weight</label>
                <span class="font-mono text-slate-200">{{ scoringWeights.auto_score_weight_tasks }}%</span>
              </div>
              <div class="flex items-center gap-4">
                <input 
                  type="range" min="0" max="100" 
                  :value="scoringWeights.auto_score_weight_tasks"
                  @input="adjustWeights('auto', 'auto_score_weight_tasks', $event.target.value)"
                  class="flex-1 accent-blue-500 h-1 bg-slate-955 rounded-lg cursor-pointer"
                />
                <input 
                  type="number" min="0" max="100" 
                  :value="scoringWeights.auto_score_weight_tasks"
                  @input="adjustWeights('auto', 'auto_score_weight_tasks', $event.target.value)"
                  class="w-16 bg-slate-950/60 border border-slate-850 rounded-lg px-2 py-1 text-slate-100 text-xs text-center focus:outline-none"
                />
              </div>
            </div>

            <!-- Deliverables -->
            <div class="space-y-1">
              <div class="flex justify-between text-xs font-semibold">
                <label class="text-slate-350">Deliverable Completion Weight</label>
                <span class="font-mono text-slate-200">{{ scoringWeights.auto_score_weight_deliverables }}%</span>
              </div>
              <div class="flex items-center gap-4">
                <input 
                  type="range" min="0" max="100" 
                  :value="scoringWeights.auto_score_weight_deliverables"
                  @input="adjustWeights('auto', 'auto_score_weight_deliverables', $event.target.value)"
                  class="flex-1 accent-emerald-500 h-1 bg-slate-955 rounded-lg cursor-pointer"
                />
                <input 
                  type="number" min="0" max="100" 
                  :value="scoringWeights.auto_score_weight_deliverables"
                  @input="adjustWeights('auto', 'auto_score_weight_deliverables', $event.target.value)"
                  class="w-16 bg-slate-950/60 border border-slate-850 rounded-lg px-2 py-1 text-slate-100 text-xs text-center focus:outline-none"
                />
              </div>
            </div>

            <!-- KPIs -->
            <div class="space-y-1">
              <div class="flex justify-between text-xs font-semibold">
                <label class="text-slate-350">KPI Achievement Weight</label>
                <span class="font-mono text-slate-200">{{ scoringWeights.auto_score_weight_kpis }}%</span>
              </div>
              <div class="flex items-center gap-4">
                <input 
                  type="range" min="0" max="100" 
                  :value="scoringWeights.auto_score_weight_kpis"
                  @input="adjustWeights('auto', 'auto_score_weight_kpis', $event.target.value)"
                  class="flex-1 accent-amber-500 h-1 bg-slate-955 rounded-lg cursor-pointer"
                />
                <input 
                  type="number" min="0" max="100" 
                  :value="scoringWeights.auto_score_weight_kpis"
                  @input="adjustWeights('auto', 'auto_score_weight_kpis', $event.target.value)"
                  class="w-16 bg-slate-950/60 border border-slate-850 rounded-lg px-2 py-1 text-slate-100 text-xs text-center focus:outline-none"
                />
              </div>
            </div>

            <!-- Attendance -->
            <div class="space-y-1">
              <div class="flex justify-between text-xs font-semibold">
                <label class="text-slate-350">Attendance Score Weight</label>
                <span class="font-mono text-slate-200">{{ scoringWeights.auto_score_weight_attendance }}%</span>
              </div>
              <div class="flex items-center gap-4">
                <input 
                  type="range" min="0" max="100" 
                  :value="scoringWeights.auto_score_weight_attendance"
                  @input="adjustWeights('auto', 'auto_score_weight_attendance', $event.target.value)"
                  class="flex-1 accent-purple-500 h-1 bg-slate-955 rounded-lg cursor-pointer"
                />
                <input 
                  type="number" min="0" max="100" 
                  :value="scoringWeights.auto_score_weight_attendance"
                  @input="adjustWeights('auto', 'auto_score_weight_attendance', $event.target.value)"
                  class="w-16 bg-slate-950/60 border border-slate-850 rounded-lg px-2 py-1 text-slate-100 text-xs text-center focus:outline-none"
                />
              </div>
            </div>
          </div>

          <!-- Dynamic auto-balancer card -->
          <div class="flex items-center justify-between text-xs text-slate-400 bg-slate-955 border border-slate-900 px-4 py-3 rounded-2xl">
            <span class="flex items-center gap-1.5">
              <Icon name="Info" :size="14" class="text-emerald-400" />
              Total is <span class="font-mono font-bold" :class="autoTotal === 100 ? 'text-emerald-400' : 'text-blue-400'">{{ autoTotal }}%</span>.
            </span>
            <button
              v-if="autoTotal !== 100"
              @click="autoBalanceAuto"
              type="button"
              class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 hover:text-emerald-350 transition-colors bg-emerald-500/10 border border-emerald-500/25 px-2.5 py-1 rounded-lg cursor-pointer"
            >
              Auto-Balance to 100%
            </button>
          </div>
        </div>
      </div>

      <!-- Other scoring constants -->
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6 space-y-6">
        <div>
          <h3 class="text-sm font-extrabold text-white tracking-wide uppercase">General Scoring Configurations</h3>
          <p class="text-xs text-slate-500 mt-1">Specify penalties and other variables used by the calculation system.</p>
        </div>

        <div class="grid sm:grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Overdue Penalty (Points)</label>
            <input 
              type="number" step="0.01" 
              v-model.number="scoringWeights.auto_score_overdue_penalty"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-2.5 text-slate-100 text-xs font-mono focus:outline-none"
            />
          </div>
          <div class="space-y-2">
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Formula Version</label>
            <input 
              type="text" 
              v-model="scoringWeights.scoring_formula_version"
              class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-2.5 text-slate-100 text-xs font-mono focus:outline-none"
            />
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end pt-2">
        <button 
          @click="saveWeights"
          :disabled="blendTotal > 100 || autoTotal > 100"
          class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-6 py-3 rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
        >
          Save Scoring Weights
        </button>
      </div>
    </div>
  </div>
</template>
