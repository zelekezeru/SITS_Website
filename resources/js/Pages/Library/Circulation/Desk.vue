<script setup>
import { ref, watch } from 'vue'
import { QrcodeStream } from 'vue-qrcode-reader'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue'

const props = defineProps({
    currency: { type: String, default: '' },
})

const phase = ref('await_borrower') // 'await_borrower' or 'await_copies'
const borrower = ref(null)
const candidates = ref([])
const transactions = ref([])
const error = ref(null)
const busy = ref(false)

const manualInput = ref('')

async function lookupUser(q) {
    if (busy.value) return
    busy.value = true; error.value = null
    try {
        const { data } = await axios.get('/circulation/lookup', { params: { q } })
        if (data.candidates) {
            candidates.value = data.candidates
            return
        }
        candidates.value = []
        borrower.value = data
        phase.value = 'await_copies'
        beep()
    } catch (e) {
        error.value = e.response?.data?.message || 'Identity verification failed'
        buzz()
    } finally {
        busy.value = false
    }
}

async function checkoutCopy(hash) {
    if (busy.value) return
    busy.value = true; error.value = null
    try {
        const { data } = await axios.post('/circulation/checkout', {
            borrower_id: borrower.value.id,
            copy_hash: hash,
        })
        transactions.value.unshift({ hash, message: data.message, due_on: data.due_on, success: true, timestamp: new Date() })
        beep()
    } catch (e) {
        const msg = e.response?.data?.message || 'Failed to authorize checkout'
        transactions.value.unshift({ hash, message: msg, success: false, timestamp: new Date() })
        buzz()
    } finally {
        setTimeout(() => busy.value = false, 800)
    }
}

async function onDecode(result) {
    const code = Array.isArray(result) ? result[0].rawValue : result
    if (!code) return
    
    if (phase.value === 'await_borrower') {
        await lookupUser(code)
    } else {
        await checkoutCopy(code)
    }
}

function handleManualSubmit() {
    if (!manualInput.value) return
    onDecode(manualInput.value)
    manualInput.value = ''
}

function reset() {
    phase.value = 'await_borrower'
    borrower.value = null
    candidates.value = []
    transactions.value = []
    error.value = null
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
            <div class="flex justify-between items-center">
                <h2 class="font-black text-2xl text-gray-900 dark:text-white leading-tight tracking-tight uppercase">Circulation Intelligence</h2>
                <button 
                    @click="reset" 
                    v-if="borrower" 
                    class="px-6 py-2 bg-gray-50 dark:bg-gray-800 text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 rounded-xl hover:text-gray-900 dark:hover:text-white transition-all border border-gray-100 dark:border-gray-700"
                >
                    Terminate Session
                </button>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-10">
                
                <!-- Scanner Viewport -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="relative rounded-[2.5rem] overflow-hidden bg-black shadow-2xl border-4 border-white dark:border-gray-800 aspect-square group transition-all duration-500">
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
                                        <span class="text-white text-xs font-black uppercase tracking-[0.2em]">Authorizing...</span>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <!-- Phase Indicator -->
                    <div class="p-6 rounded-[2rem] shadow-xl border transition-all duration-500" 
                        :class="phase === 'await_borrower' 
                            ? 'bg-amber-50 dark:bg-amber-900/10 border-amber-100 dark:border-amber-900/30' 
                            : 'bg-indigo-50 dark:bg-indigo-900/10 border-indigo-100 dark:border-indigo-900/30'">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-lg"
                                :class="phase === 'await_borrower' ? 'bg-amber-500 text-white' : 'bg-indigo-500 text-white'">
                                <svg v-if="phase === 'await_borrower'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            </div>
                            <div>
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] opacity-60">Session Phase</h4>
                                <p class="text-sm font-black uppercase tracking-tight" :class="phase === 'await_borrower' ? 'text-amber-900 dark:text-amber-200' : 'text-indigo-900 dark:text-indigo-200'">
                                    {{ phase === 'await_borrower' ? 'Scan Borrower Identity' : 'Scan Resource Copies' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Manual Entry -->
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-[2rem] shadow-xl border border-gray-100 dark:border-gray-800">
                        <form @submit.prevent="handleManualSubmit" class="flex gap-3">
                            <input 
                                v-model="manualInput" 
                                type="text" 
                                class="flex-1 rounded-2xl border-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 text-sm focus:ring-2 focus:ring-indigo-500 transition-all" 
                                :placeholder="phase === 'await_borrower' ? 'Enter Name, Email or ID...' : 'Enter Book Hash...'"
                            />
                            <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition-all active:scale-95">
                                Submit
                            </button>
                        </form>
                        <Transition
                            enter-active-class="transition duration-300 ease-out"
                            enter-from-class="opacity-0 -translate-y-2"
                            enter-to-class="opacity-100 translate-y-0"
                        >
                            <div v-if="error" class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-xs font-bold rounded-xl border border-red-100 dark:border-red-900/30">
                                {{ error }}
                            </div>
                        </Transition>

                        <!-- Multiple matches: let staff pick the right patron -->
                        <div v-if="candidates.length" class="mt-4 space-y-2">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">{{ candidates.length }} matches — select borrower</p>
                            <button
                                v-for="c in candidates"
                                :key="c.id"
                                @click="lookupUser(c.id)"
                                class="w-full flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-gray-100 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-600 transition-all text-left"
                            >
                                <div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ c.name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ c.email }}</div>
                                </div>
                                <span class="text-[10px] font-mono text-gray-300 dark:text-gray-600">#{{ c.id }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Session Profile -->
                <div class="lg:col-span-7 space-y-6">
                    <Transition
                        enter-active-class="transition duration-500 ease-out"
                        enter-from-class="opacity-0 translate-x-12"
                        enter-to-class="opacity-100 translate-x-0"
                    >
                        <div v-if="borrower" class="bg-white dark:bg-gray-900 p-8 rounded-[2.5rem] shadow-xl border border-indigo-100 dark:border-indigo-900/30 relative overflow-hidden">
                            <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-500/5 rounded-full blur-3xl"></div>
                            
                            <div class="relative z-10">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="text-2xl font-black text-gray-900 dark:text-white leading-tight uppercase tracking-tight">{{ borrower.name }}</h3>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">{{ borrower.email }}</p>
                                    </div>
                                    <div class="px-3 py-1 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-full text-[10px] font-black uppercase tracking-widest border border-emerald-100 dark:border-emerald-800/30">
                                        Identity Verified
                                    </div>
                                </div>
                                
                                <div class="mt-8 grid grid-cols-2 gap-6">
                                    <div class="p-6 bg-gray-50 dark:bg-gray-800/50 rounded-3xl border dark:border-gray-800 transition-all hover:border-indigo-500/30">
                                        <div class="text-3xl font-black text-gray-900 dark:text-white tabular-nums">{{ borrower.active_loans_count }}</div>
                                        <div class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-black tracking-widest mt-1">Active Loans</div>
                                    </div>
                                    <div class="p-6 rounded-3xl border transition-all" 
                                        :class="borrower.fines.length 
                                            ? 'bg-red-50 dark:bg-red-900/20 border-red-100 dark:border-red-900/30' 
                                            : 'bg-gray-50 dark:bg-gray-800/50 dark:border-gray-800'">
                                        <div class="text-3xl font-black tabular-nums" 
                                            :class="borrower.fines.length ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'">
                                            {{ currency }} {{ borrower.fines.reduce((sum, f) => sum + (parseFloat(f.amount) - parseFloat(f.paid_amount)), 0).toFixed(2) }}
                                        </div>
                                        <div class="text-[10px] uppercase font-black tracking-widest mt-1" 
                                            :class="borrower.fines.length ? 'text-red-500/70 dark:text-red-400/70' : 'text-gray-400 dark:text-gray-500'">
                                            Outstanding Fines
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="bg-white dark:bg-gray-900 p-12 rounded-[2.5rem] shadow-xl border dark:border-gray-800 text-center flex flex-col items-center justify-center min-h-[300px] border-dashed border-2">
                            <div class="w-20 h-20 bg-gray-50 dark:bg-gray-800/50 rounded-3xl flex items-center justify-center mb-6 text-gray-300 dark:text-gray-700">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z" /></svg>
                            </div>
                            <p class="text-sm font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">Intelligence Awaiting Borrower Scan</p>
                        </div>
                    </Transition>

                    <!-- Session Activity -->
                    <div v-if="transactions.length > 0" class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-xl border border-gray-100 dark:border-gray-800 overflow-hidden flex flex-col max-h-[400px]">
                        <div class="px-8 py-5 border-b dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between">
                            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Recent Session Scans</h3>
                            <span class="text-[10px] font-mono text-gray-300">{{ transactions.length }} Ops</span>
                        </div>
                        <ul class="divide-y dark:divide-gray-800 overflow-y-auto">
                            <li v-for="(t, i) in transactions" :key="t.timestamp.getTime()" class="p-6 transition-colors" :class="t.success ? 'bg-white dark:bg-gray-900' : 'bg-red-50/30 dark:bg-red-900/10'">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-sm font-black uppercase tracking-tight" :class="t.success ? 'text-gray-900 dark:text-gray-100' : 'text-red-900 dark:text-red-400'">
                                            {{ t.message }}
                                        </div>
                                        <div v-if="t.success" class="flex items-center gap-2 mt-2">
                                            <span class="px-2 py-0.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg text-[10px] font-black uppercase tracking-widest border border-indigo-100 dark:border-indigo-800/30">
                                                Due: {{ t.due_on }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-[10px] font-black text-gray-300 dark:text-gray-600 uppercase">
                                        {{ t.timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                    </div>
                                </div>
                            </li>
                        </ul>
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
</style>
