<script setup>
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    recentImports: Array,
});

const form = useForm({
    workbook: null,
    sheet: 'All',
    commit: false,
});

const uploadWorkbook = () => {
    form.post(route('library.admin.legacy.import'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Legacy Data Management" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-2xl text-slate-900 dark:text-white leading-tight tracking-tight uppercase">Legacy Data Intelligence</h2>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-10">
                
                <!-- Action Center -->
                <div class="space-y-10">
                    <!-- Export Actions -->
                    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 p-10">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-8">Export Central</h3>
                        
                        <div class="grid gap-6">
                            <a :href="route('library.admin.legacy.export', { type: 'current' })" 
                               class="flex items-center justify-between p-6 rounded-3xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-all group">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-lg">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-indigo-900 dark:text-indigo-200 uppercase tracking-tight">Full Library State</p>
                                        <p class="text-[10px] text-indigo-400 font-medium">Export current database for editing</p>
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-indigo-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                            </a>

                            <a :href="route('library.admin.legacy.export', { type: 'template' })" 
                               class="flex items-center justify-between p-6 rounded-3xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/50 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-all group">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-emerald-600 flex items-center justify-center text-white shadow-lg">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-emerald-900 dark:text-emerald-200 uppercase tracking-tight">Blank Templates</p>
                                        <p class="text-[10px] text-emerald-400 font-medium">Download empty shells for new data</p>
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-emerald-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Import Actions -->
                    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 p-10">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-8">Import Gateway</h3>
                        
                        <form @submit.prevent="uploadWorkbook" class="space-y-6">
                            <div class="p-8 border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-[2rem] text-center transition-all hover:border-indigo-500/30 group">
                                <input type="file" @input="form.workbook = $event.target.files[0]" class="hidden" id="workbook-upload" />
                                <label for="workbook-upload" class="cursor-pointer">
                                    <div class="w-16 h-16 rounded-2xl bg-slate-50 dark:bg-slate-800 mx-auto flex items-center justify-center text-slate-300 dark:text-slate-700 mb-4 group-hover:text-indigo-500 transition-colors">
                                        <svg v-if="!form.workbook" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                        <svg v-else class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </div>
                                    <p class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ form.workbook ? form.workbook.name : 'Select Intelligence File' }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1 uppercase font-medium tracking-widest">Excel workbooks (.xlsx) only</p>
                                </label>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Target Scope</label>
                                    <select v-model="form.sheet" class="w-full rounded-2xl border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-sm font-bold p-4">
                                        <option value="All">Complete Workbook (All Sheets)</option>
                                        <option value="Books">Books Only</option>
                                        <option value="Copies">Copies Only</option>
                                        <option value="Users">Users Only</option>
                                    </select>
                                </div>
                                
                                <div class="col-span-2 flex items-center gap-3 p-4 bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 rounded-2xl">
                                    <input type="checkbox" v-model="form.commit" id="commit-mode" class="rounded-lg text-amber-600 focus:ring-amber-500" />
                                    <label for="commit-mode" class="text-xs font-black text-amber-900 dark:text-amber-200 uppercase tracking-tight">Direct Commit Mode</label>
                                    <p class="text-[9px] text-amber-600 uppercase font-medium ml-auto">Bypasses staging review</p>
                                </div>
                            </div>

                            <button type="submit" 
                                    :disabled="form.processing || !form.workbook"
                                    class="w-full py-4 bg-slate-900 dark:bg-indigo-600 text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] hover:bg-black dark:hover:bg-indigo-700 transition-all disabled:opacity-30 disabled:grayscale">
                                {{ form.processing ? 'Synchronizing Intelligence...' : 'Initiate Round-Trip' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- History / Feed -->
                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 flex flex-col h-full min-h-[600px] overflow-hidden">
                    <div class="p-10 border-b dark:border-slate-800">
                        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Synchronization Feed</h3>
                        <p class="text-[10px] text-slate-400 font-medium mt-1">Audit of recent legacy operations</p>
                    </div>
                    
                    <div class="flex-1 p-10 flex flex-col items-center justify-center opacity-30 grayscale space-y-4">
                        <div class="w-20 h-20 bg-slate-50 dark:bg-slate-800 rounded-3xl flex items-center justify-center text-slate-300">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <p class="text-[10px] font-black uppercase tracking-widest">No recent synchronizations recorded</p>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
