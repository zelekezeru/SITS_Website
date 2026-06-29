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
  components: { type: Array, default: () => [] },
  meta: { type: Object, default: () => ({}) },
});

const TABS = [
  { key: 'allowance', label: 'Allowances' },
  { key: 'deduction', label: 'Deductions' },
  { key: 'statutory', label: 'Statutory (Pension / PF)' },
];
const activeTab = ref('allowance');

const rows = computed(() => props.components.filter(c => c.kind === activeTab.value));
const columnsForKind = computed(() => (props.meta.sheetColumns?.[activeTab.value] ?? []));

const modalOpen = ref(false);
const editing = ref(null);
const form = useForm({
  name: '', kind: 'allowance', calc_type: 'fixed', rate: null, side: null,
  applies_to: 'all', taxable: true, exempt_capped: false, sheet_column: '', is_active: true, sort_order: 0,
});

const openCreate = () => {
  editing.value = null;
  form.reset();
  form.kind = activeTab.value;
  form.calc_type = activeTab.value === 'statutory' ? 'percent' : 'fixed';
  form.clearErrors();
  modalOpen.value = true;
};

const openEdit = (c) => {
  editing.value = c;
  form.name = c.name; form.kind = c.kind; form.calc_type = c.calc_type;
  form.rate = c.rate; form.side = c.side; form.applies_to = c.applies_to;
  form.taxable = c.taxable; form.exempt_capped = c.exempt_capped;
  form.sheet_column = c.sheet_column ?? ''; form.is_active = c.is_active; form.sort_order = c.sort_order;
  form.clearErrors();
  modalOpen.value = true;
};

const submit = () => {
  const opts = { preserveScroll: true, onSuccess: () => { modalOpen.value = false; } };
  if (editing.value) form.put(`/admin/payroll/components/${editing.value.id}`, opts);
  else form.post('/admin/payroll/components', opts);
};

const remove = async (c) => {
  const ok = await confirm({ title: 'Delete Component', message: `Remove "${c.name}"? Existing assignments will be detached.` });
  if (ok) router.delete(`/admin/payroll/components/${c.id}`, { preserveScroll: true });
};

const labelize = (s) => (s || '—').replace(/_/g, ' ').replace(/\b\w/g, m => m.toUpperCase());
</script>

<template>
  <Head title="Payroll Setup — SITS ERP" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-blue-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
            <Icon name="SlidersHorizontal" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Finance · Configuration</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Payroll Setup</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Define allowance and deduction titles and the statutory pension / provident-fund rates. Core
              components can be renamed and re-rated but not deleted; new titles roll into the Cash &amp; Other /
              Other Deduction columns and are itemised on payslips.
            </p>
          </div>
        </div>
        <button @click="openCreate" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
          + New Component
        </button>
      </div>
    </section>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-slate-900">
      <button v-for="t in TABS" :key="t.key" @click="activeTab = t.key"
              class="px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors -mb-px"
              :class="activeTab === t.key ? 'border-blue-500 text-white' : 'border-transparent text-slate-500 hover:text-slate-300'">
        {{ t.label }}
      </button>
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2">
      <table class="w-full text-left border-collapse text-sm">
        <thead>
          <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
            <th class="p-3">Name</th>
            <th class="p-3">Calc</th>
            <th class="p-3 text-right">Rate %</th>
            <th class="p-3">Sheet Column</th>
            <th class="p-3" v-if="activeTab === 'allowance'">Taxable</th>
            <th class="p-3" v-if="activeTab === 'statutory'">Side · Applies</th>
            <th class="p-3 text-center">Active</th>
            <th class="p-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-900">
          <tr v-for="c in rows" :key="c.id" class="hover:bg-slate-900/30">
            <td class="p-3 font-semibold text-slate-200">
              {{ c.name }}
              <span v-if="c.is_system" class="ml-1 text-[9px] font-bold uppercase px-1 py-0.5 rounded bg-slate-800 text-slate-500 border border-slate-700">core</span>
            </td>
            <td class="p-3 text-slate-400 capitalize">{{ c.calc_type }}</td>
            <td class="p-3 text-right font-mono text-slate-300">{{ c.calc_type === 'percent' ? Number(c.rate).toFixed(2) : '—' }}</td>
            <td class="p-3 text-slate-400">{{ labelize(c.sheet_column) }}</td>
            <td class="p-3" v-if="activeTab === 'allowance'">
              <span :class="c.taxable ? 'text-amber-400' : 'text-emerald-400'" class="text-xs font-bold">{{ c.taxable ? (c.exempt_capped ? 'Capped' : 'Taxable') : 'Non-tax' }}</span>
            </td>
            <td class="p-3 text-slate-400 text-xs" v-if="activeTab === 'statutory'">{{ labelize(c.side) }} · {{ labelize(c.applies_to) }}</td>
            <td class="p-3 text-center">
              <span class="px-2 py-0.5 text-[10px] rounded-full font-bold border" :class="c.is_active ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'bg-slate-800/60 border-slate-800 text-slate-500'">
                {{ c.is_active ? 'On' : 'Off' }}
              </span>
            </td>
            <td class="p-3 text-right">
              <button @click="openEdit(c)" class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-slate-700 bg-slate-900/50 text-slate-300 rounded-lg mr-1">Edit</button>
              <button v-if="!c.is_system" @click="remove(c)" class="text-[11px] font-bold px-3 py-1.5 border border-slate-800 hover:border-rose-700 text-rose-400 bg-slate-900/50 rounded-lg">Delete</button>
            </td>
          </tr>
          <tr v-if="!rows.length"><td colspan="8" class="p-8 text-center text-slate-500 italic">No {{ activeTab }} components yet.</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-white mb-6">{{ editing ? 'Edit' : 'New' }} {{ form.kind }} component</h3>
        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Name</label>
            <input v-model="form.name" type="text" required class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
            <p v-if="form.errors.name" class="text-xs text-rose-400 mt-1">{{ form.errors.name }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Calculation</label>
              <select v-model="form.calc_type" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
                <option v-for="o in meta.calcTypes" :key="o.value" :value="o.value">{{ o.label }}</option>
              </select>
            </div>
            <div v-if="form.calc_type === 'percent'">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Rate (%)</label>
              <input v-model="form.rate" type="number" step="0.0001" min="0" max="100" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Sheet column</label>
            <select v-model="form.sheet_column" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
              <option value="">— roll into {{ form.kind === 'allowance' ? 'Cash & Other' : 'Other Deduction' }} —</option>
              <option v-for="col in columnsForKind" :key="col" :value="col">{{ labelize(col) }}</option>
            </select>
          </div>

          <div v-if="form.kind === 'statutory'" class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Side</label>
              <select v-model="form.side" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
                <option v-for="o in meta.sides" :key="o.value" :value="o.value">{{ o.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Applies to</label>
              <select v-model="form.applies_to" class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none">
                <option v-for="o in meta.appliesTo" :key="o.value" :value="o.value">{{ o.label }}</option>
              </select>
            </div>
          </div>

          <div v-if="form.kind === 'allowance'" class="flex items-center gap-6">
            <label class="flex items-center gap-2 text-sm text-slate-300"><input type="checkbox" v-model="form.taxable" class="rounded border-slate-700 bg-slate-950 text-blue-600 focus:ring-0" /> Taxable</label>
            <label class="flex items-center gap-2 text-sm text-slate-300"><input type="checkbox" v-model="form.exempt_capped" class="rounded border-slate-700 bg-slate-950 text-blue-600 focus:ring-0" /> Tax-exempt cap (transport)</label>
          </div>

          <label class="flex items-center gap-2 text-sm text-slate-300"><input type="checkbox" v-model="form.is_active" class="rounded border-slate-700 bg-slate-950 text-blue-600 focus:ring-0" /> Active</label>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="modalOpen = false" class="text-xs font-semibold px-4 py-2.5 border border-slate-850 hover:border-slate-700 bg-slate-900/50 rounded-xl">Cancel</button>
            <button type="submit" :disabled="form.processing" class="text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl shadow-md cursor-pointer disabled:opacity-50">{{ editing ? 'Save' : 'Create' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
