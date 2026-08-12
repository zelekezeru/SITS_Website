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
  categories: { type: Array, default: () => [] },
  trackingModes: { type: Array, default: () => [] },
  depreciationMethods: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
});

const search = ref('');

// Rendered as a flat, indented list rather than a nested component: the tree is
// shallow in practice and a flat table keeps the columns aligned across depths.
const tree = computed(() => {
  const byParent = new Map();
  props.categories.forEach((c) => {
    const key = c.parent_id ?? 0;
    if (!byParent.has(key)) byParent.set(key, []);
    byParent.get(key).push(c);
  });

  const out = [];
  const walk = (parentId, depth) => {
    (byParent.get(parentId) ?? []).forEach((c) => {
      out.push({ ...c, depth });
      walk(c.id, depth + 1);
    });
  };
  walk(0, 0);
  return out;
});

const filtered = computed(() => {
  if (!search.value) return tree.value;
  const q = search.value.toLowerCase();
  // Filtering flattens the hierarchy — indentation would be misleading when
  // ancestors are hidden.
  return tree.value
    .filter((c) => `${c.name_en} ${c.code} ${c.name_am ?? ''}`.toLowerCase().includes(q))
    .map((c) => ({ ...c, depth: 0 }));
});

const editing = ref(null);
const open = ref(false);

const form = useForm({
  parent_id: '',
  code: '',
  name_en: '',
  name_am: '',
  description: '',
  tracking_mode: 'consumable',
  default_depreciation_method: 'none',
  default_useful_life_months: '',
  is_active: true,
});

const openCreate = (parent = null) => {
  editing.value = null;
  form.reset();
  form.clearErrors();
  if (parent) {
    form.parent_id = parent.id;
    form.tracking_mode = parent.tracking_mode;
    form.default_depreciation_method = parent.default_depreciation_method;
  }
  open.value = true;
};

const openEdit = (category) => {
  editing.value = category;
  form.clearErrors();
  form.parent_id = category.parent_id ?? '';
  form.code = category.code;
  form.name_en = category.name_en;
  form.name_am = category.name_am ?? '';
  form.description = category.description ?? '';
  form.tracking_mode = category.tracking_mode;
  form.default_depreciation_method = category.default_depreciation_method;
  form.default_useful_life_months = category.default_useful_life_months ?? '';
  form.is_active = category.is_active;
  open.value = true;
};

const submit = () => {
  const done = { preserveScroll: true, onSuccess: () => { open.value = false; } };
  if (editing.value) form.put(`/store/categories/${editing.value.id}`, done);
  else form.post('/store/categories', done);
};

const remove = async (category) => {
  const ok = await confirm({
    title: 'Delete Category',
    message: `Delete “${category.name_en}”? Categories with items or sub-categories cannot be removed.`,
  });
  if (ok) router.delete(`/store/categories/${category.id}`, { preserveScroll: true });
};

// Parent options exclude the category being edited and its own descendants,
// which the server rejects anyway — better to not offer the choice.
const parentOptions = computed(() => {
  if (!editing.value) return tree.value;
  const banned = new Set([editing.value.id]);
  let grew = true;
  while (grew) {
    grew = false;
    tree.value.forEach((c) => {
      if (c.parent_id && banned.has(c.parent_id) && !banned.has(c.id)) {
        banned.add(c.id);
        grew = true;
      }
    });
  }
  return tree.value.filter((c) => !banned.has(c.id));
});

const MODE = {
  consumable: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
  asset: 'bg-violet-500/10 border-violet-500/20 text-violet-400',
};
</script>

<template>
  <Head title="Inventory Categories — SITS Store" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-amber-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
            <Icon name="Layers" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Store · Catalog</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Categories</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              The category tree items are filed under. Each category sets the defaults its items
              inherit — whether they're counted in bulk or tracked one at a time, and how they
              depreciate. Its code becomes the prefix in SKUs and asset tags.
            </p>
          </div>
        </div>
        <button v-if="can.manage" @click="openCreate()" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
          + New Category
        </button>
      </div>
    </section>

    <div class="relative w-full max-w-xs">
      <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"><Icon name="Search" :size="16" /></span>
      <input v-model="search" type="text" placeholder="Search categories…" class="w-full bg-slate-900/40 border border-slate-900 rounded-xl pl-10 pr-4 py-2.5 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500" />
    </div>

    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2 overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm min-w-[860px]">
        <thead>
          <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
            <th class="p-3">Category</th>
            <th class="p-3">Code</th>
            <th class="p-3">Tracking</th>
            <th class="p-3">Depreciation</th>
            <th class="p-3 text-right">Items</th>
            <th class="p-3 text-center">Active</th>
            <th class="p-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-900">
          <tr v-for="c in filtered" :key="c.id" class="hover:bg-slate-900/30 transition-colors">
            <td class="p-3">
              <div class="flex items-center gap-2" :style="{ paddingLeft: `${c.depth * 20}px` }">
                <Icon v-if="c.depth" name="ChevronRight" :size="13" class="text-slate-700 shrink-0" />
                <div class="min-w-0">
                  <p class="font-semibold text-slate-200">{{ c.name_en }}</p>
                  <p v-if="c.name_am" class="text-[10px] text-slate-500">{{ c.name_am }}</p>
                </div>
              </div>
            </td>
            <td class="p-3 font-mono text-xs text-amber-400/90">{{ c.code }}</td>
            <td class="p-3">
              <span class="px-2 py-0.5 text-[10px] rounded-full font-bold border" :class="MODE[c.tracking_mode]">{{ c.tracking_mode_label }}</span>
            </td>
            <td class="p-3 text-slate-400 text-xs">
              {{ depreciationMethods.find((m) => m.value === c.default_depreciation_method)?.label }}
              <span v-if="c.default_useful_life_months" class="text-slate-600">· {{ c.default_useful_life_months }} mo</span>
            </td>
            <td class="p-3 text-right font-mono text-slate-300">{{ c.items_count }}</td>
            <td class="p-3 text-center">
              <Icon :name="c.is_active ? 'CheckCircle2' : 'XCircle'" :size="15" :class="c.is_active ? 'text-emerald-400 inline' : 'text-slate-700 inline'" />
            </td>
            <td class="p-3 text-right whitespace-nowrap">
              <template v-if="can.manage">
                <button @click="openCreate(c)" class="text-[11px] font-bold px-2.5 py-1.5 text-slate-400 hover:text-amber-300">+ Sub</button>
                <button @click="openEdit(c)" class="text-[11px] font-bold px-2.5 py-1.5 text-amber-400 hover:text-amber-300">Edit</button>
                <button @click="remove(c)" class="text-[11px] font-bold px-2.5 py-1.5 text-slate-500 hover:text-rose-400">Delete</button>
              </template>
              <span v-else class="text-[11px] text-slate-600">View only</span>
            </td>
          </tr>
          <tr v-if="!filtered.length">
            <td colspan="7" class="p-8 text-center text-slate-500 italic">
              {{ search ? 'No categories match that search.' : 'No categories yet — add the first one to start cataloguing.' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create / edit -->
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm" @click.self="open = false">
      <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-6">{{ editing ? 'Edit Category' : 'New Category' }}</h3>
        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Parent Category</label>
            <select v-model="form.parent_id" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
              <option value="">— Top level —</option>
              <option v-for="p in parentOptions" :key="p.id" :value="p.id">{{ p.full_path }}</option>
            </select>
            <p v-if="form.errors.parent_id" class="text-xs text-rose-400 mt-1">{{ form.errors.parent_id }}</p>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Code</label>
              <input v-model="form.code" type="text" required maxlength="12" placeholder="IT" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm font-mono uppercase focus:outline-none focus:border-amber-500/50" />
              <p v-if="form.errors.code" class="text-xs text-rose-400 mt-1">{{ form.errors.code }}</p>
            </div>
            <div class="col-span-2">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Name (English)</label>
              <input v-model="form.name_en" type="text" required placeholder="IT Equipment" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              <p v-if="form.errors.name_en" class="text-xs text-rose-400 mt-1">{{ form.errors.name_en }}</p>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Name (Amharic)</label>
            <input v-model="form.name_am" type="text" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Tracking Mode</label>
            <div class="grid grid-cols-2 gap-3">
              <button v-for="m in trackingModes" :key="m.value" type="button" @click="form.tracking_mode = m.value"
                      class="text-left px-4 py-3 rounded-xl border text-sm transition-all"
                      :class="form.tracking_mode === m.value ? 'border-amber-500/50 bg-amber-500/10 text-amber-300' : 'border-slate-800 bg-slate-950/60 text-slate-400 hover:border-slate-700'">
                <span class="font-bold block">{{ m.label }}</span>
              </button>
            </div>
            <p class="text-[11px] text-slate-500 mt-2">
              Consumables are counted in bulk from the ledger. Assets get one tagged record each.
            </p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Depreciation</label>
              <select v-model="form.default_depreciation_method" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option v-for="d in depreciationMethods" :key="d.value" :value="d.value">{{ d.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Useful Life (months)</label>
              <input v-model="form.default_useful_life_months" type="number" min="1" max="1200" placeholder="48" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
              <p v-if="form.errors.default_useful_life_months" class="text-xs text-rose-400 mt-1">{{ form.errors.default_useful_life_months }}</p>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Description</label>
            <textarea v-model="form.description" rows="2" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50"></textarea>
          </div>

          <label class="flex items-center gap-3 cursor-pointer">
            <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded accent-amber-500" />
            <span class="text-sm text-slate-300">Active</span>
          </label>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="open = false" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-slate-200">Cancel</button>
            <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white disabled:opacity-50">
              {{ form.processing ? 'Saving…' : (editing ? 'Save changes' : 'Create category') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
