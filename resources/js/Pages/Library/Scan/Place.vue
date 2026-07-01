<script setup>
import { ref, computed } from 'vue'
import { QrcodeStream } from 'vue-qrcode-reader'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue'

const phase = ref('await_box')
const box   = ref(null)
const copies = ref([])
const error  = ref(null)
const success = ref(null)
const busy   = ref(false)
const manualHash = ref('')

async function processHash(hash) {
  if (busy.value) return
  busy.value = true
  error.value = null
  success.value = null
  
  try {
    const { data } = await axios.post('/scan/resolve', { hash })
    if (phase.value === 'await_box') {
      if (data.type !== 'shelf_box') throw new Error('Scan a shelf box first.')
      box.value = { ...data, hash } // Store the original hash
      phase.value = 'await_copies'
      beep()
    } else {
      if (data.type !== 'book_copy') throw new Error('That was not a book copy.')
      if (copies.value.find(c => c.hash === hash)) {
        error.value = "This copy is already in the list."
        buzz()
        return 
      }
      copies.value.unshift({ hash, ...data }) // Add to top
      beep()
    }
    manualHash.value = ''
  } catch (e) {
    error.value = e.response?.data?.message ?? e.response?.data?.errors?.hash?.[0] ?? e.message
    buzz()
  } finally {
    setTimeout(() => busy.value = false, 600)
  }
}

async function onDecode(result) {
  const hash = Array.isArray(result) && result.length > 0 ? result[0].rawValue : result;
  if (!hash) return;
  await processHash(hash)
}

function handleManualSubmit() {
  if (manualHash.value) {
    processHash(manualHash.value)
  }
}

async function doCommit() {
    if (!box.value?.hash) return;
    busy.value = true;
    error.value = null;
    success.value = null;
    try {
        await axios.post('/scan/place', {
            shelf_box_hash: box.value.hash,
            copy_hashes: copies.value.map(c => c.hash),
            reason: 'reshelve',
        });
        const count = copies.value.length;
        reset();
        success.value = `Successfully placed ${count} copies into the box.`;
    } catch (e) {
        error.value = e.response?.data?.message ?? e.response?.data?.errors?.copy_hashes?.[0] ?? e.message;
        buzz();
    } finally {
        busy.value = false;
    }
}

function reset() {
  phase.value = 'await_box'; 
  box.value = null; 
  copies.value = [];
  error.value = null;
  success.value = null;
}

function beep() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        osc.connect(ctx.destination);
        osc.frequency.value = 880;
        osc.start();
        osc.stop(ctx.currentTime + 0.1);
    } catch(e) {}
}

function buzz() {
    try {
        if (navigator.vibrate) navigator.vibrate(200);
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        osc.connect(ctx.destination);
        osc.frequency.value = 150;
        osc.type = 'sawtooth';
        osc.start();
        osc.stop(ctx.currentTime + 0.3);
    } catch(e) {}
}
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Scan & Place
            </h2>
            <div class="hidden sm:flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full" :class="phase === 'await_box' ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500'"></span>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ phase === 'await_box' ? 'Initializing' : 'Ready for copies' }}
                </span>
            </div>
        </div>
    </template>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Scanner Column -->
        <div class="lg:col-span-5 space-y-6">
          <div class="relative rounded-3xl overflow-hidden bg-black shadow-2xl border-4 border-white dark:border-gray-800 aspect-square group">
              <!-- QR Scanner -->
              <qrcode-stream @detect="onDecode" class="w-full h-full object-cover" />
              
              <!-- Scanner Frame Overlay -->
              <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                  <div class="w-64 h-64 border-2 border-indigo-500/50 rounded-3xl relative">
                      <!-- Corners -->
                      <div class="absolute -top-2 -left-2 w-8 h-8 border-t-4 border-l-4 border-indigo-500 rounded-tl-xl"></div>
                      <div class="absolute -top-2 -right-2 w-8 h-8 border-t-4 border-r-4 border-indigo-500 rounded-tr-xl"></div>
                      <div class="absolute -bottom-2 -left-2 w-8 h-8 border-b-4 border-l-4 border-indigo-500 rounded-bl-xl"></div>
                      <div class="absolute -bottom-2 -right-2 w-8 h-8 border-b-4 border-r-4 border-indigo-500 rounded-br-xl"></div>
                      
                      <!-- Scanning Line Animation -->
                      <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-indigo-500 to-transparent animate-scan"></div>
                  </div>
              </div>

              <!-- Status Overlays -->
              <div v-if="busy" class="absolute inset-0 flex items-center justify-center bg-black/60 backdrop-blur-md transition-all duration-300">
                  <div class="flex flex-col items-center">
                      <div class="w-12 h-12 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                      <span class="text-white font-bold tracking-widest uppercase text-sm">Processing</span>
                  </div>
              </div>

              <!-- Phase Banner -->
              <div class="absolute bottom-0 inset-x-0 p-4 bg-gradient-to-t from-black/80 to-transparent">
                  <div class="bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/20 text-center">
                      <p class="text-white font-bold text-sm tracking-wide">
                        <span v-if="phase === 'await_box'" class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                            1. Scan a Shelf Box
                        </span>
                        <span v-else class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            2. Add copies to <span class="text-emerald-400">{{ box?.label || 'Box' }}</span>
                        </span>
                      </p>
                  </div>
              </div>
          </div>
          
          <!-- Manual Input Card -->
          <div class="bg-white dark:bg-gray-900 p-6 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-800 transition-all">
              <div class="flex items-center gap-3 mb-4">
                  <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl text-indigo-600 dark:text-indigo-400">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                  </div>
                  <h3 class="font-bold text-gray-800 dark:text-gray-200">Manual Entry</h3>
              </div>
              <form @submit.prevent="handleManualSubmit" class="flex gap-2">
                  <input 
                    v-model="manualHash" 
                    type="text" 
                    class="flex-1 rounded-2xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-indigo-500 transition-all" 
                    placeholder="Enter tracking hash..." 
                  />
                  <button 
                    type="submit" 
                    :disabled="!manualHash"
                    class="px-6 py-2 bg-gray-900 dark:bg-indigo-600 text-white rounded-2xl text-sm font-bold hover:bg-gray-800 dark:hover:bg-indigo-500 disabled:opacity-50 transition shadow-lg shadow-gray-500/10"
                  >
                    Add
                  </button>
              </form>
          </div>
        </div>

        <!-- Details Column -->
        <div class="lg:col-span-7 flex flex-col h-full">
          <!-- Active Box Info -->
          <Transition
            enter-active-class="transform transition ease-out duration-300"
            enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
          >
            <div v-if="box" class="mb-6 p-6 rounded-3xl bg-indigo-600 shadow-xl shadow-indigo-500/20 text-white relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:bg-white/20 transition-all duration-700"></div>
                <div class="relative z-10 flex justify-between items-start">
                    <div>
                        <div class="text-xs uppercase tracking-widest text-indigo-100 font-bold mb-2">Target Location</div>
                        <div class="text-2xl font-black">{{ box.path }}</div>
                        <div class="text-indigo-200 text-sm mt-1 font-medium">{{ box.label }}</div>
                    </div>
                    <button @click="reset" class="p-2 bg-white/20 hover:bg-white/30 rounded-xl transition" title="Change Box">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
            </div>
          </Transition>

          <!-- List Section -->
          <div class="flex-1 bg-white dark:bg-gray-900 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-800 flex flex-col overflow-hidden">
            <div class="p-6 border-b dark:border-gray-800 flex items-center justify-between">
                <h3 class="font-black text-gray-900 dark:text-gray-100 uppercase tracking-wider text-sm flex items-center gap-2">
                    Scanned Copies
                    <span v-if="copies.length" class="bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded-lg text-xs">{{ copies.length }}</span>
                </h3>
                <div class="text-xs text-gray-500 font-medium">Newest at top</div>
            </div>

            <div class="flex-1 overflow-y-auto max-h-[400px] sm:max-h-none">
                <TransitionGroup 
                    tag="ul" 
                    name="list"
                    class="divide-y dark:divide-gray-800"
                >
                    <li v-for="c in copies" :key="c.hash" class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900 dark:text-gray-100 leading-none mb-1">{{ c.title }}</div>
                                <div class="text-xs text-gray-500 flex items-center gap-1">
                                    <span class="truncate max-w-[120px]">{{ c.currently_at ?? 'Unshelved' }}</span>
                                    <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                                    <span class="text-indigo-600 dark:text-indigo-400 font-bold uppercase tracking-tighter">{{ box?.label }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] px-2 py-0.5 rounded-full uppercase font-black tracking-widest border" :class="c.status === 'available' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border-amber-100 dark:border-amber-800'">
                                {{ c.status }}
                            </span>
                        </div>
                    </li>
                    <li v-if="!copies.length" key="empty" class="p-12 text-center flex flex-col items-center">
                        <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4 border border-dashed border-gray-200 dark:border-gray-700">
                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <h4 class="font-bold text-gray-400 dark:text-gray-600 uppercase tracking-widest text-xs">Waiting for Scans</h4>
                        <p class="text-xs text-gray-400 mt-1">Scan book copy barcodes or enter hashes manually.</p>
                    </li>
                </TransitionGroup>
            </div>

            <!-- Footer Actions -->
            <div class="p-6 bg-gray-50 dark:bg-gray-800/30 border-t dark:border-gray-800 space-y-4">
                <div class="flex gap-3">
                    <button 
                        :disabled="!copies.length || busy" 
                        @click="doCommit"
                        class="flex-1 px-8 py-4 rounded-2xl bg-indigo-600 text-white font-black uppercase tracking-widest text-sm shadow-xl shadow-indigo-500/30 hover:bg-indigo-700 active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed disabled:scale-100 transition-all duration-200"
                    >
                        Commit {{ copies.length ? `${copies.length} Copies` : '' }}
                    </button>
                    <button 
                        @click="reset" 
                        class="px-6 py-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 font-bold uppercase tracking-widest text-sm hover:bg-gray-100 dark:hover:bg-gray-800 active:scale-95 transition-all"
                    >
                        Reset
                    </button>
                </div>
            </div>
          </div>

          <!-- Messages -->
          <div class="mt-4 space-y-3">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="error" class="p-4 rounded-2xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm font-bold border border-red-200 dark:border-red-900/50 flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ error }}
                </div>
            </Transition>
            
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="success" class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 text-sm font-bold border border-emerald-200 dark:border-emerald-900/50 flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ success }}
                </div>
            </Transition>
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
  transition: all 0.4s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>
