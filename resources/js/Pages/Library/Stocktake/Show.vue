<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import EmptyState from '@/Components/Library/EmptyState.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    stocktake: Object,
    scans: Object,
    mismatches: Number,
    expectedCount: Number,
    unscannedCount: Number,
    progress: Number,
});

const scanForm = useForm({ identifier: '' });
const scanInputRef = ref(null);

function submitScan() {
    scanForm.post(route('library.stocktakes.scan', props.stocktake.id), {
        preserveScroll: true,
        onSuccess: () => {
            scanForm.reset();
            scanInputRef.value?.focus();
        },
    });
}

function completeStocktake() {
    if (confirm('Are you sure you want to complete this stocktake?')) {
        router.post(route('library.stocktakes.complete', props.stocktake.id));
    }
}

function cancelStocktake() {
    if (confirm('Are you sure you want to cancel this stocktake? All scan data will be preserved.')) {
        router.post(route('library.stocktakes.cancel', props.stocktake.id));
    }
}
</script>

<template>
    <Head :title="`Stocktake â€” ${stocktake.campus?.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('library.stocktakes.index')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h1 class="text-base font-semibold text-slate-900 dark:text-white">
                    Stocktake â€” {{ stocktake.campus?.name }}
                </h1>
            </div>
        </template>

        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Progress + stats -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                            Progress: {{ progress }}%
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Started by {{ stocktake.starter?.name }} on {{ new Date(stocktake.started_at).toLocaleDateString() }}
                        </p>
                    </div>
                    <div v-if="stocktake.status === 'in_progress'" class="flex gap-2">
                        <button @click="completeStocktake" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition">
                            âœ“ Complete
                        </button>
                        <button @click="cancelStocktake" class="rounded-lg border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            Cancel
                        </button>
                    </div>
                    <span v-else class="text-sm font-medium px-3 py-1 rounded-full"
                        :class="stocktake.status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">
                        {{ stocktake.status.replace(/_/g, ' ') }}
                    </span>
                </div>

                <!-- Progress bar -->
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-3 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500 ease-out"
                        :class="progress >= 80 ? 'bg-emerald-500' : progress >= 50 ? 'bg-amber-500' : 'bg-indigo-500'"
                        :style="{ width: progress + '%' }"
                    />
                </div>

                <!-- Stats row -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                    <div class="text-center p-3 rounded-xl bg-slate-50 dark:bg-slate-800">
                        <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ expectedCount }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Expected</p>
                    </div>
                    <div class="text-center p-3 rounded-xl bg-slate-50 dark:bg-slate-800">
                        <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ scans?.total ?? 0 }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Scanned</p>
                    </div>
                    <div class="text-center p-3 rounded-xl bg-slate-50 dark:bg-slate-800">
                        <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ unscannedCount }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Unscanned</p>
                    </div>
                    <div class="text-center p-3 rounded-xl bg-slate-50 dark:bg-slate-800">
                        <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ mismatches }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Mismatches</p>
                    </div>
                </div>
            </div>

            <!-- Scan input (only if in progress) -->
            <div v-if="stocktake.status === 'in_progress'" class="rounded-xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/30 p-5">
                <h3 class="text-sm font-semibold text-indigo-900 dark:text-indigo-200 mb-3">
                    ðŸ“± Scan a Copy
                </h3>
                <form @submit.prevent="submitScan" class="flex gap-3">
                    <input
                        ref="scanInputRef"
                        v-model="scanForm.identifier"
                        placeholder="Scan barcode or QR codeâ€¦"
                        class="flex-1 rounded-lg border-indigo-300 dark:border-indigo-700 dark:bg-slate-800 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        autofocus
                    />
                    <button
                        type="submit"
                        :disabled="scanForm.processing || !scanForm.identifier"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition"
                    >
                        Scan
                    </button>
                </form>
                <p v-if="scanForm.errors.identifier" class="text-xs text-red-500 mt-1">{{ scanForm.errors.identifier }}</p>
            </div>

            <!-- Scan log -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Scan Log</h3>
                </div>
                <table v-if="scans?.data?.length" class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase">Book</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase">Barcode</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase">Scanner</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase">Time</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500 uppercase">Match</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="scan in scans.data" :key="scan.id" :class="scan.location_match ? '' : 'bg-red-50/50 dark:bg-red-950/20'">
                            <td class="px-4 py-2 font-medium text-slate-900 dark:text-white">{{ scan.book_copy?.book?.title ?? 'â€”' }}</td>
                            <td class="px-4 py-2 text-slate-500 dark:text-slate-400 font-mono text-xs">{{ scan.book_copy?.barcode ?? 'â€”' }}</td>
                            <td class="px-4 py-2 text-slate-500 dark:text-slate-400">{{ scan.scanner?.name }}</td>
                            <td class="px-4 py-2 text-slate-400 text-xs">{{ new Date(scan.scanned_at).toLocaleTimeString() }}</td>
                            <td class="px-4 py-2 text-center">
                                <span v-if="scan.location_match" class="text-emerald-500">âœ“</span>
                                <span v-else class="text-red-500 font-medium">âœ—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <EmptyState v-else title="No scans yet" description="Start scanning copies to track inventory." />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
