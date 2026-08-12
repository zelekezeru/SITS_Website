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
  suppliers: { type: Array, default: () => [] },
  summary: { type: Object, default: () => ({}) },
  can: { type: Object, default: () => ({}) },
});

const money = (n) => Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const search = ref('');
const showInactive = ref(false);

const filtered = computed(() => {
  const q = search.value.toLowerCase();
  return props.suppliers.filter((s) => {
    if (!showInactive.value && !s.is_active) return false;
    if (!q) return true;
    return `${s.name} ${s.code} ${s.tin ?? ''} ${s.contact_person ?? ''} ${s.city ?? ''}`.toLowerCase().includes(q);
  });
});

const editing = ref(null);
const open = ref(false);

const form = useForm({
  name: '', tin: '', contact_person: '', phone: '', email: '',
  address: '', city: '', bank_name: '', bank_account: '',
  rating: '', notes: '', is_active: true,
});

const openCreate = () => {
  editing.value = null;
  form.reset();
  form.clearErrors();
  open.value = true;
};

const openEdit = (s) => {
  editing.value = s;
  form.clearErrors();
  Object.keys(form.data()).forEach((k) => { form[k] = s[k] ?? (k === 'is_active' ? true : ''); });
  form.is_active = s.is_active;
  open.value = true;
};

const submit = () => {
  const done = { preserveScroll: true, onSuccess: () => { open.value = false; } };
  if (editing.value) form.put(`/store/suppliers/${editing.value.id}`, done);
  else form.post('/store/suppliers', done);
};

const remove = async (s) => {
  const ok = await confirm({
    title: 'Delete Supplier',
    message: `Delete “${s.name}”? Suppliers with receipts on file are deactivated instead, so the purchase history stays intact.`,
  });
  if (ok) router.delete(`/store/suppliers/${s.id}`, { preserveScroll: true });
};
</script>

<template>
  <Head title="Suppliers — SITS Store" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-amber-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
            <Icon name="Truck" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Store · Catalog</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Suppliers</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Vendors the Seminary buys from. Every goods-received note names one, which is where
              the spend, lead-time and arrival-condition figures come from.
            </p>
          </div>
        </div>
        <button v-if="can.manage" @click="openCreate" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
          + New Supplier
        </button>
      </div>
    </section>

    <div class="grid grid-cols-3 gap-4">
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Suppliers</p>
        <p class="text-2xl font-extrabold text-white mt-1">{{ summary.total ?? 0 }}</p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Active</p>
        <p class="text-2xl font-extrabold text-emerald-400 mt-1">{{ summary.active ?? 0 }}</p>
      </div>
      <div class="rounded-2xl border border-amber-500/15 bg-amber-500/[0.04] p-5">
        <p class="text-[11px] text-amber-500/80 font-semibold uppercase tracking-wider">Total Spend</p>
        <p class="text-2xl font-extrabold text-amber-300 mt-1">{{ money(summary.total_spend) }}</p>
      </div>
    </div>

    <div class="flex items-center gap-4 flex-wrap">
      <div class="relative w-full max-w-xs">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"><Icon name="Search" :size="16" /></span>
        <input v-model="search" type="text" placeholder="Search name, TIN, contact…" class="w-full bg-slate-900/40 border border-slate-900 rounded-xl pl-10 pr-4 py-2.5 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500" />
      </div>
      <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-400">
        <input v-model="showInactive" type="checkbox" class="w-4 h-4 rounded accent-amber-500" />
        Show inactive
      </label>
    </div>

    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2 overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm min-w-[940px]">
        <thead>
          <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
            <th class="p-3">Code</th>
            <th class="p-3">Supplier</th>
            <th class="p-3">Contact</th>
            <th class="p-3">TIN</th>
            <th class="p-3 text-right">Receipts</th>
            <th class="p-3 text-right">Spend</th>
            <th class="p-3 text-center">Rating</th>
            <th class="p-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-900">
          <tr v-for="s in filtered" :key="s.id" class="hover:bg-slate-900/30 transition-colors" :class="!s.is_active && 'opacity-50'">
            <td class="p-3 font-mono text-xs text-amber-400/90">{{ s.code }}</td>
            <td class="p-3">
              <p class="font-semibold text-slate-200">{{ s.name }}</p>
              <p v-if="s.city" class="text-[10px] text-slate-500">{{ s.city }}</p>
            </td>
            <td class="p-3 text-xs text-slate-400">
              <p v-if="s.contact_person">{{ s.contact_person }}</p>
              <p v-if="s.phone" class="text-slate-500 font-mono">{{ s.phone }}</p>
            </td>
            <td class="p-3 font-mono text-xs text-slate-500">{{ s.tin || '—' }}</td>
            <td class="p-3 text-right font-mono text-slate-300">{{ s.batches_count }}</td>
            <td class="p-3 text-right font-mono text-slate-300">{{ money(s.total_spend) }}</td>
            <td class="p-3 text-center">
              <span v-if="s.rating" class="text-amber-400 text-xs tracking-tight">{{ '★'.repeat(s.rating) }}<span class="text-slate-700">{{ '★'.repeat(5 - s.rating) }}</span></span>
              <span v-else class="text-slate-700 text-xs">—</span>
            </td>
            <td class="p-3 text-right whitespace-nowrap">
              <template v-if="can.manage">
                <button @click="openEdit(s)" class="text-[11px] font-bold px-2.5 py-1.5 text-amber-400 hover:text-amber-300">Edit</button>
                <button @click="remove(s)" class="text-[11px] font-bold px-2.5 py-1.5 text-slate-500 hover:text-rose-400">Delete</button>
              </template>
              <span v-else class="text-[11px] text-slate-600">View only</span>
            </td>
          </tr>
          <tr v-if="!filtered.length">
            <td colspan="8" class="p-8 text-center text-slate-500 italic">
              {{ search ? 'No suppliers match that search.' : 'No suppliers yet — add the first one before recording a receipt.' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm" @click.self="open = false">
      <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-6">{{ editing ? 'Edit Supplier' : 'New Supplier' }}</h3>
        <form @submit.prevent="submit" class="space-y-5">
          <div class="grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Supplier Name</label>
              <input v-model="form.name" type="text" required class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              <p v-if="form.errors.name" class="text-xs text-rose-400 mt-1">{{ form.errors.name }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">TIN</label>
              <input v-model="form.tin" type="text" placeholder="0001234567" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm font-mono focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Contact Person</label>
              <input v-model="form.contact_person" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Phone</label>
              <input v-model="form.phone" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email</label>
              <input v-model="form.email" type="email" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              <p v-if="form.errors.email" class="text-xs text-rose-400 mt-1">{{ form.errors.email }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">City</label>
              <input v-model="form.city" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Address</label>
              <input v-model="form.address" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Bank</label>
              <input v-model="form.bank_name" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Account Number</label>
              <input v-model="form.bank_account" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm font-mono focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Rating (1–5)</label>
              <select v-model="form.rating" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option value="">Not rated</option>
                <option v-for="n in 5" :key="n" :value="n">{{ '★'.repeat(n) }} ({{ n }})</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Notes</label>
            <textarea v-model="form.notes" rows="2" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50"></textarea>
          </div>

          <label class="flex items-center gap-3 cursor-pointer">
            <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded accent-amber-500" />
            <span class="text-sm text-slate-300">Active</span>
          </label>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="open = false" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-slate-200">Cancel</button>
            <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white disabled:opacity-50">
              {{ form.processing ? 'Saving…' : (editing ? 'Save changes' : 'Add supplier') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
