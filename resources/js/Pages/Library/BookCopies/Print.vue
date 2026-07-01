<script setup>
import { onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    copies: Array,
});

onMounted(() => {
    // Auto-print prompt
    if (props.copies && props.copies.length > 0) {
        setTimeout(() => window.print(), 500);
    }
});
</script>

<template>
    <Head title="Print Labels" />

    <!-- Print styling specifically designed for a standard 3x8 or 3x10 label sheet (e.g., Avery 5160) -->
    <div class="print-container">
        <div v-for="copy in copies" :key="copy.id" class="label">
            <div class="flex flex-row items-center w-full h-full p-2 space-x-2">
                <img :src="route('library.copies.qr', copy.id)" class="h-16 w-16" />
                <div class="flex-1 flex flex-col justify-center overflow-hidden">
                    <div class="text-[10px] font-bold truncate">{{ copy.book.title }}</div>
                    <div class="text-[8px] text-gray-600 truncate mt-1">ID: {{ copy.id }}</div>
                    <div class="text-[8px] text-gray-600 font-mono mt-1">{{ copy.barcode || copy.tracking_hash.substring(0,8) }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    @page { margin: 0.5in; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    
    .print-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        grid-auto-rows: 1in;
        gap: 0.1in;
    }

    .label {
        border: 1px dashed #ccc; /* Helps with alignment, won't print strongly */
        page-break-inside: avoid;
    }
}

/* Screen preview styling */
@media screen {
    .print-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(2.625in, 1fr));
        gap: 1rem;
        padding: 2rem;
        background: #f3f4f6;
        min-height: 100vh;
    }

    .label {
        background: white;
        height: 1in;
        border: 1px solid #d1d5db;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-radius: 0.125rem;
    }
}
</style>
