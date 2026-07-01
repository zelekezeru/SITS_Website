<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue'
import QrModal from '@/Components/Library/QrModal.vue'

defineProps({
    documents: Array,
    can_upload: Boolean,
})

const formatSize = (bytes) => {
    if (bytes === 0) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const deleteDoc = (id) => {
    if (confirm('Are you sure you want to delete this document? This cannot be undone.')) {
        router.delete(route('library.archive.destroy', id))
    }
}

// QR modal state
const qrModal = ref({ show: false, url: '', title: '' })
const openQr = (doc) => {
    qrModal.value = { show: true, url: route('library.archive.qr', doc.id), title: doc.title }
}
</script>

<template>
    <Head title="Digital Archive" />

    <QrModal :show="qrModal.show" :qrUrl="qrModal.url" :title="qrModal.title" @close="qrModal.show = false" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div>
                    <h2 class="font-black text-3xl text-gray-900 dark:text-white leading-tight tracking-tight">
                        Digital Archive
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-medium">Manage and access secure digital documents</p>
                </div>
                <Link 
                    v-if="can_upload" 
                    :href="route('library.archive.create')" 
                    class="w-full sm:w-auto text-center inline-flex items-center justify-center px-6 py-3 bg-indigo-600 border border-transparent rounded-2xl font-bold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-500/25 active:scale-95"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                    Upload Document
                </Link>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div v-if="documents.length === 0" class="text-center py-20 bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-xl border border-gray-100 dark:border-gray-800 flex flex-col items-center">
                    <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/20 rounded-3xl flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-6">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Empty Archive</h3>
                    <p class="mt-2 text-gray-500 dark:text-gray-400 max-w-xs mx-auto text-sm">
                        No documents found. Start by uploading your first PDF to the secure storage.
                    </p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div 
                        v-for="doc in documents" 
                        :key="doc.id" 
                        class="group bg-white dark:bg-gray-900/50 backdrop-blur-xl rounded-[2.5rem] p-7 border border-gray-100 dark:border-gray-800 shadow-xl shadow-gray-200/50 dark:shadow-none hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between relative overflow-hidden"
                    >
                        <!-- Card Glow Effect -->
                        <div class="absolute -right-10 -top-10 w-32 h-32 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-full blur-3xl transition-all duration-500 group-hover:scale-150"></div>

                        <div class="relative z-10">
                            <div class="flex items-start justify-between mb-6">
                                <div class="p-4 bg-indigo-50 dark:bg-indigo-900/40 rounded-2xl text-indigo-600 dark:text-indigo-400 shadow-inner">
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </div>
                                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-800/50 px-3 py-1 rounded-full">
                                    Secure PDF
                                </div>
                            </div>
                            
                            <h3 class="font-black text-gray-900 dark:text-white text-xl leading-tight mb-2 line-clamp-2" :title="doc.title">
                                {{ doc.title }}
                            </h3>
                            
                            <div v-if="doc.book" class="flex items-center gap-2 mb-4">
                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400 font-bold italic truncate">
                                    {{ doc.book.title }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2 mt-auto">
                                <span class="text-[10px] px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-lg font-bold uppercase tracking-wider">
                                    {{ formatSize(doc.size_bytes) }}
                                </span>
                                <span class="text-[10px] px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-lg font-bold uppercase tracking-wider">
                                    {{ doc.visibility.replace('_', ' ') }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-8 flex gap-3 relative z-10">
                            <Link 
                                :href="route('library.archive.show', doc.id)" 
                                class="flex-1 text-center py-3.5 bg-gray-900 dark:bg-indigo-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-800 dark:hover:bg-indigo-500 transition-all active:scale-95 shadow-lg shadow-gray-500/10 dark:shadow-indigo-500/20"
                            >
                                Open Reader
                            </Link>
                            
                            <button 
                                @click="openQr(doc)" 
                                class="p-3.5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all hover:bg-gray-50 active:scale-90 shadow-sm" 
                                title="Show QR Code"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                            </button>

                            <button 
                                v-if="can_upload" 
                                @click="deleteDoc(doc.id)" 
                                class="p-3.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-2xl transition-all active:scale-90" 
                                title="Delete Document"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
