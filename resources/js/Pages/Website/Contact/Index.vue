<template>
  <WebsiteLayout>
    <Head title="Contact — SITS Ethiopia" />

    <!-- Hero -->
    <section class="relative py-24 pt-12 pb-16 overflow-hidden" data-aos="fade-down">
      <div class="container mx-auto px-6 text-center relative z-10">
        <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-6">Get in Touch</div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-white font-outfit mb-4">Contact SITS</h1>
        <nav class="flex justify-center items-center gap-2 text-sm text-slate-500">
          <Link :href="route('home')" class="hover:text-white transition">Home</Link>
          <span>/</span><span class="text-slate-300">Contact</span>
        </nav>
      </div>
    </section>

    <!-- Contact Content -->
    <section class="py-20 relative">
      <div class="container mx-auto px-6">
        <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
          <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-4">Send a Message</div>
          <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">You Can Send Us a Message Here!</h2>
          <p class="text-slate-400">We would love to hear from you. Please send us your query and we will get back to you as soon as possible.</p>
        </div>

        <div class="grid lg:grid-cols-5 gap-12 max-w-6xl mx-auto">
          <!-- Contact Info -->
          <div class="lg:col-span-2 space-y-6" data-aos="fade-right">
            <div v-for="info in contactInfo" :key="info.label" class="glass-card rounded-2xl p-6 border border-white/5 flex items-start gap-4">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" :class="info.iconBg">
                <svg class="w-5 h-5" :class="info.iconColor" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" v-html="info.icon"></svg>
              </div>
              <div>
                <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider mb-1">{{ info.label }}</p>
                <p class="text-sm text-white font-semibold">{{ info.value }}</p>
              </div>
            </div>

            <!-- Social Links -->
            <div class="glass-card rounded-2xl p-6 border border-white/5">
              <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider mb-4">Follow Us</p>
              <div class="flex gap-3">
                <a href="#" class="w-9 h-9 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-400 hover:text-indigo-400 hover:border-indigo-500/30 transition">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <a href="https://x.com/sitsethiopia" target="_blank" class="w-9 h-9 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-400 hover:text-indigo-400 hover:border-indigo-500/30 transition">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <a href="https://www.linkedin.com/in/shiloh-intermational-theological-seminary-4a0536346" target="_blank" class="w-9 h-9 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-400 hover:text-indigo-400 hover:border-indigo-500/30 transition">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                </a>
              </div>
            </div>
          </div>

          <!-- Message Form -->
          <div class="lg:col-span-3" data-aos="fade-left">
            <div class="relative">
              <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-indigo-500 to-violet-600 opacity-10 blur-xl"></div>
              <div class="relative glass-card rounded-3xl p-8 border border-white/5">
                <h3 class="text-xl font-bold text-white font-outfit mb-6">Send a Message</h3>

                <!-- Flash success -->
                <div v-if="$page.props.flash?.success" class="mb-6 px-4 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm">
                  {{ $page.props.flash.success }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                  <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                      <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Full Name</label>
                      <input v-model="form.name" type="text" required placeholder="Abebe Kebede" class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 transition" />
                      <p v-if="form.errors.name" class="text-xs text-rose-400 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                      <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email</label>
                      <input v-model="form.email" type="email" required placeholder="abebe@example.com" class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 transition" />
                      <p v-if="form.errors.email" class="text-xs text-rose-400 mt-1">{{ form.errors.email }}</p>
                    </div>
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Subject</label>
                    <input v-model="form.subject" type="text" required placeholder="How can we help you?" class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 transition" />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Message</label>
                    <textarea v-model="form.message" rows="5" required placeholder="Write your message here…" class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 transition resize-none"></textarea>
                    <p v-if="form.errors.message" class="text-xs text-rose-400 mt-1">{{ form.errors.message }}</p>
                  </div>
                  <button type="submit" :disabled="form.processing" class="btn-glow w-full py-4 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold rounded-xl transition duration-300 disabled:opacity-60">
                    {{ form.processing ? 'Sending…' : 'Send Message' }}
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

const form = useForm({ name: '', email: '', subject: '', message: '' })

function submit() {
  form.post('/contact', { onSuccess: () => form.reset() })
}

const contactInfo = [
  { label: 'Address',   value: 'Addis Ababa, Ethiopia',       iconBg: 'bg-indigo-500/10', iconColor: 'text-indigo-400', icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>' },
  { label: 'Phone',     value: '+251 111 234 567',             iconBg: 'bg-emerald-500/10', iconColor: 'text-emerald-400', icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>' },
  { label: 'Email',     value: 'info@sitsethiopia.org',        iconBg: 'bg-amber-500/10',  iconColor: 'text-amber-400',  icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>' },
]
</script>

<style scoped>
.glass-card { background: rgba(15,23,42,0.45); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
.btn-glow { position: relative; overflow: hidden; }
.btn-glow::after { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.15),transparent); transition:0.6s ease; }
.btn-glow:hover::after { left:100%; }
</style>
