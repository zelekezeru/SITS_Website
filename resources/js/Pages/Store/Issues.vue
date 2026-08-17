<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
export default { layout: AdminLayout };
</script>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  module: { type: Object, default: () => ({}) },
  movements: { type: Array, default: () => [] },
  items: { type: Array, default: () => [] },
  availableUnits: { type: Array, default: () => [] },
  inUseUnits: { type: Array, default: () => [] },
  locations: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  conditions: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
});

const today = () => new Date().toISOString().slice(0, 10);

const search = ref('');
const activeFilter = ref('all'); // all, issue, return

const filteredMovements = computed(() => {
  let list = props.movements;
  if (activeFilter.value !== 'all') {
    list = list.filter((m) => m.type === activeFilter.value);
  }
  if (!search.value.trim()) return list;
  const q = search.value.toLowerCase();
  return list.filter((m) =>
    (m.reference && m.reference.toLowerCase().includes(q)) ||
    (m.item_name && m.item_name.toLowerCase().includes(q)) ||
    (m.employee_name && m.employee_name.toLowerCase().includes(q)) ||
    (m.asset_tag && m.asset_tag.toLowerCase().includes(q)) ||
    (m.reason && m.reason.toLowerCase().includes(q))
  );
});

// ---- Direct Issue Modal ----
const issueOpen = ref(false);
const issueForm = useForm({
  item_id: '',
  quantity: 1,
  from_location_id: '',
  employee_id: '',
  unit_id: '',
  reference: '',
  occurred_at: today(),
  reason: '',
  notes: '',
});

const selectedIssueItem = computed(() => props.items.find((i) => i.id === Number(issueForm.item_id)) || null);
const isIssueAsset = computed(() => selectedIssueItem.value?.tracking_mode === 'asset');
const unitsForIssue = computed(() => props.availableUnits.filter((u) => u.item_id === Number(issueForm.item_id)));

const openIssueModal = () => {
  issueForm.reset();
  issueForm.clearErrors();
  issueForm.occurred_at = today();
  issueForm.quantity = 1;
  issueOpen.value = true;
};

const submitIssue = () => {
  issueForm.post('/store/issues', {
    preserveScroll: true,
    onSuccess: () => { issueOpen.value = false; },
  });
};

// ---- Return to Store Modal ----
const returnOpen = ref(false);
const returnForm = useForm({
  item_id: '',
  quantity: 1,
  to_location_id: '',
  employee_id: '',
  unit_id: '',
  condition: 'good',
  reference: '',
  occurred_at: today(),
  reason: 'Returned to store',
  notes: '',
});

const selectedReturnItem = computed(() => props.items.find((i) => i.id === Number(returnForm.item_id)) || null);
const isReturnAsset = computed(() => selectedReturnItem.value?.tracking_mode === 'asset');
const unitsForReturn = computed(() => props.inUseUnits.filter((u) => u.item_id === Number(returnForm.item_id)));

const openReturnModal = () => {
  returnForm.reset();
  returnForm.clearErrors();
  returnForm.occurred_at = today();
  returnForm.condition = 'good';
  returnForm.quantity = 1;
  returnOpen.value = true;
};

const submitReturn = () => {
  returnForm.post('/store/returns', {
    preserveScroll: true,
    onSuccess: () => { returnOpen.value = false; },
  });
};
</script>

<template>
  <Head title="Issue & Returns — SITS Store" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-amber-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
            <Icon name="PackageMinus" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Store · Stock</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Issue Vouchers & Returns</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Track materials released to staff and departments, alongside items handed back into store custody.
              Every issue generates an audit voucher with signed quantity movements.
            </p>
          </div>
        </div>
        <div v-if="can.issue" class="flex items-center gap-3 shrink-0">
          <button @click="openReturnModal" class="text-xs font-semibold px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-900/80 hover:bg-slate-800 text-slate-200 transition-all cursor-pointer flex items-center gap-1.5">
            <Icon name="RotateCcw" :size="14" /> Return Stock
          </button>
          <button @click="openIssueModal" class="text-xs font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer flex items-center gap-1.5">
            <Icon name="Plus" :size="15" /> Issue Stock
          </button>
        </div>
      </div>
    </section>

    <!-- Filter & Search -->
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-2 p-1.5 rounded-2xl border border-slate-900 bg-slate-950/40">
        <button
          @click="activeFilter = 'all'"
          :class="['px-4 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer', activeFilter === 'all' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200']"
        >
          All Activity ({{ movements.length }})
        </button>
        <button
          @click="activeFilter = 'issue'"
          :class="['px-4 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer', activeFilter === 'issue' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'text-slate-400 hover:text-slate-200']"
        >
          Issues ({{ movements.filter((m) => m.type === 'issue').length }})
        </button>
        <button
          @click="activeFilter = 'return'"
          :class="['px-4 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer', activeFilter === 'return' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'text-slate-400 hover:text-slate-200']"
        >
          Returns ({{ movements.filter((m) => m.type === 'return').length }})
        </button>
      </div>

      <div class="relative w-72">
        <Icon name="Search" :size="15" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500" />
        <input
          v-model="search"
          type="text"
          placeholder="Search voucher, item, employee..."
          class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-900 bg-slate-950/60 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-amber-500/50"
        />
      </div>
    </div>

    <!-- Movements Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2 overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm min-w-[1020px]">
        <thead>
          <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
            <th class="p-3">Reference / Voucher</th>
            <th class="p-3">Type</th>
            <th class="p-3">Item & Details</th>
            <th class="p-3 text-right">Quantity</th>
            <th class="p-3">Location</th>
            <th class="p-3">Counterparty</th>
            <th class="p-3">Date</th>
            <th class="p-3">Performed By</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-900">
          <tr v-for="m in filteredMovements" :key="m.id" class="hover:bg-slate-900/30 transition-colors">
            <td class="p-3 font-mono text-xs text-amber-400/90 font-semibold">{{ m.reference || '—' }}</td>
            <td class="p-3">
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                m.type === 'issue' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' :
                m.type === 'return' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' :
                'bg-slate-800 text-slate-400'
              ]">
                {{ m.type_label }}
              </span>
            </td>
            <td class="p-3">
              <p class="font-semibold text-slate-200">{{ m.item_name }}</p>
              <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5">
                <span class="font-mono">{{ m.item_code }}</span>
                <span v-if="m.asset_tag" class="text-amber-400 font-mono">· Tag: {{ m.asset_tag }}</span>
                <span v-if="m.reason" class="text-slate-400 truncate max-w-xs">· {{ m.reason }}</span>
              </div>
            </td>
            <td class="p-3 text-right font-mono font-semibold" :class="m.type === 'issue' ? 'text-amber-400' : 'text-emerald-400'">
              {{ m.quantity > 0 ? '+' : '' }}{{ m.quantity }} {{ m.unit_of_measure }}
            </td>
            <td class="p-3 text-xs text-slate-300">
              <span v-if="m.type === 'issue'">From: {{ m.from_location || 'Store' }}</span>
              <span v-else>To: {{ m.to_location || 'Store' }}</span>
            </td>
            <td class="p-3 text-xs text-slate-200 font-medium">
              {{ m.employee_name || 'Department Release' }}
            </td>
            <td class="p-3 text-xs font-mono text-slate-400">{{ m.occurred_at }}</td>
            <td class="p-3 text-xs text-slate-400">{{ m.performed_by }}</td>
          </tr>
          <tr v-if="filteredMovements.length === 0">
            <td colspan="8" class="p-8 text-center text-slate-500 text-sm">
              No issues or returns found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Direct Issue Modal -->
    <div v-if="issueOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
      <div class="relative w-full max-w-2xl rounded-3xl border border-slate-800 bg-slate-950 p-6 md:p-8 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-900 pb-4">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <Icon name="PackageMinus" :size="20" class="text-amber-400" /> Direct Stock Issue
            </h3>
            <p class="text-xs text-slate-400 mt-1">Disburse items or equipment directly with an issue voucher.</p>
          </div>
          <button @click="issueOpen = false" class="text-slate-400 hover:text-white p-1 cursor-pointer">
            <Icon name="X" :size="20" />
          </button>
        </div>

        <form @submit.prevent="submitIssue" class="space-y-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-300">Catalog Item *</label>
            <select v-model="issueForm.item_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-amber-500">
              <option value="">Select Item</option>
              <option v-for="it in items" :key="it.id" :value="it.id">
                {{ it.code }} — {{ it.name_en }} (Stock: {{ it.on_hand }} {{ it.unit_of_measure }})
              </option>
            </select>
            <p v-if="issueForm.errors.item_id" class="text-rose-400 text-xs">{{ issueForm.errors.item_id }}</p>
          </div>

          <div v-if="isIssueAsset" class="space-y-1.5">
            <label class="text-xs font-semibold text-amber-400">Select Specific Asset Unit *</label>
            <select v-model="issueForm.unit_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-amber-500/30 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-amber-500 font-mono">
              <option value="">Select In-Store Asset</option>
              <option v-for="u in unitsForIssue" :key="u.id" :value="u.id">
                {{ u.asset_tag }} — Loc: {{ u.location_name || 'Store' }} (Cond: {{ u.condition }})
              </option>
            </select>
            <p v-if="issueForm.errors.unit_id" class="text-rose-400 text-xs">{{ issueForm.errors.unit_id }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Quantity to Issue *</label>
              <input
                v-model="issueForm.quantity"
                type="number"
                step="any"
                min="0.001"
                required
                :disabled="isIssueAsset"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-amber-500"
              />
              <p v-if="issueForm.errors.quantity" class="text-rose-400 text-xs">{{ issueForm.errors.quantity }}</p>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">From Location</label>
              <select v-model="issueForm.from_location_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-amber-500">
                <option value="">Default Store Location</option>
                <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }} ({{ loc.code }})</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Recipient Employee</label>
              <select v-model="issueForm.employee_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-amber-500">
                <option value="">Select Employee (or Departmental)</option>
                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                  {{ emp.first_name }} {{ emp.father_name }} ({{ emp.employee_id }})
                </option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Issue Date</label>
              <input v-model="issueForm.occurred_at" type="date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-amber-500" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Voucher Reference</label>
              <input v-model="issueForm.reference" type="text" placeholder="Auto-generated if blank (ISV-YYYY-XXXX)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs font-mono focus:outline-none focus:border-amber-500" />
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Reason / Purpose</label>
              <input v-model="issueForm.reason" type="text" placeholder="e.g. Teaching material for Seminary semester" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-amber-500" />
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="issueOpen = false" class="px-5 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:bg-slate-900 transition-all cursor-pointer">
              Cancel
            </button>
            <button type="submit" :disabled="issueForm.processing" class="px-6 py-2 rounded-xl text-xs font-semibold bg-amber-600 hover:bg-amber-500 text-white transition-all shadow-md cursor-pointer disabled:opacity-50">
              Confirm & Issue
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Return Stock Modal -->
    <div v-if="returnOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
      <div class="relative w-full max-w-2xl rounded-3xl border border-slate-800 bg-slate-950 p-6 md:p-8 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-900 pb-4">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <Icon name="RotateCcw" :size="20" class="text-emerald-400" /> Return Stock to Store
            </h3>
            <p class="text-xs text-slate-400 mt-1">Receive unused consumables or assigned equipment back into store custody.</p>
          </div>
          <button @click="returnOpen = false" class="text-slate-400 hover:text-white p-1 cursor-pointer">
            <Icon name="X" :size="20" />
          </button>
        </div>

        <form @submit.prevent="submitReturn" class="space-y-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-300">Catalog Item *</label>
            <select v-model="returnForm.item_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-emerald-500">
              <option value="">Select Item</option>
              <option v-for="it in items" :key="it.id" :value="it.id">
                {{ it.code }} — {{ it.name_en }} ({{ it.tracking_mode }})
              </option>
            </select>
          </div>

          <div v-if="isReturnAsset" class="space-y-1.5">
            <label class="text-xs font-semibold text-emerald-400">Select Asset Unit Out on Loan/In-Use *</label>
            <select v-model="returnForm.unit_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-emerald-500/30 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-emerald-500 font-mono">
              <option value="">Select In-Use Asset</option>
              <option v-for="u in unitsForReturn" :key="u.id" :value="u.id">
                {{ u.asset_tag }} — Held by: {{ u.employee_name || 'Staff' }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Quantity to Return *</label>
              <input
                v-model="returnForm.quantity"
                type="number"
                step="any"
                min="0.001"
                required
                :disabled="isReturnAsset"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-emerald-500"
              />
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Store Destination Location</label>
              <select v-model="returnForm.to_location_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-emerald-500">
                <option value="">Default Store</option>
                <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }} ({{ loc.code }})</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Arrival Condition</label>
              <select v-model="returnForm.condition" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-emerald-500">
                <option v-for="c in conditions" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Returned by Employee</label>
              <select v-model="returnForm.employee_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-emerald-500">
                <option value="">Select Employee</option>
                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                  {{ emp.first_name }} {{ emp.father_name }} ({{ emp.employee_id }})
                </option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Return Date</label>
              <input v-model="returnForm.occurred_at" type="date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-emerald-500" />
            </div>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-300">Return Notes / Reason</label>
            <input v-model="returnForm.reason" type="text" placeholder="e.g. End of assignment / semester return" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-emerald-500" />
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="returnOpen = false" class="px-5 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:bg-slate-900 transition-all cursor-pointer">
              Cancel
            </button>
            <button type="submit" :disabled="returnForm.processing" class="px-6 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition-all shadow-md cursor-pointer disabled:opacity-50">
              Confirm Return
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
