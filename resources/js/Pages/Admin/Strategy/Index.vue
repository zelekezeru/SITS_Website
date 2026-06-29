<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  module: { type: Object, required: true },
  activeYear: { type: Object, default: null },
  strategies: { type: Array, default: () => [] },
  pillars: { type: Array, default: () => [] },
});

// Track which strategies and goals are expanded
const expandedStrategies = ref({});
const expandedGoals = ref({});

const toggleStrategy = (id) => {
  expandedStrategies.value[id] = !expandedStrategies.value[id];
};

const toggleGoal = (id) => {
  expandedGoals.value[id] = !expandedGoals.value[id];
};

// Group strategies by strategic pillar
const groupedStrategies = computed(() => {
  const groups = {};
  props.pillars.forEach(p => {
    groups[p.value] = {
      label: p.label,
      items: props.strategies.filter(s => s.pillar === p.value)
    };
  });
  return groups;
});
</script>

<template>
  <Head title="Strategic Plan Tree — SITS ERP" />

  <div class="space-y-8">
    <!-- Header Hero -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start justify-between gap-6 flex-wrap sm:flex-nowrap">
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

        <div v-if="activeYear" class="shrink-0 flex flex-col items-end">
          <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Active Year</span>
          <span class="text-xl font-bold bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent mt-1">
            {{ activeYear.label }}
          </span>
          <span class="text-[10px] text-slate-400 mt-1">
            {{ new Date(activeYear.start_date).toLocaleDateString() }} - {{ new Date(activeYear.end_date).toLocaleDateString() }}
          </span>
        </div>
      </div>
    </section>

    <!-- If no active year is set -->
    <div v-if="!activeYear" class="rounded-2xl border border-amber-500/15 bg-amber-500/5 p-8 text-center space-y-4">
      <span class="w-12 h-12 rounded-full bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mx-auto">
        <Icon name="AlertTriangle" :size="22" />
      </span>
      <div class="max-w-md mx-auto">
        <h3 class="font-bold text-lg text-white">No Active Fiscal Year</h3>
        <p class="text-sm text-slate-400 mt-1">
          A fiscal year must be activated before the strategic plan tree can be defined or viewed.
        </p>
      </div>
      <Link href="/admin/strategy/years" class="inline-flex items-center gap-2 text-xs font-semibold px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors">
        Manage Fiscal Years
      </Link>
    </div>

    <!-- Active Year Plan Tree -->
    <div v-else class="space-y-8">
      <div class="flex items-center justify-between border-b border-slate-900 pb-4">
        <h3 class="font-bold text-lg text-white flex items-center gap-2">
          <Icon name="GitMerge" :size="18" class="text-blue-400" /> Strategic Mapping
        </h3>
        
        <div class="flex gap-3 text-xs">
          <Link href="/admin/strategy/strategies" class="px-3 py-1.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 rounded-lg text-slate-300 transition-colors">
            + Add Strategy
          </Link>
          <Link href="/admin/strategy/goals" class="px-3 py-1.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 rounded-lg text-slate-300 transition-colors">
            + Add Goal
          </Link>
          <Link href="/admin/strategy/targets" class="px-3 py-1.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 rounded-lg text-slate-300 transition-colors">
            + Add Target
          </Link>
        </div>
      </div>

      <!-- Pillars Columns -->
      <div class="space-y-6">
        <div v-for="(pillar, key) in groupedStrategies" :key="key" class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 space-y-4">
          <div class="flex items-center gap-3 border-b border-slate-900 pb-3">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
            <h4 class="font-extrabold text-white text-base uppercase tracking-wider">{{ pillar.label }}</h4>
            <span class="text-xs text-slate-500">({{ pillar.items.length }} Strategies)</span>
          </div>

          <!-- Empty Pillar State -->
          <div v-if="pillar.items.length === 0" class="text-xs text-slate-650 py-4 italic">
            No strategies mapped under this pillar yet.
          </div>

          <!-- Strategies list -->
          <div v-else class="space-y-4">
            <div v-for="strategy in pillar.items" :key="strategy.id" class="rounded-xl border border-slate-900/60 bg-slate-950/40 p-4">
              <div class="flex items-start justify-between gap-4 cursor-pointer" @click="toggleStrategy(strategy.id)">
                <div>
                  <h5 class="font-bold text-slate-200 text-sm flex items-center gap-2 hover:text-white transition-colors">
                    <Icon name="Compass" :size="15" class="text-blue-500" />
                    {{ strategy.name }}
                  </h5>
                  <p class="text-xs text-slate-500 mt-1">{{ strategy.description }}</p>
                </div>
                <Icon :name="expandedStrategies[strategy.id] ? 'ChevronUp' : 'ChevronDown'" :size="16" class="text-slate-500" />
              </div>

              <!-- Expanded Strategy (Goals) -->
              <div v-if="expandedStrategies[strategy.id]" class="mt-4 pt-4 border-t border-slate-900 space-y-3 pl-4">
                <div v-if="!strategy.goals || strategy.goals.length === 0" class="text-xs text-slate-650 italic">
                  No objectives or goals added to this strategy yet.
                </div>
                
                <div v-for="goal in strategy.goals" :key="goal.id" class="border-l border-slate-850 pl-4 py-1">
                  <div class="flex items-center justify-between cursor-pointer" @click.stop="toggleGoal(goal.id)">
                    <div>
                      <h6 class="text-xs font-bold text-slate-300 hover:text-white transition-colors flex items-center gap-1.5">
                        <Icon name="Activity" :size="13" class="text-indigo-400" />
                        {{ goal.name }}
                      </h6>
                      <p class="text-[11px] text-slate-500 mt-0.5">{{ goal.description }}</p>
                    </div>
                    <Icon :name="expandedGoals[goal.id] ? 'ChevronUp' : 'ChevronDown'" :size="14" class="text-slate-550" />
                  </div>

                  <!-- Expanded Goal (Targets) -->
                  <div v-if="expandedGoals[goal.id]" class="mt-3 space-y-2 pl-4">
                    <div v-if="!goal.targets || goal.targets.length === 0" class="text-[11px] text-slate-600 italic">
                      No measurable targets linked yet.
                    </div>

                    <div v-for="target in goal.targets" :key="target.id" class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2.5 rounded bg-slate-900/30 border border-slate-900 text-xs">
                      <div>
                        <span class="font-semibold text-slate-300">{{ target.name }}</span>
                        <div class="flex flex-wrap gap-1.5 mt-1.5">
                          <span v-for="dept in target.departments" :key="dept.id" class="px-1.5 py-0.5 rounded bg-blue-500/10 border border-blue-500/25 text-blue-400 text-[10px]">
                            {{ dept.name_en }}
                          </span>
                        </div>
                      </div>
                      <div class="flex items-center gap-4 text-right shrink-0">
                        <div>
                          <span class="text-[10px] text-slate-500 block uppercase">Budget</span>
                          <span class="font-bold text-slate-200">ETB {{ parseFloat(target.budget).toLocaleString() }}</span>
                        </div>
                        <div>
                          <span class="text-[10px] text-slate-500 block uppercase">Target Value</span>
                          <span class="font-bold text-indigo-400">{{ parseFloat(target.value) }} {{ target.unit }}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
