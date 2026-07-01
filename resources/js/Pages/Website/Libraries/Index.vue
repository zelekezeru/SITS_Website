<template>
  <WebsiteLayout>
    <Head title="Digital Libraries — SITS Ethiopia" />

    <!-- Hero -->
    <section class="relative py-24 pt-12 pb-16 overflow-hidden" data-aos="fade-down">
      <div class="container mx-auto px-6 text-center relative z-10">
        <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-6">Knowledge Hub</div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-white font-outfit mb-4">Libraries</h1>
        <nav class="flex justify-center items-center gap-2 text-sm text-slate-500">
          <Link :href="route('home')" class="hover:text-white transition">Home</Link>
          <span>/</span><span class="text-slate-300">Libraries</span>
        </nav>
      </div>
    </section>

    <!-- Resources Grid -->
    <section class="py-20 relative">
      <div class="container mx-auto px-6">
        <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
          <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-4">Browse Resources</div>
          <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">Digital Resource Center</h2>
          <p class="text-slate-400">Access our curated collection of theological books, journals, and research materials.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <div v-for="lib in libraries.data" :key="lib.id" class="glass-card glass-card-hover rounded-2xl overflow-hidden border border-white/5 flex flex-col" data-aos="zoom-in" data-aos-delay="100">
            <div class="relative h-44 bg-slate-950 overflow-hidden">
              <img v-if="lib.banner" :src="`/storage/${lib.banner}`" :alt="lib.title" class="w-full h-full object-cover hover:scale-105 transition duration-500" loading="lazy" />
              <div v-else class="w-full h-full bg-gradient-to-br from-slate-900 to-indigo-950/60 flex items-center justify-center text-slate-700">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
              </div>
              <div class="absolute top-3 left-3"><span class="bg-indigo-500/90 backdrop-blur-md text-white text-[10px] px-2.5 py-1 rounded-md font-bold uppercase">{{ lib.category }}</span></div>
            </div>
            <div class="p-5 flex-1 flex flex-col justify-between">
              <div>
                <h4 class="text-sm font-bold text-white font-outfit line-clamp-2 mb-2">{{ lib.title }}</h4>
                <p class="text-xs text-slate-400 line-clamp-3 leading-relaxed">{{ lib.description }}</p>
              </div>
              <div class="pt-4 mt-4 border-t border-slate-900/60">
                <a v-if="lib.link" :href="lib.link" target="_blank" class="inline-flex items-center gap-1.5 text-indigo-400 hover:text-indigo-300 font-semibold text-xs transition group">
                  <span>Access Resource</span>
                  <svg class="w-3 h-3 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                <span v-else class="text-xs text-slate-600 font-medium">No link available</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="libraries.last_page > 1" class="flex justify-center gap-2 mt-12">
          <Link v-for="link in libraries.links" :key="link.label"
            :href="link.url || '#'"
            :class="['px-4 py-2 rounded-xl text-xs font-semibold transition', link.active ? 'bg-indigo-600 text-white' : 'bg-slate-900 border border-slate-800 text-slate-400 hover:text-white', !link.url && 'opacity-40 pointer-events-none']"
            v-html="link.label" preserve-scroll />
        </div>
      </div>
    </section>
  </WebsiteLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue'
defineProps({ libraries: { type: Object, required: true } })
</script>

<style scoped>
.glass-card { background: rgba(15,23,42,0.45); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
.glass-card-hover { transition: all 0.4s cubic-bezier(0.16,1,0.3,1); }
.glass-card-hover:hover { transform: translateY(-6px); background: rgba(15,23,42,0.6); }
.line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.line-clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
</style>
