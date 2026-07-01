<template>
  <WebsiteLayout>
    <Head title="SITS Ethiopia — Shiloh International Theological Seminary" />

    <div class="relative overflow-hidden">

      <!-- ══ Hero ══ -->
      <section class="relative min-h-screen flex items-center justify-center pt-8 pb-12">
        <div class="container mx-auto px-6 z-10 grid lg:grid-cols-12 gap-12 items-center">

          <!-- Left -->
          <div class="lg:col-span-7 space-y-8 text-left" data-aos="fade-right" data-aos-duration="1000">
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide">
              <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
              Convenient Theological Education
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight font-outfit">
              Theological Education <br>
              <span class="text-gradient">In a Digital &amp; Modern Way</span>
            </h1>

            <p class="text-lg text-slate-400 max-w-xl leading-relaxed">
              Shiloh International Theological Seminary (SITS) has been empowering leaders and transforming communities since
              <span class="text-gradient-accent font-bold">1994 G.C.</span>
              Access flexible, high-quality Christian education anywhere, anytime.
            </p>

            <div class="flex flex-wrap gap-4 pt-4">
              <Link :href="route('abouts.index')" class="btn-glow px-8 py-4 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold rounded-xl shadow-lg shadow-indigo-600/25 transition duration-300 flex items-center gap-2">
                <span>Learn More</span>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
              </Link>
              <Link :href="route('courses.index')" class="px-8 py-4 bg-slate-900/60 hover:bg-slate-800/80 text-white font-semibold rounded-xl border border-slate-800 hover:border-slate-700 transition duration-300">
                See Courses
              </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-6 pt-8 border-t border-slate-900">
              <div><span class="block text-3xl font-extrabold text-white font-outfit">30+</span><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Years of Grace</span></div>
              <div><span class="block text-3xl font-extrabold text-white font-outfit">15K+</span><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Graduates</span></div>
              <div><span class="block text-3xl font-extrabold text-white font-outfit">100%</span><span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Digital &amp; Flexible</span></div>
            </div>
          </div>

          <!-- Right card -->
          <div class="lg:col-span-5 relative" data-aos="fade-left" data-aos-duration="1000">
            <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-indigo-500 to-pink-500 opacity-20 blur-xl"></div>
            <div class="relative glass-card rounded-3xl p-8 border border-white/5 space-y-6">
              <div class="relative h-56 rounded-2xl overflow-hidden bg-slate-900 group">
                <img :src="'/img/banner/header.png'" alt="SITS Learning Portal" class="w-full h-full object-cover group-hover:scale-105 transition duration-700" />
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 to-transparent"></div>
                <div class="absolute bottom-4 left-4">
                  <span class="bg-indigo-600/90 backdrop-blur-md text-white text-xs px-3 py-1.5 rounded-lg font-bold">Digital Campus</span>
                </div>
              </div>
              <div class="space-y-4">
                <div class="flex items-start gap-3"><div class="w-6 h-6 rounded-full bg-indigo-500/10 border border-indigo-500/25 flex items-center justify-center text-indigo-400 mt-0.5 shrink-0"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div><p class="text-sm text-slate-300"><strong class="text-white">Fast Setup:</strong> Register and create your student account in minutes.</p></div>
                <div class="flex items-start gap-3"><div class="w-6 h-6 rounded-full bg-indigo-500/10 border border-indigo-500/25 flex items-center justify-center text-indigo-400 mt-0.5 shrink-0"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div><p class="text-sm text-slate-300"><strong class="text-white">Structured Learning:</strong> Access digital textbooks, lecture notes, and assignments.</p></div>
                <div class="flex items-start gap-3"><div class="w-6 h-6 rounded-full bg-indigo-500/10 border border-indigo-500/25 flex items-center justify-center text-indigo-400 mt-0.5 shrink-0"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div><p class="text-sm text-slate-300"><strong class="text-white">Expert Mentors:</strong> Regular interactive sessions with highly experienced faculty.</p></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ Courses carousel ══ -->
      <section v-if="courses.length" class="relative py-24 bg-slate-950/40 border-t border-slate-900/60">
        <div class="container mx-auto px-6">
          <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-4">Popular Programs</div>
            <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">Featured Courses</h2>
            <p class="text-slate-400">Explore our most sought-after theological programs tailored for every stage of ministry.</p>
          </div>

          <!-- Simple CSS slider -->
          <div class="relative">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              <div v-for="course in courses" :key="course.id" class="glass-card glass-card-hover rounded-2xl overflow-hidden border border-white/5 flex flex-col" data-aos="zoom-in" data-aos-delay="100">
                <div class="relative h-48 bg-slate-950 overflow-hidden">
                  <img v-if="course.banner" :src="`/storage/${course.banner}`" :alt="course.title" class="w-full h-full object-cover hover:scale-105 transition duration-500" loading="lazy" />
                  <div v-else class="w-full h-full bg-gradient-to-br from-slate-900 to-indigo-950/60 flex items-center justify-center text-slate-700">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                  </div>
                  <div class="absolute top-4 left-4"><span class="bg-indigo-500/90 backdrop-blur-md text-white text-xs px-3 py-1.5 rounded-lg font-bold uppercase">{{ course.category }}</span></div>
                </div>
                <div class="p-6 flex-1 flex flex-col justify-between">
                  <div>
                    <h4 class="text-lg font-bold text-white font-outfit line-clamp-2 mb-2">{{ course.title }}</h4>
                    <p class="text-sm text-slate-400 line-clamp-3 leading-relaxed">{{ course.description }}</p>
                  </div>
                  <div class="pt-5 mt-5 border-t border-slate-900/60 flex items-center justify-between">
                    <span class="text-gradient-accent font-extrabold text-lg font-outfit">{{ formatAmount(course.amount_paid) }} Br</span>
                    <Link :href="route('courses.show', course.id)" class="text-xs text-indigo-400 hover:text-indigo-300 font-bold transition">View →</Link>
                  </div>
                </div>
              </div>
            </div>
            <div class="text-center mt-10">
              <Link :href="route('courses.index')" class="inline-flex items-center gap-2 text-indigo-400 hover:text-indigo-300 font-semibold text-sm transition group">
                <span>View All Courses</span>
                <svg class="w-3 h-3 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
              </Link>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ Registration form ══ -->
      <section class="relative py-24 bg-slate-950/20 border-t border-slate-900/60">
        <div class="container mx-auto px-6">
          <div class="grid lg:grid-cols-12 gap-12 items-center">
            <div class="lg:col-span-6 space-y-6" data-aos="fade-right">
              <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs font-semibold">Join SITS Today</div>
              <h2 class="text-3xl md:text-5xl font-bold text-white font-outfit leading-tight">Ready to Begin <br><span class="text-gradient">Your Theological Journey?</span></h2>
              <p class="text-slate-400 leading-relaxed">Take the first step toward spiritual and academic growth. SITS provides a streamlined enrollment process to help you access customized courses, online resources, and expert guidance.</p>
              <div class="space-y-4 pt-2">
                <div v-for="item in features" :key="item.title" class="flex items-start gap-3">
                  <div class="w-6 h-6 rounded-full bg-indigo-500/10 border border-indigo-500/25 flex items-center justify-center text-indigo-400 mt-0.5 shrink-0"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                  <p class="text-sm text-slate-300"><strong class="text-white">{{ item.title }}:</strong> {{ item.desc }}</p>
                </div>
              </div>
            </div>
            <div class="lg:col-span-6" data-aos="zoom-in" data-aos-delay="200">
              <div class="relative">
                <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-amber-500 to-orange-600 opacity-15 blur-xl"></div>
                <div class="relative glass-card rounded-3xl p-8 border border-white/5">
                  <h3 class="text-2xl font-bold text-white font-outfit mb-2">Enrollment Registration</h3>
                  <p class="text-xs text-slate-400 mb-6">Submit this form to express your interest in SITS academic programs.</p>
                  <form @submit.prevent="submitEnrollment" class="space-y-5">
                    <div>
                      <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Your Full Name</label>
                      <input v-model="enrollForm.name" type="text" required placeholder="Abebe Kebede" class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-500 transition" />
                    </div>
                    <div class="grid sm:grid-cols-2 gap-5">
                      <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Phone</label>
                        <input v-model="enrollForm.phone" type="tel" required placeholder="+251 911..." class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-500 transition" />
                      </div>
                      <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email</label>
                        <input v-model="enrollForm.email" type="email" required placeholder="abebe@example.com" class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-500 transition" />
                      </div>
                    </div>
                    <div>
                      <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Address</label>
                      <input v-model="enrollForm.address" type="text" required placeholder="Addis Ababa, Ethiopia" class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-500 transition" />
                    </div>
                    <button type="submit" :disabled="enrollForm.processing" class="btn-glow w-full py-4 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-slate-950 font-bold rounded-xl shadow-lg transition duration-300 disabled:opacity-60">
                      {{ enrollForm.processing ? 'Submitting…' : 'Submit Registration' }}
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ Trainers ══ -->
      <section v-if="trainers.length" class="relative py-24">
        <div class="container mx-auto px-6">
          <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-4">Faculty Members</div>
            <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">Our Expert Trainers</h2>
            <p class="text-slate-400">Our expert trainers provide top-tier academic guidance, real-world ministry experience, and dedicated mentorship.</p>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="trainer in trainers" :key="trainer.id" class="glass-card glass-card-hover rounded-2xl overflow-hidden border border-white/5" data-aos="fade-up" data-aos-delay="100">
              <div class="relative h-64 bg-slate-950 overflow-hidden group">
                <img v-if="trainer.image" :src="`/storage/${trainer.image}`" :alt="trainer.name" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy" />
                <div v-else class="w-full h-full bg-gradient-to-br from-slate-900 to-indigo-950/40 flex items-center justify-center text-slate-700">
                  <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
              </div>
              <div class="p-6 text-center">
                <h4 class="text-lg font-bold text-white font-outfit">{{ trainer.name }}</h4>
                <p class="text-amber-500 text-xs font-semibold uppercase tracking-wider mt-1">{{ trainer.position }}</p>
                <p class="text-sm text-slate-400 mt-3 line-clamp-3">{{ trainer.description }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ Events ══ -->
      <section v-if="events.length" class="relative py-24 bg-slate-950/40 border-y border-slate-900/60">
        <div class="container mx-auto px-6">
          <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-4">Institution Calendar</div>
            <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">Upcoming Events</h2>
            <p class="text-slate-400">Keep track of our latest seminars, webinars, and academic conferences.</p>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6" data-aos="fade-up" data-aos-delay="150">
            <div v-for="event in events" :key="event.id" class="glass-card glass-card-hover rounded-2xl overflow-hidden flex flex-col border border-white/5">
              <div class="relative h-48 bg-slate-950">
                <img v-if="event.banner" :src="`/storage/${event.banner}`" :alt="event.title" class="w-full h-full object-cover" loading="lazy" />
                <div v-else class="w-full h-full bg-gradient-to-br from-slate-900 to-indigo-950/40 flex items-center justify-center text-slate-700">
                  <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
              </div>
              <div class="p-6 flex-1 flex flex-col justify-between">
                <div class="flex items-start gap-4">
                  <div class="text-center bg-indigo-600 text-white px-3 py-2.5 rounded-xl shadow-md flex flex-col justify-center min-w-[55px] shrink-0">
                    <span class="block text-xl font-extrabold leading-none font-outfit">{{ formatDay(event.date) }}</span>
                    <span class="block text-[10px] uppercase font-bold tracking-wider mt-1">{{ formatMonth(event.date) }}</span>
                  </div>
                  <div class="space-y-1.5">
                    <h4 class="text-lg font-bold text-white font-outfit">{{ event.title }}</h4>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                      <span>{{ formatTime(event.start_time) }} – {{ formatTime(event.end_time) }}</span>
                      <span>{{ event.location }}</span>
                    </div>
                  </div>
                </div>
                <p class="text-sm text-slate-400 mt-4 leading-relaxed line-clamp-3">{{ event.description }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ Newsletter CTA ══ -->
      <section class="relative py-24 overflow-hidden">
        <div class="container mx-auto px-6 relative z-10">
          <div class="max-w-4xl mx-auto text-center space-y-8">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/25 text-indigo-300 text-xs font-semibold">
              <span class="px-2 py-0.5 rounded-full bg-indigo-600 text-white font-bold text-[10px] mr-1.5">New</span>
              Introducing SITS Online Programs!
            </div>
            <h2 class="text-4xl md:text-5xl font-extrabold text-white font-outfit leading-tight">
              Transforming Lives Through <br><span class="text-gradient">Accessible Theological Education</span>
            </h2>
            <p class="text-slate-400 text-lg max-w-2xl mx-auto leading-relaxed">At SITS, we empower individuals by delivering sustainable, accessible, and innovative theological education. Subscribe to our newsletter to receive updates on new programs and academic news.</p>
            <div class="max-w-md mx-auto">
              <div class="relative">
                <div class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-indigo-500 to-purple-600 opacity-15 blur-lg"></div>
                <form @submit.prevent="submitNewsletterCta" class="relative flex flex-col sm:flex-row gap-3 p-2 rounded-2xl bg-slate-900 border border-white/5">
                  <input v-model="newsletterCtaEmail" type="email" required placeholder="Enter your email address…" class="flex-1 px-4 py-3 rounded-xl bg-transparent text-white placeholder-slate-500 text-sm focus:outline-none" />
                  <button type="submit" class="btn-glow px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-sm transition duration-300">Subscribe</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>
  </WebsiteLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue'

const props = defineProps({
  courses:  { type: Array, default: () => [] },
  trainers: { type: Array, default: () => [] },
  events:   { type: Array, default: () => [] },
})

const enrollForm = useForm({ name: '', email: '', phone: '', address: '', type: 'subscribe' })
const newsletterCtaEmail = ref('')

const features = [
  { title: 'Fast Setup',         desc: 'Register and create your student account in minutes.' },
  { title: 'Structured Learning',desc: 'Access digital textbooks, lecture notes, and assignments.' },
  { title: 'Expert Mentors',     desc: 'Regular interactive sessions with highly experienced faculty.' },
]

function submitEnrollment() {
  enrollForm.post('/subscriptions', { onSuccess: () => enrollForm.reset() })
}

function submitNewsletterCta() {
  router.post('/subscriptions', {
    name: newsletterCtaEmail.value.split('@')[0] || 'Subscriber',
    email: newsletterCtaEmail.value,
    phone: 'N/A', address: 'N/A', type: 'newsletter',
  }, { onSuccess: () => { newsletterCtaEmail.value = '' }, preserveScroll: true })
}

function formatAmount(val) { return Number(val).toLocaleString('en-ET', { minimumFractionDigits: 2 }) }
function formatDay(date)   { return new Date(date).toLocaleDateString('en-ET', { day: '2-digit' }) }
function formatMonth(date) { return new Date(date).toLocaleDateString('en-ET', { month: 'short' }) }
function formatTime(t)     { return t ? new Date(`2000-01-01T${t}`).toLocaleTimeString('en', { hour: '2-digit', minute: '2-digit' }) : '' }
</script>

<style scoped>
.glass-card { background: rgba(15,23,42,0.45); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
.glass-card-hover { transition: all 0.4s cubic-bezier(0.16,1,0.3,1); }
.glass-card-hover:hover { transform: translateY(-6px); background: rgba(15,23,42,0.6); }
.text-gradient { background: linear-gradient(135deg,#fff 30%,#a5b4fc 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.text-gradient-accent { background: linear-gradient(135deg,#fbbf24 0%,#f59e0b 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.btn-glow { position: relative; overflow: hidden; }
.btn-glow::after { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent); transition:all 0.6s ease; }
.btn-glow:hover::after { left:100%; }
.line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.line-clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
</style>
