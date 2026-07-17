<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue'
import QrModal from '@/Components/Library/QrModal.vue'
import EmptyState from '@/Components/Library/EmptyState.vue'

const props = defineProps({
    book: Object,
    canModerateReviews: Boolean,
    campuses: { type: Array, default: () => [] },
})

const page = usePage()
const translations = computed(() => page.props.translations || {})
const t = (key, replace = {}) => {
    let trans = translations.value[key] || key
    Object.keys(replace).forEach((r) => {
        trans = trans.replace(`:${r}`, replace[r])
    })
    return trans
}

const withdrawCopy = (copyId) => {
    if (confirm(t('Are you sure you want to mark this copy as withdrawn?'))) {
        router.patch(route('library.copies.withdraw', copyId))
    }
}

const markLost = (copyId) => {
    if (confirm(t('Are you sure you want to mark this copy as lost?'))) {
        router.patch(route('library.copies.mark-lost', copyId))
    }
}

// QR modal state
const qrModal = ref({ show: false, url: '', title: '' })
const openQr = (url, title) => {
    qrModal.value = { show: true, url, title }
}

// Review system setup
const currentUser = computed(() => page.props.auth?.user)

const myReview = computed(() => {
    return props.book.reviews?.find(r => r.user_id === currentUser.value?.id)
})

const reviewForm = useForm({
    rating: 5,
    review: '',
})

watch(myReview, (newVal) => {
    if (newVal) {
        reviewForm.rating = newVal.rating
        reviewForm.review = newVal.review
    } else {
        reviewForm.rating = 5
        reviewForm.review = ''
    }
}, { immediate: true })

const hoverRating = ref(0)

const submitReview = () => {
    reviewForm.post(route('library.reviews.store', props.book.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Success status will be handled by the global Toast system
        }
    })
}

const deleteReview = (reviewId) => {
    if (confirm(t('Are you sure you want to delete this review?'))) {
        router.delete(route('library.reviews.destroy', reviewId), {
            preserveScroll: true
        })
    }
}

const toggleApproval = (reviewId) => {
    router.post(route('library.reviews.toggle', reviewId), {}, {
        preserveScroll: true
    })
}

// Hold request setup
const holdForm = useForm({
    book_id: props.book.id,
    campus_id: currentUser.value?.current_campus_id || (props.campuses[0]?.id ?? ''),
})

const submitHold = () => {
    holdForm.post(route('library.holds.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success status will be handled by the global Toast system
        }
    })
}
</script>

<template>
    <Head :title="book.title" />

    <QrModal :show="qrModal.show" :qrUrl="qrModal.url" :title="qrModal.title" @close="qrModal.show = false" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div class="flex items-center gap-4 min-w-0">
                    <Link :href="route('library.catalog.index')" class="p-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700 text-slate-500 hover:text-indigo-600 transition shadow-sm active:scale-90">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </Link>
                    <div class="truncate">
                        <h2 class="font-black text-2xl text-slate-900 dark:text-white leading-tight tracking-tight truncate">
                            {{ book.title }}
                        </h2>
                    </div>
                </div>
                <div v-if="$page.props.auth.permissions.includes('edit_book')" class="flex shrink-0">
                    <Link :href="route('library.books.edit', book.id)" class="px-6 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs text-slate-700 dark:text-slate-300 uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-sm active:scale-95">
                        {{ __('Edit Title') }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- Title Details -->
                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-2xl shadow-slate-200/50 dark:shadow-none rounded-[2.5rem] border border-transparent dark:border-slate-800 flex flex-col lg:flex-row p-1 gap-1">
                    <!-- Cover Section -->
                    <div class="lg:w-1/3 p-6 flex items-center justify-center">
                        <div class="w-full max-w-[280px] aspect-[3/4] bg-gradient-to-tr from-slate-100 to-slate-50 dark:from-slate-850 dark:to-slate-800 rounded-[2.5rem] flex items-center justify-center text-slate-300 dark:text-slate-650 border border-slate-200/50 dark:border-slate-800 relative overflow-hidden shadow-xl hover:shadow-2xl hover:scale-102 hover:rotate-1 transition-all duration-500 group">
                            <!-- Premium Spine effect overlay -->
                            <div class="absolute left-0 top-0 bottom-0 w-2.5 bg-gradient-to-r from-black/15 to-transparent z-10"></div>
                            
                            <img v-if="book.cover_path" :src="'/storage/' + book.cover_path" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                            <img v-else-if="book.cover_url" :src="book.cover_url" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                            <div v-else class="flex flex-col items-center">
                                <svg class="w-16 h-16 mb-2 text-indigo-500/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                <span class="text-xs font-black uppercase tracking-widest text-indigo-600/80 dark:text-indigo-400/80">{{ __('No Cover Image') }}</span>
                            </div>
                            <!-- Badge -->
                            <div class="absolute top-4 right-4 px-3 py-1 bg-white/85 dark:bg-black/55 backdrop-blur-md rounded-full text-[10px] font-black uppercase tracking-widest text-indigo-750 dark:text-indigo-300 border border-white/25 dark:border-slate-800 shadow-sm">
                                {{ book.language }}
                            </div>
                        </div>
                    </div>

                    <!-- Details Section -->
                    <div class="lg:w-2/3 p-8 lg:pl-4">
                        <div class="flex flex-wrap gap-2 mb-4">
                            <span v-for="cat in book.categories" :key="cat.id" class="px-3 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg text-[10px] font-black uppercase tracking-wider">
                                {{ cat.name }}
                            </span>
                        </div>

                        <h1 class="text-4xl font-black text-slate-900 dark:text-white mb-2 leading-tight">{{ book.title }}</h1>
                        <div v-if="book.average_rating" class="flex items-center gap-2 mb-6">
                            <div class="flex items-center text-amber-500">
                                <svg v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= Math.round(book.average_rating) ? 'fill-current' : 'text-slate-300 dark:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.907c.961 0 1.36 1.246.588 1.81l-3.972 2.87a1 1 0 00-.364 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.971-2.87a1 1 0 00-1.175 0l-3.97 2.87c-.783.57-1.838-.197-1.539-1.118l1.518-4.674a1 1 0 00-.364-1.118L2.98 9.42c-.773-.564-.373-1.81.588-1.81h4.906a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                            <span class="text-sm font-black text-slate-900 dark:text-slate-100">{{ book.average_rating }} / 5</span>
                            <span class="text-xs text-slate-400 font-bold">({{ book.reviews?.filter(r => r.is_approved).length ?? 0 }} {{ __('reviews') }})</span>
                        </div>
                        <p v-if="book.subtitle" class="text-xl text-slate-500 dark:text-slate-400 mb-8 font-medium leading-relaxed">{{ book.subtitle }}</p>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 mb-8">
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                    <div>
                                        <div class="text-[10px] uppercase tracking-widest font-bold text-slate-400 mb-0.5">{{ __('Author(s)') }}</div>
                                        <div class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ book.authors.map(a => a.name).join(', ') }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" /></svg>
                                    </div>
                                    <div>
                                        <div class="text-[10px] uppercase tracking-widest font-bold text-slate-400 mb-0.5">{{ __('ISBN') }}</div>
                                        <div class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ book.isbn || __('NOT ASSIGNED') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                    </div>
                                    <div>
                                        <div class="text-[10px] uppercase tracking-widest font-bold text-slate-400 mb-0.5">{{ __('Publication') }}</div>
                                        <div class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                            {{ book.publisher || 'N/A' }} 
                                            <span v-if="book.published_year" class="text-slate-400 font-medium">({{ book.published_year }})</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    </div>
                                    <div>
                                        <div class="text-[10px] uppercase tracking-widest font-bold text-slate-400 mb-0.5">{{ __('Edition') }}</div>
                                        <div class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ book.edition || __('1st Edition') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6 rounded-[2rem] bg-slate-50 dark:bg-slate-800/30 text-sm text-slate-600 dark:text-slate-400 leading-relaxed italic mb-8 border border-slate-100 dark:border-slate-800">
                            "{{ book.description || __('No description available for this title.') }}"
                        </div>

                        <!-- Self-Service Hold Form -->
                        <div class="mb-8 p-6 rounded-[2.5rem] bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100/50 dark:border-indigo-900/35 space-y-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-indigo-600/10 dark:bg-indigo-400/15 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ __('Reserve this Book') }}</h4>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-450 font-medium">{{ __('Place a hold request and collect at your preferred campus') }}</p>
                                </div>
                            </div>
                            
                            <form @submit.prevent="submitHold" class="flex flex-col sm:flex-row gap-3 items-end">
                                <div class="w-full flex-1">
                                    <label class="block text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-widest mb-1.5">{{ __('Pickup Campus') }}</label>
                                    <select 
                                        v-model="holdForm.campus_id" 
                                        required
                                        class="w-full py-3 px-4 bg-white dark:bg-slate-950 border border-slate-205 dark:border-slate-800 rounded-2xl text-xs font-bold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                    >
                                        <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }} ({{ c.code }})</option>
                                    </select>
                                </div>
                                <button 
                                    type="submit" 
                                    :disabled="holdForm.processing"
                                    class="w-full sm:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-705 text-white rounded-2xl text-xs font-black uppercase tracking-widest transition-all active:scale-95 disabled:opacity-50 shadow-md shadow-indigo-600/10"
                                >
                                    {{ holdForm.processing ? __('Requesting...') : __('Place Hold') }}
                                </button>
                            </form>
                        </div>

                        <!-- Secure Documents (Phase 8) -->
                        <div v-if="book.secure_documents?.length && $page.props.auth.permissions.includes('view_secure_pdf')" class="flex flex-wrap gap-4">
                            <div v-for="doc in book.secure_documents" :key="doc.id" class="flex items-center gap-3">
                                <Link 
                                    :href="route('library.archive.show', doc.id)" 
                                    class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-500/30 active:scale-95"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                    {{ __('Read Digital Copy') }}
                                </Link>
                                <button 
                                    @click="openQr(route('library.archive.qr', doc.id), doc.title)" 
                                    class="p-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl text-slate-500 hover:text-indigo-600 transition shadow-sm active:scale-90"
                                    :title="__('Get QR Code')"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Copies -->
                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-transparent dark:border-slate-800 overflow-hidden">
                    <div class="p-8 border-b dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight flex items-center gap-2">
                                {{ __('Physical Inventory') }}
                                <span class="bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 px-3 py-0.5 rounded-full text-xs">{{ book.copies.length }}</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1 font-medium">{{ __('Tracking and location status for each copy') }}</p>
                        </div>
                        <form v-if="$page.props.auth.permissions.includes('create_book')" @submit.prevent="router.post(route('library.books.copies.store', book.id))">
                            <button type="submit" class="px-5 py-2.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition active:scale-95">
                                {{ __('+ Add Copy') }}
                            </button>
                        </form>
                    </div>

                    <div v-if="book.copies.length === 0" class="p-16 text-center text-slate-400 dark:text-slate-600 italic">
                        {{ __('No physical copies exist for this title yet.') }}
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y dark:divide-slate-800">
                            <thead class="bg-slate-50/50 dark:bg-slate-800/30">
                                <tr>
                                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Barcode / Hash') }}</th>
                                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Location') }}</th>
                                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Status') }}</th>
                                    <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-slate-800">
                                <tr v-for="copy in book.copies" :key="copy.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                                    <td class="px-8 py-5 whitespace-nowrap text-sm font-bold text-slate-900 dark:text-slate-200 font-mono tracking-tighter">
                                        {{ copy.barcode || copy.tracking_hash.substring(0,12) + '...' }}
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                                        <div v-if="copy.shelf_box" class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                            <span class="font-bold text-slate-900 dark:text-slate-200">{{ copy.shelf_box.row.floor.campus.code }}</span>
                                            <span class="text-slate-300 dark:text-slate-700">â€º</span>
                                            <span class="font-medium uppercase">{{ copy.shelf_box.label }}</span>
                                        </div>
                                        <span v-else class="text-slate-400 dark:text-slate-600 italic">{{ __('Unassigned') }}</span>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-[10px] font-black rounded-lg uppercase tracking-widest border" :class="{
                                            'bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800': copy.status === 'available',
                                            'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800': copy.status === 'checked_out',
                                            'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700': ['withdrawn', 'lost'].includes(copy.status)
                                        }">
                                            {{ __(copy.status) }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <button 
                                            @click="openQr(route('library.copies.qr', copy.id), `Copy: ${copy.barcode || copy.tracking_hash.substring(0,8)}`)" 
                                            class="p-2 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl text-slate-400 hover:text-indigo-600 transition shadow-sm active:scale-90"
                                            :title="__('QR Label')"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                                        </button>
                                        
                                        <template v-if="$page.props.auth.permissions.includes('edit_book')">
                                            <button v-if="copy.status !== 'withdrawn' && copy.status !== 'lost'" @click="withdrawCopy(copy.id)" class="px-3 py-2 text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-xl text-xs font-bold transition-all">{{ __('Withdraw') }}</button>
                                            <button v-if="copy.status === 'checked_out' || copy.status === 'in_transit'" @click="markLost(copy.id)" class="px-3 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl text-xs font-bold transition-all">{{ __('Mark Lost') }}</button>
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Reviews Section -->
                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-transparent dark:border-slate-800 overflow-hidden p-8 space-y-8">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight flex items-center gap-2">
                            {{ __('Reviews & Ratings') }}
                            <span class="bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 px-3 py-0.5 rounded-full text-xs">
                                {{ book.reviews?.length ?? 0 }}
                            </span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 font-medium">{{ __('Hear what other patrons and faculty think about this title') }}</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Review form & summary -->
                        <div class="space-y-6">
                            <!-- Summary -->
                            <div class="p-6 rounded-[2rem] bg-slate-50 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800 space-y-4">
                                <h4 class="text-sm font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">{{ __('Ratings Overview') }}</h4>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-5xl font-black text-slate-900 dark:text-white">{{ book.average_rating || '0.0' }}</span>
                                    <span class="text-sm text-slate-400 font-bold">/ 5.0</span>
                                </div>
                                <div class="flex items-center text-amber-500">
                                    <svg v-for="i in 5" :key="i" class="w-5 h-5" :class="i <= Math.round(book.average_rating || 0) ? 'fill-current' : 'text-slate-300 dark:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.907c.961 0 1.36 1.246.588 1.81l-3.972 2.87a1 1 0 00-.364 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.971-2.87a1 1 0 00-1.175 0l-3.97 2.87c-.783.57-1.838-.197-1.539-1.118l1.518-4.674a1 1 0 00-.364-1.118L2.98 9.42c-.773-.564-.373-1.81.588-1.81h4.906a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                </div>
                                <p class="text-xs text-slate-500 font-medium">{{ __('Based on') }} {{ book.reviews?.filter(r => r.is_approved).length ?? 0 }} {{ __('approved reviews') }}</p>
                            </div>

                            <!-- Write/Edit Review Form -->
                            <div class="p-6 rounded-[2rem] bg-slate-50 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800 space-y-4">
                                <h4 class="text-sm font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    {{ myReview ? __('Edit Your Review') : __('Write a Review') }}
                                </h4>
                                <form @submit.prevent="submitReview" class="space-y-4">
                                    <!-- Interactive Star Rating selection -->
                                    <div class="space-y-1">
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Your Rating') }}</label>
                                        <div class="flex items-center gap-1">
                                            <button 
                                                v-for="star in 5" 
                                                :key="star" 
                                                type="button"
                                                @click="reviewForm.rating = star"
                                                @mouseover="hoverRating = star"
                                                @mouseleave="hoverRating = 0"
                                                class="text-amber-500 hover:scale-110 active:scale-95 transition focus:outline-none"
                                            >
                                                <svg 
                                                    class="w-8 h-8" 
                                                    :class="star <= (hoverRating || reviewForm.rating) ? 'fill-current' : 'text-slate-300 dark:text-slate-600'" 
                                                    fill="none" 
                                                    stroke="currentColor" 
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.907c.961 0 1.36 1.246.588 1.81l-3.972 2.87a1 1 0 00-.364 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.971-2.87a1 1 0 00-1.175 0l-3.97 2.87c-.783.57-1.838-.197-1.539-1.118l1.518-4.674a1 1 0 00-.364-1.118L2.98 9.42c-.773-.564-.373-1.81.588-1.81h4.906a1 1 0 00.951-.69l1.519-4.674z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Review comment -->
                                    <div class="space-y-1">
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Your Review') }}</label>
                                        <textarea 
                                            v-model="reviewForm.review"
                                            rows="4" 
                                            :placeholder="__('What did you think of the writing style, content, or relevance to your studies?...')" 
                                            class="w-full text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition placeholder-slate-400 text-slate-700 dark:text-slate-300"
                                        ></textarea>
                                    </div>

                                    <button 
                                        type="submit" 
                                        :disabled="reviewForm.processing"
                                        class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all active:scale-95 disabled:opacity-50"
                                    >
                                        {{ myReview ? __('Update Review') : __('Submit Review') }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Review list -->
                        <div class="lg:col-span-2 space-y-4">
                            <h4 class="text-sm font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                {{ __('Patron Reviews') }}
                            </h4>
                            
                            <div v-if="!book.reviews || book.reviews.length === 0">
                                <EmptyState 
                                    :title="__('No Reviews Yet')" 
                                    :description="__('Be the first to share your thoughts on this book. Your review will help other students and staff in their academic research.')"
                                    icon="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.907c.961 0 1.36 1.246.588 1.81l-3.972 2.87a1 1 0 00-.364 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.971-2.87a1 1 0 00-1.175 0l-3.97 2.87c-.783.57-1.838-.197-1.539-1.118l1.518-4.674a1 1 0 00-.364-1.118L2.98 9.42c-.773-.564-.373-1.81.588-1.81h4.906a1 1 0 00.951-.69l1.519-4.674z"
                                />
                            </div>

                            <div v-else class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                                <div 
                                    v-for="review in book.reviews" 
                                    :key="review.id"
                                    class="p-6 rounded-[2rem] bg-slate-50 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800 space-y-3 relative group"
                                >
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-white font-black text-sm uppercase">
                                                {{ review.user?.name ? review.user.name.charAt(0) : 'U' }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ review.user?.name || __('Unknown Patron') }}</div>
                                                <div class="text-[10px] text-slate-400 font-medium">{{ new Date(review.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) }}</div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <div class="flex text-amber-500">
                                                <svg v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= review.rating ? 'fill-current' : 'text-slate-300 dark:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.907c.961 0 1.36 1.246.588 1.81l-3.972 2.87a1 1 0 00-.364 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.971-2.87a1 1 0 00-1.175 0l-3.97 2.87c-.783.57-1.838-.197-1.539-1.118l1.518-4.674a1 1 0 00-.364-1.118L2.98 9.42c-.773-.564-.373-1.81.588-1.81h4.906a1 1 0 00.951-.69l1.519-4.674z" />
                                                </svg>
                                            </div>

                                            <!-- Status badges -->
                                            <span v-if="!review.is_approved" class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400 text-[10px] font-black uppercase tracking-wider">
                                                {{ __('Pending Approval') }}
                                            </span>
                                        </div>
                                    </div>

                                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
                                        {{ review.review || __('Rating only, no review text provided.') }}
                                    </p>

                                    <!-- Moderator Actions & Delete -->
                                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button 
                                            v-if="canModerateReviews"
                                            @click="toggleApproval(review.id)"
                                            class="px-3 py-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-500 hover:text-indigo-600 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all"
                                        >
                                            {{ review.is_approved ? __('Unapprove') : __('Approve') }}
                                        </button>
                                        <button 
                                            v-if="canModerateReviews || review.user_id === currentUser?.id"
                                            @click="deleteReview(review.id)"
                                            class="px-3 py-1 bg-red-50 dark:bg-red-900/10 text-red-600 hover:bg-red-100 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all"
                                        >
                                            {{ __('Delete') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
