<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    show: Boolean,
    qrUrl: String,   // The endpoint URL to fetch the SVG from
    title: String,   // Label shown in the modal header
})

const emit = defineEmits(['close'])

const svgContent = ref('')
const loading = ref(false)
const error = ref(false)

watch(() => props.show, async (val) => {
    if (val && props.qrUrl) {
        loading.value = true
        error.value = false
        svgContent.value = ''
        try {
            const res = await fetch(props.qrUrl, { credentials: 'same-origin' })
            if (!res.ok) throw new Error('Failed to load QR code')
            svgContent.value = await res.text()
        } catch {
            error.value = true
        } finally {
            loading.value = false
        }
    }
})

const download = () => {
    const blob = new Blob([svgContent.value], { type: 'image/svg+xml' })
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = `qr-${props.title.toLowerCase().replace(/\s+/g, '-')}.svg`
    a.click()
    URL.revokeObjectURL(a.href)
}

const printQr = () => {
    const win = window.open('', '_blank')
    win.document.write(`
        <html>
            <head>
                <title>Print QR Code - ${props.title}</title>
                <style>
                    body { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: sans-serif; }
                    .container { width: 300px; text-align: center; border: 1px solid #eee; padding: 40px; border-radius: 20px; }
                    .qr-wrapper svg { width: 100%; height: auto; }
                    h2 { margin-top: 20px; color: #333; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="qr-wrapper">${svgContent.value}</div>
                    <h2>${props.title}</h2>
                </div>
                <script>window.print(); setTimeout(() => window.close(), 500);<\/script>
            </body>
        </html>
    `)
    win.document.close()
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-hidden" @click.self="emit('close')">
                <!-- Premium Backdrop -->
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xl transition-opacity" @click="emit('close')" />

                <!-- Panel -->
                <Transition
                    enter-active-class="transition ease-out duration-300 transform"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition ease-in duration-200 transform"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4"
                >
                    <div v-if="show" class="relative bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.3)] w-full max-w-sm border border-white/20 dark:border-slate-800 z-10 overflow-hidden">
                        
                        <!-- Header Area -->
                        <div class="px-8 pt-8 pb-4 flex items-start justify-between">
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400 mb-1">Secure Discovery</p>
                                <h3 class="font-black text-xl text-slate-900 dark:text-white truncate leading-tight">{{ title }}</h3>
                            </div>
                            <button @click="emit('close')" class="p-2 bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all active:scale-90">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Content Area -->
                        <div class="px-8 pb-8 flex flex-col items-center">
                            
                            <div class="w-full aspect-square bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] border-2 border-dashed border-slate-200 dark:border-slate-700 flex flex-col items-center justify-center p-6 mb-6 relative group">
                                <!-- Loading -->
                                <div v-if="loading" class="flex flex-col items-center gap-4">
                                    <div class="w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Generating...</span>
                                </div>

                                <!-- Error -->
                                <div v-else-if="error" class="flex flex-col items-center text-center gap-3">
                                    <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-2xl text-red-500">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Generation Failed</p>
                                </div>

                                <!-- QR SVG Content -->
                                <div v-else-if="svgContent"
                                    class="w-full h-full p-2 bg-white rounded-2xl shadow-xl transition-transform duration-500 group-hover:scale-105"
                                    v-html="svgContent"
                                />

                                <!-- Glow effect -->
                                <div v-if="svgContent" class="absolute inset-0 bg-indigo-500/5 rounded-[2rem] blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                            </div>

                            <div class="text-center space-y-1 mb-8">
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Universal Scan Key</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-500 font-medium px-4 leading-relaxed">
                                    This QR code provides instant mobile access to this resource.
                                </p>
                            </div>

                            <!-- Premium Actions -->
                            <div class="grid grid-cols-2 gap-3 w-full">
                                <button
                                    @click="download"
                                    :disabled="!svgContent"
                                    class="flex items-center justify-center gap-2 py-3.5 bg-slate-900 dark:bg-indigo-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl hover:bg-slate-800 dark:hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-lg shadow-slate-500/10 dark:shadow-indigo-500/20 active:scale-95"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Save SVG
                                </button>
                                <button 
                                    @click="printQr"
                                    :disabled="!svgContent"
                                    class="flex items-center justify-center gap-2 py-3.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white border border-slate-100 dark:border-slate-700 text-[11px] font-black uppercase tracking-widest rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 transition-all active:scale-95"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    Print
                                </button>
                            </div>
                        </div>

                        <!-- Footer Accent -->
                        <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
