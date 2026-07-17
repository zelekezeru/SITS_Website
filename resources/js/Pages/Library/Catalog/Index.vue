<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import Icon from '@/Components/Icon.vue';
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
            <div class="flex items-center justify-between gap-3">
                <h2>{{ __('Institutional Catalog') }}</h2>
                <Link v-if="$page.props.auth.permissions.includes('create_book')"
                      :href="route('library.books.create')"
                      class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-wide hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20 shrink-0">
                    <Icon name="BookPlus" :size="15" /> {{ __('Add Title') }}
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Advanced Search Console -->
                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 p-8 mb-10 flex flex-col md:flex-row gap-6 items-center">
                    <div class="w-full flex-1 relative group">
                        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                            <Icon v-if="!searching" name="Search" :size="20" class="text-slate-400 group-focus-within:text-indigo-500 transition-colors" />
                            <svg v-else class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                        <input v-model="q" 
                               type="text" 
                               :placeholder="__('Search catalog by title, author, or ISBN...')" 
                               class="w-full pl-16 pr-6 py-5 bg-slate-50/50 dark:bg-slate-800/50 border-slate-100 dark:border-slate-700 dark:text-slate-200 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-0 rounded-3xl text-sm font-bold placeholder-slate-400 dark:placeholder-slate-500 transition-all" />
                    </div>
                    <div class="w-full md:w-64">
                        <select v-model="campusId" 
                                class="w-full py-5 px-6 bg-slate-50/50 dark:bg-slate-800/50 border-slate-100 dark:border-slate-700 dark:text-slate-200 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-0 rounded-3xl text-xs font-black uppercase tracking-widest transition-all">
                            <option value="">{{ __('Global Network') }}</option>
                            <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Intelligence Grid -->
                <div v-if="books.data.length > 0">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div v-for="(book, idx) in books.data" :key="book.id" 
                             class="group bg-white dark:bg-slate-900 rounded-[2.5rem] p-6 border border-slate-100 dark:border-slate-800 hover:border-indigo-500/30 dark:hover:border-indigo-500/30 transition-all hover:shadow-2xl hover:-translate-y-1.5 duration-300 flex flex-col justify-between"
                             :style="{ animation: `slideUp 0.4s ease-out both ${idx * 0.04}s` }"
                        >
                            <div class="flex gap-4 items-start">
                                <!-- Cover thumbnail -->
                                <div class="w-20 h-28 shrink-0 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-100 dark:border-slate-800 overflow-hidden relative shadow-sm group-hover:shadow-md transition-shadow duration-300">
                                    <img v-if="book.cover_path" :src="'/storage/' + book.cover_path" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" />
                                    <img v-else-if="book.cover_url" :src="book.cover_url" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-indigo-50/50 to-indigo-100/30 dark:from-indigo-950/20 dark:to-indigo-900/10 text-indigo-500 dark:text-indigo-400">
                                        <Icon name="BookOpen" :size="22" class="mb-0.5 opacity-60" />
                                        <span class="text-[8px] font-black uppercase tracking-wider">{{ __('No Cover') }}</span>
                                    </div>
                                </div>
                                
                                <div class="flex-1 min-w-0 space-y-1">
                                    <!-- Categories -->
                                    <div v-if="book.categories?.length" class="flex flex-wrap gap-1 mb-1">
                                        <span v-for="cat in book.categories.slice(0, 1)" :key="cat.id"
                                              class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">
                                            {{ cat.name }}
                                        </span>
                                    </div>
                                    <h3 class="text-base font-black text-slate-900 dark:text-white leading-snug group-hover:text-indigo-650 dark:group-hover:text-indigo-400 transition-colors line-clamp-2 text-left">
                                        <Link :href="route('library.catalog.show', book.id)">{{ book.title }}</Link>
                                    </h3>
                                    <p v-if="book.subtitle" class="text-[10px] font-bold text-slate-400 uppercase tracking-tight line-clamp-1 text-left">{{ book.subtitle }}</p>
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        <span v-for="author in book.authors" :key="author.id" 
                                              class="text-[9px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest bg-indigo-50 dark:bg-indigo-950/40 px-2 py-0.5 rounded">
                                            {{ author.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between pt-4 mt-4 border-t border-slate-50 dark:border-slate-800">
                                <div class="flex flex-col">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ __('Availability') }}</span>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <div :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            book.available_at_campus > 0 ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300 dark:bg-slate-700'
                                        ]"></div>
                                        <span class="text-xs font-black text-slate-900 dark:text-white">
                                            {{ book.available_at_campus }} {{ campusId ? __('Here') : __('Total') }} {{ __('available') }}
                                        </span>
                                    </div>
                                </div>
                                <Link :href="route('library.catalog.show', book.id)"
                                      class="w-8 h-8 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-indigo-600 group-hover:text-white transition-all shadow-sm active:scale-90">
                                    <Icon name="ChevronRight" :size="16" :stroke-width="2.5" />
                                </Link>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Smart Pagination -->
                    <div class="mt-12 flex items-center justify-center" v-if="books.links.length > 3">
                        <div class="flex gap-2 p-2 bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-xl">
                            <template v-for="(link, i) in books.links" :key="i">
                                <Link v-if="link.url" 
                                      :href="link.url" 
                                      v-html="link.label" 
                                      class="px-4 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all" 
                                      :class="{'bg-indigo-600 text-white shadow-lg': link.active, 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800': !link.active}"></Link>
                                <span v-else v-html="link.label" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-300 dark:text-slate-700"></span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-20 text-center">
                    <div class="w-24 h-24 bg-slate-50 dark:bg-slate-800 rounded-[2rem] mx-auto flex items-center justify-center text-slate-300 dark:text-slate-700 mb-6">
                        <Icon name="Search" :size="48" />
                    </div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ __('No Results Found') }}</h3>
                    <p class="text-sm text-slate-400 mt-2 font-medium">
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
