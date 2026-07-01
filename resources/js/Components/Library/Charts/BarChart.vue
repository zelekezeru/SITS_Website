<script setup>
import { computed } from 'vue';

const props = defineProps({
    // [{ label, value }]
    items: { type: Array, default: () => [] },
    barClass: { type: String, default: 'bg-indigo-500' },
});

const max = computed(() => Math.max(1, ...props.items.map((i) => i.value ?? 0)));
</script>

<template>
    <div class="space-y-2.5">
        <p v-if="!items.length" class="py-6 text-center text-xs text-gray-400">No data yet.</p>
        <div v-for="(it, idx) in items" :key="idx" class="flex items-center gap-3">
            <span class="w-32 shrink-0 truncate text-xs text-gray-600 dark:text-gray-400" :title="it.label">{{ it.label }}</span>
            <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                <div class="h-full rounded-full" :class="barClass" :style="{ width: ((it.value ?? 0) / max) * 100 + '%' }" />
            </div>
            <span class="w-8 shrink-0 text-right text-xs font-medium text-gray-700 dark:text-gray-300">{{ it.value }}</span>
        </div>
    </div>
</template>
