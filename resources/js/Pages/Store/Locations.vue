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
  locations: { type: Array, default: () => [] },
  campuses: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  locationTypes: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
});

const search = ref('');

const tree = computed(() => {
  const byParent = new Map();
  props.locations.forEach((l) => {
    const key = l.parent_id ?? 0;
    if (!byParent.has(key)) byParent.set(key, []);
    byParent.get(key).push(l);
  });

  const out = [];
  const walk = (parentId, depth) => {
    (byParent.get(parentId) ?? []).forEach((l) => {
      out.push({ ...l, depth });
      walk(l.id, depth + 1);
    });
  };
  walk(0, 0);
  return out;
});

const filtered = computed(() => {
  if (!search.value) return tree.value;
  const q = search.value.toLowerCase();
  return tree.value
    .filter((l) => `${l.name} ${l.code} ${l.type_label} ${l.campus ?? ''}`.toLowerCase().includes(q))
    .map((l) => ({ ...l, depth: 0 }));
});

const storableCount = computed(() => props.locations.filter((l) => l.is_storable && l.is_active).length);

const editing = ref(null);
const open = ref(false);

const form = useForm({
  campus_id: '', parent_id: '', name: '', type: 'room',
  description: '', custodian_employee_id: '', is_issuable: true, is_active: true,
});

const openCreate = (parent = null) => {
  editing.value = null;
  form.reset();
  form.clearErrors();
  if (parent) {
    form.parent_id = parent.id;
    form.campus_id = parent.campus_id ?? '';
  }
  open.value = true;
};

const openEdit = (l) => {
  editing.value = l;
  form.clearErrors();
  form.campus_id = l.campus_id ?? '';
  form.parent_id = l.parent_id ?? '';
  form.name = l.name;
  form.type = l.type;
  form.description = l.description ?? '';
  form.custodian_employee_id = l.custodian_employee_id ?? '';
  form.is_issuable = l.is_issuable;
  form.is_active = l.is_active;
  open.value = true;
};

const submit = () => {
  const done = { preserveScroll: true, onSuccess: () => { open.value = false; } };
  if (editing.value) form.put(`/store/locations/${editing.value.id}`, done);
  else form.post('/store/locations', done);
};

const remove = async (l) => {
  const ok = await confirm({
    title: 'Delete Location',
    message: `Delete “${l.name}”? Locations that stock has ever passed through are deactivated instead, so the ledger history stays readable.`,
  });
  if (ok) router.delete(`/store/locations/${l.id}`, { preserveScroll: true });
};

const parentOptions = computed(() => {
  if (!editing.value) return tree.value;
  const banned = new Set([editing.value.id]);
  let grew = true;
  while (grew) {
    grew = false;
    tree.value.forEach((l) => {
      if (l.parent_id && banned.has(l.parent_id) && !banned.has(l.id)) {
        banned.add(l.id);
        grew = true;
      }
    });
  }
  return tree.value.filter((l) => !banned.has(l.id));
});

const selectedType = computed(() => props.locationTypes.find((t) => t.value === form.type));
</script>

<template>
  <Head title="Store Locations — SITS Store" />

  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8">
      <div class="absolute top-[-30%] right-[-5%] w-80 h-80 rounded-full bg-amber-600/10 blur-[110px] pointer-events-none"></div>
      <div class="relative z-10 flex items-center justify-between gap-6 flex-wrap sm:flex-nowrap">
        <div class="flex items-start gap-4">
          <span class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
            <Icon name="MapPin" :size="26" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-widest">Store · Catalog</p>
            <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight mt-1">Store Locations</h2>
            <p class="text-slate-400 text-sm mt-2 max-w-2xl">
              Where things physically are: campus → store → room → shelf → bin, to whatever depth the
              building actually has. Buildings and floors group; stores, rooms, shelves, bins,
              offices and vehicles can hold stock.
            </p>
          </div>
        </div>
        <button v-if="can.manage" @click="openCreate()" class="shrink-0 text-sm font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white px-5 py-2.5 rounded-xl transition-all shadow-md cursor-pointer">
          + New Location
        </button>
      </div>
    </section>

    <div class="grid grid-cols-3 gap-4">
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Locations</p>
        <p class="text-2xl font-extrabold text-white mt-1">{{ locations.length }}</p>
      </div>
      <div class="rounded-2xl border border-amber-500/15 bg-amber-500/[0.04] p-5">
        <p class="text-[11px] text-amber-500/80 font-semibold uppercase tracking-wider">Can Hold Stock</p>
        <p class="text-2xl font-extrabold text-amber-300 mt-1">{{ storableCount }}</p>
      </div>
      <div class="rounded-2xl border border-slate-900 bg-slate-900/20 p-5">
        <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Assets Placed</p>
        <p class="text-2xl font-extrabold text-white mt-1">{{ locations.reduce((n, l) => n + (l.units_count || 0), 0) }}</p>
      </div>
    </div>

    <div class="relative w-full max-w-xs">
      <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"><Icon name="Search" :size="16" /></span>
      <input v-model="search" type="text" placeholder="Search locations…" class="w-full bg-slate-900/40 border border-slate-900 rounded-xl pl-10 pr-4 py-2.5 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-amber-500" />
    </div>

    <div class="rounded-2xl border border-slate-900 bg-slate-900/10 shadow-md p-2 overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm min-w-[900px]">
        <thead>
          <tr class="border-b border-slate-900 text-xs font-semibold text-slate-500 uppercase">
            <th class="p-3">Location</th>
            <th class="p-3">Code</th>
            <th class="p-3">Type</th>
            <th class="p-3">Campus</th>
            <th class="p-3">Custodian</th>
            <th class="p-3 text-right">Assets</th>
            <th class="p-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-900">
          <tr v-for="l in filtered" :key="l.id" class="hover:bg-slate-900/30 transition-colors" :class="!l.is_active && 'opacity-50'">
            <td class="p-3">
              <div class="flex items-center gap-2" :style="{ paddingLeft: `${l.depth * 20}px` }">
                <Icon :name="l.icon" :size="14" class="shrink-0" :class="l.is_storable ? 'text-amber-400/80' : 'text-slate-600'" />
                <div class="min-w-0">
                  <p class="font-semibold text-slate-200">{{ l.name }}</p>
                  <p v-if="!l.is_storable" class="text-[10px] text-slate-600">Grouping only — cannot hold stock</p>
                  <p v-else-if="!l.is_issuable" class="text-[10px] text-amber-500/70">Not issuable</p>
                </div>
              </div>
            </td>
            <td class="p-3 font-mono text-xs text-amber-400/90">{{ l.code }}</td>
            <td class="p-3 text-xs text-slate-400">{{ l.type_label }}</td>
            <td class="p-3 text-xs text-slate-400">{{ l.campus || '—' }}</td>
            <td class="p-3 text-xs text-slate-400">{{ l.custodian || '—' }}</td>
            <td class="p-3 text-right font-mono text-slate-300">{{ l.units_count }}</td>
            <td class="p-3 text-right whitespace-nowrap">
              <template v-if="can.manage">
                <button @click="openCreate(l)" class="text-[11px] font-bold px-2.5 py-1.5 text-slate-400 hover:text-amber-300">+ Sub</button>
                <button @click="openEdit(l)" class="text-[11px] font-bold px-2.5 py-1.5 text-amber-400 hover:text-amber-300">Edit</button>
                <button @click="remove(l)" class="text-[11px] font-bold px-2.5 py-1.5 text-slate-500 hover:text-rose-400">Delete</button>
              </template>
              <span v-else class="text-[11px] text-slate-600">View only</span>
            </td>
          </tr>
          <tr v-if="!filtered.length">
            <td colspan="7" class="p-8 text-center text-slate-500 italic">
              {{ search ? 'No locations match that search.' : 'No locations yet — start with a store, then add its rooms and shelves.' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm" @click.self="open = false">
      <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-3xl border border-slate-900 bg-gradient-to-b from-slate-900 to-slate-950 p-8 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-6">{{ editing ? 'Edit Location' : 'New Location' }}</h3>
        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Name</label>
            <input v-model="form.name" type="text" required placeholder="Central Store" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50" />
            <p v-if="form.errors.name" class="text-xs text-rose-400 mt-1">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Type</label>
            <select v-model="form.type" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
              <option v-for="t in locationTypes" :key="t.value" :value="t.value">{{ t.label }}{{ t.storable ? '' : ' — grouping only' }}</option>
            </select>
            <p v-if="selectedType && !selectedType.storable" class="text-[11px] text-amber-500/80 mt-1.5 flex items-center gap-1.5">
              <Icon name="Info" :size="13" /> Stock cannot be placed directly in a {{ selectedType.label.toLowerCase() }} — add rooms or shelves under it.
            </p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Campus</label>
              <select v-model="form.campus_id" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option value="">— None —</option>
                <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name_en }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Inside</label>
              <select v-model="form.parent_id" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
                <option value="">— Top level —</option>
                <option v-for="p in parentOptions" :key="p.id" :value="p.id">{{ p.full_path }}</option>
              </select>
              <p v-if="form.errors.parent_id" class="text-xs text-rose-400 mt-1">{{ form.errors.parent_id }}</p>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Custodian</label>
            <select v-model="form.custodian_employee_id" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50">
              <option value="">— Inherit from parent —</option>
              <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.full_name_en }}<template v-if="e.staff_no"> ({{ e.staff_no }})</template></option>
            </select>
            <p class="text-[11px] text-slate-500 mt-1">Who answers for what's here. A shelf normally inherits its store's custodian.</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Description</label>
            <textarea v-model="form.description" rows="2" class="w-full bg-slate-950/60 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 text-sm focus:outline-none focus:border-amber-500/50"></textarea>
          </div>

          <div class="flex flex-col gap-3">
            <label class="flex items-center gap-3 cursor-pointer">
              <input v-model="form.is_issuable" type="checkbox" class="w-4 h-4 rounded accent-amber-500" />
              <span class="text-sm text-slate-300">Stock may be issued out of here</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded accent-amber-500" />
              <span class="text-sm text-slate-300">Active</span>
            </label>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="open = false" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-slate-200">Cancel</button>
            <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white disabled:opacity-50">
              {{ form.processing ? 'Saving…' : (editing ? 'Save changes' : 'Create location') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
