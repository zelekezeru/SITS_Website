<script setup>
import { watch } from 'vue';

/**
 * The title form, shared by Create and Edit so the two can never drift.
 * `form` is an Inertia useForm instance owned by the parent page.
 */
const props = defineProps({
    form: { type: Object, required: true },
    options: { type: Object, required: true },
});

// Keep the denormalised course name in step when the course changes: a book
// printed under an old course must still print with the name it carried.
watch(() => props.form.course_id, (id) => {
    const course = props.options.courses.find((c) => c.id === Number(id));
    if (course) props.form.course_name = course.title;
});

const field = 'mt-1 w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-xs font-medium text-slate-600 dark:text-slate-400';
</script>

<template>
    <div class="space-y-6">

        <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">The book</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label :class="label">Title <span class="text-rose-500">*</span></label>
                    <input v-model="form.title" type="text" :class="field" required />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-rose-500">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label :class="label">Subtitle</label>
                    <input v-model="form.subtitle" type="text" :class="field" />
                </div>

                <div>
                    <label :class="label">Code</label>
                    <input v-model="form.code" type="text" :class="field" placeholder="Auto-generated, e.g. SM-02" />
                    <p class="mt-1 text-xs text-slate-400">Printed under the QR label and on the shelf sticker.</p>
                    <p v-if="form.errors.code" class="mt-1 text-xs text-rose-500">{{ form.errors.code }}</p>
                </div>

                <div>
                    <label :class="label">Author</label>
                    <input v-model="form.author" type="text" :class="field" />
                </div>

                <div>
                    <label :class="label">Edition</label>
                    <input v-model="form.edition" type="text" :class="field" />
                </div>

                <div>
                    <label :class="label">ISBN</label>
                    <input v-model="form.isbn" type="text" :class="field" />
                </div>

                <div>
                    <label :class="label">Pages</label>
                    <input v-model="form.page_count" type="number" min="1" :class="field" />
                </div>

                <div class="sm:col-span-2">
                    <label :class="label">Description</label>
                    <textarea v-model="form.description" rows="3" :class="field"></textarea>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Course &amp; category</h2>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                Programme, language and study mode are the three axes every stock report pivots on.
            </p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label :class="label">Course</label>
                    <select v-model="form.course_id" :class="field">
                        <option :value="null">—</option>
                        <option v-for="c in options.courses" :key="c.id" :value="c.id">{{ c.title }}</option>
                    </select>
                </div>

                <div>
                    <label :class="label">Course code</label>
                    <input v-model="form.course_code" type="text" :class="field" />
                </div>

                <div>
                    <label :class="label">Programme of study</label>
                    <select v-model="form.program_id" :class="field">
                        <option :value="null">—</option>
                        <option v-for="p in options.programs" :key="p.id" :value="p.id">{{ p.title }}</option>
                    </select>
                </div>

                <div>
                    <label :class="label">Study mode</label>
                    <select v-model="form.study_mode_id" :class="field">
                        <option :value="null">—</option>
                        <option v-for="m in options.studyModes" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>

                <div>
                    <label :class="label">Language <span class="text-rose-500">*</span></label>
                    <select v-model="form.language" :class="field" required>
                        <option v-for="l in options.languages" :key="l.value" :value="l.value">{{ l.label }}</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Pricing &amp; reordering</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label :class="label">Selling price <span class="text-rose-500">*</span></label>
                    <input v-model="form.unit_price" type="number" step="0.01" min="0" :class="field" required />
                    <p v-if="form.errors.unit_price" class="mt-1 text-xs text-rose-500">{{ form.errors.unit_price }}</p>
                </div>

                <div>
                    <label :class="label">Print cost</label>
                    <input v-model="form.unit_cost" type="number" step="0.01" min="0" :class="field" />
                    <p class="mt-1 text-xs text-slate-400">Recalculated from each print run.</p>
                </div>

                <div>
                    <label :class="label">Reorder level <span class="text-rose-500">*</span></label>
                    <input v-model="form.reorder_level" type="number" min="0" :class="field" required />
                    <p class="mt-1 text-xs text-slate-400">Alerts fire at or below this.</p>
                </div>

                <div>
                    <label :class="label">Reprint quantity</label>
                    <input v-model="form.reorder_quantity" type="number" min="1" :class="field" />
                </div>

                <div class="sm:col-span-2 lg:col-span-4">
                    <label class="inline-flex items-center gap-2">
                        <input v-model="form.is_active" type="checkbox"
                               class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-sm text-slate-700 dark:text-slate-300">Active — may be requested and dispatched</span>
                    </label>
                </div>

                <div class="sm:col-span-2 lg:col-span-4">
                    <label :class="label">Notes</label>
                    <textarea v-model="form.notes" rows="2" :class="field"></textarea>
                </div>
            </div>
        </section>
    </div>
</template>
