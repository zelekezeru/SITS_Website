<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import axios from 'axios';

const props = defineProps({
  employee: { type: Object, required: true },
  components: { type: Array, default: () => [] },
  periods: { type: Array, default: () => [] },
  scheduleTypes: { type: Array, default: () => [] },
  apiPrefix: { type: String, default: 'admin' }, // 'admin' or 'finance'
});

const emit = defineEmits(['close', 'updated']);

const loading = ref(true);
const saving = ref(false);

const config = ref({
  flags: {
    has_provident_fund: false,
    statutory_exempt: false,
    attendance_exempt: false,
    attendance_exempt_reason: '',
  },
  assignments: [],
});

const fetchConfig = async () => {
  loading.value = true;
  try {
    const res = await axios.get(`/${props.apiPrefix}/employees/${props.employee.id}/payroll-config`);
    config.value = res.data;
  } catch (e) {
    console.error('Failed to load employee config', e);
  } finally {
    loading.value = false;
  }
};

watch(() => props.employee?.id, (newId) => {
  if (newId) fetchConfig();
}, { immediate: true });

const flagsError = ref('');
const flagsSaved = ref(false);

const updateFlags = async () => {
  saving.value = true;
  flagsError.value = '';
  flagsSaved.value = false;
  try {
    await axios.post(`/${props.apiPrefix}/employees/${props.employee.id}/payroll-config`, config.value.flags);
    flagsSaved.value = true;
    emit('updated');
  } catch (e) {
    const data = e?.response?.data;
    const first = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
    flagsError.value = first || data?.message || 'Could not save these rules.';
  } finally {
    saving.value = false;
  }
};

// ─── ASSIGNMENTS ─────────────────────────────────────────────────────────────
// Callers hand `scheduleTypes` over as either plain enum values or {value,label}
// pairs — normalise so the <option> always binds the value, never the object.
const scheduleOptions = computed(() => (props.scheduleTypes ?? []).map(st => (
  typeof st === 'string'
    ? { value: st, label: st.replace(/_/g, ' ').replace(/\b\w/g, m => m.toUpperCase()) }
    : st
)));

const assignFormOpen = ref(false);
const assignForm = useForm({
  payroll_component_id: '',
  amount: 0,
  schedule_type: 'monthly',
  start_period_id: '',
  end_period_id: '',
  note: '',
});

// A one-time entry needs the period it lands in; a range needs both bounds.
const needsStartPeriod = computed(() => assignForm.schedule_type !== 'monthly');
const needsEndPeriod = computed(() => assignForm.schedule_type === 'range');

const assignError = ref('');
const assignErrors = ref({});
const assignSaving = ref(false);

/** Pull the message(s) out of a Laravel 422 response for display. */
const readError = (e, fallback) => {
  const data = e?.response?.data;
  assignErrors.value = data?.errors ?? {};
  const first = data?.errors ? Object.values(data.errors)[0]?.[0] : null;

  return first || data?.message || fallback;
};

const submitAssignment = async () => {
  assignError.value = '';
  assignErrors.value = {};
  assignSaving.value = true;
  try {
    await axios.post(`/${props.apiPrefix}/employees/${props.employee.id}/assignments`, {
      ...assignForm.data(),
      start_period_id: assignForm.start_period_id || null,
      end_period_id: assignForm.end_period_id || null,
    });
    assignForm.reset();
    assignFormOpen.value = false;
    await fetchConfig();
    emit('updated');
  } catch (e) {
    assignError.value = readError(e, 'Could not assign this component.');
  } finally {
    assignSaving.value = false;
  }
};

const deleteAssignment = async (assignmentId) => {
  if (!confirm('Are you sure you want to remove this assignment?')) return;
  try {
    await axios.delete(`/${props.apiPrefix}/employees/${props.employee.id}/assignments/${assignmentId}`);
    await fetchConfig();
    emit('updated');
  } catch (e) {
    assignError.value = readError(e, 'Could not remove this assignment.');
  }
};

const money = (n) => Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
    <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="emit('close')"></div>
    <div class="relative bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
      
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-950/50 shrink-0">
        <div>
          <h2 class="text-lg font-bold text-slate-200 flex items-center gap-2">
            <Icon name="Settings" class="text-blue-400" />
            Payroll Configuration
          </h2>
          <p class="text-xs text-slate-500 mt-1">
            Managing <span class="font-bold text-slate-300">{{ employee.full_name_en }}</span>
          </p>
        </div>
        <button @click="emit('close')" class="text-slate-500 hover:text-slate-300 transition-colors cursor-pointer">
          <Icon name="X" />
        </button>
      </div>

      <!-- Body -->
      <div class="flex-1 overflow-y-auto p-6 space-y-8" v-if="!loading">
        
        <!-- Flags Section -->
        <section class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
              <Icon name="ShieldAlert" :size="15" /> Base Rules
            </h3>
            <button @click="updateFlags" :disabled="saving" class="text-xs font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20 px-3 py-1.5 rounded-lg hover:bg-blue-500/20 transition-colors disabled:opacity-50 cursor-pointer">
              {{ saving ? 'Saving...' : 'Save Rules' }}
            </button>
          </div>
          
          <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-800 bg-slate-950/50 cursor-pointer hover:border-slate-700 transition-colors">
              <input type="checkbox" v-model="config.flags.has_provident_fund" class="mt-1 rounded bg-slate-900 border-slate-700 text-blue-500 focus:ring-blue-500/50" />
              <div>
                <span class="block text-sm font-bold text-slate-200">Provident Fund (PF)</span>
                <span class="block text-xs text-slate-500 mt-0.5">Enables PF deductions</span>
              </div>
            </label>

            <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-800 bg-slate-950/50 cursor-pointer hover:border-slate-700 transition-colors">
              <input type="checkbox" v-model="config.flags.statutory_exempt" class="mt-1 rounded bg-slate-900 border-slate-700 text-blue-500 focus:ring-blue-500/50" />
              <div>
                <span class="block text-sm font-bold text-slate-200">Statutory Exempt</span>
                <span class="block text-xs text-slate-500 mt-0.5">Exempt from pension and tax</span>
              </div>
            </label>

            <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-800 bg-slate-950/50 cursor-pointer hover:border-slate-700 transition-colors">
              <input type="checkbox" v-model="config.flags.attendance_exempt" class="mt-1 rounded bg-slate-900 border-slate-700 text-blue-500 focus:ring-blue-500/50" />
              <div>
                <span class="block text-sm font-bold text-slate-200">Attendance Exempt</span>
                <span class="block text-xs text-slate-500 mt-0.5">No deductions for absence</span>
              </div>
            </label>
          </div>

          <div v-if="config.flags.attendance_exempt" class="pt-2">
            <label class="block text-xs font-semibold text-slate-400 mb-1">Exemption Reason</label>
            <input type="text" v-model="config.flags.attendance_exempt_reason" class="w-full bg-slate-950 border-slate-800 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500/20 text-slate-200" placeholder="e.g. Executive management..." />
          </div>

          <p v-if="flagsError" class="text-xs text-rose-400 bg-rose-500/10 border border-rose-500/20 rounded-lg px-3 py-2">
            {{ flagsError }}
          </p>
          <p v-else-if="flagsSaved" class="text-xs text-emerald-400">
            Rules saved — recompute the period to apply them.
          </p>
        </section>

        <!-- Assignments Section -->
        <section class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
              <Icon name="Banknote" :size="15" /> Allowances & Deductions
            </h3>
            <button @click="assignFormOpen = !assignFormOpen" class="text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1.5 rounded-lg hover:bg-emerald-500/20 transition-colors cursor-pointer">
              + Assign Component
            </button>
          </div>

          <!-- Assign Form -->
          <div v-if="assignFormOpen" class="p-5 rounded-xl border border-slate-800 bg-slate-950/50 space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Component</label>
                <select v-model="assignForm.payroll_component_id" class="w-full bg-slate-900 border-slate-800 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500/20 text-slate-200">
                  <option value="" disabled>Select Component...</option>
                  <optgroup label="Allowances">
                    <option v-for="c in components.filter(x => x.kind === 'allowance')" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </optgroup>
                  <optgroup label="Deductions">
                    <option v-for="c in components.filter(x => x.kind === 'deduction')" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </optgroup>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Amount (ETB)</label>
                <input type="number" step="0.01" v-model="assignForm.amount" class="w-full bg-slate-900 border-slate-800 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500/20 text-slate-200" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Schedule Type</label>
                <select v-model="assignForm.schedule_type" class="w-full bg-slate-900 border-slate-800 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500/20 text-slate-200">
                  <option v-for="st in scheduleOptions" :key="st.value" :value="st.value">{{ st.label }}</option>
                </select>
              </div>
              <div v-if="needsStartPeriod">
                <label class="block text-xs font-semibold text-slate-400 mb-1">
                  Start Period <span class="text-rose-400">*</span>
                </label>
                <select v-model="assignForm.start_period_id" class="w-full bg-slate-900 border-slate-800 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500/20 text-slate-200">
                  <option value="">— select a period —</option>
                  <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
                <p v-if="assignErrors.start_period_id" class="text-xs text-rose-400 mt-1">{{ assignErrors.start_period_id[0] }}</p>
              </div>
              <div v-if="needsEndPeriod">
                <label class="block text-xs font-semibold text-slate-400 mb-1">End Period</label>
                <select v-model="assignForm.end_period_id" class="w-full bg-slate-900 border-slate-800 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500/20 text-slate-200">
                  <option value="">(Open-ended)</option>
                  <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
              </div>
              <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-400 mb-1">Note (Optional)</label>
                <input type="text" v-model="assignForm.note" class="w-full bg-slate-900 border-slate-800 rounded-xl text-sm focus:border-blue-500 focus:ring-blue-500/20 text-slate-200" />
              </div>
            </div>
            <p v-if="assignError" class="text-xs text-rose-400 bg-rose-500/10 border border-rose-500/20 rounded-lg px-3 py-2">
              {{ assignError }}
            </p>

            <div class="flex justify-end gap-3 pt-2">
              <button @click="assignFormOpen = false" class="text-xs font-semibold text-slate-400 hover:text-slate-200 cursor-pointer">Cancel</button>
              <button @click="submitAssignment" :disabled="assignSaving || !assignForm.payroll_component_id"
                      class="text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl cursor-pointer disabled:opacity-50">
                {{ assignSaving ? 'Saving…' : 'Save Assignment' }}
              </button>
            </div>
          </div>

          <!-- Errors raised outside the assign form (e.g. a failed removal) -->
          <p v-if="assignError && !assignFormOpen" class="text-xs text-rose-400 bg-rose-500/10 border border-rose-500/20 rounded-lg px-3 py-2">
            {{ assignError }}
          </p>

          <!-- List -->
          <div class="rounded-xl border border-slate-800 overflow-hidden bg-slate-950/30">
            <table class="w-full text-left">
              <thead>
                <tr class="border-b border-slate-800 bg-slate-900/50">
                  <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Component</th>
                  <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right">Amount</th>
                  <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Schedule</th>
                  <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Note</th>
                  <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider w-10"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800 text-sm">
                <tr v-if="!config.assignments.length">
                  <td colspan="5" class="px-4 py-8 text-center text-slate-500 text-xs italic">No components assigned.</td>
                </tr>
                <tr v-for="a in config.assignments" :key="a.id" class="hover:bg-slate-900/30">
                  <td class="px-4 py-3 font-medium text-slate-200">
                    {{ a.component_name }}
                    <span class="ml-2 text-[9px] uppercase px-1.5 py-0.5 rounded border font-bold tracking-wider"
                          :class="a.component_type === 'allowance' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'bg-rose-500/10 border-rose-500/20 text-rose-400'">
                      {{ a.component_type }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-right font-mono text-slate-300">{{ money(a.amount) }}</td>
                  <td class="px-4 py-3 text-slate-400 text-xs capitalize">{{ a.schedule_type.replace('_', ' ') }}</td>
                  <td class="px-4 py-3 text-slate-500 text-xs">{{ a.note || '—' }}</td>
                  <td class="px-4 py-3 text-right">
                    <button @click="deleteAssignment(a.id)" class="text-slate-500 hover:text-rose-400 transition-colors cursor-pointer">
                      <Icon name="Trash2" :size="14" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

      </div>
      <div v-else class="flex-1 flex items-center justify-center py-20">
        <Icon name="Loader2" class="animate-spin text-blue-500" :size="32" />
      </div>
    </div>
  </div>
</template>
