<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, default: () => ({}) },
  massPermissions: { type: Array, default: () => [] },
  closedDays: { type: Array, default: () => [] },
  periods: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
  user_id: { type: Number },
});

const STATUS_CLASSES = {
  draft: 'bg-slate-500/10 border-slate-500/20 text-slate-400',
  pending_approval: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
  pending_confirmation: 'bg-purple-500/10 border-purple-500/20 text-purple-400',
  approved: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  rejected: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
};

const modalOpen = ref(false);
const rejectModal = ref(null);
const rejectNotes = ref('');

const form = useForm({
  name: '',
  reason: '',
  payroll_period_id: '',
  closed_day_ids: [],
});

const openCreate = () => {
  form.reset();
  form.payroll_period_id = props.periods[0]?.id ?? '';
  form.clearErrors();
  modalOpen.value = true;
};

const submitCreate = () => {
  form.post('/admin/mass-permissions', {
    preserveScroll: true,
    onSuccess: () => { modalOpen.value = false; },
  });
};

const submitToWorkflow = async (mp) => {
  const ok = await confirm({
    title: 'Submit Mass Permission',
    message: `Submit "${mp.name}" for approval? This will begin the two-stage approval workflow.`,
  });
  if (ok) {
    router.post(`/admin/mass-permissions/${mp.id}/submit`, {}, { preserveScroll: true });
  }
};

const firstApprove = async (mp) => {
  const ok = await confirm({
    title: 'First Approval',
    message: `Record first approval for "${mp.name}"? It will proceed to final confirmation.`,
  });
  if (ok) {
    router.post(`/admin/mass-permissions/${mp.id}/first-approve`, {}, { preserveScroll: true });
  }
};

const finalApprove = async (mp) => {
  const ok = await confirm({
    title: 'Final Confirmation',
    message: `Confirm and approve "${mp.name}"? This will auto-spawn excused absences for all active employees for this period. This action is irreversible.`,
  });
  if (ok) {
    router.post(`/admin/mass-permissions/${mp.id}/final-approve`, {}, { preserveScroll: true });
  }
};

const openReject = (mp) => {
  rejectModal.value = mp.id;
  rejectNotes.value = '';
};

const submitReject = () => {
  router.post(`/admin/mass-permissions/${rejectModal.value}/reject`, {
    review_notes: rejectNotes.value,
  }, {
    preserveScroll: true,
    onSuccess: () => { rejectModal.value = null; rejectNotes.value = ''; },
  });
};

const toggleClosedDaySelection = (dayId) => {
  const idx = form.closed_day_ids.indexOf(dayId);
  if (idx > -1) {
    form.closed_day_ids.splice(idx, 1);
  } else {
    form.closed_day_ids.push(dayId);
  }
};
</script>

<template>
  <Head title="Mass Permissions — SITS ERP" />

  <div class="space-y-8">
    <!-- Header Section -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="UserCheck" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Finance · Attendance</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Mass Permissions</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Apply batch excused absences for holidays, organizational closed days, or special events.
              Uses a secure **two-layer approval workflow** (First Approval → Final Confirmation) to prevent salary deductions.
            </p>
          </div>
        </div>
        <button v-if="can.create" @click="openCreate" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
          + Initiate Mass Permission
        </button>
      </div>
    </section>

    <!-- Table List -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2">
      <table class="w-full text-left border-collapse text-sm">
        <thead>
          <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
            <th class="p-3">Title & Reason</th>
            <th class="p-3">Payroll Period</th>
            <th class="p-3">Holidays Covered</th>
            <th class="p-3 text-center">Days</th>
            <th class="p-3">Initiated By</th>
            <th class="p-3 text-center">Status & Approval Flow</th>
            <th class="p-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-900">
          <tr v-for="mp in massPermissions" :key="mp.id" class="hover:bg-slate-900/30 transition-colors">
            <td class="p-3">
              <div class="font-semibold text-slate-200">{{ mp.name }}</div>
              <div class="text-xs text-slate-500 mt-1 max-w-xs truncate" :title="mp.reason">{{ mp.reason || 'No reason provided' }}</div>
            </td>
            <td class="p-3 text-slate-300 font-medium">{{ mp.period }}</td>
            <td class="p-3">
              <div class="flex flex-wrap gap-1">
                <span v-for="d in mp.closed_days" :key="d.id" class="px-2 py-0.5 text-[10px] rounded bg-slate-900 border border-slate-800 text-slate-400 font-medium">
                  {{ d.name }} ({{ d.date }})
                </span>
              </div>
            </td>
            <td class="p-3 text-center font-mono font-semibold text-slate-300">{{ mp.total_days }}</td>
            <td class="p-3">
              <div class="text-slate-300 text-xs">{{ mp.initiated_by }}</div>
              <div class="text-[10px] text-slate-500 mt-0.5" v-if="mp.submitted_at">Submitted: {{ mp.submitted_at }}</div>
            </td>
            <td class="p-3">
              <div class="flex flex-col items-center gap-1">
                <span class="px-2 py-0.5 text-[10px] rounded-full font-bold border" :class="STATUS_CLASSES[mp.status]">
                  {{ mp.status_label }}
                </span>
                
                <!-- Status Mini Timeline -->
                <div class="flex items-center gap-1 mt-1 text-[9px] text-slate-500">
                  <span :class="{'text-indigo-400': mp.status !== 'draft'}">Init</span>
                  <span class="text-[8px]">➔</span>
                  <span :class="{'text-amber-400': mp.first_approved_by || mp.status === 'approved'}">Approve</span>
                  <span class="text-[8px]">➔</span>
                  <span :class="{'text-emerald-400': mp.status === 'approved'}">Confirm</span>
                </div>
              </div>
            </td>
            <td class="p-3 text-right whitespace-nowrap">
              <!-- Initiator Submit -->
              <button v-if="mp.status === 'draft' && can.create" @click="submitToWorkflow(mp)" class="text-[11px] font-bold px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-400 border border-indigo-500/20 rounded-lg mr-1 transition-all">
                Submit
              </button>

              <!-- First Approver Actions -->
              <template v-if="mp.status === 'pending_approval' && can.approve">
                <button @click="openReject(mp)" class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-rose-700 text-rose-400 bg-slate-900/40 rounded-lg mr-1 transition-all">
                  Reject
                </button>
                <button @click="firstApprove(mp)" class="text-[11px] font-bold px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white rounded-lg transition-all">
                  Approve
                </button>
              </template>

              <!-- Final Confirmer Actions -->
              <template v-if="mp.status === 'pending_confirmation' && can.approve">
                <button @click="openReject(mp)" class="text-[11px] font-bold px-3 py-1.5 border border-slate-850 hover:border-rose-700 text-rose-400 bg-slate-900/40 rounded-lg mr-1 transition-all">
                  Reject
                </button>
                
                <!-- Disable button if the current user was the first approver -->
                <button 
                  @click="finalApprove(mp)" 
                  :disabled="mp.first_approved_by_id === user_id"
                  :title="mp.first_approved_by_id === user_id ? 'Cannot confirm your own approval' : ''"
                  class="text-[11px] font-bold px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                >
                  Confirm
                </button>
              </template>

              <!-- Post-Approval Stats / Rejection Notes -->
              <div v-if="mp.status === 'approved'" class="text-[11px] text-emerald-400 font-semibold">
                Applied to {{ mp.employees_affected }} employees
              </div>
              <div v-else-if="mp.status === 'rejected'" class="text-[11px] text-slate-500 italic max-w-xs truncate" :title="mp.first_review_notes || mp.final_review_notes">
                {{ mp.first_review_notes || mp.final_review_notes || 'No review notes' }}
              </div>
            </td>
          </tr>
          <tr v-if="!massPermissions.length">
            <td colspan="7" class="p-8 text-center text-slate-500 italic">No mass permissions initiated yet.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-xl rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-6">Initiate Mass Permission</h3>
        <form @submit.prevent="submitCreate" class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Title</label>
              <input v-model="form.name" type="text" placeholder="e.g. Christmas Closure" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-blue-500/50" />
              <p v-if="form.errors.name" class="text-xs text-rose-400 mt-1">{{ form.errors.name }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Payroll Period</label>
              <select v-model="form.payroll_period_id" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-blue-500/50">
                <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
              <p v-if="form.errors.payroll_period_id" class="text-xs text-rose-400 mt-1">{{ form.errors.payroll_period_id }}</p>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Reason / Memo</label>
            <textarea v-model="form.reason" rows="2" placeholder="Describe why this mass permission is being initiated..." class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-blue-500/50"></textarea>
            <p v-if="form.errors.reason" class="text-xs text-rose-400 mt-1">{{ form.errors.reason }}</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Select Holidays / Closed Days</label>
            <div class="border border-slate-850 rounded-xl max-h-40 overflow-y-auto p-3 space-y-2 bg-slate-950/40">
              <div v-for="day in closedDays" :key="day.id" class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-900/50 cursor-pointer transition-colors" @click="toggleClosedDaySelection(day.id)">
                <div class="flex items-center gap-3">
                  <input type="checkbox" :checked="form.closed_day_ids.includes(day.id)" class="rounded border-slate-800 bg-slate-950 text-blue-600 focus:ring-blue-500" @click.stop="toggleClosedDaySelection(day.id)" />
                  <div>
                    <span class="text-sm font-semibold text-slate-200">{{ day.name }}</span>
                    <span class="text-[10px] text-slate-500 ml-2">({{ day.type }})</span>
                  </div>
                </div>
                <span class="font-mono text-xs text-slate-400">{{ day.date }}</span>
              </div>
              <div v-if="!closedDays.length" class="text-center text-xs text-slate-500 py-4">
                No active closed days registered in the calendar.
              </div>
            </div>
            <p v-if="form.errors.closed_day_ids" class="text-xs text-rose-400 mt-1">{{ form.errors.closed_day_ids }}</p>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="modalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors">Cancel</button>
            <button type="submit" :disabled="form.processing" class="text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50 transition-all">
              Save Draft
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-4">Reject Mass Permission</h3>
        <textarea v-model="rejectNotes" rows="3" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-rose-500/50" placeholder="Specify rejection reasons or review notes..."></textarea>
        <div class="flex items-center justify-end gap-3 pt-5">
          <button @click="rejectModal = null" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl transition-colors">Cancel</button>
          <button @click="submitReject" class="text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white px-5 py-2.5 rounded-xl cursor-pointer transition-colors">Reject</button>
        </div>
      </div>
    </div>
  </div>
</template>
