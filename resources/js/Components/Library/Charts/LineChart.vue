<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: { type: Array, default: () => [] }, // [{ date, checkout, return }]
});

const maxVal = computed(() => {
    let max = 0;
    props.data.forEach(d => {
        max = Math.max(max, d.checkout ?? 0, d.return ?? 0);
    });
    return max || 1;
});

const width = 100;
const height = 40;
const padding = 2;

function buildPath(key) {
    const len = props.data.length;
    if (len < 2) return '';
    const stepX = (width - padding * 2) / (len - 1);
    return props.data.map((d, i) => {
        const x = padding + i * stepX;
        const y = height - padding - ((d[key] ?? 0) / maxVal.value) * (height - padding * 2);
        return `${i === 0 ? 'M' : 'L'}${x.toFixed(2)},${y.toFixed(2)}`;
    }).join(' ');
}
</script>

<template>
    <div class="w-full overflow-hidden">
        <svg v-if="data.length >= 2" :viewBox="`0 0 ${width} ${height}`" class="w-full h-48" preserveAspectRatio="none">
            <!-- Grid lines -->
            <line v-for="i in 4" :key="i" :x1="padding" :x2="width - padding" :y1="padding + (i-1) * ((height - 2*padding) / 3)" :y2="padding + (i-1) * ((height - 2*padding) / 3)" stroke="currentColor" stroke-width="0.1" class="text-slate-200 dark:text-slate-700" />

            <!-- Checkout line -->
            <path :d="buildPath('checkout')" fill="none" stroke="#6366f1" stroke-width="0.5" stroke-linecap="round" stroke-linejoin="round" />

            <!-- Return line -->
            <path :d="buildPath('return')" fill="none" stroke="#10b981" stroke-width="0.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <div class="flex items-center justify-center gap-4 mt-2 text-xs text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-indigo-500"></span> Checkouts</span>
            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Returns</span>
        </div>
    </div>
</template>
