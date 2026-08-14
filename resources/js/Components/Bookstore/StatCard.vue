<script setup>
import Icon from '@/Components/Icon.vue';

defineProps({
    label: String,
    value: [String, Number],
    sub: String,
    icon: { type: String, default: 'Package' },
    tone: { type: String, default: 'slate' }, // slate | indigo | emerald | amber | rose
});

const tones = {
    slate:   'bg-slate-500/10 text-slate-600 dark:text-slate-300',
    indigo:  'bg-indigo-500/10 text-indigo-600 dark:text-indigo-300',
    emerald: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300',
    amber:   'bg-amber-500/10 text-amber-600 dark:text-amber-300',
    rose:    'bg-rose-500/10 text-rose-600 dark:text-rose-300',
};
</script>

<template>
    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 truncate">{{ label }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ value }}</p>
                <p v-if="sub" class="mt-0.5 text-xs text-slate-400 dark:text-slate-500 truncate">{{ sub }}</p>
            </div>
            <span class="shrink-0 grid h-9 w-9 place-items-center rounded-lg" :class="tones[tone]">
                <Icon :name="icon" :size="18" />
            </span>
        </div>
    </div>
</template>
