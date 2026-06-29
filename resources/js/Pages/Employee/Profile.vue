<script>
import PortalLayout from '@/Layouts/PortalLayout.vue';
export default { layout: PortalLayout };
</script>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  employee: { type: Object, default: null },
  account: { type: Object, required: true },
});

const initials = computed(() => (props.account.name || 'U').split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase());
const fmtDate = (d) => (d ? new Date(d).toLocaleDateString() : '—');
const money = (v) => (v != null ? 'ETB ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) : '—');

const pw = useForm({ current_password: '', password: '', password_confirmation: '' });
const changePassword = () => {
  pw.post('/dashboard/profile/password', {
    preserveScroll: true,
    onSuccess: () => pw.reset(),
  });
};

const field = (label, value) => ({ label, value });
const facts = computed(() => props.employee ? [
  field('Staff Number', props.employee.staff_no),
  field('Position', props.employee.position?.title_en),
  field('Department', props.employee.department?.name_en),
  field('Campus', props.employee.department?.campus?.name_en),
  field('Employment Type', props.employee.employment_type),
  field('Reports To', props.employee.reporting_to?.full_name_en),
  field('Hired', fmtDate(props.employee.hired_at)),
  field('Base Salary', money(props.employee.base_salary)),
] : []);
</script>

<template>
  <Head title="My Profile — SITS ERP" />

  <div class="space-y-8">
    <!-- Header / identity -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center gap-5">
        <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-blue-500 to-purple-600 flex items-center justify-center text-2xl font-bold text-white shadow-lg shadow-blue-500/20 shrink-0">{{ initials }}</div>
        <div class="min-w-0">
          <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight">{{ account.name }}</h2>
          <p class="text-slate-400 text-sm mt-1">{{ account.email }}</p>
          <div class="flex flex-wrap gap-2 mt-3">
            <span v-for="r in account.roles" :key="r" class="text-[11px] font-semibold px-2.5 py-1 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-400">{{ r }}</span>
          </div>
        </div>
      </div>
    </section>

    <div class="grid lg:grid-cols-12 gap-6">
      <!-- Personnel record -->
      <div class="lg:col-span-7 rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md p-6">
        <h3 class="font-bold text-lg text-white flex items-center gap-2 mb-5"><Icon name="FileText" :size="19" class="text-blue-400" /> Personnel Record</h3>
        <template v-if="employee">
          <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4">
            <div v-for="f in facts" :key="f.label">
              <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">{{ f.label }}</dt>
              <dd class="text-sm text-slate-200 font-medium mt-0.5 capitalize">{{ f.value || '—' }}</dd>
            </div>
          </dl>
          <p class="text-xs text-slate-600 mt-6 pt-4 border-t border-slate-900">
            Your personnel details are maintained by the People &amp; HR office. Contact your department head to request a change.
          </p>
        </template>
        <p v-else class="text-sm text-slate-600 italic py-6">No employee profile is linked to your account yet.</p>
      </div>

      <!-- Security -->
      <div class="lg:col-span-5 rounded-2xl border border-slate-900 bg-slate-900/20 shadow-md p-6">
        <h3 class="font-bold text-lg text-white flex items-center gap-2 mb-1"><Icon name="ShieldCheck" :size="19" class="text-emerald-400" /> Security</h3>
        <p class="text-xs text-slate-500 mb-5">
          <span v-if="!account.passwordChanged" class="text-amber-400 font-semibold">You're still using your default password. </span>
          Choose a strong password you don't use elsewhere.
        </p>
        <form @submit.prevent="changePassword" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Current Password</label>
            <input v-model="pw.current_password" type="password" required class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 text-sm" />
            <p v-if="pw.errors.current_password" class="text-xs text-rose-400 mt-1">{{ pw.errors.current_password }}</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">New Password</label>
            <input v-model="pw.password" type="password" required class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 text-sm" />
            <p v-if="pw.errors.password" class="text-xs text-rose-400 mt-1">{{ pw.errors.password }}</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Confirm New Password</label>
            <input v-model="pw.password_confirmation" type="password" required class="w-full bg-slate-950/60 border border-slate-800 focus:border-blue-500/50 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500/50 text-sm" />
          </div>
          <button type="submit" :disabled="pw.processing" class="w-full text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md shadow-blue-500/10 cursor-pointer">
            {{ pw.processing ? 'Updating…' : 'Update Password' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
