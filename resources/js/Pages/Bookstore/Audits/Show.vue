<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import StatCard from '@/Components/Bookstore/StatCard.vue';
import StatusBadge from '@/Components/Bookstore/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    audit: Object,
    lines: Array,
    focusedSection: Number,
    sections: Array,
    summary: Object,
});

const counts = ref(Object.fromEntries(props.lines.map((l) => [l.id, l.counted_quantity])));
const notes = ref(Object.fromEntries(props.lines.map((l) => [l.id, l.note ?? ''])));

const editable = computed(() => props.audit.status === 'in_progress');

function saveCount(line) {
    router.post(route('bookstore.audits.count', line.id), {
        counted_quantity: counts.value[line.id] ?? 0,
        note: notes.value[line.id],
    }, { preserveScroll: true, preserveState: true });
}

const variance = (line) =>
    counts.value[line.id] === null || counts.value[line.id] === undefined || counts.value[line.id] === ''
        ? null
        : Number(counts.value[line.id]) - line.system_quantity;

function focusSection(id) {
    router.get(route('bookstore.audits.show', props.audit.id), id ? { section: id } : {}, {
        preserveState: true, replace: true,
    });
}

const colors = { draft: 'gray', in_progress: 'blue', completed: 'amber', approved: 'green', cancelled: 'rose' };
</script>

<template>
    <Head :title="audit.reference" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="truncate text-base font-semibold text-slate-900 dark:text-white">{{ audit.reference }}</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ audit.store_room?.name }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <StatusBadge :label="audit.status.replace(/_/g, ' ')" :color="colors[audit.status]" />
                    <button v-if="audit.status === 'in_progress'" type="button"
                            @click="router.post(route('bookstore.audits.complete', audit.id), {}, { preserveScroll: true })"
                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                        Finish counting
                    </button>
                    <button v-if="audit.status === 'completed'" type="button"
                            @click="router.post(route('bookstore.audits.approve', audit.id), {}, { preserveScroll: true })"
                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700 transition">
                        Approve variance
                    </button>
                </div>
            </div>
        </template>

        <div class="p-6 max-w-6xl mx-auto space-y-6">

            <div v-if="audit.status === 'completed'"
                 class="flex items-center gap-3 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 px-4 py-3">
                <Icon name="AlertTriangle" :size="18" class="text-amber-600 dark:text-amber-400 shrink-0" />
                <p class="text-sm text-amber-900 dark:text-amber-200">
                    Counting is finished. Nothing has moved yet — approving posts {{ summary.variances }} correction(s) to the ledger.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-4">
                <StatCard label="Lines" :value="summary.total" icon="ListChecks" tone="slate" />
                <StatCard label="Counted" :value="summary.counted" :sub="`${summary.progress}% done`" icon="ClipboardCheck" tone="indigo" />
                <StatCard label="Variances" :value="summary.variances" icon="AlertTriangle"
                          :tone="summary.variances > 0 ? 'amber' : 'emerald'" />
                <StatCard label="Started by" :value="audit.started_by?.name ?? '—'" icon="User" tone="slate" />
            </div>

            <!-- Scanning a section QR lands here with it already focused. -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Section:</span>
                <button type="button" @click="focusSection(null)"
                        class="rounded-lg px-2.5 py-1 text-xs font-medium transition"
                        :class="!focusedSection ? 'bg-indigo-600 text-white' : 'border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'">
                    All
                </button>
                <button v-for="s in sections" :key="s.id" type="button" @click="focusSection(s.id)"
                        class="rounded-lg px-2.5 py-1 text-xs font-medium transition"
                        :class="focusedSection === s.id ? 'bg-indigo-600 text-white' : 'border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'">
                    {{ s.code }}
                </button>
                <Link :href="route('bookstore.scan.index')"
                      class="ml-auto inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-2.5 py-1 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    <Icon name="QrCode" :size="14" /> Scan a section
                </Link>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-medium">Section</th>
                                <th class="px-4 py-2.5 text-left font-medium">Book</th>
                                <th class="px-4 py-2.5 text-right font-medium">System</th>
                                <th class="px-4 py-2.5 text-right font-medium">Counted</th>
                                <th class="px-4 py-2.5 text-right font-medium">Variance</th>
                                <th class="px-4 py-2.5 text-left font-medium">Note</th>
                                <th class="px-4 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="line in lines" :key="line.id">
                                <td class="px-4 py-2.5 font-mono text-xs text-slate-500 dark:text-slate-400">{{ line.shelf_section?.code }}</td>
                                <td class="px-4 py-2.5 text-slate-800 dark:text-slate-200">
                                    {{ line.book_title?.title }}
                                    <span class="text-xs text-slate-400">({{ line.book_title?.code }})</span>
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400">{{ line.system_quantity }}</td>
                                <td class="px-4 py-2.5 text-right">
                                    <input v-if="editable" v-model="counts[line.id]" type="number" min="0"
                                           class="w-20 rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-right text-sm tabular-nums focus:border-indigo-500 focus:ring-indigo-500" />
                                    <span v-else class="tabular-nums">{{ line.counted_quantity ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums font-medium"
                                    :class="variance(line) === null ? 'text-slate-400'
                                        : variance(line) === 0 ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-rose-600 dark:text-rose-400'">
                                    <template v-if="variance(line) === null">—</template>
                                    <template v-else>{{ variance(line) > 0 ? '+' : '' }}{{ variance(line) }}</template>
                                </td>
                                <td class="px-4 py-2.5">
                                    <input v-if="editable" v-model="notes[line.id]" type="text"
                                           class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <span v-else class="text-xs text-slate-500 dark:text-slate-400">{{ line.note || '' }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <button v-if="editable" type="button" @click="saveCount(line)"
                                            class="rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                                        Save
                                    </button>
                                    <span v-else-if="line.counted_by" class="text-xs text-slate-400">{{ line.counted_by.name }}</span>
                                </td>
                            </tr>
                            <tr v-if="!lines.length">
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                    Nothing to count in this store room.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
