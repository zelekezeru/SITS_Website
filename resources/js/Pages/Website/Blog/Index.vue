<template>
  <WebsiteLayout>
    <Head title="Blog — SITS Ethiopia" />

    <!-- Hero -->
    <section class="relative py-24 pt-12 pb-16 overflow-hidden" data-aos="fade-down">
      <div class="container mx-auto px-6 text-center relative z-10">
        <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-6">Insights & News</div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-white font-outfit mb-4">Blog</h1>
        <nav class="flex justify-center items-center gap-2 text-sm text-slate-500">
          <Link :href="route('home')" class="hover:text-white transition">Home</Link>
          <span>/</span><span class="text-slate-300">Blog</span>
        </nav>
      </div>
    </section>

    <!-- Articles -->
    <section class="py-20 relative">
      <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <article v-for="blog in blogs.data" :key="blog.id" class="glass-card glass-card-hover rounded-2xl overflow-hidden border border-white/5 flex flex-col" data-aos="fade-up" data-aos-delay="100">
            <div class="relative h-52 bg-slate-950 overflow-hidden">
              <img v-if="blog.image" :src="`/storage/${blog.image}`" :alt="blog.title" class="w-full h-full object-cover hover:scale-105 transition duration-500" loading="lazy" />
              <div v-else class="w-full h-full bg-gradient-to-br from-slate-900 to-indigo-950/60 flex items-center justify-center text-slate-700">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
              </div>
              <div class="absolute top-4 left-4"><span class="bg-indigo-500/90 backdrop-blur-md text-white text-xs px-3 py-1.5 rounded-lg font-bold uppercase">{{ blog.category }}</span></div>
            </div>
            <div class="p-6 flex-1 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-3 text-xs text-slate-500 mb-3">
                  <span>{{ blog.author }}</span>
                  <span>·</span>
                  <span>{{ formatDate(blog.date) }}</span>
                </div>
                <h3 class="text-lg font-bold text-white font-outfit line-clamp-2 mb-2 hover:text-indigo-400 transition">
                  <Link :href="route('blogs.show', blog.id)">{{ blog.title }}</Link>
                </h3>
                <p class="text-sm text-slate-400 line-clamp-3 leading-relaxed">{{ stripHtml(blog.content) }}</p>
              </div>
              <div class="pt-5 mt-5 border-t border-slate-900/60">
                <Link :href="route('blogs.show', blog.id)" class="inline-flex items-center gap-2 text-indigo-400 hover:text-indigo-300 font-semibold text-xs transition group">
                  <span>Read Article</span>
                  <svg class="w-3 h-3 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </Link>
              </div>
            </div>
          </article>
        </div>

        <!-- Pagination -->
        <div v-if="blogs.last_page > 1" class="flex justify-center gap-2 mt-12">
          <Link v-for="link in blogs.links" :key="link.label"
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

defineProps({ blogs: { type: Object, required: true } })

function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-ET', { year: 'numeric', month: 'short', day: '2-digit' }) : '' }
function stripHtml(h) { return h ? h.replace(/<[^>]+>/g, '') : '' }
</script>

<style scoped>
.glass-card { background: rgba(15,23,42,0.45); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
.glass-card-hover { transition: all 0.4s cubic-bezier(0.16,1,0.3,1); }
.glass-card-hover:hover { transform: translateY(-6px); background: rgba(15,23,42,0.6); }
.line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.line-clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
</style>
