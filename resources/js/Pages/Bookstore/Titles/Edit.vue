<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import TitleForm from './Form.vue';

const props = defineProps({ title: Object, options: Object });

const form = useForm({
    code: props.title.code,
    title: props.title.title,
    subtitle: props.title.subtitle,
    description: props.title.description,
    author: props.title.author,
    edition: props.title.edition,
    isbn: props.title.isbn,
    course_id: props.title.course_id,
    course_code: props.title.course_code,
    course_name: props.title.course_name,
    program_id: props.title.program_id,
    language: props.title.language,
    study_mode_id: props.title.study_mode_id,
    page_count: props.title.page_count,
    unit_price: props.title.unit_price,
    unit_cost: props.title.unit_cost,
    reorder_level: props.title.reorder_level,
    reorder_quantity: props.title.reorder_quantity,
    is_active: props.title.is_active,
    notes: props.title.notes,
});
</script>

<template>
    <Head :title="`Edit ${title.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-slate-900 dark:text-white">Edit {{ title.title }}</h1>
        </template>

        <form @submit.prevent="form.put(route('bookstore.titles.update', title.id))" class="p-6 max-w-4xl mx-auto space-y-6">
            <TitleForm :form="form" :options="options" />

            <div class="flex items-center justify-end gap-3">
                <Link :href="route('bookstore.titles.show', title.id)"
                      class="rounded-lg border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancel
                </Link>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition">
                    Save changes
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
