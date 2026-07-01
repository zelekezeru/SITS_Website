<nav class="fixed top-0 w-full z-50 bg-slate-950/90 backdrop-blur-md border-b border-slate-900/80" id="mainNav">
    <div class="w-full px-4 sm:px-6">
        <div class="flex justify-between items-center h-16">

            <!-- ── Brand & Logo ── -->
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group shrink-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-cyan-400 p-0.5 shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-300">
                    <div class="w-full h-full bg-slate-900 rounded-[10px] flex items-center justify-center overflow-hidden">
                        <img src="/img/logo.png" alt="SITS Logo" class="h-6 w-auto object-contain" />
                    </div>
                </div>
                <div class="leading-tight">
                    <span class="block text-[9px] text-slate-500 font-semibold uppercase tracking-widest">SITS Ethiopia</span>
                    <span class="block text-xs font-extrabold text-white uppercase tracking-wide">
                        @if(request()->routeIs('home'))          {{ __('app.home') }}
                        @elseif(request()->routeIs('courses.*')) {{ __('app.courses') }}
                        @elseif(request()->routeIs('libraries.*')){{ __('app.libraries') }}
                        @elseif(request()->routeIs('blogs.*'))   {{ __('app.blog') }}
                        @elseif(request()->routeIs('abouts.*'))  {{ __('app.about') }}
                        @elseif(request()->routeIs('contacts.*')){{ __('app.contact') }}
                        @else                                     {{ __('app.home') }}
                        @endif
                    </span>
                </div>
            </a>

            <!-- ── Desktop Navigation Links ── -->
            <ul class="hidden lg:flex items-center gap-1 text-sm font-semibold text-slate-400">
                <li><a href="{{ route('home') }}"           class="px-3 py-2 rounded-lg {{ request()->routeIs('home')         ? 'text-white bg-slate-800/60' : 'hover:text-white hover:bg-slate-900/60' }} transition">{{ __('app.home') }}</a></li>
                <li><a href="{{ route('courses.index') }}"  class="px-3 py-2 rounded-lg {{ request()->routeIs('courses.*')    ? 'text-white bg-slate-800/60' : 'hover:text-white hover:bg-slate-900/60' }} transition">{{ __('app.courses') }}</a></li>
                <li><a href="{{ route('libraries.index') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('libraries.*') ? 'text-white bg-slate-800/60' : 'hover:text-white hover:bg-slate-900/60' }} transition">{{ __('app.libraries') }}</a></li>
                <li><a href="{{ route('lms.redirect') }}" target="_blank" rel="noopener" class="px-3 py-2 rounded-lg hover:text-white hover:bg-slate-900/60 transition inline-flex items-center gap-1.5">{{ __('app.lms') }}<svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a></li>
                <li><a href="{{ route('blogs.index') }}"    class="px-3 py-2 rounded-lg {{ request()->routeIs('blogs.*')      ? 'text-white bg-slate-800/60' : 'hover:text-white hover:bg-slate-900/60' }} transition">{{ __('app.blog') }}</a></li>
                <li><a href="{{ route('abouts.index') }}"   class="px-3 py-2 rounded-lg {{ request()->routeIs('abouts.*')     ? 'text-white bg-slate-800/60' : 'hover:text-white hover:bg-slate-900/60' }} transition">{{ __('app.about') }}</a></li>
                <li><a href="{{ route('contacts.index') }}" class="px-3 py-2 rounded-lg {{ request()->routeIs('contacts.*')   ? 'text-white bg-slate-800/60' : 'hover:text-white hover:bg-slate-900/60' }} transition">{{ __('app.contact') }}</a></li>
            </ul>

            <!-- ── Right Controls ── -->
            <div class="flex items-center gap-2">

                <!-- Language Selector (desktop) -->
                <div class="hidden sm:block">
                    <form action="/locale" method="POST" class="m-0 p-0">
                        @csrf
                        <select name="locale" onchange="this.form.submit()"
                            class="bg-slate-900 border border-slate-800 text-xs font-semibold text-slate-300 rounded-xl px-2.5 py-1.5 focus:outline-none cursor-pointer hover:border-slate-700 transition appearance-none">
                            <option value="en" {{ app()->getLocale()==='en'?'selected':'' }}>EN</option>
                            <option value="am" {{ app()->getLocale()==='am'?'selected':'' }}>አማ</option>
                            <option value="om" {{ app()->getLocale()==='om'?'selected':'' }}>ORM</option>
                            <option value="ti" {{ app()->getLocale()==='ti'?'selected':'' }}>ትግ</option>
                            <option value="so" {{ app()->getLocale()==='so'?'selected':'' }}>SOM</option>
                            <option value="sw" {{ app()->getLocale()==='sw'?'selected':'' }}>SWA</option>
                            <option value="zh" {{ app()->getLocale()==='zh'?'selected':'' }}>中文</option>
                            <option value="fr" {{ app()->getLocale()==='fr'?'selected':'' }}>FR</option>
                            <option value="es" {{ app()->getLocale()==='es'?'selected':'' }}>ES</option>
                            <option value="ku" {{ app()->getLocale()==='ku'?'selected':'' }}>KRD</option>
                        </select>
                    </form>
                </div>

                <!-- Auth: Profile Dropdown or Login -->
                @if(Auth::check())
                    @include('layouts.user-dropdown')
                @else
                    <a href="{{ route('login') }}"
                       class="hidden sm:inline-flex items-center bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold py-2 px-4 rounded-xl shadow-md shadow-indigo-600/20 hover:scale-[1.02] active:scale-[0.98] transition">
                        {{ __('app.login') }}
                    </a>
                @endif

                <!-- Hamburger (mobile only) -->
                <button type="button" id="mobileMenuBtn" aria-label="Toggle menu" aria-expanded="false"
                    class="lg:hidden w-9 h-9 flex items-center justify-center rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white hover:border-slate-700 transition duration-200">
                    <!-- Hamburger icon (SVG — no font dependency) -->
                    <svg id="iconBars"   class="w-4 h-4 block" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg id="iconClose"  class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- ── Mobile Drawer ── -->
    <div id="mobileMenu"
         class="lg:hidden hidden border-t border-slate-900/60 bg-slate-950/98 backdrop-blur-xl shadow-2xl"
         style="max-height: 0; overflow: hidden; transition: max-height 0.35s cubic-bezier(0.4,0,0.2,1);">
        <div class="px-4 pt-3 pb-5 space-y-1">

            <!-- Mobile Nav Links -->
            <a href="{{ route('home') }}"           class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('home')         ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/20' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }} transition">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                {{ __('app.home') }}
            </a>
            <a href="{{ route('courses.index') }}"   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('courses.*')    ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/20' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }} transition">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                {{ __('app.courses') }}
            </a>
            <a href="{{ route('libraries.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('libraries.*')  ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/20' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }} transition">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                {{ __('app.libraries') }}
            </a>
            <a href="{{ route('lms.redirect') }}" target="_blank" rel="noopener" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-300 hover:bg-slate-900 hover:text-white transition">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                {{ __('app.lms') }}
            </a>
            <a href="{{ route('blogs.index') }}"     class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('blogs.*')      ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/20' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }} transition">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                {{ __('app.blog') }}
            </a>
            <a href="{{ route('abouts.index') }}"    class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('abouts.*')     ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/20' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }} transition">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ __('app.about') }}
            </a>
            <a href="{{ route('contacts.index') }}"  class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold {{ request()->routeIs('contacts.*')   ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/20' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }} transition">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                {{ __('app.contact') }}
            </a>

            <!-- Divider -->
            <div class="border-t border-slate-900 my-2"></div>

            <!-- Mobile: Language Switcher -->
            <div class="px-4 py-2">
                <p class="text-[10px] text-slate-600 font-semibold uppercase tracking-widest mb-2">{{ __('app.language') ?? 'Language' }}</p>
                <form action="/locale" method="POST" class="m-0 p-0">
                    @csrf
                    <select name="locale" onchange="this.form.submit()"
                        class="w-full bg-slate-900 border border-slate-800 text-sm font-semibold text-slate-300 rounded-xl px-3 py-2.5 focus:outline-none cursor-pointer hover:border-slate-700 transition">
                        <option value="en" {{ app()->getLocale()==='en'?'selected':'' }}>🌐 English</option>
                        <option value="am" {{ app()->getLocale()==='am'?'selected':'' }}>አማርኛ</option>
                        <option value="om" {{ app()->getLocale()==='om'?'selected':'' }}>Afaan Oromoo</option>
                        <option value="ti" {{ app()->getLocale()==='ti'?'selected':'' }}>ትግርኛ</option>
                        <option value="so" {{ app()->getLocale()==='so'?'selected':'' }}>Soomaali</option>
                        <option value="sw" {{ app()->getLocale()==='sw'?'selected':'' }}>Kiswahili</option>
                        <option value="zh" {{ app()->getLocale()==='zh'?'selected':'' }}>中文</option>
                        <option value="fr" {{ app()->getLocale()==='fr'?'selected':'' }}>Français</option>
                        <option value="es" {{ app()->getLocale()==='es'?'selected':'' }}>Español</option>
                        <option value="ku" {{ app()->getLocale()==='ku'?'selected':'' }}>Kurdî</option>
                    </select>
                </form>
            </div>

            <!-- Mobile: Login button (for guests) -->
            @guest
            <div class="px-4 pt-1">
                <a href="{{ route('login') }}"
                   class="block text-center bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold py-3 px-4 rounded-xl transition">
                    {{ __('app.login') }}
                </a>
            </div>
            @endguest
        </div>
    </div>
</nav>

<script>
    (function() {
        var btn  = document.getElementById('mobileMenuBtn');
        var menu = document.getElementById('mobileMenu');
        var bars = document.getElementById('iconBars');
        var close= document.getElementById('iconClose');
        if (!btn || !menu) return;

        function openMenu() {
            menu.classList.remove('hidden');
            // Animate height
            menu.style.maxHeight = menu.scrollHeight + 'px';
            bars.classList.add('hidden');
            close.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'true');
        }

        function closeMenu() {
            menu.style.maxHeight = '0';
            bars.classList.remove('hidden');
            close.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
            // Hide after transition
            setTimeout(function() { menu.classList.add('hidden'); }, 360);
        }

        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.contains('hidden') ? openMenu() : closeMenu();
        });

        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!menu.classList.contains('hidden') && !menu.contains(e.target) && !btn.contains(e.target)) {
                closeMenu();
            }
        });

        // Close on nav link tap (mobile UX)
        menu.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() { closeMenu(); });
        });

        // Close on resize to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) closeMenu();
        });
    })();
</script>
