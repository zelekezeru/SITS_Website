<script setup>
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue'
import { QrcodeStream } from 'vue-qrcode-reader'
import { ref } from 'vue'

const props = defineProps({ campuses: Array, copy_id: String })

const form = useForm({
    copy_hash: '',
    to_campus_id: '',
    reason: '',
})

const showScanner = ref(false)

function onDecode(result) {
    const code = Array.isArray(result) ? result[0].rawValue : result
    if (code) {
        form.copy_hash = code
        showScanner.value = false
        beep()
    }
}

function beep() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)()
        const osc = ctx.createOscillator()
        osc.connect(ctx.destination)
        osc.frequency.value = 880
        osc.start()
        osc.stop(ctx.currentTime + 0.1)
    } catch(e) {}
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-2xl text-slate-900 dark:text-white leading-tight tracking-tight uppercase">Request Transfer</h2>
        </template>

        <div class="max-w-2xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 p-10 transition-all">
                <form @submit.prevent="form.post(route('library.transfers.store'))" class="space-y-8">
                    
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-4">Copy Identification</label>
                        <div class="flex rounded-2xl shadow-sm overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800">
                            <input 
                                v-model="form.copy_hash" 
                                type="text" 
                                class="flex-1 border-none bg-transparent text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm transition-all font-mono" 
                                placeholder="Scan or type tracking hash..." 
                                required 
                            />
                            <button 
                                type="button" 
                                @click="showScanner = !showScanner" 
                                class="inline-flex items-center px-6 py-2 bg-indigo-600 text-white hover:bg-indigo-700 transition-colors font-black text-[10px] uppercase tracking-widest shadow-lg"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                {{ showScanner ? 'Close' : 'Scan' }}
                            </button>
                        </div>
                        
                        <Transition
                            enter-active-class="transition duration-500 ease-out"
                            enter-from-class="opacity-0 -translate-y-4 scale-95"
                            enter-to-class="opacity-100 translate-y-0 scale-100"
                            leave-active-class="transition duration-300 ease-in"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div v-if="showScanner" class="mt-6 aspect-video bg-black rounded-[2rem] overflow-hidden border-4 border-white dark:border-slate-800 shadow-2xl relative group">
                                <qrcode-stream @detect="onDecode" />
                                
                                <!-- Smart Overlay -->
                                <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                                    <div class="w-48 h-48 border-2 border-white/20 rounded-[1.5rem] relative">
                                        <!-- Corners -->
                                        <div class="absolute -top-1 -left-1 w-8 h-8 border-t-4 border-l-4 border-indigo-500 rounded-tl-xl"></div>
                                        <div class="absolute -top-1 -right-1 w-8 h-8 border-t-4 border-r-4 border-indigo-500 rounded-tr-xl"></div>
                                        <div class="absolute -bottom-1 -left-1 w-8 h-8 border-b-4 border-l-4 border-indigo-500 rounded-bl-xl"></div>
                                        <div class="absolute -bottom-1 -right-1 w-8 h-8 border-b-4 border-r-4 border-indigo-500 rounded-br-xl"></div>
                                        
                                        <!-- Scan Line -->
                                        <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-indigo-400 to-transparent animate-scan shadow-[0_0_10px_rgba(99,102,241,0.5)]"></div>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-4">Destination Campus</label>
                        <select 
                            v-model="form.to_campus_id" 
                            required 
                            class="block w-full rounded-2xl border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all sm:text-sm font-bold p-4"
                        >
                            <option value="">Select Target Destination...</option>
                            <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }} ({{ c.code }})</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-4">Justification / Reason</label>
                        <textarea 
                            v-model="form.reason" 
                            rows="4" 
                            class="block w-full rounded-2xl border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 transition-all sm:text-sm p-4" 
                            placeholder="Why is this transfer necessary? (Optional context)"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-between pt-8 border-t border-slate-50 dark:border-slate-800">
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 font-black uppercase tracking-widest italic">Admin approval required</p>
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="inline-flex justify-center py-4 px-10 border border-transparent shadow-xl shadow-indigo-500/20 text-xs font-black uppercase tracking-widest rounded-2xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all active:scale-95 disabled:opacity-50"
                        >
                            Submit Request
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes scan {
    0% { top: 0; }
    50% { top: 100%; }
    100% { top: 0; }
}

.animate-scan {
    position: absolute;
    animation: scan 3s ease-in-out infinite;
}
</style>
