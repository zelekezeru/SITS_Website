<script>
import PortalLayout from '@/Layouts/PortalLayout.vue';
export default { layout: PortalLayout };
</script>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  kpis: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({ approve: false }) },
});

const tab = ref('pending');
const tabs = [
  { key: 'pending', label: 'Awaiting Approval' },
  { key: 'approved', label: 'Approved' },
  { key: 'confirmed', label: 'Confirmed' },
];

const stageOf = (k) => (k.confirmed_by ? 'confirmed' : k.approved_by ? 'approved' : 'pending');
const shown = computed(() => props.kpis.filter((k) => stageOf(k) === tab.value));
const counts = computed(() => ({
  pending: props.kpis.filter((k) => stageOf(k) === 'pending').length,
  approved: props.kpis.filter((k) => stageOf(k) === 'approved').length,
  confirmed: props.kpis.filter((k) => stageOf(k) === 'confirmed').length,
}));

const approve = async (kpi) => {
  if (await confirm({ title: 'Approve KPI', message: `Approve “${kpi.title_en}”? It will then await the President's confirmation.` })) {
    router.post(`/department/kpis/${kpi.id}/approve`, {}, { preserveScroll: true });
  }
};

const label = (s) => (s ?? '').replace(/_/g, ' ');
</script>

<template>
  <Head title="KPI Approvals — SITS ERP" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0"><Icon name="Gauge" :size="26" /></span>
        <div>
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Performance</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">KPI Approvals</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">The maker step: <span class="text-slate-300">created → <span class="text-blue-400 font-semibold">approved</span> → confirmed</span>. You approve your team's KPIs; the President confirms them.</p>
        </div>
      </div>
    </section>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-slate-900">
      <button v-for="t in tabs" :key="t.key" @click="tab = t.key"
              class="px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition-colors"
              :class="tab === t.key ? 'border-blue-500 text-white' : 'border-transparent text-slate-500 hover:text-slate-300'">
        {{ t.label }}
        <span class="ml-1.5 text-[10px] font-bold px-1.5 py-0.5 rounded-full" :class="tab === t.key ? 'bg-blue-500/20 text-blue-300' : 'bg-slate-800 text-slate-500'">{{ counts[t.key] }}</span>
      </button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div v-for="k in shown" :key="k.id" class="rounded-2xl border border-slate-900 bg-slate-900/20 p-6 shadow-md">
        <div class="flex items-start justify-between gap-3">
          <h3 class="font-bold text-white leading-snug">{{ k.title_en }}</h3>
          <span v-if="k.confirmed_by" class="text-[9px] font-bold uppercase tracking-wide px-2 py-1 rounded bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 shrink-0">Confirmed</span>
          <span v-else-if="k.approved_by" class="text-[9px] font-bold uppercase tracking-wide px-2 py-1 rounded bg-blue-500/10 border border-blue-500/20 text-blue-400 shrink-0">Approved</span>
          <span v-else class="text-[9px] font-bold uppercase tracking-wide px-2 py-1 rounded bg-amber-500/10 border border-amber-500/20 text-amber-400 shrink-0">Created</span>
        </div>

        <div class="flex flex-wrap items-center gap-2 mt-3 text-xs">
          <span class="px-2 py-0.5 rounded-md bg-slate-950/50 border border-slate-900 text-slate-400 capitalize">{{ label(k.measure_type) }}</span>
          <span class="px-2 py-0.5 rounded-md bg-slate-950/50 border border-slate-900 text-slate-400">Weight {{ Number(k.weight) }}</span>
          <span v-if="k.target_value" class="px-2 py-0.5 rounded-md bg-slate-950/50 border border-slate-900 text-slate-400">Target {{ Number(k.target_value) }} {{ k.unit }}</span>
        </div>

        <div class="mt-4 pt-3 border-t border-slate-900">
          <p class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold mb-1.5">Assigned to</p>
          <div class="flex flex-wrap gap-1.5">
            <span v-for="e in k.employees" :key="e.id" class="text-xs px-2 py-0.5 rounded-md bg-slate-950/50 border border-slate-900 text-slate-300">{{ e.full_name_en }}</span>
            <span v-if="!k.employees?.length" class="text-xs text-slate-600 italic">No assignees</span>
          </div>
        </div>

        <div class="mt-5 flex items-center justify-between">
          <p class="text-xs text-slate-600">
            <span v-if="k.approved_by">Approved by {{ k.approved_by?.name }}</span>
            <span v-else>Awaiting your approval</span>
          </p>
          <button v-if="can.approve && !k.approved_by" @click="approve(k)"
                  class="text-xs font-semibold bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white px-4 py-2 rounded-xl transition-all shadow-md shadow-emerald-500/10 cursor-pointer flex items-center gap-1.5">
            <Icon name="ShieldCheck" :size="14" /> Approve
          </button>
        </div>
      </div>

      <div v-if="!shown.length" class="md:col-span-2 rounded-2xl border border-slate-900 bg-slate-900/10 p-12 text-center text-slate-600 italic">
        No KPIs in this stage.
      </div>
    </div>
  </div>
</template>
