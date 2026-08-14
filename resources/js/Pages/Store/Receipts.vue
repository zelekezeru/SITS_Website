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
  batches: { type: Array, default: () => [] },
  items: { type: Array, default: () => [] },
  suppliers: { type: Array, default: () => [] },
  locations: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  conditions: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
});

const money = (n) => n === null || n === undefined ? '—' : Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const today = () => new Date().toISOString().slice(0, 10);

const open = ref(false);

const form = useForm({
  item_id: '', supplier_id: '', location_id: '',
  quantity_received: '', unit_cost: '', total_cost: '', currency: 'ETB',
  purchase_date: today(), production_date: '', expiry_date: '', warranty_until: '',
  invoice_number: '', purchase_order_number: '', delivery_note_number: '',
  condition_on_arrival: 'new', received_by_employee_id: '', notes: '',
});

const selectedItem = computed(() => props.items.find((i) => i.id === Number(form.item_id)) || null);
const isAsset = computed(() => selectedItem.value?.tracking_mode === 'asset');

const openCreate = () => {
  form.reset();
  form.clearErrors();
  form.purchase_date = today();
  form.condition_on_arrival = 'new';
  form.currency = 'ETB';
  open.value = true;
};

const submit = () => {
  form.post('/store/receipts', {
    preserveScroll: true,
    onSuccess: () => { open.value = false; },
  });
};
</script>

<template>
  <Head title="Receive Stock (GRN) — SITS Store" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-amber-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
            <Icon name="PackagePlus" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Store · Stock</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Receive Stock (GRN)</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Every goods-received note: supplier, quantity, unit cost, and who physically took delivery.
              Serialized items get one asset tag per unit received automatically.
            </p>
          </div>
        </div>
        <button v-if="can.receive" @click="openCreate" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
          + Record Receipt
        </button>
      </div>
    </section>

    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2 overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm min-w-[1080px]">
        <thead>
          <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
            <th class="p-3">GRN</th>
            <th class="p-3">Item</th>
            <th class="p-3">Supplier</th>
            <th class="p-3">Location</th>
            <th class="p-3 text-right">Qty</th>
            <th class="p-3 text-right">Unit Cost</th>
            <th class="p-3 text-right">Total</th>
            <th class="p-3">Purchased</th>
            <th class="p-3">Registered by</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-900">
          <tr v-for="b in batches" :key="b.id" class="hover:bg-slate-900/30 transition-colors">
            <td class="p-3 font-mono text-xs text-amber-400/90">{{ b.grn_number }}</td>
            <td class="p-3">
              <p class="font-semibold text-slate-200">{{ b.item }}</p>
              <p class="font-mono text-[11px] text-slate-500">{{ b.item_code }}</p>
            </td>
            <td class="p-3 text-xs text-slate-400">{{ b.supplier || '—' }}</td>
            <td class="p-3 text-xs text-slate-400">{{ b.location || '—' }}</td>
            <td class="p-3 text-right font-mono text-slate-300">{{ b.quantity_received }}</td>
            <td class="p-3 text-right font-mono text-slate-400">{{ money(b.unit_cost) }}</td>
            <td class="p-3 text-right font-mono text-slate-200">{{ money(b.total_cost) }}</td>
            <td class="p-3 text-xs text-slate-400">{{ b.purchase_date }}</td>
            <td class="p-3 text-xs text-slate-500">{{ b.registered_by || '—' }}</td>
          </tr>
          <tr v-if="!batches.length">
            <td colspan="9" class="p-8 text-center text-slate-500 italic">No receipts recorded yet.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm" @click.self="open = false">
      <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-6">Record a Receipt</h3>
        <form @submit.prevent="submit" class="space-y-5">
          <div class="grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Item</label>
              <select v-model="form.item_id" required class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option value="" disabled>Select an item…</option>
                <option v-for="i in items" :key="i.id" :value="i.id">{{ i.name_en }} ({{ i.code }})</option>
              </select>
              <p v-if="form.errors.item_id" class="text-xs text-rose-400 mt-1">{{ form.errors.item_id }}</p>
              <p v-if="isAsset" class="text-xs text-violet-400 mt-1.5">
                This is a fixed asset — quantity must be a whole number and one asset tag will be
                generated per unit received.
              </p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Quantity Received</label>
              <input v-model="form.quantity_received" type="number" :step="isAsset ? 1 : 0.001" min="0" required class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              <p v-if="form.errors.quantity_received" class="text-xs text-rose-400 mt-1">{{ form.errors.quantity_received }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Unit Cost</label>
              <input v-model="form.unit_cost" type="number" step="0.01" min="0" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Total Cost (optional override)</label>
              <input v-model="form.total_cost" type="number" step="0.01" min="0" placeholder="Defaults to unit cost × quantity" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              <p class="text-[11px] text-slate-600 mt-1">Set this only if the invoice carries rounding, freight or fees.</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Currency</label>
              <input v-model="form.currency" type="text" maxlength="3" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm uppercase focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Supplier</label>
              <select v-model="form.supplier_id" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option value="">—</option>
                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Location</label>
              <select v-model="form.location_id" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option value="">—</option>
                <option v-for="l in locations" :key="l.id" :value="l.id">{{ l.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Purchase Date</label>
              <input v-model="form.purchase_date" type="date" required class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              <p v-if="form.errors.purchase_date" class="text-xs text-rose-400 mt-1">{{ form.errors.purchase_date }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Production Date</label>
              <input v-model="form.production_date" type="date" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div v-if="selectedItem?.tracks_expiry">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Expiry Date</label>
              <input v-model="form.expiry_date" type="date" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              <p v-if="form.errors.expiry_date" class="text-xs text-rose-400 mt-1">{{ form.errors.expiry_date }}</p>
            </div>
            <div v-if="isAsset">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Warranty Until</label>
              <input v-model="form.warranty_until" type="date" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Condition on Arrival</label>
              <select v-model="form.condition_on_arrival" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option v-for="c in conditions" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Received By</label>
              <select v-model="form.received_by_employee_id" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option value="">—</option>
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.full_name_en }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Invoice Number</label>
              <input v-model="form.invoice_number" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Purchase Order Number</label>
              <input v-model="form.purchase_order_number" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Delivery Note Number</label>
              <input v-model="form.delivery_note_number" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Notes</label>
              <textarea v-model="form.notes" rows="2" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50"></textarea>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="open = false" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-slate-200">Cancel</button>
            <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white disabled:opacity-50">
              {{ form.processing ? 'Recording…' : 'Record receipt' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
