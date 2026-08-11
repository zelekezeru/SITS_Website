<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import RequestForm from './Form.vue';

defineProps({ options: Object });

const form = useForm({
    destination_type: 'center',
    center_id: '',
    campus_id: '',
    student_count: 0,
    contact_name: '',
    contact_phone: '',
    needed_by: '',
    notes: '',
    items: [{ book_title_id: '', quantity_requested: 1, remark: '' }],
});
</script>

<template>
    <Head title="New book request" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-slate-900 dark:text-white">New book request</h1>
        </template>

        <form @submit.prevent="form.post(route('bookstore.requests.store'))" class="p-6 max-w-5xl mx-auto space-y-6">
            <RequestForm :form="form" :options="options" />

            <div class="flex items-center justify-between gap-3">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Saved as a draft first — nothing is reserved until you submit it and it is verified.
                </p>
                <div class="flex items-center gap-3">
                    <Link :href="route('bookstore.requests.index')"
                          class="rounded-lg border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition">
                        Save draft
                    </button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
