<script setup>
import Icon from '@/Components/Icon.vue';

const items = defineModel({ type: Array, default: () => [] });
defineProps({ errors: { type: Object, default: () => ({}) } });

const categories = [
  { value: 'responsibility', label: 'Responsibility' },
  { value: 'authority', label: 'Authority' },
  { value: 'qualification', label: 'Qualification' },
  { value: 'relationship', label: 'Reporting / Relationship' },
];

const measureTypes = [
  { value: 'quantitative', label: 'Quantitative' },
  { value: 'qualitative', label: 'Qualitative' },
  { value: 'boolean', label: 'Yes / No' },
  { value: 'narrative', label: 'Narrative' },
];

const blank = () => ({
  category: 'responsibility',
  title_en: '',
  title_am: '',
  is_kpi: false,
  measure_type: 'qualitative',
  target_value: '',
  unit: '',
  weight: 1,
});

const addItem = () => { items.value = [...items.value, blank()]; };
const removeItem = (i) => { items.value = items.value.filter((_, idx) => idx !== i); };
const move = (i, dir) => {
  const j = i + dir;
  if (j < 0 || j >= items.value.length) return;
  const next = [...items.value];
  [next[i], next[j]] = [next[j], next[i]];
  items.value = next;
};
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Job Description Items</label>
      <span class="text-[11px] text-slate-500">{{ items.length }} item(s) · {{ items.filter(i => i.is_kpi).length }} as KPI</span>
    </div>

    <div v-for="(item, i) in items" :key="i"
         class="rounded-xl border bg-slate-950/40 p-4 space-y-3"
         :class="item.is_kpi ? 'border-blue-500/30' : 'border-slate-850'">
      <!-- Row header: order, category, remove -->
      <div class="flex items-center gap-2">
        <div class="flex flex-col">
          <button type="button" class="text-slate-600 hover:text-slate-300 leading-none" :disabled="i === 0" @click="move(i, -1)">▲</button>
          <button type="button" class="text-slate-600 hover:text-slate-300 leading-none" :disabled="i === items.length - 1" @click="move(i, 1)">▼</button>
        </div>
        <span class="w-6 h-6 rounded-md bg-slate-800 text-slate-400 text-xs font-bold flex items-center justify-center shrink-0">{{ i + 1 }}</span>
        <select v-model="item.category"
                class="bg-slate-950/60 border border-slate-850 focus:border-blue-500/50 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none">
          <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
        </select>

        <label class="ml-auto flex items-center gap-1.5 cursor-pointer select-none" :title="'Use this item as a KPI'">
          <input type="checkbox" v-model="item.is_kpi" class="w-4 h-4 rounded border-slate-700 bg-slate-950 text-blue-600 focus:ring-0" />
          <span class="text-[11px] font-semibold" :class="item.is_kpi ? 'text-blue-400' : 'text-slate-500'">KPI</span>
        </label>
        <button type="button" @click="removeItem(i)"
                class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-500 hover:text-rose-400 hover:bg-rose-950/30 transition-colors">
          <Icon name="X" :size="15" />
        </button>
      </div>

      <!-- Titles -->
      <div class="grid sm:grid-cols-2 gap-2">
        <div>
          <input v-model="item.title_en" type="text" required placeholder="Item (English)"
                 class="w-full bg-slate-950/60 border rounded-lg px-3 py-2 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-blue-500/50"
                 :class="errors[`items.${i}.title_en`] ? 'border-rose-500/50' : 'border-slate-850'" />
          <p v-if="errors[`items.${i}.title_en`]" class="text-[11px] text-rose-400 mt-1">{{ errors[`items.${i}.title_en`] }}</p>
        </div>
        <input v-model="item.title_am" type="text" placeholder="Item (አማርኛ)"
               class="w-full bg-slate-950/60 border border-slate-850 rounded-lg px-3 py-2 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-blue-500/50" />
      </div>

      <!-- KPI measurement (only when flagged) -->
      <div v-if="item.is_kpi" class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 border-t border-slate-900">
        <div>
          <label class="block text-[10px] text-slate-500 uppercase mb-1">Measure</label>
          <select v-model="item.measure_type" class="w-full bg-slate-950/60 border border-slate-850 rounded-lg px-2 py-1.5 text-xs text-slate-200 focus:outline-none">
            <option v-for="m in measureTypes" :key="m.value" :value="m.value">{{ m.label }}</option>
          </select>
        </div>
        <div>
          <label class="block text-[10px] text-slate-500 uppercase mb-1">Target</label>
          <input v-model="item.target_value" type="number" step="any" placeholder="0"
                 class="w-full bg-slate-950/60 border border-slate-850 rounded-lg px-2 py-1.5 text-xs text-slate-200 focus:outline-none" />
        </div>
        <div>
          <label class="block text-[10px] text-slate-500 uppercase mb-1">Unit</label>
          <input v-model="item.unit" type="text" placeholder="%"
                 class="w-full bg-slate-950/60 border border-slate-850 rounded-lg px-2 py-1.5 text-xs text-slate-200 focus:outline-none" />
        </div>
        <div>
          <label class="block text-[10px] text-slate-500 uppercase mb-1">Weight</label>
          <input v-model="item.weight" type="number" step="any" min="0"
                 class="w-full bg-slate-950/60 border border-slate-850 rounded-lg px-2 py-1.5 text-xs text-slate-200 focus:outline-none" />
        </div>
      </div>
    </div>

    <p v-if="typeof errors.items === 'string'" class="text-[11px] text-rose-400">{{ errors.items }}</p>

    <button type="button" @click="addItem"
            class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border border-dashed border-slate-800 text-slate-400 hover:text-blue-400 hover:border-blue-500/40 transition-colors text-sm font-semibold">
      <Icon name="Plus" :size="16" /> Add item
    </button>
  </div>
</template>
