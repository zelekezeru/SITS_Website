<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  module: { type: Object, required: true },
  totalEmployees: { type: Number, default: 0 },
  totalBudget: { type: Number, default: 0 },
  avgScore: { type: Number, default: 0 },
  employees: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
});
</script>

<template>
  <Head title="Executive Reporting — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
          <Icon name="PieChart" :size="26" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">{{ module.description }}</p>
        </div>
      </div>
    </section>

    <!-- Summary Widgets -->
    <div class="grid sm:grid-cols-3 gap-6">
      <!-- Active staff -->
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 flex items-center gap-4 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-24 h-24 rounded-full bg-blue-500/5 blur-2xl pointer-events-none"></div>
        <span class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-400 shrink-0">
          <Icon name="Users" :size="22" />
        </span>
        <div>
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Active Employees</p>
          <p class="text-2xl font-black text-white mt-1">{{ totalEmployees }}</p>
        </div>
      </div>

      <!-- Allocated Budget -->
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 flex items-center gap-4 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-24 h-24 rounded-full bg-emerald-500/5 blur-2xl pointer-events-none"></div>
        <span class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 shrink-0">
          <Icon name="Banknote" :size="22" />
        </span>
        <div>
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Allocated Budget</p>
          <p class="text-2xl font-black text-emerald-400 mt-1 font-mono">{{ totalBudget.toLocaleString() }} ETB</p>
        </div>
      </div>

      <!-- Avg performance score -->
      <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6 flex items-center gap-4 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-24 h-24 rounded-full bg-purple-500/5 blur-2xl pointer-events-none"></div>
        <span class="w-12 h-12 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400 shrink-0">
          <Icon name="Star" :size="22" />
        </span>
        <div>
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Average Score</p>
          <p class="text-2xl font-black text-white mt-1 font-mono">{{ Number(avgScore).toFixed(1) }}%</p>
        </div>
      </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">
      <!-- Left: Departments overview -->
      <div class="space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Department Status</h3>
        <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
                <th class="pb-3">Name</th>
                <th class="pb-3">Campus</th>
                <th class="pb-3">Status</th>
              </tr>
            </thead>
            <tbody class="text-xs divide-y divide-slate-900">
              <tr v-for="dept in departments" :key="dept.id" class="hover:bg-slate-900/40">
                <td class="py-3 font-semibold text-slate-200">{{ dept.name_en }}</td>
                <td class="py-3 text-slate-400">{{ dept.campus?.name_en || 'Remote' }}</td>
                <td class="py-3">
                  <span 
                    class="px-2 py-0.5 rounded-full font-bold border text-[9px] uppercase"
                    :class="dept.is_active 
                      ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400' 
                      : 'bg-slate-800/60 border-slate-800 text-slate-500'"
                  >
                    {{ dept.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Right: Employees overview -->
      <div class="space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Staff Overview</h3>
        <div class="rounded-2xl border border-slate-900 bg-slate-900/10 p-6">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
                <th class="pb-3">Staff ID</th>
                <th class="pb-3">Name</th>
                <th class="pb-3">Department</th>
              </tr>
            </thead>
            <tbody class="text-xs divide-y divide-slate-900">
              <tr v-for="emp in employees" :key="emp.id" class="hover:bg-slate-900/40">
                <td class="py-3 font-mono text-blue-400 font-semibold">{{ emp.staff_no }}</td>
                <td class="py-3 font-semibold text-slate-200">{{ emp.full_name_en }}</td>
                <td class="py-3 text-slate-400">{{ emp.department?.name_en || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
