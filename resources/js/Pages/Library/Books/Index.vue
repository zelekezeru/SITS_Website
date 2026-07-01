<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import debounce from 'lodash/debounce';

const props = defineProps({
    books: Object,
    filters: Object,
    campuses: Array,
    statuses: Array,
});

const { can } = useCan();

const search = ref(props.filters.search || '');
const campusId = ref(props.filters.campus_id || '');
const status = ref(props.filters.status || '');

const selectedBooks = ref([]);

const toggleAll = (event) => {
    if (event.target.checked) {
        selectedBooks.value = props.books.data.map(b => b.id);
    } else {
        selectedBooks.value = [];
    }
};

watch([search, campusId, status], debounce(() => {
    router.get(route('library.books.index'), {
        search: search.value,
        campus_id: campusId.value,
        status: status.value,
    }, { preserveState: true, replace: true });
}, 300));

const withdrawBook = (id) => {
    if (confirm('Are you sure you want to mark this book as withdrawn?')) {
        router.post(route('library.books.withdraw', id));
    }
};
</script>

<template>
    <Head title="Catalog" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Library Catalog
                </h2>
                <div class="space-x-2">
                    <a v-if="selectedBooks.length > 0" 
                       :href="route('library.books.labels', { ids: selectedBooks.join(',') })" 
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-indigo-100 border border-transparent rounded-md font-semibold text-xs text-indigo-700 uppercase tracking-widest hover:bg-indigo-200">
                        Print Labels ({{ selectedBooks.length }})
                    </a>
                    <Link v-if="can('create_book')" :href="route('library.books.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        + Add Book
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filters -->
                <div class="bg-white p-4 mb-6 shadow-sm sm:rounded-lg flex gap-4 items-center">
                    <div class="flex-1">
                        <input v-model="search" type="text" placeholder="Search title, author, ISBN..." class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                    </div>
                    <div class="w-48">
                        <select v-model="campusId" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Campuses</option>
                            <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div class="w-48">
                        <select v-model="status" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                </div>

                <!-- Catalog Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left">
                                    <input type="checkbox" @change="toggleAll" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="book in books.data" :key="book.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" v-model="selectedBooks" :value="book.id" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ book.title }}</div>
                                    <div class="text-sm text-gray-500">{{ book.authors }} • ISBN: {{ book.isbn || 'N/A' }} • Copy: {{ book.copy_number }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div v-if="book.current_shelf_box" class="text-sm text-gray-900">
                                        {{ book.current_shelf_box.row.floor.campus.code }} › 
                                        {{ book.current_shelf_box.row.floor.name }} › 
                                        {{ book.current_shelf_box.label }}
                                    </div>
                                    <div v-else class="text-sm text-gray-400 italic">Unassigned</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        {{ book.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    <a :href="route('library.books.qr', book.id)" target="_blank" class="text-gray-500 hover:text-gray-700" title="QR Code">QR</a>
                                    <Link v-if="can('edit_book')" :href="route('library.books.edit', book.id)" class="text-indigo-600 hover:text-indigo-900">Edit</Link>
                                    <button v-if="can('withdraw_book') && book.status !== 'withdrawn'" @click="withdrawBook(book.id)" class="text-orange-600 hover:text-orange-900">Withdraw</button>
                                </td>
                            </tr>
                            <tr v-if="books.data.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">No books found matching the criteria.</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <div class="px-6 py-3 border-t border-gray-200 flex items-center justify-between" v-if="books.links.length > 3">
                        <div class="flex space-x-1">
                            <Link v-for="(link, i) in books.links" :key="i" :href="link.url" 
                                  v-html="link.label" 
                                  class="px-3 py-1 border rounded text-sm" 
                                  :class="{'bg-indigo-50 border-indigo-500 text-indigo-600 font-semibold': link.active, 'text-gray-500 border-gray-300 hover:bg-gray-50': !link.active, 'opacity-50 cursor-not-allowed': !link.url}"></Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
