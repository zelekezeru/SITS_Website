<script setup>
import { ref, shallowRef, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue'
import * as pdfjsLib from 'pdfjs-dist'
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url'

const props = defineProps({ secureDocument: Object })
pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl

const canvasContainer = ref(null)
const page = ref(1), totalPages = ref(0), scale = ref(1.25)
const pdfDoc = shallowRef(null)
const loading = ref(true)

onMounted(async () => {
  window.addEventListener('resize', onResize)
  try {
    const loadingTask = pdfjsLib.getDocument({
      url: `/archive/${props.secureDocument.id}/stream`,
      withCredentials: true,
      isEvalSupported: false,
      disableAutoFetch: true,
      disableStream: false,
    })
    pdfDoc.value = await loadingTask.promise
    totalPages.value = pdfDoc.value.numPages
    await render(page.value)
    loading.value = false

    // Anti-grab hardening
    document.addEventListener('contextmenu', block, true)
    document.addEventListener('keydown', blockKeys, true)
  } catch (error) {
    console.error('Error loading PDF:', error)
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', onResize)
  document.removeEventListener('contextmenu', block, true)
  document.removeEventListener('keydown', blockKeys, true)
})

function block(e) { e.preventDefault() }
function blockKeys(e) {
  const k = e.key.toLowerCase()
  if ((e.ctrlKey || e.metaKey) && (k === 's' || k === 'p')) {
    e.preventDefault()
  }
}

async function render(n) {
  const pdfPage = await pdfDoc.value.getPage(n)
  
  let currentScale = scale.value
  if (currentScale === 'auto' && canvasContainer.value) {
    const containerWidth = canvasContainer.value.clientWidth || window.innerWidth - 64
    const unscaledViewport = pdfPage.getViewport({ scale: 1 })
    currentScale = (containerWidth - 16) / unscaledViewport.width
  }
  
  if (typeof currentScale !== 'number' || currentScale < 0.1) {
    currentScale = 1.25
  }

  const outputScale = window.devicePixelRatio || 1
  const viewport = pdfPage.getViewport({ scale: currentScale })
  
  const canvas = document.createElement('canvas')
  const ctx = canvas.getContext('2d')
  
  canvas.width = Math.floor(viewport.width * outputScale)
  canvas.height = Math.floor(viewport.height * outputScale)
  canvas.style.width = Math.floor(viewport.width) + "px"
  canvas.style.height = Math.floor(viewport.height) + "px"
  
  canvasContainer.value.replaceChildren(canvas)
  
  const renderContext = {
    canvasContext: ctx,
    viewport: viewport,
    transform: outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null
  }
  
  await pdfPage.render(renderContext).promise

  // Watermark
  if (props.secureDocument.watermark_user) {
    ctx.save()
    ctx.font = Math.round(24 * currentScale) + 'px sans-serif'
    ctx.fillStyle = 'rgba(0,0,0,0.12)'
    ctx.translate(canvas.width/2, canvas.height/2)
    ctx.rotate(-0.5)
    ctx.textAlign = 'center'
    ctx.fillText(props.secureDocument.watermark_text, 0, 0)
    ctx.restore()
  }

  // Heartbeat
  fetch(`/archive/${props.secureDocument.id}/heartbeat?page=${n}`, { 
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    }
  })
}

const changePage = (delta) => {
    const next = page.value + delta
    if (next >= 1 && next <= totalPages.value) {
        page.value = next
        render(page.value)
    }
}

const updateScale = (delta) => {
    if (scale.value === 'auto') scale.value = 1.5
    scale.value = Math.max(0.5, Math.min(3, scale.value + delta))
    render(page.value)
}

const setAutoFit = () => {
    scale.value = 'auto'
    render(page.value)
}

const onResize = () => {
    if (scale.value === 'auto') {
        render(page.value)
    }
}
</script>

<template>
  <Head :title="secureDocument.title" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <Link :href="route('library.archive.index')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition">&larr;</Link>
            <h2 class="font-semibold text-lg sm:text-xl text-slate-800 dark:text-slate-200 leading-tight truncate max-w-[200px] sm:max-w-none">
            {{ secureDocument.title }}
            </h2>
        </div>
        <div class="flex flex-wrap items-center gap-2 sm:gap-4">
            <!-- Zoom Controls -->
            <div class="flex items-center bg-slate-100 dark:bg-slate-800 rounded-lg p-1 border dark:border-slate-700">
                <button @click="updateScale(-0.25)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded text-slate-700 dark:text-slate-300" title="Zoom Out">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                </button>
                <button @click="setAutoFit" class="px-2 text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400" title="Fit to Width">
                    Fit
                </button>
                <button @click="updateScale(0.25)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded text-slate-700 dark:text-slate-300" title="Zoom In">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                </button>
            </div>

            <!-- Page Controls -->
            <div class="flex items-center bg-slate-100 dark:bg-slate-800 rounded-lg p-1 border dark:border-slate-700">
                <button @click="changePage(-1)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded shadow-sm disabled:opacity-50 text-slate-700 dark:text-slate-300 transition" :disabled="page <= 1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <span class="px-3 text-xs font-bold text-slate-900 dark:text-slate-100 min-w-[50px] text-center">{{ page }} / {{ totalPages }}</span>
                <button @click="changePage(1)" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded shadow-sm disabled:opacity-50 text-slate-700 dark:text-slate-300 transition" :disabled="page >= totalPages">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </button>
            </div>
        </div>
      </div>
    </template>

    <div class="py-6 sm:py-12 select-none" oncopy="return false" oncut="return false" oncontextmenu="return false">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-center overflow-x-auto">
        <div v-if="loading" class="flex items-center justify-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>
        <div ref="canvasContainer" class="border dark:border-slate-800 rounded-lg shadow-2xl bg-white dark:bg-slate-900 overflow-hidden transition-all duration-300"></div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style>
@media print {
  body * { display: none !important; }
  body::before { content: 'Printing is disabled for secure documents.'; padding: 20px; font-size: 20px; }
}
</style>
