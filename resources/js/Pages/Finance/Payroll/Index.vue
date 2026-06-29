<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

defineProps({
  periods: { type: Array, default: () => [] },
});

const money = (n) => Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const STATUS = {
  open: 'bg-slate-700/40 border-slate-700 text-slate-300',
  processing: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  pending_approval: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
  approved: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  rejected: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
  locked: 'bg-violet-500/10 border-violet-500/20 text-violet-400',
  paid: 'bg-teal-500/10 border-teal-500/20 text-teal-400',
};
const statusClass = (s) => STATUS[s] || STATUS.open;
const statusLabel = (s) => (s || '').replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
</script>

<template>
  <Head title="Payroll Periods — SITS ERP" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-teal-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-teal-500/10 border border-teal-500/20 flex items-center justify-center text-teal-400 shrink-0">
          <Icon name="Banknote" :size="26" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Finance · Payroll</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Payroll Periods</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">Open a month to prepare its payroll, add deductions and submit for approval.</p>
        </div>
      </div>
    </section>

    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
            <th class="p-4">Period</th>
            <th class="p-4">Dates</th>
            <th class="p-4 text-center">Payslips</th>
            <th class="p-4 text-right">Net Total (ETB)</th>
            <th class="p-4 text-center">Status</th>
            <th class="p-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="text-sm divide-y divide-slate-900">
          <tr v-for="p in periods" :key="p.id" class="hover:bg-slate-900/30">
            <td class="p-4 font-semibold text-slate-200">{{ p.name }}</td>
            <td class="p-4 text-slate-400 text-xs">{{ p.start_date }} → {{ p.end_date }}</td>
            <td class="p-4 text-center font-mono text-slate-300">{{ p.payslips }}</td>
            <td class="p-4 text-right font-mono text-slate-300">{{ money(p.netTotal) }}</td>
            <td class="p-4 text-center">
              <span class="px-2 py-0.5 text-[10px] rounded-full font-bold border" :class="statusClass(p.status)">{{ statusLabel(p.status) }}</span>
            </td>
            <td class="p-4 text-right">
              <Link :href="`/finance/payroll/${p.id}`" class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-teal-700 bg-slate-900/50 text-teal-300 rounded-lg transition-colors">Open</Link>
            </td>
          </tr>
          <tr v-if="!periods.length">
            <td colspan="6" class="p-8 text-center text-slate-500 italic">No monthly payroll periods for the active year.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
