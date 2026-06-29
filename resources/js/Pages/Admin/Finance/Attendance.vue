<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

defineProps({
  module: { type: Object, required: true },
  attendanceRecords: { type: Array, default: () => [] },
});
</script>

<template>
  <Head title="Attendance Logs — SITS ERP" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="Clock" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">{{ module.section }}</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">{{ module.label }}</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Attendance is sourced from imported HikVision device exports. Use the import workflow to add or
              update records — these logs are read-only.
            </p>
          </div>
        </div>
        <Link
          href="/admin/attendance-imports/create"
          class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer"
        >
          Import Attendance
        </Link>
      </div>
    </section>

    <!-- Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
              <th class="pb-3">Employee</th>
              <th class="pb-3">Period</th>
              <th class="pb-3">Regular Hours</th>
              <th class="pb-3">Late Min</th>
              <th class="pb-3">Absences</th>
              <th class="pb-3">OT (Normal / Night)</th>
              <th class="pb-3">OT (Rest / Holiday)</th>
              <th class="pb-3">Status</th>
            </tr>
          </thead>
          <tbody class="text-sm divide-y divide-slate-900">
            <tr v-for="rec in attendanceRecords" :key="rec.id" class="hover:bg-slate-900/40">
              <td class="py-4 font-semibold text-slate-200">{{ rec.employee?.full_name_en }}</td>
              <td class="py-4 text-slate-450">{{ rec.payroll_period?.name }}</td>
              <td class="py-4 font-mono text-slate-300">{{ Number(rec.work_hours).toFixed(1) }}h</td>
              <td class="py-4 font-mono text-amber-500 font-semibold">{{ rec.late_minutes }}m</td>
              <td class="py-4 text-slate-400">
                <span class="text-rose-400 font-bold" v-if="rec.absent_days > 0">{{ rec.absent_days }}d absent</span>
                <span v-else>0 absences</span>
              </td>
              <td class="py-4 font-mono text-slate-400">
                <span>{{ Number(rec.overtime_normal).toFixed(1) }}h</span> /
                <span class="text-blue-400">{{ Number(rec.ot_night).toFixed(1) }}h</span>
              </td>
              <td class="py-4 font-mono text-slate-400">
                <span>{{ Number(rec.ot_rest).toFixed(1) }}h</span> /
                <span class="text-purple-400">{{ Number(rec.ot_holiday).toFixed(1) }}h</span>
              </td>
              <td class="py-4 capitalize">
                <span
                  class="px-2 py-0.5 text-[10px] rounded-full font-bold border"
                  :class="rec.status === 'verified'
                    ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400'
                    : 'bg-slate-800/60 border-slate-800 text-slate-500'"
                >
                  {{ rec.status }}
                </span>
              </td>
            </tr>
            <tr v-if="!attendanceRecords.length">
              <td colspan="8" class="py-8 text-center text-slate-650 italic">
                No attendance records yet. <Link href="/admin/attendance-imports/create" class="underline">Import a device export</Link> to get started.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
