<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  module: { type: Object, default: () => ({}) },
  permissions: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  periods: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
});

const STATUS = {
  pending: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
  approved: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
  rejected: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
};

const modalOpen = ref(false);
const form = useForm({ employee_id: '', payroll_period_id: '', start_date: '', end_date: '', days: 1, reason: '', file: null });

const openCreate = () => {
  form.reset();
  form.employee_id = props.employees[0]?.id ?? '';
  form.payroll_period_id = props.periods[0]?.id ?? '';
  form.clearErrors();
  modalOpen.value = true;
};

const submit = () => {
  form.post('/attendance-permissions', { preserveScroll: true, onSuccess: () => { modalOpen.value = false; } });
};

const approve = async (p) => {
  const ok = await confirm({ title: 'Approve Permission', message: `Approve ${p.days} excused day(s) for ${p.employee}?` });
  if (ok) router.post(`/attendance-permissions/${p.id}/approve`, {}, { preserveScroll: true });
};

const rejectModal = ref(null);
const rejectNotes = ref('');
const reject = () => {
  router.post(`/attendance-permissions/${rejectModal.value}/reject`, { review_notes: rejectNotes.value }, {
    preserveScroll: true, onSuccess: () => { rejectModal.value = null; rejectNotes.value = ''; },
  });
};

// Attachment preview
const previewOpen = ref(false);
const previewSrc = ref('');

const openPreview = (p) => {
  previewSrc.value = p.file_path;
  previewOpen.value = true;
};

const previewName = computed(() => {
  const path = (previewSrc.value || '').split('?')[0];
  return decodeURIComponent(path.split('/').pop() || 'attachment');
});

const previewKind = computed(() => {
  const path = (previewSrc.value || '').split('?')[0].toLowerCase();
  if (/\.(png|jpe?g|gif|webp|bmp|svg)$/.test(path)) return 'image';
  if (/\.pdf$/.test(path)) return 'pdf';
  return 'other';
});
</script>

<template>
  <Head title="Attendance Permissions — SITS ERP" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="CalendarCheck" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Finance · Attendance</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Attendance Permissions</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Excused-absence requests for absent employees. Created by the Admin or Operations Manager and
              approved by the Admin before payroll — approved days reduce unpaid absence.
            </p>
          </div>
        </div>
        <button v-if="can.create" @click="openCreate" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
          + New Permission
        </button>
      </div>
    </section>

    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2">
      <table class="w-full text-left border-collapse text-sm">
        <thead>
          <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
            <th class="p-3">Employee</th>
            <th class="p-3">Period</th>
            <th class="p-3">Dates</th>
            <th class="p-3 text-center">Days</th>
            <th class="p-3">Reason</th>
            <th class="p-3">Requested by</th>
            <th class="p-3">Approver</th>
            <th class="p-3 text-center">Status</th>
            <th class="p-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-900">
          <tr v-for="p in permissions" :key="p.id" class="hover:bg-slate-900/30">
            <td class="p-3 font-semibold text-slate-200">{{ p.employee }}</td>
            <td class="p-3 text-slate-400">{{ p.period }}</td>
            <td class="p-3 text-slate-400 text-xs">{{ p.start_date || '—' }}<span v-if="p.end_date"> → {{ p.end_date }}</span></td>
            <td class="p-3 text-center font-mono text-slate-300">{{ p.days }}</td>
            <td class="p-3 text-slate-400 text-xs">
              <div class="flex flex-col gap-1">
                <span>{{ p.reason || '—' }}</span>
                <button v-if="p.file_path" type="button" @click="openPreview(p)" class="inline-flex items-center gap-1 text-[10px] font-semibold text-blue-400 hover:text-blue-300 hover:underline mt-1 w-max cursor-pointer">
                  <Icon name="File" :size="12" />
                  <span>View Attachment</span>
                </button>
              </div>
            </td>
            <td class="p-3 text-slate-400 text-xs">{{ p.created_by || '—' }}</td>
            <td class="p-3 text-slate-400 text-xs">{{ p.approved_by || '—' }}</td>
            <td class="p-3 text-center">
              <span class="px-2 py-0.5 text-[10px] rounded-full font-bold border" :class="STATUS[p.status]">{{ p.status_label }}</span>
            </td>
            <td class="p-3 text-right whitespace-nowrap">
              <template v-if="can.approve && p.status === 'pending'">
                <button @click="rejectModal = p.id" class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-rose-700 text-rose-400 bg-slate-900/50 rounded-lg mr-1">Reject</button>
                <button @click="approve(p)" class="text-[11px] font-bold px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg">Approve</button>
              </template>
              <span v-else-if="p.status === 'rejected' && p.review_notes" class="text-[11px] text-slate-500 italic">{{ p.review_notes }}</span>
            </td>
          </tr>
          <tr v-if="!permissions.length"><td colspan="9" class="p-8 text-center text-slate-500 italic">No attendance permissions yet.</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Create modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-6">New Attendance Permission</h3>
        <form @submit.prevent="submit" class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Employee</label>
              <select v-model="form.employee_id" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.full_name_en }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Period</label>
              <select v-model="form.payroll_period_id" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
                <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">From</label>
              <input v-model="form.start_date" type="date" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-3 py-3 text-slate-100 text-sm focus:outline-none" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">To</label>
              <input v-model="form.end_date" type="date" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-3 py-3 text-slate-100 text-sm focus:outline-none" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Days</label>
              <input v-model="form.days" type="number" min="1" max="31" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-3 py-3 text-slate-100 text-sm focus:outline-none" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Reason</label>
            <input v-model="form.reason" type="text" placeholder="e.g. Approved sick leave" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Attachment (Optional)</label>
            <div class="relative flex items-center justify-center border border-dashed border-slate-800 hover:border-blue-500/50 rounded-xl p-4 transition-colors bg-slate-950/40">
              <input type="file" @change="form.file = $event.target.files[0]" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
              <div class="text-center pointer-events-none">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 mb-2">
                  <Icon name="UploadCloud" :size="16" />
                </span>
                <p class="text-xs text-slate-400 font-medium">
                  <span class="text-blue-400 hover:underline">Click to upload</span> or drag and drop
                </p>
                <p class="text-[10px] text-slate-500 mt-1">PDF, DOC, DOCX, PNG, JPG up to 10MB</p>
                <p v-if="form.file" class="text-xs text-emerald-400 font-semibold mt-2">
                  Selected: {{ form.file.name }}
                </p>
              </div>
            </div>
          </div>
          <p v-if="form.errors.days" class="text-xs text-rose-400">{{ form.errors.days }}</p>
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="modalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl">Cancel</button>
            <button type="submit" :disabled="form.processing" class="text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50">Submit</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Reject modal -->
    <div v-if="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-4">Reject Permission</h3>
        <textarea v-model="rejectNotes" rows="3" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" placeholder="Reason (optional)…"></textarea>
        <div class="flex items-center justify-end gap-3 pt-5">
          <button @click="rejectModal = null" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl">Cancel</button>
          <button @click="reject" class="text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white px-5 py-2.5 rounded-xl cursor-pointer">Reject</button>
        </div>
      </div>
    </div>

    <!-- Attachment preview modal -->
    <div v-if="previewOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm" @click.self="previewOpen = false">
      <div class="w-full max-w-3xl max-h-[90vh] flex flex-col rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-slate-900">
          <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
              <Icon name="File" :size="16" />
            </span>
            <div class="min-w-0">
              <p class="text-sm font-bold text-white truncate">{{ previewName }}</p>
              <p class="text-[10px] text-slate-500 uppercase tracking-widest">Attachment Preview</p>
            </div>
          </div>
          <button type="button" @click="previewOpen = false" class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg border border-slate-800 text-slate-400 hover:text-white hover:border-slate-700 transition cursor-pointer">
            <Icon name="X" :size="16" />
          </button>
        </div>

        <div class="flex-1 min-h-0 overflow-auto bg-slate-950/60 flex items-center justify-center p-4">
          <img v-if="previewKind === 'image'" :src="previewSrc" :alt="previewName" class="max-w-full max-h-[70vh] rounded-lg object-contain" />
          <iframe v-else-if="previewKind === 'pdf'" :src="previewSrc" class="w-full h-[70vh] rounded-lg bg-white" title="PDF preview"></iframe>
          <div v-else class="text-center py-12 px-6">
            <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 text-slate-400 mb-4">
              <Icon name="FileText" :size="26" />
            </span>
            <p class="text-sm text-slate-300 font-semibold">Preview not available</p>
            <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">This file type can't be shown in the browser. Download it to view the contents.</p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-900">
          <a :href="previewSrc" target="_blank" rel="noopener" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl inline-flex items-center gap-1.5">
            <Icon name="Link2" :size="14" /> Open in new tab
          </a>
          <a :href="previewSrc" :download="previewName" class="text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl shadow-md inline-flex items-center gap-1.5">
            <Icon name="Download" :size="14" /> Download
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
