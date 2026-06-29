<script setup>
import { router } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  modules: { type: Array, default: () => [] },
});
const emit = defineEmits(['close']);

const query = ref('');
const active = ref(0);
const inputEl = ref(null);

const results = computed(() => {
  const q = query.value.trim().toLowerCase();
  const list = props.modules;
  if (!q) return list.slice(0, 8);
  return list
    .filter((m) =>
      m.label.toLowerCase().includes(q) ||
      (m.section || '').toLowerCase().includes(q) ||
      (m.description || '').toLowerCase().includes(q))
    .slice(0, 12);
});

watch(() => props.open, async (isOpen) => {
  if (isOpen) {
    query.value = '';
    active.value = 0;
    await nextTick();
    inputEl.value?.focus();
  }
});

watch(results, () => { active.value = 0; });

const go = (m) => {
  if (!m) return;
  emit('close');
  router.visit(m.path);
};

const onKeydown = (e) => {
  if (e.key === 'ArrowDown') { e.preventDefault(); active.value = (active.value + 1) % Math.max(results.value.length, 1); }
  else if (e.key === 'ArrowUp') { e.preventDefault(); active.value = (active.value - 1 + results.value.length) % Math.max(results.value.length, 1); }
  else if (e.key === 'Enter') { e.preventDefault(); go(results.value[active.value]); }
  else if (e.key === 'Escape') { emit('close'); }
};
</script>

<template>
  <Transition
    enter-active-class="transition duration-150 ease-out"
    enter-from-class="opacity-0"
    leave-active-class="transition duration-100 ease-in"
    leave-to-class="opacity-0"
  >
    <div v-if="open" class="fixed inset-0 z-[60] flex items-start justify-center p-4 pt-[12vh]" @click.self="emit('close')">
      <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm"></div>

      <div class="relative w-full max-w-xl rounded-2xl border border-slate-800 bg-slate-900/95 shadow-2xl overflow-hidden">
        <div class="flex items-center gap-3 px-4 border-b border-slate-800">
          <Icon name="Search" :size="18" class="text-slate-500 shrink-0" />
          <input
            ref="inputEl"
            v-model="query"
            type="text"
            placeholder="Search modules… (try 'payroll', 'kpi', 'users')"
            class="flex-1 bg-transparent py-4 text-sm text-slate-100 placeholder-slate-500 focus:outline-none"
            @keydown="onKeydown"
          />
          <kbd class="hidden sm:block text-[10px] font-semibold text-slate-500 border border-slate-700 rounded px-1.5 py-0.5">ESC</kbd>
        </div>

        <div class="max-h-80 overflow-y-auto p-2">
          <button
            v-for="(m, i) in results"
            :key="m.name"
            type="button"
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left transition-colors"
            :class="i === active ? 'bg-blue-500/15 text-white' : 'text-slate-300 hover:bg-slate-800/60'"
            @mouseenter="active = i"
            @click="go(m)"
          >
            <span class="w-8 h-8 rounded-lg bg-slate-800/80 border border-slate-700 flex items-center justify-center shrink-0">
              <Icon :name="m.icon || 'Dot'" :size="16" />
            </span>
            <span class="flex-1 min-w-0">
              <span class="block text-sm font-medium truncate">{{ m.label }}</span>
              <span class="block text-xs text-slate-500 truncate">{{ m.section }}</span>
            </span>
            <Icon name="ArrowRight" :size="15" class="text-slate-600 shrink-0" />
          </button>

          <p v-if="!results.length" class="text-center text-sm text-slate-500 py-8">No modules match “{{ query }}”.</p>
        </div>

        <div class="flex items-center justify-between px-4 py-2.5 border-t border-slate-800 text-[11px] text-slate-500">
          <span class="flex items-center gap-1.5"><Icon name="Command" :size="13" /> Quick navigation</span>
          <span>↑↓ to move · ↵ to open</span>
        </div>
      </div>
    </div>
  </Transition>
</template>
