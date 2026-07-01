<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    books: Object,
    filters: Object,
    campuses: Array,
});

const q = ref(props.filters.q || '');
const campusId = ref(props.filters.campus_id || '');
const searching = ref(false);

watch([q, campusId], debounce(() => {
    searching.value = true;
    router.get(route('library.catalog.index'), {
        q: q.value,
        campus_id: campusId.value,
    }, { 
        preserveState: true, 
        preserveScroll: true, 
        replace: true,
        onFinish: () => searching.value = false
    });
}, 300));
</script>

<template>
    <Head :title="__('Library Catalog')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-black text-2xl text-gray-900 dark:text-white leading-tight tracking-tight uppercase">
                    {{ __('Institutional Catalog') }}
                </h2>
                <Link v-if="$page.props.auth.permissions.includes('create_book')" 
                      :href="route('library.books.create')" 
                      class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-2xl font-black text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg hover:shadow-indigo-500/20">
                    {{ __('+ Add New Title') }}
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Advanced Search Console -->
                <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-2xl border border-gray-100 dark:border-gray-800 p-8 mb-10 flex flex-col md:flex-row gap-6 items-center">
                    <div class="w-full flex-1 relative group">
                        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                            <svg v-if="!searching" class="h-5 w-5 text-gray-400 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <svg v-else class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                        <input v-model="q" 
                               type="text" 
                               :placeholder="__('Search catalog by title, author, or ISBN...')" 
                               class="w-full pl-16 pr-6 py-5 bg-gray-50/50 dark:bg-gray-800/50 border-gray-100 dark:border-gray-700 dark:text-gray-200 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-0 rounded-3xl text-sm font-bold placeholder-gray-400 dark:placeholder-gray-500 transition-all" />
                    </div>
                    <div class="w-full md:w-64">
                        <select v-model="campusId" 
                                class="w-full py-5 px-6 bg-gray-50/50 dark:bg-gray-800/50 border-gray-100 dark:border-gray-700 dark:text-gray-200 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-0 rounded-3xl text-xs font-black uppercase tracking-widest transition-all">
                            <option value="">{{ __('Global Network') }}</option>
                            <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Intelligence Grid -->
                <div v-if="books.data.length > 0">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div v-for="(book, idx) in books.data" :key="book.id" 
                             class="group bg-white dark:bg-gray-900 rounded-[2.5rem] p-6 border border-gray-100 dark:border-gray-800 hover:border-indigo-500/30 dark:hover:border-indigo-500/30 transition-all hover:shadow-2xl hover:-translate-y-1.5 duration-300 flex flex-col justify-between"
                             :style="{ animation: `slideUp 0.4s ease-out both ${idx * 0.04}s` }"
                        >
                            <div class="flex gap-4 items-start">
                                <!-- Cover thumbnail -->
                                <div class="w-20 h-28 shrink-0 rounded-2xl bg-gray-50 dark:bg-gray-850 border border-gray-100 dark:border-gray-800 overflow-hidden relative shadow-sm group-hover:shadow-md transition-shadow duration-300">
                                    <img v-if="book.cover_path" :src="'/storage/' + book.cover_path" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" />
                                    <img v-else-if="book.cover_url" :src="book.cover_url" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-indigo-50/50 to-indigo-100/30 dark:from-indigo-950/20 dark:to-indigo-900/10 text-indigo-500 dark:text-indigo-400">
                                        <svg class="w-6 h-6 mb-0.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                        <span class="text-[8px] font-black uppercase tracking-wider">{{ __('No Cover') }}</span>
                                    </div>
                                </div>
                                
                                <div class="flex-1 min-w-0 space-y-1">
                                    <!-- Categories -->
                                    <div v-if="book.categories?.length" class="flex flex-wrap gap-1 mb-1">
                                        <span v-for="cat in book.categories.slice(0, 1)" :key="cat.id"
                                              class="text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest truncate">
                                            {{ cat.name }}
                                        </span>
                                    </div>
                                    <h3 class="text-base font-black text-gray-900 dark:text-white leading-snug group-hover:text-indigo-650 dark:group-hover:text-indigo-400 transition-colors line-clamp-2 text-left">
                                        <Link :href="route('library.catalog.show', book.id)">{{ book.title }}</Link>
                                    </h3>
                                    <p v-if="book.subtitle" class="text-[10px] font-bold text-gray-400 uppercase tracking-tight line-clamp-1 text-left">{{ book.subtitle }}</p>
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        <span v-for="author in book.authors" :key="author.id" 
                                              class="text-[9px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest bg-indigo-50 dark:bg-indigo-950/40 px-2 py-0.5 rounded">
                                            {{ author.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between pt-4 mt-4 border-t border-gray-50 dark:border-gray-800">
                                <div class="flex flex-col">
                                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ __('Availability') }}</span>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <div :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            book.available_at_campus > 0 ? 'bg-emerald-500 animate-pulse' : 'bg-gray-300 dark:bg-gray-700'
                                        ]"></div>
                                        <span class="text-xs font-black text-gray-900 dark:text-white">
                                            {{ book.available_at_campus }} {{ campusId ? __('Here') : __('Total') }} {{ __('available') }}
                                        </span>
                                    </div>
                                </div>
                                <Link :href="route('library.catalog.show', book.id)" 
                                      class="w-8 h-8 rounded-xl bg-gray-50 dark:bg-gray-800 flex items-center justify-center text-gray-400 group-hover:bg-indigo-600 group-hover:text-white transition-all shadow-sm active:scale-90">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Smart Pagination -->
                    <div class="mt-12 flex items-center justify-center" v-if="books.links.length > 3">
                        <div class="flex gap-2 p-2 bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-xl">
                            <template v-for="(link, i) in books.links" :key="i">
                                <Link v-if="link.url" 
                                      :href="link.url" 
                                      v-html="link.label" 
                                      class="px-4 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all" 
                                      :class="{'bg-indigo-600 text-white shadow-lg': link.active, 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800': !link.active}"></Link>
                                <span v-else v-html="link.label" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-gray-300 dark:text-gray-700"></span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-20 text-center">
                    <div class="w-24 h-24 bg-gray-50 dark:bg-gray-800 rounded-[2rem] mx-auto flex items-center justify-center text-gray-300 dark:text-gray-700 mb-6">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ __('No Results Found') }}</h3>
                    <p class="text-sm text-gray-400 mt-2 font-medium">
                        {{ __('Your search for') }} "<span class="text-indigo-500 font-bold">{{ q }}</span>" {{ __('returned zero results.') }}
                    </p>
                    <button @click="q = ''" class="mt-8 px-6 py-3 text-xs font-black text-indigo-600 uppercase tracking-widest hover:text-indigo-700">{{ __('Reset Search') }}</button>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
