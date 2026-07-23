<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue'
import TextInput from '@/Components/Library/TextInput.vue'
import InputLabel from '@/Components/Library/InputLabel.vue'
import InputError from '@/Components/Library/InputError.vue'
import PrimaryButton from '@/Components/Library/PrimaryButton.vue'

defineProps({
    books: Array,
})

const form = useForm({
    title: '',
    pdf: null,
    book_id: '',
    visibility: 'role_gated',
})

const submit = () => {
    form.post(route('library.archive.store'), {
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
    <Head title="Upload Digital Copy" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                Upload Secure Digital Copy
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-lg p-8 space-y-6 border border-transparent dark:border-slate-800">
                    <div>
                        <InputLabel for="title" value="Document Title" />
                        <TextInput
                            id="title"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.title"
                            required
                            autofocus
                        />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>

                    <div>
                        <InputLabel for="pdf" value="PDF File" />
                        <input
                            id="pdf"
                            type="file"
                            class="mt-1 block w-full text-sm text-slate-500 dark:text-slate-400
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-xs file:font-semibold
                                file:bg-indigo-50 file:text-indigo-700
                                dark:file:bg-indigo-900/30 dark:file:text-indigo-300
                                hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50"
                            @input="form.pdf = $event.target.files[0]"
                            accept=".pdf"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.pdf" />
                        <p class="mt-1 text-xs text-slate-500">PDF format · up to 500 MB</p>
                        <progress
                            v-if="form.progress"
                            :value="form.progress.percentage"
                            max="100"
                            class="mt-2 w-full h-1.5"
                        >{{ form.progress.percentage }}%</progress>
                    </div>

                    <div>
                        <InputLabel for="book_id" value="Link to Catalog Title (Optional)" />
                        <select
                            id="book_id"
                            v-model="form.book_id"
                            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option value="">No link</option>
                            <option v-for="book in books" :key="book.id" :value="book.id">
                                {{ book.title }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.book_id" />
                    </div>

                    <div>
                        <InputLabel for="visibility" value="Access Level" />
                        <select
                            id="visibility"
                            v-model="form.visibility"
                            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option value="role_gated">Role Gated (Staff Only)</option>
                            <option value="public_authenticated">Authenticated (All logged-in users)</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.visibility" />
                    </div>

                    <div class="flex items-center justify-end mt-4 pt-4 border-t dark:border-slate-800">
                        <Link :href="route('library.archive.index')" class="text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 mr-4 transition">
                            Cancel
                        </Link>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Upload Document
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
