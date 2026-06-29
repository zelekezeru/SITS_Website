<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

defineProps({
  stats: { type: Object, default: () => ({}) },
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
  <Head title="Finance — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-teal-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-teal-500/10 border border-teal-500/20 flex items-center justify-center text-teal-400 shrink-0">
          <Icon name="Banknote" :size="26" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Finance Office</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Payroll Preparation</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">
            Prepare monthly payroll, manage deductions and submit drafts for the President's approval before export.
          </p>
        </div>
      </div>
    </section>

    <!-- Stat cards -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-5">
        <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Active Employees</p>
        <p class="text-3xl font-extrabold text-white mt-2">{{ stats.activeEmployees ?? 0 }}</p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-5">
        <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Payroll Periods</p>
        <p class="text-3xl font-extrabold text-white mt-2">{{ stats.periodsTotal ?? 0 }}</p>
      </div>
      <div class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-5">
        <p class="text-[11px] uppercase tracking-wider text-amber-500/80 font-semibold">Awaiting Approval</p>
        <p class="text-3xl font-extrabold text-amber-400 mt-2">{{ stats.pendingApproval ?? 0 }}</p>
      </div>
      <div class="rounded-2xl border border-rose-500/20 bg-rose-500/5 p-5">
        <p class="text-[11px] uppercase tracking-wider text-rose-500/80 font-semibold">Returned to Finance</p>
        <p class="text-3xl font-extrabold text-rose-400 mt-2">{{ stats.rejected ?? 0 }}</p>
      </div>
    </div>

    <!-- Current period spotlight -->
    <div v-if="stats.currentPeriod" class="rounded-2xl border border-slate-900 bg-gradient-to-br from-slate-900/40 to-slate-950 p-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <p class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Current Period</p>
        <div class="flex items-center gap-3 mt-1">
          <h3 class="text-xl font-bold text-white">{{ stats.currentPeriod.name }}</h3>
          <span class="px-2 py-0.5 text-[10px] rounded-full font-bold border" :class="statusClass(stats.currentPeriod.status)">{{ statusLabel(stats.currentPeriod.status) }}</span>
        </div>
        <p class="text-sm text-slate-400 mt-1">{{ stats.currentPeriod.payslips }} payslip(s) · Net {{ money(stats.currentPeriod.netTotal) }} ETB</p>
      </div>
      <Link :href="`/finance/payroll/${stats.currentPeriod.id}`"
            class="text-sm font-semibold bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
        Open Period Summary
      </Link>
    </div>

    <!-- Periods list -->
    <div class="space-y-4">
      <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Monthly Periods</h3>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <Link v-for="p in periods" :key="p.id" :href="`/finance/payroll/${p.id}`"
              class="p-5 rounded-2xl border border-slate-900 bg-slate-900/10 hover:border-slate-800 transition-all">
          <div class="flex items-center justify-between">
            <span class="font-bold text-white">{{ p.name }}</span>
            <span class="px-2 py-0.5 text-[9px] rounded-md font-bold uppercase border" :class="statusClass(p.status)">{{ statusLabel(p.status) }}</span>
          </div>
          <p class="text-xs text-slate-500 mt-3">{{ p.payslips }} payslip(s)</p>
          <p class="text-sm font-mono text-slate-300 mt-1">Net {{ money(p.netTotal) }} ETB</p>
        </Link>
        <p v-if="!periods.length" class="text-sm text-slate-500 italic col-span-full">No payroll periods for the active year yet.</p>
      </div>
    </div>
  </div>
</template>
