<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    book: Object,
    campuses: Array,
});

const isEditing = computed(() => !!props.book.id);

const form = useForm({
    title: props.book.title || '',
    authors: props.book.authors || '',
    isbn: props.book.isbn || '',
    copy_number: props.book.copy_number || 1,
    status: props.book.status || 'available',
    current_shelf_box_id: props.book.current_shelf_box_id || '',
});

const submit = () => {
    if (isEditing.value) {
        form.patch(route('library.books.update', props.book.id));
    } else {
        form.post(route('library.books.store'));
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Book' : 'Add Book'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isEditing ? 'Edit Book: ' + book.title : 'Add New Book' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input v-model="form.title" type="text" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required />
                            <div v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Authors (comma separated)</label>
                            <input v-model="form.authors" type="text" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required />
                            <div v-if="form.errors.authors" class="text-red-600 text-sm mt-1">{{ form.errors.authors }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">ISBN</label>
                                <input v-model="form.isbn" type="text" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                <div v-if="form.errors.isbn" class="text-red-600 text-sm mt-1">{{ form.errors.isbn }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Copy Number</label>
                                <input v-model="form.copy_number" type="number" min="1" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required />
                                <div v-if="form.errors.copy_number" class="text-red-600 text-sm mt-1">{{ form.errors.copy_number }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <select v-model="form.status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="available">Available</option>
                                    <option value="checked_out">Checked Out</option>
                                    <option value="withdrawn">Withdrawn</option>
                                    <option value="lost">Lost</option>
                                    <option value="in_transit">In Transit</option>
                                </select>
                                <div v-if="form.errors.status" class="text-red-600 text-sm mt-1">{{ form.errors.status }}</div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Location (Shelf Box)</label>
                                <select v-model="form.current_shelf_box_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">-- Unassigned --</option>
                                    <optgroup v-for="campus in campuses" :key="campus.id" :label="campus.name">
                                        <template v-for="floor in campus.floors" :key="floor.id">
                                            <template v-for="row in floor.rows" :key="row.id">
                                                <option v-for="box in row.shelf_boxes" :key="box.id" :value="box.id">
                                                    {{ campus.code }} › {{ floor.name }} › {{ box.label }}
                                                </option>
                                            </template>
                                        </template>
                                    </optgroup>
                                </select>
                                <div v-if="form.errors.current_shelf_box_id" class="text-red-600 text-sm mt-1">{{ form.errors.current_shelf_box_id }}</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                            <Link :href="route('library.books.index')" class="text-gray-600 hover:text-gray-900 font-medium">Cancel</Link>
                            <button type="submit" :disabled="form.processing" class="inline-flex items-center px-6 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50 transition ease-in-out duration-150">
                                {{ isEditing ? 'Update Book' : 'Save Book' }}
                            </button>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
