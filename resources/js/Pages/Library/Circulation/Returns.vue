<script setup>
import { ref } from 'vue'
import { QrcodeStream } from 'vue-qrcode-reader'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue'

const props = defineProps({ campuses: Array })

// Auto-select campus or allow manual
const returnCampusId = ref(props.campuses[0]?.id)
const transactions = ref([])
const busy = ref(false)
const manualInput = ref('')

async function processReturn(hash) {
    if (busy.value) return
    if (!returnCampusId.value) return alert('Select a return campus first.')
    
    busy.value = true
    try {
        const { data } = await axios.post('/circulation/return', {
            copy_hash: hash,
            return_campus_id: returnCampusId.value,
        })
        transactions.value.unshift({ hash, message: data.message, success: true, timestamp: new Date() })
        beep()
    } catch (e) {
        const msg = e.response?.data?.message || 'Failed to return copy'
        transactions.value.unshift({ hash, message: msg, success: false, timestamp: new Date() })
        buzz()
    } finally {
        setTimeout(() => busy.value = false, 800)
    }
}

async function onDecode(result) {
    const code = Array.isArray(result) ? result[0].rawValue : result
    if (!code) return
    await processReturn(code)
}

function handleManualSubmit() {
    if (!manualInput.value) return
    processReturn(manualInput.value)
    manualInput.value = ''
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

function buzz() {
    try {
        if (navigator.vibrate) navigator.vibrate(200)
        const ctx = new (window.AudioContext || window.webkitAudioContext)()
        const osc = ctx.createOscillator()
        osc.connect(ctx.destination)
        osc.frequency.value = 150
        osc.type = 'sawtooth'
        osc.start()
        osc.stop(ctx.currentTime + 0.3)
    } catch(e) {}
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <h2 class="font-black text-2xl text-slate-900 dark:text-white leading-tight tracking-tight uppercase">Returns Intelligence</h2>
                <div class="flex items-center gap-3 bg-white dark:bg-slate-800 p-2 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-2">Receiving Point:</label>
                    <select v-model="returnCampusId" class="border-none bg-transparent text-sm font-bold text-slate-700 dark:text-slate-200 focus:ring-0 min-w-[150px]">
                        <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-10">
                
                <!-- Scanner Viewport -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="relative rounded-[2.5rem] overflow-hidden bg-black shadow-2xl border-4 border-white dark:border-slate-800 aspect-square group transition-all duration-500">
                        <qrcode-stream @detect="onDecode" class="absolute inset-0" />
                        
                        <!-- Smart Overlay -->
                        <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                            <!-- Target Frame -->
                            <div class="w-64 h-64 border-2 border-white/20 rounded-[2rem] relative">
                                <!-- Corner Accents -->
                                <div class="absolute -top-1 -left-1 w-10 h-10 border-t-4 border-l-4 border-indigo-500 rounded-tl-2xl"></div>
                                <div class="absolute -top-1 -right-1 w-10 h-10 border-t-4 border-r-4 border-indigo-500 rounded-tr-2xl"></div>
                                <div class="absolute -bottom-1 -left-1 w-10 h-10 border-b-4 border-l-4 border-indigo-500 rounded-bl-2xl"></div>
                                <div class="absolute -bottom-1 -right-1 w-10 h-10 border-b-4 border-r-4 border-indigo-500 rounded-br-2xl"></div>
                                
                                <!-- Scanning Animation -->
                                <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-indigo-400 to-transparent animate-scan shadow-[0_0_15px_rgba(99,102,241,0.5)]"></div>
                            </div>

                            <!-- Processing State -->
                            <Transition
                                enter-active-class="transition duration-300 ease-out"
                                enter-from-class="opacity-0 scale-95"
                                enter-to-class="opacity-100 scale-100"
                                leave-active-class="transition duration-200 ease-in"
                                leave-from-class="opacity-100 scale-100"
                                leave-to-class="opacity-0 scale-95"
                            >
                                <div v-if="busy" class="absolute inset-0 flex items-center justify-center bg-indigo-900/40 backdrop-blur-md">
                                    <div class="flex flex-col items-center gap-4">
                                        <div class="w-12 h-12 border-4 border-white border-t-transparent rounded-full animate-spin"></div>
                                        <span class="text-white text-xs font-black uppercase tracking-[0.2em]">Resolving Copy...</span>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <!-- Manual Input Panel -->
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-[2rem] shadow-xl border border-slate-100 dark:border-slate-800">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Manual Entry Correction</label>
                        <form @submit.prevent="handleManualSubmit" class="flex gap-3">
                            <input 
                                v-model="manualInput" 
                                type="text" 
                                class="flex-1 rounded-2xl border-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 transition-all font-mono" 
                                placeholder="Paste or type book hash..." 
                            />
                            <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition-all active:scale-95">
                                Process
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Session Intelligence (Log) -->
                <div class="lg:col-span-7 flex flex-col gap-6">
                    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col h-[650px] transition-all">
                        <div class="px-8 py-6 border-b dark:border-slate-800 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Session Intelligence</h3>
                                <p class="text-[10px] text-slate-400 font-medium mt-0.5">Real-time return activity log</p>
                            </div>
                            <div class="text-[10px] font-black text-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1 rounded-full uppercase tracking-tighter">
                                {{ transactions.length }} Actions
                            </div>
                        </div>
                        
                        <div class="overflow-y-auto flex-1 p-4">
                            <TransitionGroup name="list" tag="ul" class="space-y-4">
                                <li v-for="(t, i) in transactions" :key="t.timestamp.getTime()" class="p-6 rounded-3xl border transition-all" :class="t.success 
                                    ? 'bg-emerald-50/50 dark:bg-emerald-900/10 border-emerald-100 dark:border-emerald-800/30' 
                                    : 'bg-red-50 dark:bg-red-900/10 border-red-100 dark:border-red-800/30'">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex items-start gap-4">
                                            <div class="p-2 rounded-xl" :class="t.success ? 'bg-emerald-100 dark:bg-emerald-800 text-emerald-600 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-800 text-red-600 dark:text-red-400'">
                                                <svg v-if="t.success" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-black uppercase tracking-tight" :class="t.success ? 'text-emerald-900 dark:text-emerald-200' : 'text-red-900 dark:text-red-200'">
                                                    {{ t.message }}
                                                </div>
                                                <div class="text-[10px] font-mono text-slate-400 dark:text-slate-500 mt-1 uppercase tracking-tighter">
                                                    Hash Ref: {{ t.hash.substring(0, 24) }}...
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-[10px] font-black text-slate-300 dark:text-slate-600 uppercase tabular-nums">
                                            {{ t.timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }}
                                        </div>
                                    </div>
                                </li>
                            </TransitionGroup>
                            
                            <div v-if="!transactions.length" class="h-full flex flex-col items-center justify-center opacity-30 grayscale p-12 text-center">
                                <svg class="w-20 h-20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                <p class="text-sm font-black uppercase tracking-widest">Intelligence Awaiting Input</p>
                            </div>
                        </div>
                    </div>
                </div>

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

.list-enter-active,
.list-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>
