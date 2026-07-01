<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import { ref } from 'vue';
import axios from 'axios';

const form = useForm({
    title: '',
    subtitle: '',
    isbn: '',
    call_number: '',
    classification_type: 'dewey',
    publisher: '',
    published_year: '',
    edition: '',
    page_count: '',
    language: 'en',
    subject: '',
    description: '',
    cover_url: '',
    notes: '',
});

const isSearchingIsbn = ref(false);

const lookupIsbn = async () => {
    if (!form.isbn) return;
    isSearchingIsbn.value = true;
    try {
        const response = await axios.post(route('library.isbn.lookup'), { isbn: form.isbn });
        if (response.data.success) {
            const data = response.data.data;
            form.title = data.title || '';
            form.subtitle = data.subtitle || '';
            form.publisher = data.publisher || '';
            form.published_year = data.published_year || '';
            form.page_count = data.page_count || '';
            form.subject = data.subject || '';
            form.cover_url = data.cover_url || '';
            form.description = data.description || '';
        }
    } catch (error) {
        console.error(error);
        alert(error.response?.data?.message || 'ISBN not found. Please fill in details manually.');
    } finally {
        isSearchingIsbn.value = false;
    }
};

const submit = () => {
    form.post(route('library.books.store'));
};
</script>

<template>
    <Head title="Add New Title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('library.catalog.index')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition">&larr; Back to Catalog</Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight border-l pl-4 border-gray-300 dark:border-gray-700">
                    Add New Title
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg p-8 border border-transparent dark:border-gray-800">
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title <span class="text-red-500">*</span></label>
                            <input v-model="form.title" type="text" class="mt-1 block w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required />
                            <div v-if="form.errors.title" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.title }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subtitle</label>
                            <input v-model="form.subtitle" type="text" class="mt-1 block w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            <div v-if="form.errors.subtitle" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.subtitle }}</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ISBN</label>
                                <div class="mt-1 flex gap-2">
                                    <input v-model="form.isbn" type="text" placeholder="e.g. 9780132350884" class="block w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                    <button 
                                        type="button" 
                                        @click="lookupIsbn"
                                        :disabled="isSearchingIsbn || !form.isbn"
                                        class="px-4 py-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800 rounded-md text-xs font-bold uppercase hover:bg-indigo-100 dark:hover:bg-indigo-900/50 disabled:opacity-50 transition active:scale-95 whitespace-nowrap"
                                    >
                                        {{ isSearchingIsbn ? 'Searching...' : 'Auto-fill' }}
                                    </button>
                                </div>
                                <div v-if="form.errors.isbn" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.isbn }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Publisher</label>
                                <input v-model="form.publisher" type="text" class="mt-1 block w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                <div v-if="form.errors.publisher" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.publisher }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Call Number</label>
                                <input v-model="form.call_number" type="text" placeholder="e.g. QA76.73.J38" class="mt-1 block w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                <div v-if="form.errors.call_number" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.call_number }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Classification Type</label>
                                <select v-model="form.classification_type" class="mt-1 block w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="dewey">Dewey Decimal</option>
                                    <option value="loc">Library of Congress</option>
                                </select>
                                <div v-if="form.errors.classification_type" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.classification_type }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Page Count</label>
                                <input v-model="form.page_count" type="number" min="1" class="mt-1 block w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                <div v-if="form.errors.page_count" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.page_count }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subject / Tags</label>
                                <input v-model="form.subject" type="text" placeholder="e.g. Computer Science, Algorithms" class="mt-1 block w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                <div v-if="form.errors.subject" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.subject }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cover URL</label>
                                <input v-model="form.cover_url" type="text" placeholder="e.g. https://covers.openlibrary.org/..." class="mt-1 block w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                <div v-if="form.errors.cover_url" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.cover_url }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Published Year</label>
                                <input v-model="form.published_year" type="number" min="1000" max="2100" class="mt-1 block w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                <div v-if="form.errors.published_year" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.published_year }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Edition</label>
                                <input v-model="form.edition" type="text" placeholder="e.g. 2nd Edition" class="mt-1 block w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                <div v-if="form.errors.edition" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.edition }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Language</label>
                                <select v-model="form.language" class="mt-1 block w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="en">English</option>
                                    <option value="sw">Swahili</option>
                                    <option value="fr">French</option>
                                </select>
                                <div v-if="form.errors.language" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.language }}</div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                <textarea v-model="form.description" rows="4" class="mt-1 block w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                                <div v-if="form.errors.description" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.description }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Internal Notes</label>
                                <textarea v-model="form.notes" rows="4" class="mt-1 block w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                                <div v-if="form.errors.notes" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.notes }}</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <Link :href="route('library.catalog.index')" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 font-medium transition">Cancel</Link>
                            <button type="submit" :disabled="form.processing" class="inline-flex items-center px-6 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50 transition ease-in-out duration-150 shadow-lg shadow-indigo-500/20">
                                Save Title
                            </button>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
