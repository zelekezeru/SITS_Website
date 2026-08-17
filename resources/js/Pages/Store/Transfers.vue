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
  transfers: { type: Array, default: () => [] },
  items: { type: Array, default: () => [] },
  availableUnits: { type: Array, default: () => [] },
  locations: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
});

const today = () => new Date().toISOString().slice(0, 10);

const search = ref('');

const filteredTransfers = computed(() => {
  if (!search.value.trim()) return props.transfers;
  const q = search.value.toLowerCase();
  return props.transfers.filter((t) =>
    (t.reference && t.reference.toLowerCase().includes(q)) ||
    (t.item_name && t.item_name.toLowerCase().includes(q)) ||
    (t.asset_tag && t.asset_tag.toLowerCase().includes(q)) ||
    (t.from_location && t.from_location.toLowerCase().includes(q)) ||
    (t.to_location && t.to_location.toLowerCase().includes(q)) ||
    (t.reason && t.reason.toLowerCase().includes(q))
  );
});

// Group paired TransferOut and TransferIn by reference or display flat
const transferGroups = computed(() => {
  return filteredTransfers.value;
});

// ---- Transfer Modal ----
const open = ref(false);
const form = useForm({
  item_id: '',
  from_location_id: '',
  to_location_id: '',
  quantity: 1,
  unit_id: '',
  reference: '',
  occurred_at: today(),
  reason: '',
  notes: '',
});

const selectedItem = computed(() => props.items.find((i) => i.id === Number(form.item_id)) || null);
const isAsset = computed(() => selectedItem.value?.tracking_mode === 'asset');
const unitsForTransfer = computed(() => {
  return props.availableUnits.filter((u) => {
    const matchesItem = u.item_id === Number(form.item_id);
    if (!form.from_location_id) return matchesItem;
    return matchesItem && u.location_id === Number(form.from_location_id);
  });
});

const openTransferModal = () => {
  form.reset();
  form.clearErrors();
  form.occurred_at = today();
  form.quantity = 1;
  open.value = true;
};

const submit = () => {
  form.post('/store/transfers', {
    preserveScroll: true,
    onSuccess: () => { open.value = false; },
  });
};
</script>

<template>
  <Head title="Transfers — SITS Store" />

  <div class="space-y-8">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-cyan-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 shrink-0">
            <Icon name="ArrowLeftRight" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Store · Stock</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Inter-Location Transfers</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Relocate materials and equipment between campuses, buildings, store rooms, shelves and bins.
              Transfers post atomic dispatch and receipt movements.
            </p>
          </div>
        </div>
        <button v-if="can.transfer" @click="openTransferModal" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer flex items-center gap-2">
          <Icon name="Plus" :size="16" /> New Transfer
        </button>
      </div>
    </section>

    <!-- Search Bar -->
    <div class="flex items-center justify-between gap-4">
      <p class="text-xs text-slate-400 font-semibold">Transfer History ({{ transfers.length }})</p>
      <div class="relative w-72">
        <Icon name="Search" :size="15" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500" />
        <input
          v-model="search"
          type="text"
          placeholder="Search transfer, item, location..."
          class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-900 bg-slate-950/60 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-cyan-500/50"
        />
      </div>
    </div>

    <!-- Transfers Table -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2 overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm min-w-[1020px]">
        <thead>
          <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
            <th class="p-3">Reference</th>
            <th class="p-3">Type</th>
            <th class="p-3">Item</th>
            <th class="p-3 text-right">Quantity</th>
            <th class="p-3">From Location</th>
            <th class="p-3">To Location</th>
            <th class="p-3">Date</th>
            <th class="p-3">Transferred By</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-900">
          <tr v-for="t in transferGroups" :key="t.id" class="hover:bg-slate-900/30 transition-colors">
            <td class="p-3 font-mono text-xs text-cyan-400/90 font-semibold">{{ t.reference || '—' }}</td>
            <td class="p-3">
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                t.type === 'transfer_out' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' :
                'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
              ]">
                {{ t.type_label }}
              </span>
            </td>
            <td class="p-3">
              <p class="font-semibold text-slate-200">{{ t.item_name }}</p>
              <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5">
                <span class="font-mono">{{ t.item_code }}</span>
                <span v-if="t.asset_tag" class="text-cyan-400 font-mono">· Tag: {{ t.asset_tag }}</span>
                <span v-if="t.reason" class="text-slate-400 truncate max-w-xs">· {{ t.reason }}</span>
              </div>
            </td>
            <td class="p-3 text-right font-mono font-semibold" :class="t.type === 'transfer_out' ? 'text-amber-400' : 'text-emerald-400'">
              {{ t.quantity > 0 ? '+' : '' }}{{ t.quantity }} {{ t.unit_of_measure }}
            </td>
            <td class="p-3 text-xs text-slate-300">{{ t.from_location || '—' }}</td>
            <td class="p-3 text-xs text-slate-300">{{ t.to_location || '—' }}</td>
            <td class="p-3 text-xs font-mono text-slate-400">{{ t.occurred_at }}</td>
            <td class="p-3 text-xs text-slate-400">{{ t.performed_by }}</td>
          </tr>
          <tr v-if="transferGroups.length === 0">
            <td colspan="8" class="p-8 text-center text-slate-500 text-sm">
              No inter-location transfers recorded.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Transfer Modal -->
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
      <div class="relative w-full max-w-2xl rounded-3xl border border-slate-800 bg-slate-950 p-6 md:p-8 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-900 pb-4">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <Icon name="ArrowLeftRight" :size="20" class="text-cyan-400" /> Transfer Stock Between Locations
            </h3>
            <p class="text-xs text-slate-400 mt-1">Move items or asset units between rooms, shelves or campuses.</p>
          </div>
          <button @click="open = false" class="text-slate-400 hover:text-white p-1 cursor-pointer">
            <Icon name="X" :size="20" />
          </button>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-300">Catalog Item *</label>
            <select v-model="form.item_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-cyan-500">
              <option value="">Select Item</option>
              <option v-for="it in items" :key="it.id" :value="it.id">
                {{ it.code }} — {{ it.name_en }} (Stock: {{ it.on_hand }} {{ it.unit_of_measure }})
              </option>
            </select>
            <p v-if="form.errors.item_id" class="text-rose-400 text-xs">{{ form.errors.item_id }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Source Location (From) *</label>
              <select v-model="form.from_location_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-cyan-500">
                <option value="">Select Source Location</option>
                <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }} ({{ loc.code }})</option>
              </select>
              <p v-if="form.errors.from_location_id" class="text-rose-400 text-xs">{{ form.errors.from_location_id }}</p>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Destination Location (To) *</label>
              <select v-model="form.to_location_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-cyan-500">
                <option value="">Select Destination Location</option>
                <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }} ({{ loc.code }})</option>
              </select>
              <p v-if="form.errors.to_location_id" class="text-rose-400 text-xs">{{ form.errors.to_location_id }}</p>
            </div>
          </div>

          <div v-if="isAsset" class="space-y-1.5">
            <label class="text-xs font-semibold text-cyan-400">Select Specific Asset Unit *</label>
            <select v-model="form.unit_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-cyan-500/30 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-cyan-500 font-mono">
              <option value="">Select In-Store Asset</option>
              <option v-for="u in unitsForTransfer" :key="u.id" :value="u.id">
                {{ u.asset_tag }} — Loc: {{ u.location_name || 'Store' }} (Cond: {{ u.condition }})
              </option>
            </select>
            <p v-if="form.errors.unit_id" class="text-rose-400 text-xs">{{ form.errors.unit_id }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Quantity to Transfer *</label>
              <input
                v-model="form.quantity"
                type="number"
                step="any"
                min="0.001"
                required
                :disabled="isAsset"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-cyan-500"
              />
              <p v-if="form.errors.quantity" class="text-rose-400 text-xs">{{ form.errors.quantity }}</p>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Transfer Date</label>
              <input v-model="form.occurred_at" type="date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-cyan-500" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Transfer Reference</label>
              <input v-model="form.reference" type="text" placeholder="Auto-generated if blank (TRF-YYYY-XXXX)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs font-mono focus:outline-none focus:border-cyan-500" />
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-300">Reason / Description</label>
              <input v-model="form.reason" type="text" placeholder="e.g. Relocating to Seminary computer lab" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-slate-200 text-xs focus:outline-none focus:border-cyan-500" />
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-900">
            <button type="button" @click="open = false" class="px-5 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:bg-slate-900 transition-all cursor-pointer">
              Cancel
            </button>
            <button type="submit" :disabled="form.processing" class="px-6 py-2 rounded-xl text-xs font-semibold bg-cyan-600 hover:bg-cyan-500 text-white transition-all shadow-md cursor-pointer disabled:opacity-50">
              Execute Transfer
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
