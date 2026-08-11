<script setup>
import { Head } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

/**
 * A printable sheet of QR labels — three across, each with the human-readable
 * name printed directly beneath the code. Deliberately layout-only: no app
 * chrome, so what you see is what comes out of the printer.
 */
defineProps({ labels: Array, type: String });

const printSheet = () => window.print();
</script>

<template>
    <Head title="QR labels" />

    <div class="min-h-screen bg-white text-slate-900">
        <div class="mx-auto max-w-4xl p-6">

            <div class="mb-6 flex items-center justify-between print:hidden">
                <div>
                    <h1 class="text-base font-semibold">QR labels — {{ labels.length }} {{ type }}(s)</h1>
                    <p class="mt-0.5 text-sm text-slate-500">
                        Print on adhesive stock and stick each one on the thing it names.
                    </p>
                </div>
                <button type="button" @click="printSheet"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                    <Icon name="Printer" :size="15" /> Print
                </button>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div v-for="(label, index) in labels" :key="index"
                     class="flex break-inside-avoid flex-col items-center rounded-lg border border-slate-300 p-3 text-center">
                    <img :src="label.qr" :alt="`QR for ${label.name}`" class="h-32 w-32" />
                    <p class="mt-2 text-sm font-bold leading-tight break-words">{{ label.name }}</p>
                    <p v-if="label.sub" class="mt-0.5 text-[11px] leading-tight text-slate-600 break-words">{{ label.sub }}</p>
                </div>
            </div>

            <p v-if="!labels.length" class="rounded-lg border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500">
                Nothing selected to print.
            </p>
        </div>
    </div>
</template>

<style scoped>
@media print {
    @page { margin: 10mm; }
}
</style>
