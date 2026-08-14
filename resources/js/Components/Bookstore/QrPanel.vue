<script setup>
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

/**
 * A QR code with its human-readable name printed directly beneath — the half a
 * store keeper actually reads. Prints on its own, and links out to the bulk
 * label sheet for a whole rack.
 */
const props = defineProps({
    type: { type: String, required: true },   // title | store | shelf | section | waybill
    id: { type: [Number, String], required: true },
    name: { type: String, required: true },
    sub: { type: String, default: null },
    size: { type: Number, default: 220 },
});

const imageUrl = computed(() => route('bookstore.labels.png', { type: props.type, id: props.id, size: props.size }));
const sheetUrl = computed(() => route('bookstore.labels.sheet', { type: props.type, 'ids[]': props.id }));

function printLabel() {
    const win = window.open('', '_blank', 'width=420,height=560');
    if (!win) return;

    win.document.write(`
        <html><head><title>${props.name}</title>
        <style>
            body { font-family: system-ui, sans-serif; text-align: center; margin: 24px; }
            img { width: 240px; height: 240px; }
            .name { font-size: 17px; font-weight: 700; margin-top: 10px; }
            .sub { font-size: 12px; color: #555; margin-top: 3px; }
            @media print { @page { margin: 8mm; } }
        </style></head>
        <body onload="window.print()">
            <img src="${imageUrl.value}" alt="QR">
            <div class="name">${props.name}</div>
            ${props.sub ? `<div class="sub">${props.sub}</div>` : ''}
        </body></html>
    `);
    win.document.close();
}
</script>

<template>
    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 text-center">
        <img
            :src="imageUrl"
            :alt="`QR code for ${name}`"
            class="mx-auto rounded-lg bg-white p-2"
            :width="size"
            :height="size"
        />
        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white break-words">{{ name }}</p>
        <p v-if="sub" class="text-xs text-slate-500 dark:text-slate-400 break-words">{{ sub }}</p>

        <div class="mt-3 flex items-center justify-center gap-2">
            <button
                type="button"
                @click="printLabel"
                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 transition"
            >
                <Icon name="Printer" :size="14" /> Print label
            </button>
            <a
                :href="imageUrl"
                download
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
            >
                <Icon name="Download" :size="14" /> Download
            </a>
            <a
                :href="sheetUrl"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
            >
                <Icon name="Copy" :size="14" /> Sheet
            </a>
        </div>
    </div>
</template>
