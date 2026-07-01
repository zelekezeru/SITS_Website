<template>
  <WebsiteLayout>
    <Head title="Courses — SITS Ethiopia" />

    <!-- Hero -->
    <section class="relative py-24 pt-12 pb-16 overflow-hidden" data-aos="fade-down">
      <div class="container mx-auto px-6 text-center relative z-10">
        <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-6">Academic Catalog</div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-white font-outfit mb-4">Courses</h1>
        <nav class="flex justify-center items-center gap-2 text-sm text-slate-500">
          <Link :href="route('home')" class="hover:text-white transition">Home</Link>
          <span>/</span>
          <span class="text-slate-300">Courses</span>
        </nav>
      </div>
    </section>

    <!-- Courses Grid -->
    <section class="py-20 relative">
      <div class="container mx-auto px-6">
        <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
          <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-4">Explore Programs</div>
          <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">Our Popular Courses</h2>
          <p class="text-slate-400">SITS offers a comprehensive theological curriculum to prepare you for ministry leadership.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
          <div v-for="course in courses.data" :key="course.id"
            class="glass-card glass-card-hover rounded-2xl overflow-hidden h-full flex flex-col border border-white/5"
            data-aos="zoom-in" data-aos-delay="100">
            <!-- Banner -->
            <div class="relative h-48 overflow-hidden bg-slate-950">
              <img v-if="course.banner" :src="`/storage/${course.banner}`" :alt="course.title" class="w-full h-full object-cover hover:scale-105 transition duration-500" loading="lazy" />
              <div v-else class="w-full h-full bg-gradient-to-br from-slate-900 to-indigo-950/60 flex items-center justify-center text-slate-700">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
              </div>
              <div class="absolute top-4 left-4"><span class="bg-indigo-500/90 backdrop-blur-md text-white text-xs px-3 py-1.5 rounded-lg font-bold uppercase">{{ course.category }}</span></div>
            </div>
            <!-- Content -->
            <div class="p-6 flex-1 flex flex-col justify-between">
              <div class="space-y-2">
                <h4 class="text-lg font-bold text-white font-outfit line-clamp-2 hover:text-indigo-400 transition">
                  <Link :href="route('courses.show', course.id)">{{ course.title }}</Link>
                </h4>
                <p class="text-sm text-slate-400 line-clamp-3 leading-relaxed">{{ course.description }}</p>
              </div>
              <div class="pt-5 mt-5 border-t border-slate-900/60 flex items-center justify-between">
                <div>
                  <span class="block text-[10px] text-slate-500 uppercase font-bold tracking-wider">Tuition Fee</span>
                  <span class="text-gradient-accent font-extrabold text-lg font-outfit">{{ formatAmount(course.amount_paid) }} Br</span>
                </div>
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-slate-900 border border-white/5 flex items-center justify-center text-indigo-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  </div>
                  <span class="text-xs text-slate-400 font-medium">{{ course.instructor || 'Field Experts' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Inertia Pagination -->
        <div v-if="courses.last_page > 1" class="flex justify-center gap-2 mt-12">
          <Link v-for="link in courses.links" :key="link.label"
            :href="link.url || '#'"
            :class="['px-4 py-2 rounded-xl text-xs font-semibold transition', link.active ? 'bg-indigo-600 text-white' : 'bg-slate-900 border border-slate-800 text-slate-400 hover:text-white', !link.url && 'opacity-40 pointer-events-none']"
            v-html="link.label" preserve-scroll />
        </div>
      </div>
    </section>

    <!-- Registration CTA -->
    <section class="py-24 bg-slate-950/40 border-t border-slate-900/60">
      <div class="container mx-auto px-6">
        <div class="grid lg:grid-cols-12 gap-12 items-center">
          <div class="lg:col-span-6 space-y-6" data-aos="fade-right">
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs font-semibold">Join SITS Today</div>
            <h2 class="text-3xl md:text-5xl font-bold text-white font-outfit leading-tight">Ready to Begin <br><span class="text-gradient">Your Theological Journey?</span></h2>
            <p class="text-slate-400 leading-relaxed">Take the first step toward spiritual and academic growth. SITS provides a streamlined enrollment process.</p>
          </div>
          <div class="lg:col-span-6" data-aos="zoom-in" data-aos-delay="200">
            <div class="relative">
              <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-amber-500 to-orange-600 opacity-15 blur-xl"></div>
              <div class="relative glass-card rounded-3xl p-8 border border-white/5">
                <h3 class="text-2xl font-bold text-white font-outfit mb-2">Enrollment Registration</h3>
                <p class="text-xs text-slate-400 mb-6">Submit this form to express your interest in SITS academic programs.</p>
                <form @submit.prevent="submitEnrollment" class="space-y-5">
                  <input v-model="form.name" type="text" required placeholder="Your Full Name" class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-500 transition" />
                  <div class="grid sm:grid-cols-2 gap-5">
                    <input v-model="form.phone" type="tel" required placeholder="Phone Number" class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-500 transition" />
                    <input v-model="form.email" type="email" required placeholder="Email Address" class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-500 transition" />
                  </div>
                  <input v-model="form.address" type="text" required placeholder="Living Address" class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-500 transition" />
                  <button type="submit" :disabled="form.processing" class="btn-glow w-full py-4 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-slate-950 font-bold rounded-xl transition duration-300 disabled:opacity-60">
                    {{ form.processing ? 'Submitting…' : 'Submit Registration' }}
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </WebsiteLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue'

defineProps({ courses: { type: Object, required: true } })

const form = useForm({ name: '', email: '', phone: '', address: '', type: 'subscribe' })
function submitEnrollment() { form.post('/subscriptions', { onSuccess: () => form.reset() }) }
function formatAmount(val) { return Number(val).toLocaleString('en-ET', { minimumFractionDigits: 2 }) }
</script>

<style scoped>
.glass-card { background: rgba(15,23,42,0.45); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
.glass-card-hover { transition: all 0.4s cubic-bezier(0.16,1,0.3,1); }
.glass-card-hover:hover { transform: translateY(-6px); background: rgba(15,23,42,0.6); }
.text-gradient { background: linear-gradient(135deg,#fff 30%,#a5b4fc 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.text-gradient-accent { background: linear-gradient(135deg,#fbbf24 0%,#f59e0b 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.btn-glow::after { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent); transition:all 0.6s ease; }
.btn-glow:hover::after { left:100%; }
.line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.line-clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
</style>
