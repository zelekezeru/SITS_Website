<template>
  <WebsiteLayout>
    <Head title="About — SITS Ethiopia" />

    <!-- Hero -->
    <section class="relative py-24 pt-12 pb-16 overflow-hidden" data-aos="fade-down">
      <div class="container mx-auto px-6 text-center relative z-10">
        <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-6">Our Story</div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-white font-outfit mb-4">About SITS</h1>
        <nav class="flex justify-center items-center gap-2 text-sm text-slate-500">
          <Link :href="route('home')" class="hover:text-white transition">Home</Link>
          <span>/</span><span class="text-slate-300">About</span>
        </nav>
      </div>
    </section>

    <!-- Mission Section -->
    <section class="relative py-20">
      <div class="container mx-auto px-6">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
          <div data-aos="fade-right">
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-6">Who We Are</div>
            <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-6">Shiloh International Theological Seminary</h2>
            <p class="text-slate-400 leading-relaxed mb-4">Founded in 1994 G.C., SITS has been a beacon of theological education in Ethiopia and beyond. We are committed to raising spiritual leaders equipped for transformative ministry.</p>
            <p class="text-slate-400 leading-relaxed mb-8">Our programs blend traditional theological depth with modern pedagogical approaches, making quality Christian education accessible to all.</p>
            <div class="grid grid-cols-3 gap-6 border-t border-slate-900 pt-8">
              <div><span class="block text-3xl font-extrabold text-white font-outfit">30+</span><span class="text-xs text-slate-500 uppercase tracking-wider">Years</span></div>
              <div><span class="block text-3xl font-extrabold text-white font-outfit">15K+</span><span class="text-xs text-slate-500 uppercase tracking-wider">Graduates</span></div>
              <div><span class="block text-3xl font-extrabold text-white font-outfit">100%</span><span class="text-xs text-slate-500 uppercase tracking-wider">Dedicated</span></div>
            </div>
          </div>
          <div data-aos="fade-left">
            <div class="relative">
              <div class="absolute -inset-2 rounded-3xl bg-gradient-to-r from-indigo-500 to-cyan-400 opacity-15 blur-2xl"></div>
              <img :src="'/img/about.png'" alt="SITS Campus" class="relative rounded-2xl w-full object-cover shadow-2xl" onerror="this.style.display='none'" loading="lazy" />
              <div class="relative rounded-2xl glass-card p-8 border border-white/5 mt-6">
                <h3 class="text-lg font-bold text-white font-outfit mb-3">Our Mission</h3>
                <p class="text-sm text-slate-400 leading-relaxed">To provide accessible, affordable, and high-quality theological education that transforms lives and communities through the power of the Gospel.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Values Section -->
    <section class="relative py-20 bg-slate-950/40 border-t border-slate-900/60">
      <div class="container mx-auto px-6">
        <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
          <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-4">Core Principles</div>
          <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">Our Values</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div v-for="value in values" :key="value.title" class="glass-card rounded-2xl p-6 border border-white/5 text-center" data-aos="zoom-in" data-aos-delay="100">
            <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center border border-white/5" :class="value.iconBg">
              <img :src="value.imgSrc" :alt="value.title" class="w-8 h-8 object-contain" />
            </div>
            <h3 class="text-lg font-bold text-white font-outfit mb-2">{{ value.title }}</h3>
            <p class="text-sm text-slate-400 leading-relaxed">{{ value.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Gallery (if any) -->
    <section v-if="galleries && galleries.length" class="relative py-20">
      <div class="container mx-auto px-6">
        <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
          <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-4">Campus Life</div>
          <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">Photo Gallery</h2>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          <div v-for="item in galleries" :key="item.id" class="relative rounded-xl overflow-hidden h-48 group cursor-pointer" @click="openLightbox(item)">
            <img :src="`/storage/${item.image}`" :alt="item.description" class="w-full h-full object-cover group-hover:scale-110 transition duration-500" loading="lazy" />
            <div class="absolute inset-0 bg-slate-950/0 group-hover:bg-slate-950/40 transition duration-300 flex items-end">
              <p class="text-xs text-white font-semibold px-3 py-2 opacity-0 group-hover:opacity-100 transition">{{ item.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Lightbox -->
    <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="lightbox" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/95 backdrop-blur-xl" @click.self="lightbox=null">
        <div class="relative max-w-4xl w-full mx-4">
          <button @click="lightbox=null" class="absolute -top-10 right-0 text-slate-400 hover:text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
          <img :src="`/storage/${lightbox.image}`" :alt="lightbox.description" class="w-full rounded-2xl shadow-2xl" />
          <p v-if="lightbox.description" class="text-center text-sm text-slate-400 mt-4">{{ lightbox.description }}</p>
        </div>
      </div>
    </Transition>
  </WebsiteLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue'

defineProps({ galleries: { type: Array, default: () => [] } })

const lightbox = ref(null)
function openLightbox(item) { lightbox.value = item }

const values = [
  { title: 'Sustainable', desc: 'Building lasting impact through ethical and responsible education.', iconBg: 'bg-indigo-500/10', imgSrc: '/img/features/sustainability.png' },
  { title: 'Accessible',  desc: 'Making quality theological education available to everyone everywhere.', iconBg: 'bg-amber-500/10', imgSrc: '/img/features/accessibility.png' },
  { title: 'Vital',       desc: 'Providing life-giving, Spirit-filled theological training for ministry.', iconBg: 'bg-purple-500/10', imgSrc: '/img/features/vital.png' },
  { title: 'Excellent',   desc: 'Maintaining the highest academic standards in all our programs.', iconBg: 'bg-emerald-500/10', imgSrc: '/img/features/excellent.png' },
]
</script>

<style scoped>
.glass-card { background: rgba(15,23,42,0.45); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
</style>
