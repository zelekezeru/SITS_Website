<script setup>
import { computed, watch } from 'vue';
import Icon from '@/Components/Icon.vue';

/**
 * The request form — the header block and lines of the paper
 * "የመጽሃፍት መጠየቂያ ቅጽ". Shared by Create and Edit.
 */
const props = defineProps({
    form: { type: Object, required: true },
    options: { type: Object, required: true },
});

const field = 'mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-xs font-medium text-slate-600 dark:text-slate-400';

// Picking a centre fills the coordinator and student count from its record,
// exactly as the paper header block is filled in.
watch(() => props.form.center_id, (id) => {
    const centre = props.options.centers.find((c) => c.id === Number(id));
    if (!centre) return;

    props.form.student_count = centre.student_count;
    props.form.contact_name = props.form.contact_name || centre.coordinator_name;
    props.form.contact_phone = props.form.contact_phone || centre.coordinator_phone;
});

const titleFor = (id) => props.options.titles.find((t) => t.id === Number(id));

function addLine() {
    props.form.items.push({ book_title_id: '', quantity_requested: 1, remark: '' });
}

function removeLine(index) {
    props.form.items.splice(index, 1);
}

const lineTotal = (line) => {
    const title = titleFor(line.book_title_id);
    return title ? title.unit_price * (Number(line.quantity_requested) || 0) : 0;
};

const total = computed(() => props.form.items.reduce((sum, line) => sum + lineTotal(line), 0));
const totalBooks = computed(() => props.form.items.reduce((sum, l) => sum + (Number(l.quantity_requested) || 0), 0));

// A request is sized per student; more books than students is usually a typo.
const overStudentCount = computed(() =>
    props.form.student_count > 0 && props.form.items.some((l) => Number(l.quantity_requested) > props.form.student_count));

const money = (v) => Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <div class="space-y-6">

        <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Who the books are for</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label :class="label">Destination <span class="text-rose-500">*</span></label>
                    <select v-model="form.destination_type" :class="field" required>
                        <option value="center">Distribution centre</option>
                        <option value="campus">Campus</option>
                    </select>
                </div>

                <div v-if="form.destination_type === 'center'">
                    <label :class="label">Centre <span class="text-rose-500">*</span></label>
                    <select v-model="form.center_id" :class="field" required>
                        <option value="">Choose a centre…</option>
                        <option v-for="c in options.centers" :key="c.id" :value="c.id">{{ c.name }} ({{ c.code }})</option>
                    </select>
                    <p v-if="form.errors.center_id" class="mt-1 text-xs text-rose-500">{{ form.errors.center_id }}</p>
                </div>

                <div v-else>
                    <label :class="label">Campus <span class="text-rose-500">*</span></label>
                    <select v-model="form.campus_id" :class="field" required>
                        <option value="">Choose a campus…</option>
                        <option v-for="c in options.campuses" :key="c.id" :value="c.id">{{ c.name || c.name_en }}</option>
                    </select>
                    <p v-if="form.errors.campus_id" class="mt-1 text-xs text-rose-500">{{ form.errors.campus_id }}</p>
                </div>

                <div>
                    <label :class="label">Verified students <span class="text-rose-500">*</span></label>
                    <input v-model="form.student_count" type="number" min="0" :class="field" required />
                    <p class="mt-1 text-xs text-slate-400">The quantity is checked against this.</p>
                </div>

                <div>
                    <label :class="label">Needed by</label>
                    <input v-model="form.needed_by" type="date" :class="field" />
                </div>

                <div>
                    <label :class="label">Coordinator / contact</label>
                    <input v-model="form.contact_name" type="text" :class="field" />
                </div>

                <div>
                    <label :class="label">Mobile</label>
                    <input v-model="form.contact_phone" type="text" :class="field" />
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Books requested</h2>
                <button type="button" @click="addLine"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-700 px-2.5 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    <Icon name="Plus" :size="14" /> Add book
                </button>
            </div>

            <div v-if="overStudentCount"
                 class="mt-3 flex items-center gap-2 rounded-lg border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 px-3 py-2">
                <Icon name="AlertTriangle" :size="15" class="text-amber-600 dark:text-amber-400 shrink-0" />
                <p class="text-xs text-amber-900 dark:text-amber-200">
                    A line asks for more copies than there are students ({{ form.student_count }}). Check before submitting.
                </p>
            </div>

            <div class="mt-4 space-y-3">
                <div v-for="(line, index) in form.items" :key="index"
                     class="grid gap-3 sm:grid-cols-12 items-start rounded-lg border border-slate-200 dark:border-slate-800 p-3">
                    <div class="sm:col-span-5">
                        <label :class="label">Book / course</label>
                        <select v-model="line.book_title_id" :class="field" required>
                            <option value="">Choose a book…</option>
                            <option v-for="t in options.titles" :key="t.id" :value="t.id">
                                {{ t.code }} — {{ t.title }} ({{ t.available }} available)
                            </option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label :class="label">Quantity</label>
                        <input v-model="line.quantity_requested" type="number" min="1" :class="field" required />
                    </div>

                    <div class="sm:col-span-3">
                        <label :class="label">Remark</label>
                        <input v-model="line.remark" type="text" :class="field" />
                    </div>

                    <div class="sm:col-span-2 flex items-end justify-between gap-2 h-full pt-5">
                        <span class="text-sm font-medium tabular-nums text-slate-800 dark:text-slate-200">{{ money(lineTotal(line)) }}</span>
                        <button type="button" @click="removeLine(index)"
                                class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 transition"
                                aria-label="Remove line">
                            <Icon name="Trash2" :size="15" />
                        </button>
                    </div>

                    <p v-if="titleFor(line.book_title_id) && Number(line.quantity_requested) > titleFor(line.book_title_id).available"
                       class="sm:col-span-12 text-xs text-amber-600 dark:text-amber-400">
                        Only {{ titleFor(line.book_title_id).available }} available right now — the verifier may approve a smaller quantity.
                    </p>
                </div>

                <p v-if="!form.items.length" class="rounded-lg border border-dashed border-slate-300 dark:border-slate-700 px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                    No books yet. Add the first line.
                </p>
            </div>

            <div class="mt-4 flex items-center justify-end gap-6 border-t border-slate-200 dark:border-slate-800 pt-4">
                <div class="text-right">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Total books</p>
                    <p class="text-lg font-semibold tabular-nums text-slate-900 dark:text-white">{{ totalBooks }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Total amount</p>
                    <p class="text-lg font-semibold tabular-nums text-slate-900 dark:text-white">{{ money(total) }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <label :class="label">Notes</label>
            <textarea v-model="form.notes" rows="3" :class="field" placeholder="Anything the verifier or store should know."></textarea>
        </section>
    </div>
</template>
