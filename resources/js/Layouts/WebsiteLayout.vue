<template>
  <div class="bg-[#090d16] text-slate-300 font-jakarta min-h-screen relative overflow-x-hidden">

    <!-- Global ambient blobs -->
    <div class="glow-blob glow-blob-1 pointer-events-none"></div>
    <div class="glow-blob glow-blob-2 pointer-events-none"></div>
    <div class="glow-blob glow-blob-3 pointer-events-none"></div>
    <div class="absolute inset-0 grid-overlay pointer-events-none z-0"></div>

    <!-- ── Navigation ── -->
    <nav class="fixed top-0 w-full z-50 bg-slate-950/90 backdrop-blur-md border-b border-slate-900/80">
      <div class="w-full px-4 sm:px-6">
        <div class="flex justify-between items-center h-16">

          <!-- Brand -->
          <Link :href="route('home')" class="flex items-center gap-2.5 group shrink-0">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-cyan-400 p-0.5 shadow-md group-hover:scale-105 transition-transform duration-300">
              <div class="w-full h-full bg-slate-900 rounded-[10px] flex items-center justify-center overflow-hidden">
                <img :src="'/img/logo.png'" alt="SITS" class="h-6 w-auto object-contain" />
              </div>
            </div>
            <div class="leading-tight">
              <span class="block text-[9px] text-slate-500 font-semibold uppercase tracking-widest">SITS Ethiopia</span>
              <span class="block text-xs font-extrabold text-white uppercase tracking-wide">{{ currentPageLabel }}</span>
            </div>
          </Link>

          <!-- Desktop links -->
          <ul class="hidden lg:flex items-center gap-1 text-sm font-semibold text-slate-400">
            <li v-for="link in navLinks" :key="link.href">
              <Link :href="route(link.route)"
                :class="['px-3 py-2 rounded-lg transition', isActive(link.route) ? 'text-white bg-slate-800/60' : 'hover:text-white hover:bg-slate-900/60']">
                {{ link.label }}
              </Link>
            </li>
            <!-- eLearning dropdown: SITS LMS (external site) + Moodle (integrated, single sign-on) -->
            <li v-if="auth.user" class="relative group">
              <button type="button"
                class="px-3 py-2 rounded-lg transition hover:text-white hover:bg-slate-900/60 inline-flex items-center gap-1.5">
                {{ t('lms', 'eLearning') }}
                <svg class="w-3 h-3 opacity-60 transition-transform duration-200 group-hover:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
              </button>
              <!-- pt-2 bridges the gap so hover isn't lost between the button and the menu -->
              <div class="absolute left-0 top-full pt-2 w-56 hidden group-hover:block z-50">
                <ul class="bg-slate-900/95 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-2xl py-2">
                  <li>
                    <a href="https://lms.sits.edu.et" target="_blank" rel="noopener"
                      class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-slate-300 hover:text-white hover:bg-slate-800/50 transition">
                      <svg class="w-4 h-4 shrink-0 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                      {{ t('sits_lms', 'SITS LMS') }}
                    </a>
                  </li>
                  <li>
                    <a href="/go/lms" target="_blank" rel="noopener"
                      class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-slate-300 hover:text-white hover:bg-slate-800/50 transition">
                      <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                      {{ t('moodle', 'Moodle') }}
                    </a>
                  </li>
                </ul>
              </div>
            </li>
          </ul>

          <!-- Right controls -->
          <div class="flex items-center gap-2">
            <!-- Language selector (desktop) -->
            <div class="hidden sm:block">
              <form @submit.prevent method="POST" action="/locale">
                <select @change="switchLocale($event.target.value)"
                  class="bg-slate-900 border border-slate-800 text-xs font-semibold text-slate-300 rounded-xl px-2.5 py-1.5 focus:outline-none cursor-pointer hover:border-slate-700 transition appearance-none">
                  <option v-for="lang in languages" :key="lang.code" :value="lang.code" :selected="locale === lang.code">{{ lang.label }}</option>
                </select>
              </form>
            </div>

            <!-- Auth: user dropdown or login -->
            <div v-if="auth.user" class="relative" ref="userMenuRef">
              <button @click="userMenuOpen = !userMenuOpen"
                class="flex items-center gap-2 p-1 rounded-full hover:bg-slate-900 border border-transparent hover:border-slate-800 transition">
                <img :src="userAvatar" alt="Profile" class="h-8 w-8 rounded-full object-cover ring-2 ring-indigo-500/20" />
                <span class="text-xs font-semibold text-slate-300 hidden sm:inline-block">{{ auth.user.name }}</span>
                <svg class="w-3 h-3 text-slate-500 hidden sm:block transition-transform duration-200" :class="{'rotate-180': userMenuOpen}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>

              <!-- Dropdown -->
              <Transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                <ul v-if="userMenuOpen" class="absolute right-0 mt-2 w-56 bg-slate-900/95 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-2xl py-2 z-50">
                  <li class="px-4 py-2 border-b border-slate-800/60 mb-1 lg:hidden">
                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">{{ t('logged_in_as', 'Logged in as') }}</p>
                    <p class="text-xs font-semibold text-white truncate">{{ auth.user.name }}</p>
                  </li>
                  <li><Link :href="route('portal')" class="block px-4 py-2.5 text-xs text-slate-400 hover:text-white hover:bg-slate-800/40 transition">{{ t('dashboard', 'Dashboard') }}</Link></li>
                  <li><Link :href="route('profile.edit')" class="block px-4 py-2.5 text-xs text-slate-400 hover:text-white hover:bg-slate-800/40 transition">{{ t('view_profile', 'View Profile') }}</Link></li>
                  <li class="border-t border-slate-800/60 my-1"></li>
                  <li><a :href="lmsUrl" class="block px-4 py-2.5 text-xs text-slate-400 hover:text-white hover:bg-slate-800/40 transition">{{ lmsLabel }}</a></li>
                  <li v-if="hasErpAccess"><Link :href="route('dashboard')" class="block px-4 py-2.5 text-xs text-slate-400 hover:text-white hover:bg-slate-800/40 transition">{{ t('erp_portal', 'ERP Portal') }}</Link></li>
                  <li><Link :href="route('library.dashboard')" class="block px-4 py-2.5 text-xs text-slate-400 hover:text-white hover:bg-slate-800/40 transition">{{ t('digital_library', 'Digital Library') }}</Link></li>
                  <li class="border-t border-slate-800/60 my-1"></li>
                  <li>
                    <Link :href="route('logout')" method="post" as="button" class="block w-full text-left px-4 py-2.5 text-xs text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition cursor-pointer">{{ t('sign_out', 'Sign Out') }}</Link>
                  </li>
                </ul>
              </Transition>
            </div>
            <a v-else :href="route('login')"
              class="hidden sm:inline-flex items-center bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold py-2 px-4 rounded-xl shadow-md transition">
              {{ t('login', 'Login') }}
            </a>

            <!-- Hamburger -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Menu"
              class="lg:hidden w-9 h-9 flex items-center justify-center rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white hover:border-slate-700 transition duration-200">
              <svg v-if="!mobileMenuOpen" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Drawer -->
      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2">
        <div v-if="mobileMenuOpen" class="lg:hidden border-t border-slate-900/60 bg-slate-950/98 backdrop-blur-xl shadow-2xl">
          <div class="px-4 pt-3 pb-5 space-y-1">
            <Link v-for="link in navLinks" :key="link.href" :href="route(link.route)" @click="mobileMenuOpen = false"
              :class="['flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition', isActive(link.route) ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/20' : 'text-slate-300 hover:bg-slate-900 hover:text-white']">
              <component :is="'svg'" class="w-4 h-4 shrink-0" v-html="link.icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"></component>
              {{ link.label }}
            </Link>
            <!-- eLearning: SITS LMS (external) + Moodle (integrated SSO) -->
            <template v-if="auth.user">
              <p class="px-4 pt-3 pb-1 text-[10px] text-slate-600 font-semibold uppercase tracking-widest">{{ t('lms', 'eLearning') }}</p>
              <a href="https://lms.sits.edu.et" target="_blank" rel="noopener" @click="mobileMenuOpen = false"
                class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-300 hover:bg-slate-900 hover:text-white transition">
                <svg class="w-4 h-4 shrink-0 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                {{ t('sits_lms', 'SITS LMS') }}
              </a>
              <a href="/go/lms" target="_blank" rel="noopener" @click="mobileMenuOpen = false"
                class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-300 hover:bg-slate-900 hover:text-white transition">
                <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                {{ t('moodle', 'Moodle') }}
              </a>
            </template>

            <div class="border-t border-slate-900 my-2"></div>

            <!-- Mobile language -->
            <div class="px-4 py-2">
              <p class="text-[10px] text-slate-600 font-semibold uppercase tracking-widest mb-2">{{ t('language', 'Language') }}</p>
              <select @change="switchLocale($event.target.value)"
                class="w-full bg-slate-900 border border-slate-800 text-sm font-semibold text-slate-300 rounded-xl px-3 py-2.5 focus:outline-none cursor-pointer">
                <option v-for="lang in languages" :key="lang.code" :value="lang.code" :selected="locale === lang.code">{{ lang.fullLabel }}</option>
              </select>
            </div>

            <!-- Mobile login -->
            <div v-if="!auth.user" class="px-4 pt-1">
              <a :href="route('login')" class="block text-center bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold py-3 px-4 rounded-xl transition">
                {{ t('login', 'Login') }}
              </a>
            </div>
          </div>
        </div>
      </Transition>
    </nav>

    <!-- Page Content -->
    <main class="relative z-10 pt-16">
      <slot />
    </main>

    <!-- ── Footer ── -->
    <footer class="bg-slate-950/80 border-t border-slate-900/60 py-16 text-slate-400">
      <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
          <!-- Brand -->
          <div>
            <Link :href="route('home')" class="flex items-center gap-2.5 mb-4 group inline-flex">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-cyan-400 p-0.5 group-hover:scale-105 transition-transform duration-300">
                <div class="w-full h-full bg-slate-900 rounded-[10px] flex items-center justify-center overflow-hidden">
                  <img :src="'/img/logo.png'" alt="SITS" class="h-6 w-auto object-contain" />
                </div>
              </div>
              <div>
                <span class="block text-[9px] text-slate-500 font-semibold uppercase tracking-widest">SITS Ethiopia</span>
                <span class="block text-xs font-extrabold text-white uppercase">Seminary</span>
              </div>
            </Link>
            <p class="text-sm text-slate-500 leading-relaxed">Empowering leaders and transforming communities through accessible theological education since 1994 G.C.</p>
          </div>

          <!-- Our Values -->
          <div>
            <h3 class="text-sm font-bold text-white mb-4 uppercase tracking-wider">Our Values</h3>
            <ul class="space-y-2.5 text-sm">
              <li><a href="#" class="hover:text-amber-400 transition">Sustainable</a></li>
              <li><a href="#" class="hover:text-amber-400 transition">Accessible</a></li>
              <li><a href="#" class="hover:text-amber-400 transition">Vital</a></li>
              <li><a href="#" class="hover:text-amber-400 transition">Excellent</a></li>
            </ul>
          </div>

          <!-- Resources -->
          <div>
            <h3 class="text-sm font-bold text-white mb-4 uppercase tracking-wider">Resources</h3>
            <ul class="space-y-2.5 text-sm">
              <li><a href="/go/lms" target="_blank" rel="noopener" class="hover:text-indigo-400 transition">{{ t('lms', 'eLearning') }}</a></li>
              <li><a href="/library/dashboard" class="hover:text-indigo-400 transition">Digital Library</a></li>
              <li><Link :href="route('courses.index')" class="hover:text-indigo-400 transition">Courses</Link></li>
              <li><Link :href="route('blogs.index')" class="hover:text-indigo-400 transition">Blog</Link></li>
              <li><Link :href="route('abouts.index')" class="hover:text-indigo-400 transition">About Us</Link></li>
            </ul>
          </div>

          <!-- Newsletter -->
          <div>
            <h3 class="text-sm font-bold text-white mb-4 uppercase tracking-wider">Newsletter</h3>
            <p class="text-sm text-slate-500 mb-4">Stay updated with new programs and academic news.</p>
            <form @submit.prevent="submitNewsletter" class="flex gap-2">
              <input v-model="newsletterEmail" type="email" required placeholder="Your email…"
                class="flex-1 px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition min-w-0" />
              <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shrink-0">Go</button>
            </form>
          </div>
        </div>

        <!-- Bottom bar -->
        <div class="border-t border-slate-900/60 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p class="text-xs text-slate-600">Copyright © 2025 SITS Ethiopia. Developed by AEZ Technologies.</p>
          <div class="flex gap-5 text-xs">
            <a href="#" class="hover:text-indigo-400 transition">Facebook</a>
            <a href="https://x.com/sitsethiopia" target="_blank" class="hover:text-indigo-400 transition">Twitter</a>
            <a href="https://www.linkedin.com/in/shiloh-intermational-theological-seminary-4a0536346" target="_blank" class="hover:text-indigo-400 transition">LinkedIn</a>
          </div>
        </div>
      </div>
    </footer>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import { useTranslations } from '@/Composables/useTranslations'

const page = usePage()
const auth = computed(() => page.props.auth)
const { t, locale } = useTranslations()

const mobileMenuOpen = ref(false)
const userMenuOpen   = ref(false)
const userMenuRef    = ref(null)
const newsletterEmail = ref('')

// ── Navigation links ──────────────────────────────────────────────────────────
const navLinks = computed(() => [
  { route: 'home',            label: t('home', 'Home'),      icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>' },
  { route: 'courses.index',   label: t('courses', 'Courses'),   icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>' },
  { route: 'libraries.index', label: t('libraries', 'Libraries'), icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>' },
  { route: 'blogs.index',     label: t('blog', 'Blog'),      icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>' },
  { route: 'abouts.index',    label: t('about', 'About'),     icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>' },
  { route: 'contacts.index',  label: t('contact', 'Contact'),   icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>' },
])

// ── Languages ────────────────────────────────────────────────────────────────
const languages = [
  { code: 'en', label: 'EN',  fullLabel: '🌐 English'    },
  { code: 'am', label: 'አማ',  fullLabel: 'አማርኛ'         },
  { code: 'om', label: 'ORM', fullLabel: 'Afaan Oromoo'  },
  { code: 'ti', label: 'ትግ',  fullLabel: 'ትግርኛ'          },
  { code: 'so', label: 'SOM', fullLabel: 'Soomaali'      },
  { code: 'sw', label: 'SWA', fullLabel: 'Kiswahili'     },
  { code: 'zh', label: '中文', fullLabel: '中文'           },
  { code: 'fr', label: 'FR',  fullLabel: 'Français'      },
  { code: 'es', label: 'ES',  fullLabel: 'Español'       },
  { code: 'ku', label: 'KRD', fullLabel: 'Kurdî'         },
]

// ── Active route detection ────────────────────────────────────────────────────
function isActive(routeName) {
  const current = page.url
  const routeMap = {
    'home':            '/',
    'courses.index':   '/courses',
    'libraries.index': '/libraries',
    'blogs.index':     '/blog',
    'abouts.index':    '/about',
    'contacts.index':  '/contact',
  }
  const path = routeMap[routeName] || ''
  if (routeName === 'home') return current === '/' || current === ''
  return current.startsWith(path)
}

const currentPageLabel = computed(() => {
  const url = page.url
  if (url === '/' || url === '') return 'Home'
  if (url.startsWith('/courses'))  return 'Courses'
  if (url.startsWith('/libraries'))return 'Libraries'
  if (url.startsWith('/blog'))     return 'Blog'
  if (url.startsWith('/about'))    return 'About'
  if (url.startsWith('/contact'))  return 'Contact'
  return 'SITS'
})

// ── User computed ─────────────────────────────────────────────────────────────
const userAvatar = computed(() => {
  const img = auth.value?.user?.profile_image
  return img ? `/storage/${img}` : '/img/user.png'
})

const hasErpAccess = computed(() => {
  const roles = (auth.value?.user?.roles ?? []).map(r => r.toLowerCase())
  if (!roles.length) return false
  return !roles.includes('student')
})

const lmsLabel = computed(() => {
  const roles = (auth.value?.user?.roles ?? []).map(r => r.toLowerCase())
  if (roles.includes('student')) return t('student_portal', 'Student Portal')
  if (roles.includes('trainer')) return t('trainer_portal', 'Instructor Portal')
  return t('lms_portal', 'LMS Portal')
})

const lmsUrl = computed(() => {
  const roles = (auth.value?.user?.roles ?? []).map(r => r.toLowerCase())
  if (roles.includes('student') || roles.includes('trainer')) {
    return '/go/lms'
  }
  return 'https://lms.sits.edu.et'
})

// ── Actions ───────────────────────────────────────────────────────────────────
function switchLocale(code) {
  router.post('/locale', { locale: code }, { preserveState: false })
}

function logout() {
  router.post('/logout')
}

function submitNewsletter() {
  router.post('/subscriptions', {
    name:    newsletterEmail.value.split('@')[0] || 'Subscriber',
    email:   newsletterEmail.value,
    phone:   'N/A',
    address: 'N/A',
    type:    'newsletter',
  }, {
    onSuccess: () => { newsletterEmail.value = '' },
    preserveScroll: true,
  })
}

// ── Close user menu on outside click ─────────────────────────────────────────
function handleOutsideClick(e) {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
    userMenuOpen.value = false
  }
}

// ── AOS init ──────────────────────────────────────────────────────────────────
onMounted(() => {
  document.addEventListener('click', handleOutsideClick)
  if (typeof window !== 'undefined' && typeof window.AOS !== 'undefined') {
    window.AOS.init({ once: true, duration: 700, offset: 60 })
  }
})
onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick)
})
</script>

<style scoped>
.glow-blob {
  position: fixed;
  border-radius: 50%;
  filter: blur(120px);
  opacity: 0.12;
  z-index: 0;
  pointer-events: none;
  animation: float-blob 20s infinite alternate;
}
.glow-blob-1 { background: radial-gradient(circle, #4f46e5 0%, #06b6d4 100%); width: 500px; height: 500px; top: 5%; right: -5%; }
.glow-blob-2 { background: radial-gradient(circle, #f59e0b 0%, #ef4444 100%); width: 600px; height: 600px; bottom: 10%; left: -10%; animation-delay: -5s; animation-duration: 25s; }
.glow-blob-3 { background: radial-gradient(circle, #a855f7 0%, #6366f1 100%); width: 400px; height: 400px; top: 45%; right: 10%; animation-delay: -10s; animation-duration: 18s; }
@keyframes float-blob {
  0%   { transform: translate(0px, 0px) scale(1); }
  50%  { transform: translate(60px, -40px) scale(1.15); }
  100% { transform: translate(-40px, 50px) scale(0.9); }
}
.grid-overlay {
  background-size: 40px 40px;
  background-image: linear-gradient(to right, rgba(255,255,255,0.02) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(255,255,255,0.02) 1px, transparent 1px);
}
</style>
