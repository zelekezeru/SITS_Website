<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import TitleForm from './Form.vue';

defineProps({ options: Object });

const form = useForm({
    code: '',
    title: '',
    subtitle: '',
    description: '',
    author: '',
    edition: '',
    isbn: '',
    course_id: null,
    course_code: '',
    course_name: '',
    program_id: null,
    language: 'am',
    study_mode_id: null,
    page_count: null,
    unit_price: 0,
    unit_cost: null,
    reorder_level: 20,
    reorder_quantity: null,
    is_active: true,
    notes: '',
});
</script>

<template>
    <Head title="New book title" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-slate-900 dark:text-white">New book title</h1>
        </template>

        <form @submit.prevent="form.post(route('bookstore.titles.store'))" class="p-6 max-w-4xl mx-auto space-y-6">
            <TitleForm :form="form" :options="options" />

            <div class="flex items-center justify-end gap-3">
                <Link :href="route('bookstore.titles.index')"
                      class="rounded-lg border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancel
                </Link>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition">
                    Create title
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
