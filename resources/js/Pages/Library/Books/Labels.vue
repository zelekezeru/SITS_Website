<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    books: Array,
});
</script>

<template>
    <Head title="Print Labels" />
    
    <div class="print-container">
        <!-- Optional Print Button (hidden when actually printing) -->
        <div class="no-print p-4 bg-slate-100 flex justify-between items-center mb-8 shadow-sm">
            <h1 class="text-lg font-bold text-slate-800">Print QR Labels ({{ books.length }})</h1>
            <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 text-white rounded font-medium hover:bg-indigo-700">Print Now</button>
        </div>

        <div class="labels-grid">
            <div v-for="book in books" :key="book.id" class="label-card">
                <div class="label-content">
                    <img :src="route('library.books.qr', book.id)" class="qr-img" alt="QR Code" />
                    <div class="label-text">
                        <div class="book-title">{{ book.title }}</div>
                        <div class="book-hash">{{ book.tracking_hash.substring(0, 8) }}...</div>
                        <div class="lib-name">SITS Library</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* A4 specific print styles */
@media print {
    @page { margin: 0.5cm; size: A4 portrait; }
    .no-print { display: none !important; }
    body { background: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}

.print-container {
    width: 100%;
    background: #fff;
    min-height: 100vh;
}

.labels-grid {
    display: grid;
    /* Typically 3x8 or similar for standard label sheets like Avery 5160, adjusting for roughly 2.625" x 1" */
    /* Let's use a 3-column grid layout */
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5cm;
    padding: 0.5cm;
}

.label-card {
    border: 1px dashed #ccc; /* Guide lines for cutting/peeling */
    padding: 10px;
    height: 3.5cm; /* approximate height per label */
    display: flex;
    align-items: center;
    page-break-inside: avoid;
}

.label-content {
    display: flex;
    align-items: center;
    gap: 15px;
    width: 100%;
}

.qr-img {
    width: 2.5cm;
    height: 2.5cm;
    object-fit: contain;
}

.label-text {
    flex: 1;
    overflow: hidden;
    font-family: sans-serif;
}

.book-title {
    font-weight: bold;
    font-size: 11px;
    line-height: 1.2;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.book-hash {
    font-family: monospace;
    font-size: 9px;
    color: #555;
}

.lib-name {
    font-size: 9px;
    text-transform: uppercase;
    margin-top: 4px;
    color: #333;
    font-weight: 600;
}
</style>
