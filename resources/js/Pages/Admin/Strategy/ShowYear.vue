<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  year: { type: Object, required: true },
});

// Expand/collapse states for quarters and fortnights
const expandedQuarters = ref(new Set());
const expandedFortnights = ref(new Set());

const toggleQuarter = (id) => {
  const next = new Set(expandedQuarters.value);
  if (next.has(id)) next.delete(id);
  else next.add(id);
  expandedQuarters.value = next;
};

const toggleFortnight = (id) => {
  const next = new Set(expandedFortnights.value);
  if (next.has(id)) next.delete(id);
  else next.add(id);
  expandedFortnights.value = next;
};

const fmt = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';

const dayTypeColor = (type) => {
  const map = {
    working: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
    weekend: 'bg-slate-900/60 border-slate-800 text-slate-500',
    holiday: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
    leave: 'bg-purple-500/10 border-purple-500/20 text-purple-400',
  };
  return map[type] || 'bg-slate-900/60 border-slate-800 text-slate-500';
};
</script>

<template>
  <Head :title="`Fiscal Year Details — ${year.label}`" />

  <div class="space-y-8">
    <!-- Back to Years -->
    <div class="flex items-center gap-3">
      <Link
        href="/admin/strategy/years"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-400 hover:text-white bg-slate-900/60 hover:bg-slate-900 border border-slate-900 hover:border-slate-700 px-4 py-2 rounded-xl transition-all"
      >
        <Icon name="ArrowLeft" :size="15" />
        Back to Fiscal Years
      </Link>
      <div class="flex items-center gap-2 text-xs text-slate-600 font-semibold">
        <Icon name="ChevronRight" :size="13" />
        <span class="text-slate-400">{{ year.label }}</span>
      </div>
    </div>

    <!-- Header Section -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start justify-between gap-6 flex-wrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="CalendarDays" :size="26" />
          </span>
          <div>
            <div class="flex items-center gap-3 flex-wrap">
              <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight">{{ year.label }}</h2>
              <span
                class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border"
                :class="year.active
                  ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400'
                  : 'bg-slate-800/60 border-slate-800 text-slate-500'"
              >
                {{ year.active ? 'Active Year' : 'Inactive' }}
              </span>
            </div>
            <p class="text-slate-400 text-sm mt-2">
              Fiscal period: <span class="text-slate-200 font-semibold">{{ fmt(year.start_date) }}</span> → <span class="text-slate-200 font-semibold">{{ fmt(year.end_date) }}</span>
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Quarters List -->
    <div class="space-y-4">
      <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Quarters &amp; Sprints Tree</h3>

      <div v-if="!year.quarters || !year.quarters.length" class="py-16 text-center text-slate-500 border border-dashed border-slate-900 rounded-3xl">
        No quarters generated for this fiscal year yet.
      </div>

      <div v-else class="space-y-4">
        <div v-for="q in year.quarters" :key="q.id" class="border border-slate-900 rounded-2xl bg-slate-900/10 overflow-hidden">
          <!-- Quarter Header -->
          <button
            @click="toggleQuarter(q.id)"
            class="w-full flex items-center justify-between p-5 text-left transition-colors hover:bg-slate-900/20 cursor-pointer"
          >
            <div class="flex items-center gap-3">
              <Icon
                name="ChevronRight"
                :size="18"
                class="text-blue-400 transition-transform"
                :class="expandedQuarters.has(q.id) ? 'rotate-90' : ''"
              />
              <div>
                <h4 class="font-bold text-white text-base">{{ q.name }}</h4>
                <p class="text-xs text-slate-500 mt-0.5">
                  {{ fmt(q.start_date) }} — {{ fmt(q.end_date) }}
                </p>
              </div>
            </div>
            <span class="text-xs text-slate-500 bg-slate-950/50 border border-slate-900 px-3 py-1 rounded-lg">
              {{ q.fortnights?.length || 0 }} Fortnights
            </span>
          </button>

          <!-- Fortnights Sub-list -->
          <div v-if="expandedQuarters.has(q.id)" class="border-t border-slate-900 p-5 space-y-3 bg-slate-950/30">
            <div v-if="!q.fortnights || !q.fortnights.length" class="text-xs text-slate-600 italic">
              No fortnights assigned to this quarter.
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="f in q.fortnights"
                :key="f.id"
                class="border border-slate-900/60 rounded-xl bg-slate-900/10 overflow-hidden"
              >
                <!-- Fortnight Header -->
                <button
                  @click="toggleFortnight(f.id)"
                  class="w-full flex items-center justify-between p-4 text-left hover:bg-slate-900/20 transition-colors cursor-pointer"
                >
                  <div class="flex items-center gap-3">
                    <Icon
                      name="ChevronRight"
                      :size="15"
                      class="text-violet-400 transition-transform"
                      :class="expandedFortnights.has(f.id) ? 'rotate-90' : ''"
                    />
                    <div>
                      <h5 class="font-bold text-sm text-slate-200">{{ f.name }}</h5>
                      <p class="text-[11px] text-slate-500 mt-0.5">
                        {{ fmt(f.start_date) }} — {{ fmt(f.end_date) }}
                      </p>
                    </div>
                  </div>
                  <span class="text-[10px] text-slate-500 bg-slate-950/40 border border-slate-900/60 px-2 py-0.5 rounded-md">
                    {{ f.days?.length || 0 }} Days
                  </span>
                </button>

                <!-- Days List -->
                <div v-if="expandedFortnights.has(f.id)" class="border-t border-slate-900/60 p-4 bg-slate-950/50">
                  <div v-if="!f.days || !f.days.length" class="text-xs text-slate-650 italic">
                    No days generated for this sprint.
                  </div>
                  <div v-else class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
                    <div
                      v-for="d in f.days"
                      :key="d.id"
                      class="p-2.5 rounded-lg border text-center transition-all"
                      :class="dayTypeColor(d.type)"
                    >
                      <p class="text-xs font-mono font-bold">{{ new Date(d.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) }}</p>
                      <p class="text-[9px] uppercase tracking-wider font-semibold mt-1 opacity-70">{{ d.type }}</p>
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
