<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import RequestForm from './Form.vue';

const props = defineProps({ request: Object, options: Object });

const form = useForm({
    destination_type: props.request.destination_type,
    center_id: props.request.center_id ?? '',
    campus_id: props.request.campus_id ?? '',
    student_count: props.request.student_count,
    contact_name: props.request.contact_name ?? '',
    contact_phone: props.request.contact_phone ?? '',
    needed_by: props.request.needed_by ?? '',
    notes: props.request.notes ?? '',
    items: props.request.items.map((item) => ({
        book_title_id: item.book_title_id,
        quantity_requested: item.quantity_requested,
        remark: item.remark ?? '',
    })),
});
</script>

<template>
    <Head :title="`Edit ${request.request_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-slate-900 dark:text-white">Edit {{ request.request_number }}</h1>
        </template>

        <form @submit.prevent="form.put(route('bookstore.requests.update', request.id))" class="p-6 max-w-5xl mx-auto space-y-6">
            <RequestForm :form="form" :options="options" />

            <div class="flex items-center justify-end gap-3">
                <Link :href="route('bookstore.requests.show', request.id)"
                      class="rounded-lg border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancel
                </Link>
                <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition">
                    Save draft
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
