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
  items: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  trackingModes: { type: Array, default: () => [] },
  unitsOfMeasure: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  depreciationMethods: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
});

const money = (n) => Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// ---- Filters ----------------------------------------------------------------
const search = ref('');
const modeFilter = ref('');
const statusFilter = ref('active');

const filtered = computed(() => {
  const q = search.value.toLowerCase();
  return props.items.filter((i) => {
    if (modeFilter.value && i.tracking_mode !== modeFilter.value) return false;
    if (statusFilter.value && i.status !== statusFilter.value) return false;
    if (!q) return true;
    return `${i.name_en} ${i.code} ${i.brand ?? ''} ${i.model ?? ''}`.toLowerCase().includes(q);
  });
});

const summary = computed(() => ({
  total: props.items.length,
  assets: props.items.filter((i) => i.tracking_mode === 'asset').length,
  consumables: props.items.filter((i) => i.tracking_mode === 'consumable').length,
  reorder: props.items.filter((i) => i.needs_reorder).length,
}));

// ---- Create / edit modal -----------------------------------------------------
const editing = ref(null);
const open = ref(false);
const imageInput = ref(null);

const blankForm = () => ({
  category_id: '', name_en: '', name_am: '', description: '',
  tracking_mode: 'consumable', unit_of_measure: 'piece', status: 'active',
  brand: '', model: '', specification: '',
  reorder_level: 0, reorder_quantity: '', standard_unit_cost: '',
  tracks_expiry: false, depreciation_method: '', useful_life_months: '',
  notes: '', image: null,
});

const form = useForm(blankForm());

const openCreate = () => {
  editing.value = null;
  form.defaults(blankForm());
  form.reset();
  form.clearErrors();
  if (imageInput.value) imageInput.value.value = '';
  open.value = true;
};

const openEdit = (item) => {
  editing.value = item;
  form.clearErrors();
  form.category_id = item.category_id;
  form.name_en = item.name_en;
  form.name_am = item.name_am ?? '';
  form.description = item.description ?? '';
  form.tracking_mode = item.tracking_mode;
  form.unit_of_measure = item.unit_of_measure;
  form.status = item.status;
  form.brand = item.brand ?? '';
  form.model = item.model ?? '';
  form.specification = item.specification ?? '';
  form.reorder_level = item.reorder_level ?? 0;
  form.reorder_quantity = item.reorder_quantity ?? '';
  form.standard_unit_cost = item.standard_unit_cost ?? '';
  form.tracks_expiry = item.tracks_expiry;
  form.depreciation_method = item.depreciation_method ?? '';
  form.useful_life_months = item.useful_life_months ?? '';
  form.notes = item.notes ?? '';
  form.image = null;
  if (imageInput.value) imageInput.value.value = '';
  open.value = true;
};

// A category's tracking_mode is the DEFAULT for a new item — pick it up once,
// but don't fight the user if they then change it deliberately.
const onCategoryChange = () => {
  if (editing.value) return; // don't re-default on edit
  const cat = props.categories.find((c) => c.id === Number(form.category_id));
  if (cat) {
    form.tracking_mode = cat.tracking_mode;
    if (cat.default_depreciation_method) form.depreciation_method = cat.default_depreciation_method;
    if (cat.default_useful_life_months) form.useful_life_months = cat.default_useful_life_months;
  }
};

const onImagePicked = (e) => { form.image = e.target.files[0] ?? null; };

const submit = () => {
  const options = { preserveScroll: true, onSuccess: () => { open.value = false; } };

  // File uploads on an update must go through POST with a spoofed _method —
  // Laravel reads multipart bodies correctly on POST; a real PUT with a
  // multipart body is not guaranteed to parse. This mirrors the @method()
  // spoofing already used elsewhere in this app (see oauth/authorize.blade.php).
  if (editing.value) {
    form.transform((data) => ({ ...data, _method: 'put' }))
        .post(`/store/items/${editing.value.id}`, options);
  } else {
    form.post('/store/items', options);
  }
};

const remove = async (item) => {
  const ok = await confirm({
    title: 'Delete Item',
    message: `Delete "${item.name_en}"? Items with a receipt, unit or movement on file are archived instead, so history stays intact.`,
  });
  if (ok) router.delete(`/store/items/${item.id}`, { preserveScroll: true });
};

// ---- Attachments (only inside an existing item) ------------------------------
const docForm = useForm({ title: '', category: 'invoice', file: null });
const docFileInput = ref(null);

const onDocPicked = (e) => { docForm.file = e.target.files[0] ?? null; };

const submitDoc = () => {
  if (!editing.value) return;
  docForm.post(`/store/items/${editing.value.id}/documents`, {
    preserveScroll: true,
    onSuccess: () => { docForm.reset(); if (docFileInput.value) docFileInput.value.value = ''; },
  });
};

const removeDoc = (doc) => {
  if (!editing.value) return;
  router.delete(`/store/items/${editing.value.id}/documents/${doc.id}`, { preserveScroll: true });
};
</script>

<template>
  <Head title="Items — SITS Store" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-amber-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
            <Icon name="Package" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Store · Catalog</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Items</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Every material and asset the Seminary owns, with photos, specification and reorder policy.
            </p>
          </div>
        </div>
        <button v-if="can.manage" @click="openCreate" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
          + New Item
        </button>
      </div>
    </section>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Items</p>
        <p class="text-2xl font-extrabold text-white mt-1">{{ summary.total }}</p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Consumables</p>
        <p class="text-2xl font-extrabold text-blue-400 mt-1">{{ summary.consumables }}</p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Fixed Assets</p>
        <p class="text-2xl font-extrabold text-violet-400 mt-1">{{ summary.assets }}</p>
      </div>
      <div class="rounded-2xl border border-amber-500/15 bg-amber-500/[0.04] p-5">
        <p class="text-[11px] text-amber-500/80 font-semibold uppercase tracking-wider">Needs Reorder</p>
        <p class="text-2xl font-extrabold text-amber-300 mt-1">{{ summary.reorder }}</p>
      </div>
    </div>

    <div class="flex items-center gap-4 flex-wrap">
      <div class="relative w-full max-w-xs">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"><Icon name="Search" :size="16" /></span>
        <input v-model="search" type="text" placeholder="Search name, code, brand…" class="w-full bg-slate-900/40 border border-slate-900 rounded-xl pl-10 pr-4 py-2.5 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500" />
      </div>
      <select v-model="modeFilter" class="bg-slate-900/40 border border-slate-900 rounded-xl px-3 py-2.5 text-slate-300 text-sm focus:outline-none focus:border-amber-500">
        <option value="">All modes</option>
        <option v-for="m in trackingModes" :key="m.value" :value="m.value">{{ m.label }}</option>
      </select>
      <select v-model="statusFilter" class="bg-slate-900/40 border border-slate-900 rounded-xl px-3 py-2.5 text-slate-300 text-sm focus:outline-none focus:border-amber-500">
        <option value="">All statuses</option>
        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
      </select>
    </div>

    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2 overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm min-w-[1040px]">
        <thead>
          <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
            <th class="p-3">Item</th>
            <th class="p-3">Category</th>
            <th class="p-3">Mode</th>
            <th class="p-3 text-right">On hand</th>
            <th class="p-3">Status</th>
            <th class="p-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-900">
          <tr v-for="i in filtered" :key="i.id" class="hover:bg-slate-900/30 transition-colors">
            <td class="p-3">
              <div class="flex items-center gap-3">
                <img v-if="i.image_path" :src="'/' + i.image_path" class="w-9 h-9 rounded-lg object-cover border border-slate-800" />
                <span v-else class="w-9 h-9 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-600"><Icon name="Package" :size="16" /></span>
                <div class="min-w-0">
                  <p class="font-semibold text-slate-200">{{ i.name_en }}</p>
                  <p class="font-mono text-[11px] text-amber-400/80">{{ i.code }}</p>
                </div>
              </div>
            </td>
            <td class="p-3 text-xs text-slate-400">{{ i.category || '—' }}</td>
            <td class="p-3">
              <span class="text-[10px] font-bold uppercase px-2 py-1 rounded-lg" :class="i.tracking_mode === 'asset' ? 'bg-violet-500/10 text-violet-300 border border-violet-500/20' : 'bg-blue-500/10 text-blue-300 border border-blue-500/20'">
                {{ i.tracking_mode_label }}
              </span>
            </td>
            <td class="p-3 text-right font-mono" :class="i.needs_reorder ? 'text-amber-400 font-bold' : 'text-slate-300'">
              {{ i.on_hand }} <span class="text-slate-600">{{ i.unit_of_measure }}</span>
            </td>
            <td class="p-3">
              <span class="text-[10px] font-bold uppercase px-2 py-1 rounded-lg" :class="`bg-${i.status_tone}-500/10 text-${i.status_tone}-300 border border-${i.status_tone}-500/20`">
                {{ i.status_label }}
              </span>
            </td>
            <td class="p-3 text-right whitespace-nowrap">
              <template v-if="can.manage">
                <button @click="openEdit(i)" class="text-[11px] font-bold px-2.5 py-1.5 text-amber-400 hover:text-amber-300">Edit</button>
                <button @click="remove(i)" class="text-[11px] font-bold px-2.5 py-1.5 text-slate-500 hover:text-rose-400">Delete</button>
              </template>
              <span v-else class="text-[11px] text-slate-600">View only</span>
            </td>
          </tr>
          <tr v-if="!filtered.length">
            <td colspan="6" class="p-8 text-center text-slate-500 italic">
              {{ search ? 'No items match that search.' : 'No items yet — add the first one.' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm" @click.self="open = false">
      <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-1">{{ editing ? 'Edit Item' : 'New Item' }}</h3>
        <p v-if="editing" class="font-mono text-xs text-amber-400/80 mb-6">{{ editing.code }}</p>
        <p v-else class="text-xs text-slate-500 mb-6">A SKU is generated automatically once saved.</p>

        <form @submit.prevent="submit" class="space-y-5">
          <div class="grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Category</label>
              <select v-model="form.category_id" @change="onCategoryChange" required class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option value="" disabled>Select a category…</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name_en }}</option>
              </select>
              <p v-if="form.errors.category_id" class="text-xs text-rose-400 mt-1">{{ form.errors.category_id }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Name (English)</label>
              <input v-model="form.name_en" type="text" required class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              <p v-if="form.errors.name_en" class="text-xs text-rose-400 mt-1">{{ form.errors.name_en }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Name (Amharic)</label>
              <input v-model="form.name_am" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Tracking Mode</label>
              <select v-model="form.tracking_mode" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option v-for="m in trackingModes" :key="m.value" :value="m.value">{{ m.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Unit of Measure</label>
              <select v-model="form.unit_of_measure" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option v-for="u in unitsOfMeasure" :key="u.value" :value="u.value">{{ u.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Status</label>
              <select v-model="form.status" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Brand</label>
              <input v-model="form.brand" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Model</label>
              <input v-model="form.model" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Specification</label>
              <textarea v-model="form.specification" rows="2" placeholder="Size, colour, rating, capacity…" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50"></textarea>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Reorder Level</label>
              <input v-model="form.reorder_level" type="number" step="0.001" min="0" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Reorder Quantity</label>
              <input v-model="form.reorder_quantity" type="number" step="0.001" min="0" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Standard Unit Cost (planning)</label>
              <input v-model="form.standard_unit_cost" type="number" step="0.01" min="0" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            </div>
            <label class="flex items-center gap-3 cursor-pointer pt-7">
              <input v-model="form.tracks_expiry" type="checkbox" class="w-4 h-4 rounded accent-amber-500" />
              <span class="text-sm text-slate-300">Batches of this item carry an expiry date</span>
            </label>

            <!-- Depreciation — only meaningful for fixed assets -->
            <template v-if="form.tracking_mode === 'asset'">
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Depreciation Method</label>
                <select v-model="form.depreciation_method" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                  <option value="">Inherit from category</option>
                  <option v-for="d in depreciationMethods" :key="d.value" :value="d.value">{{ d.label }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Useful Life (months)</label>
                <input v-model="form.useful_life_months" type="number" min="1" max="1200" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              </div>
            </template>

            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Photo</label>
              <input ref="imageInput" @change="onImagePicked" type="file" accept="image/*" class="w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-amber-500/10 file:text-amber-400 hover:file:bg-amber-500/20" />
              <img v-if="editing?.image_path" :src="'/' + editing.image_path" class="mt-3 w-20 h-20 rounded-xl object-cover border border-slate-800" />
              <p v-if="form.errors.image" class="text-xs text-rose-400 mt-1">{{ form.errors.image }}</p>
            </div>

            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Notes</label>
              <textarea v-model="form.notes" rows="2" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50"></textarea>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="open = false" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-slate-200">Cancel</button>
            <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white disabled:opacity-50">
              {{ form.processing ? 'Saving…' : (editing ? 'Save changes' : 'Add item') }}
            </button>
          </div>
        </form>

        <!-- Attachments — only once the item exists -->
        <div v-if="editing" class="mt-8 pt-6 border-t border-slate-900">
          <h4 class="text-sm font-bold text-slate-300 mb-4">Attachments</h4>
          <ul class="space-y-2 mb-4">
            <li v-for="d in editing.documents" :key="d.id" class="flex items-center justify-between bg-slate-900/30 rounded-xl px-4 py-2.5">
              <div class="flex items-center gap-2 min-w-0">
                <span class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 shrink-0">{{ d.category }}</span>
                <span class="text-xs text-slate-300 truncate">{{ d.title }}</span>
              </div>
              <button @click="removeDoc(d)" class="text-[11px] font-bold text-slate-500 hover:text-rose-400 shrink-0 ml-3">Remove</button>
            </li>
            <li v-if="!editing.documents?.length" class="text-xs text-slate-600 italic">No attachments yet.</li>
          </ul>
          <form @submit.prevent="submitDoc" class="flex items-end gap-3 flex-wrap">
            <div class="flex-1 min-w-[160px]">
              <input v-model="docForm.title" type="text" placeholder="Title (e.g. Purchase invoice)" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-3 py-2 text-slate-100 text-xs focus:outline-none focus:border-amber-500/50" />
            </div>
            <select v-model="docForm.category" class="bg-slate-950/60 border border-slate-800 rounded-xl px-3 py-2 text-slate-100 text-xs focus:outline-none focus:border-amber-500/50">
              <option value="invoice">Invoice</option>
              <option value="warranty">Warranty</option>
              <option value="manual">Manual</option>
              <option value="image">Photo</option>
            </select>
            <input ref="docFileInput" @change="onDocPicked" type="file" class="text-xs text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-300" />
            <button type="submit" :disabled="docForm.processing || !docForm.file" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 disabled:opacity-40">Attach</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
