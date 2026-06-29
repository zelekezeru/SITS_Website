<script>
import PortalLayout from '@/Layouts/PortalLayout.vue';
export default { layout: PortalLayout };
</script>

<script setup>
import { Head } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

defineProps({
  team: { type: Array, default: () => [] },
  summary: { type: Object, default: () => ({ total: 0, active: 0, open_tasks: 0 }) },
});

const initials = (name) => (name || 'U').split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase();

const scoreColor = (v) => {
  if (v == null) return 'text-slate-500';
  const n = Number(v);
  if (n >= 80) return 'text-emerald-400';
  if (n >= 60) return 'text-blue-400';
  if (n >= 40) return 'text-amber-400';
  return 'text-rose-400';
};
</script>

<template>
  <Head title="My Team — SITS ERP" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-emerald-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0"><Icon name="Users" :size="26" /></span>
        <div>
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">People</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">My Team</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">The staff reporting into your department(s).</p>
        </div>
      </div>
    </section>

    <div class="grid grid-cols-3 gap-5">
      <div class="p-5 rounded-2xl border border-slate-900 bg-slate-900/35"><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Members</span><p class="text-2xl font-extrabold text-white mt-1">{{ summary.total }}</p></div>
      <div class="p-5 rounded-2xl border border-slate-900 bg-slate-900/35"><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Active</span><p class="text-2xl font-extrabold text-emerald-400 mt-1">{{ summary.active }}</p></div>
      <div class="p-5 rounded-2xl border border-slate-900 bg-slate-900/35"><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Open Tasks</span><p class="text-2xl font-extrabold text-white mt-1">{{ summary.open_tasks }}</p></div>
    </div>

    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Member</th>
              <th class="pb-3">Position</th>
              <th class="pb-3 text-center">Open</th>
              <th class="pb-3 text-center">Done</th>
              <th class="pb-3 text-center">KPIs</th>
              <th class="pb-3 text-right">Latest Score</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="m in team" :key="m.id" class="hover:bg-slate-900/40">
              <td class="py-4">
                <div class="flex items-center gap-3">
                  <span class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-500 to-purple-600 flex items-center justify-center text-[11px] font-bold text-white shrink-0">{{ initials(m.full_name_en) }}</span>
                  <div class="min-w-0">
                    <p class="font-semibold text-slate-200 truncate">{{ m.full_name_en }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ m.user?.email || m.staff_no }}</p>
                  </div>
                  <span v-if="!m.is_active" class="text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-rose-500/10 border border-rose-500/20 text-rose-400">Inactive</span>
                </div>
              </td>
              <td class="py-4 text-slate-400">{{ m.position?.title_en || 'Staff' }}</td>
              <td class="py-4 text-center font-mono text-slate-300">{{ m.open_tasks_count }}</td>
              <td class="py-4 text-center font-mono text-emerald-400">{{ m.completed_tasks_count }}</td>
              <td class="py-4 text-center font-mono text-slate-300">{{ m.kpis_count }}</td>
              <td class="py-4 text-right font-bold" :class="scoreColor(m.latest_score)">{{ m.latest_score != null ? Number(m.latest_score).toFixed(1) : '—' }}</td>
            </tr>
            <tr v-if="!team.length"><td colspan="6" class="py-12 text-center text-slate-600 italic">No team members found in your department(s).</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
