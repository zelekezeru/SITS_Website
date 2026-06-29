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
  // Accepts a paginator ({ data, links }) or a plain array.
  payslips: { type: [Object, Array], default: () => ({ data: [] }) },
});

const rows = computed(() => props.payslips?.data ?? props.payslips ?? []);
const pageLinks = computed(() => props.payslips?.links ?? []);
const selectedPayslip = ref(null);
</script>

<template>
  <Head title="Employee Payslips — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
          <Icon name="ReceiptText" :size="26" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
        </div>
      </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left side: list payslips -->
      <div class="lg:col-span-1 space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Payslips Log</h3>
        <div class="space-y-3">
          <div
            v-for="slip in rows"
            :key="slip.id"
            @click="selectedPayslip = slip"
            class="p-4 rounded-xl border transition-all cursor-pointer select-none"
            :class="selectedPayslip && selectedPayslip.id === slip.id 
              ? 'border-blue-500/40 bg-blue-500/5' 
              : 'border-slate-900 bg-slate-900/10 hover:bg-slate-900/30 hover:border-slate-800'"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <h4 class="font-bold text-white text-sm">{{ slip.employee?.full_name_en }}</h4>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ slip.payroll_period?.name }}</p>
              </div>
              <span class="text-xs font-mono font-bold text-slate-300">{{ Number(slip.net_pay).toLocaleString() }} ETB</span>
            </div>
          </div>

          <div v-if="!rows.length" class="py-8 text-center text-slate-650 italic border border-dashed border-slate-900 rounded-xl">
            No payslips calculated yet.
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pageLinks.length > 3" class="flex flex-wrap gap-1 pt-2">
          <component
            v-for="(link, i) in pageLinks" :key="i"
            :is="link.url ? Link : 'span'"
            :href="link.url || undefined"
            preserve-scroll
            class="px-3 py-1.5 text-xs rounded-lg border"
            :class="link.active
              ? 'border-blue-500/40 bg-blue-500/10 text-blue-300'
              : link.url ? 'border-slate-850 text-slate-400 hover:border-slate-700' : 'border-slate-900 text-slate-700'"
            v-html="link.label"
          />
        </div>
      </div>

      <!-- Right side: Detail Slip View -->
      <div class="lg:col-span-2">
        <div v-if="selectedPayslip" class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 space-y-6">
          <div class="border-b border-slate-900 pb-4 flex items-start justify-between gap-4">
            <div>
              <h3 class="text-lg font-bold text-white">SITS SEMINARY ERP</h3>
              <p class="text-xs text-slate-500 mt-0.5">Pay Advice Period: {{ selectedPayslip.payroll_period?.name }}</p>
            </div>
            <a :href="`/admin/payslips/${selectedPayslip.id}/pdf`" target="_blank"
               class="shrink-0 inline-flex items-center gap-2 text-xs font-semibold bg-slate-900 hover:bg-slate-800 border border-slate-850 hover:border-slate-700 text-slate-200 px-3.5 py-2 rounded-xl transition-all">
              <Icon name="FileText" :size="15" /> Download PDF
            </a>
          </div>

          <div class="grid grid-cols-2 gap-4 text-xs text-slate-400">
            <div>
              <p>Employee Name: <span class="font-semibold text-slate-200">{{ selectedPayslip.employee?.full_name_en }}</span></p>
              <p class="mt-1">Staff ID: <span class="font-semibold text-slate-200 font-mono">{{ selectedPayslip.employee?.staff_no }}</span></p>
            </div>
            <div class="text-right">
              <p>Employment Type: <span class="font-semibold text-slate-200 capitalize">{{ selectedPayslip.employee?.employment_type.replace('_', ' ') }}</span></p>
              <p class="mt-1">Date: <span class="font-semibold text-slate-200">{{ new Date(selectedPayslip.created_at).toLocaleDateString() }}</span></p>
            </div>
          </div>

          <!-- Breakdown Line Items -->
          <div class="space-y-4 pt-4 border-t border-slate-900">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Earnings &amp; Deductions Breakdown</h4>
            <div class="divide-y divide-slate-900 border border-slate-900 rounded-xl overflow-hidden">
              <div 
                v-for="line in selectedPayslip.lines" 
                :key="line.id"
                class="flex items-center justify-between p-3.5 text-xs bg-slate-950/20"
              >
                <span class="font-medium" :class="line.type === 'earning' ? 'text-slate-200' : 'text-slate-400'">
                  {{ line.label }}
                </span>
                <span class="font-mono font-bold" :class="line.type === 'earning' ? 'text-emerald-400' : 'text-rose-455'">
                  {{ line.type === 'earning' ? '+' : '-' }}{{ Number(line.amount).toLocaleString() }} ETB
                </span>
              </div>
            </div>
          </div>

          <!-- Totals summary -->
          <div class="bg-slate-950/60 p-5 rounded-xl border border-slate-850 space-y-2 text-xs">
            <div class="flex justify-between text-slate-400">
              <span>Gross Earnings:</span>
              <span class="font-mono font-medium text-slate-200">{{ Number(selectedPayslip.gross).toLocaleString() }} ETB</span>
            </div>
            <div class="flex justify-between text-slate-400">
              <span>Total Deductions:</span>
              <span class="font-mono font-medium text-rose-455">-{{ Number(selectedPayslip.total_deductions).toLocaleString() }} ETB</span>
            </div>
            <div class="flex justify-between text-sm font-bold text-white pt-2 border-t border-slate-900">
              <span>NET TAKE HOME PAY:</span>
              <span class="font-mono text-emerald-400">{{ Number(selectedPayslip.net_pay).toLocaleString() }} ETB</span>
            </div>
          </div>
        </div>

        <div v-else class="h-64 flex flex-col items-center justify-center border border-dashed border-slate-900 rounded-2xl text-slate-500">
          <Icon name="ReceiptText" :size="32" class="text-slate-700 mb-2" />
          <p class="text-sm">Select a payslip entry from the left to view the breakdown details.</p>
        </div>
      </div>
    </div>
  </div>
</template>
