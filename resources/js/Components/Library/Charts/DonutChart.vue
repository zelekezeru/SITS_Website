<script setup>
import { computed } from 'vue';

const props = defineProps({
    // [{ label, value, color }]  color = CSS color string
    segments: { type: Array, default: () => [] },
});

const R = 42;
const CIRC = 2 * Math.PI * R;

const total = computed(() =>
    Math.max(1, props.segments.reduce((s, x) => s + (x.value ?? 0), 0))
);

const arcs = computed(() => {
    let offset = 0;
    return props.segments
        .filter((s) => (s.value ?? 0) > 0)
        .map((s) => {
            const len = ((s.value ?? 0) / total.value) * CIRC;
            const arc = { ...s, dash: `${len} ${CIRC - len}`, offset: -offset };
            offset += len;
            return arc;
        });
});

const grandTotal = computed(() => props.segments.reduce((s, x) => s + (x.value ?? 0), 0));
</script>

<template>
    <div class="flex items-center gap-5">
        <div class="relative shrink-0">
            <svg viewBox="0 0 100 100" class="h-28 w-28 -rotate-90">
                <circle cx="50" cy="50" :r="R" fill="none" stroke="currentColor" class="text-slate-100 dark:text-slate-800" stroke-width="12" />
                <circle
                    v-for="(a, i) in arcs"
                    :key="i"
                    cx="50" cy="50" :r="R"
                    fill="none"
                    :stroke="a.color"
                    stroke-width="12"
                    :stroke-dasharray="a.dash"
                    :stroke-dashoffset="a.offset"
                />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-lg font-bold text-slate-900 dark:text-white">{{ grandTotal }}</span>
                <span class="text-[10px] text-slate-400">total</span>
            </div>
        </div>
        <ul class="flex-1 space-y-1.5">
            <li v-for="(s, i) in segments" :key="i" class="flex items-center gap-2 text-xs">
                <span class="h-2.5 w-2.5 rounded-sm" :style="{ background: s.color }" />
                <span class="flex-1 capitalize text-slate-600 dark:text-slate-400">{{ s.label }}</span>
                <span class="font-medium text-slate-800 dark:text-slate-200">{{ s.value }}</span>
            </li>
        </ul>
    </div>
</template>
