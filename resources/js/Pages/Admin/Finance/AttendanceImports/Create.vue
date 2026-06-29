<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  periods: { type: Array, default: () => [] },
  baseUrl: { type: String, default: '/admin/attendance-imports' },
});

const form = useForm({
  payroll_period_id: props.periods.length ? props.periods[0].id : '',
  file: null,
});

const onFileChange = (e) => {
  form.file = e.target.files[0] ?? null;
};

const submit = () => {
  form.post(props.baseUrl, { forceFormData: true });
};
</script>

<template>
  <Head title="Import Attendance — SITS ERP" />

  <div class="space-y-8 max-w-2xl">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-start gap-4">
        <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
          <Icon name="UploadCloud" :size="26" />
        </span>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Finance</p>
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Import Attendance</h2>
          <p class="text-slate-400 text-sm mt-2 max-w-2xl">
            Upload a HikVision "Attendance Summary" Excel export. The system will parse it, match each
            row to an employee, and show you a review screen before anything posts to payroll.
          </p>
        </div>
      </div>
    </section>

    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-8">
      <form @submit.prevent="submit" class="space-y-5">
        <div>
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Payroll Period</label>
          <select
            v-model="form.payroll_period_id"
            class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 transition-all text-sm"
            required
          >
            <option v-for="p in periods" :key="p.id" :value="p.id" :disabled="p.status === 'locked' || p.status === 'paid'">
              {{ p.name }} ({{ p.status }})
            </option>
          </select>
          <p v-if="!periods.length" class="text-xs text-amber-400 mt-2">
            No payroll periods exist yet. <Link href="/admin/payroll" class="underline">Create one in Payroll</Link> first.
          </p>
          <p v-if="form.errors.payroll_period_id" class="text-xs text-rose-400 mt-2">{{ form.errors.payroll_period_id }}</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Excel File (.xlsx / .xls)</label>
          <input
            type="file"
            accept=".xlsx,.xls"
            @change="onFileChange"
            class="w-full bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-300 text-sm file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-xs file:font-semibold"
            required
          />
          <p v-if="form.errors.file" class="text-xs text-rose-400 mt-2">{{ form.errors.file }}</p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
          <Link
            :href="baseUrl"
            class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors cursor-pointer"
          >
            Cancel
          </Link>
          <button
            type="submit"
            :disabled="form.processing || !periods.length"
            class="text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 hover:shadow-blue-500/20 cursor-pointer disabled:opacity-50"
          >
            {{ form.processing ? 'Parsing…' : 'Import & Review' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
