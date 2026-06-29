<script>
import PortalLayout from '@/Layouts/PortalLayout.vue';
export default { layout: PortalLayout };
</script>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';

defineProps({
  payslips: { type: Array, default: () => [] },
});

const expanded = ref(null);
const toggle = (id) => (expanded.value = expanded.value === id ? null : id);

const money = (v) => 'ETB ' + Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const STATUS = {
  draft: 'bg-slate-800/60 border-slate-800 text-slate-400',
  locked: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  paid: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
};
const statusClass = (s) => STATUS[s] ?? STATUS.draft;
</script>

<template>
  <Head title="My Payslips — SITS ERP" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-emerald-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0"><Icon name="ReceiptText" :size="26" /></span>
        <div>
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Finance</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">My Payslips</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">Your monthly statements — earnings, tax, pension and net pay (ETB).</p>
        </div>
      </div>
    </section>

    <div class="space-y-4">
      <div v-for="p in payslips" :key="p.id" class="rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md overflow-hidden">
        <div class="flex items-center gap-4 p-6">
          <button @click="toggle(p.id)" class="flex-1 flex items-center gap-4 text-left min-w-0">
            <span class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-emerald-400 shrink-0"><Icon name="Banknote" :size="18" /></span>
            <div class="min-w-0">
              <h3 class="font-bold text-white truncate">{{ p.payroll_period?.name || 'Payroll Period' }}</h3>
              <span class="text-[9px] font-bold uppercase tracking-wide px-2 py-0.5 rounded border capitalize mt-1 inline-block" :class="statusClass(p.status)">{{ p.status }}</span>
            </div>
          </button>
          <div class="text-right shrink-0">
            <p class="text-2xl font-extrabold text-white">{{ money(p.net_pay) }}</p>
            <p class="text-[10px] uppercase tracking-wide text-slate-500">Net Pay</p>
          </div>
          <a :href="`/dashboard/payslips/${p.id}/pdf`"
             class="shrink-0 text-xs font-semibold px-3 py-2 border border-slate-800 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-xl transition-colors flex items-center gap-1.5">
            <Icon name="FileText" :size="14" /> PDF
          </a>
          <button @click="toggle(p.id)" class="shrink-0 text-slate-500"><Icon name="ChevronDown" :size="18" class="transition-transform" :class="expanded === p.id ? 'rotate-180' : ''" /></button>
        </div>

        <div v-if="expanded === p.id" class="border-t border-slate-900 p-6 grid md:grid-cols-2 gap-6">
          <!-- Breakdown -->
          <div class="space-y-2 text-sm">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Summary</p>
            <div class="flex justify-between"><span class="text-slate-500">Gross</span><span class="font-mono text-slate-200">{{ money(p.gross) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Taxable income</span><span class="font-mono text-slate-200">{{ money(p.taxable_income) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Income tax</span><span class="font-mono text-rose-400">−{{ money(p.income_tax) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Pension (7%)</span><span class="font-mono text-rose-400">−{{ money(p.employee_pension) }}</span></div>
            <div class="flex justify-between border-t border-slate-900 pt-2"><span class="text-slate-400 font-semibold">Total deductions</span><span class="font-mono text-rose-400">−{{ money(p.total_deductions) }}</span></div>
            <div class="flex justify-between border-t border-slate-900 pt-2"><span class="text-white font-bold">Net pay</span><span class="font-mono text-emerald-400 font-bold">{{ money(p.net_pay) }}</span></div>
          </div>
          <!-- Line items -->
          <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Line Items</p>
            <div class="space-y-1.5">
              <div v-for="l in p.lines" :key="l.id" class="flex justify-between text-sm p-2 rounded-lg bg-slate-950/30 border border-slate-900">
                <span class="text-slate-400">{{ l.label }}</span>
                <span class="font-mono" :class="l.type === 'deduction' ? 'text-rose-400' : 'text-slate-200'">{{ money(l.amount) }}</span>
              </div>
              <p v-if="!p.lines?.length" class="text-xs text-slate-600 italic">No line items recorded.</p>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!payslips.length" class="rounded-2xl border border-slate-900 bg-slate-900/10 p-12 text-center text-slate-600 italic">
        No payslips have been issued to you yet.
      </div>
    </div>
  </div>
</template>
