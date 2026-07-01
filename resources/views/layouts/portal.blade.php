<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png" />
    <title>@yield('title', 'SITS Portal Hub')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <!-- Tailwind CSS (Vite compiled) -->
    @vite(['resources/css/app.css'])

    <!-- Custom Premium Styles & Keyframe Animations -->
    <style>
        @keyframes float-blob-1 {
            0%, 100% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }
        @keyframes float-blob-2 {
            0%, 100% { transform: translate(0px, 0px) scale(1.1); }
            33% { transform: translate(-40px, 30px) scale(0.95); }
            66% { transform: translate(30px, -20px) scale(1.05); }
        }
        @keyframes float-blob-3 {
            0%, 100% { transform: translate(0px, 0px) scale(0.9); }
            33% { transform: translate(20px, 40px) scale(1.05); }
            66% { transform: translate(-30px, -30px) scale(0.95); }
        }
        .animate-blob-1 {
            animation: float-blob-1 12s infinite alternate ease-in-out;
        }
        .animate-blob-2 {
            animation: float-blob-2 15s infinite alternate ease-in-out;
        }
        .animate-blob-3 {
            animation: float-blob-3 10s infinite alternate ease-in-out;
        }
        .glassmorphism {
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-card-hover {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .glass-card-hover:hover {
            transform: translateY(-6px);
            background: rgba(15, 23, 42, 0.7);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5), 0 0 40px 2px rgba(99, 102, 241, 0.12);
        }
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.3);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        .neon-glow-indigo:hover {
            box-shadow: 0 0 25px rgba(99, 102, 241, 0.45);
        }
        .neon-glow-cyan:hover {
            box-shadow: 0 0 25px rgba(6, 182, 212, 0.45);
        }
        .neon-glow-amber:hover {
            box-shadow: 0 0 25px rgba(245, 158, 11, 0.45);
        }
    </style>
    @yield('styles')
</head>

<body class="bg-slate-950 text-slate-100 font-sans h-full min-h-screen flex flex-col justify-between overflow-x-hidden selection:bg-indigo-500 selection:text-white">

    <!-- Glowing Background blobs -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
        <div class="absolute top-1/4 -left-32 w-96 h-96 rounded-full bg-indigo-900/20 blur-[100px] animate-blob-1"></div>
        <div class="absolute bottom-1/4 -right-32 w-96 h-96 rounded-full bg-cyan-900/20 blur-[100px] animate-blob-2"></div>
        <div class="absolute top-1/2 left-1/3 w-96 h-96 rounded-full bg-purple-900/15 blur-[120px] animate-blob-3"></div>
    </div>

    <!-- Main Navigation Bar -->
    <header class="relative z-20 w-full bg-slate-950/80 backdrop-blur-md border-b border-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex justify-between items-center">
            <!-- Brand Logo & Breadcrumb -->
            <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-cyan-400 p-0.5 shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-300">
                    <div class="w-full h-full bg-slate-900 rounded-[10px] flex items-center justify-center overflow-hidden">
                        <img src="{{ asset('img/logo.png') }}" alt="SITS Logo" class="h-7 w-auto object-contain" />
                    </div>
                </div>
                <div>
                    <span class="block text-[10px] text-slate-500 font-medium uppercase tracking-wider">
                        SITS ETHIOPIA
                    </span>
                    <span class="block text-xs font-bold text-white -mt-0.5 uppercase tracking-wide">
                        {{ __('app.dashboard') }}
                    </span>
                </div>
            </a>

            <!-- Right actions: Lang & User Dropdown & Mobile Toggle -->
            <div class="flex items-center space-x-3">
                <a href="{{ route('home') }}" class="hidden md:inline-block text-xs font-semibold text-slate-400 hover:text-white transition mr-2">
                    <i class="fa fa-home mr-1"></i> Public Site
                </a>

                <!-- Language Selector -->
                <div class="relative">
                    <form action="/locale" method="POST" id="lang-switch-form-portal" class="inline-block m-0 p-0">
                        @csrf
                        <select name="locale" onchange="this.form.submit()" class="bg-slate-900 border border-slate-800 text-xs font-semibold text-slate-300 rounded-xl px-2.5 py-1.5 focus:outline-none cursor-pointer hover:border-slate-700 transition">
                            <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>EN</option>
                            <option value="am" {{ app()->getLocale() === 'am' ? 'selected' : '' }}>አማ</option>
                            <option value="om" {{ app()->getLocale() === 'om' ? 'selected' : '' }}>ORM</option>
                            <option value="ti" {{ app()->getLocale() === 'ti' ? 'selected' : '' }}>ትግ</option>
                            <option value="so" {{ app()->getLocale() === 'so' ? 'selected' : '' }}>SOM</option>
                            <option value="sw" {{ app()->getLocale() === 'sw' ? 'selected' : '' }}>SWA</option>
                            <option value="zh" {{ app()->getLocale() === 'zh' ? 'selected' : '' }}>中文</option>
                            <option value="fr" {{ app()->getLocale() === 'fr' ? 'selected' : '' }}>FR</option>
                            <option value="es" {{ app()->getLocale() === 'es' ? 'selected' : '' }}>ES</option>
                            <option value="ku" {{ app()->getLocale() === 'ku' ? 'selected' : '' }}>KRD</option>
                            <option value="ur" {{ app()->getLocale() === 'ur' ? 'selected' : '' }}>URD</option>
                        </select>
                    </form>
                </div>

                @if(Auth::check())
                    @include('layouts.user-dropdown')
                @else
                    <a href="{{ route('login') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold py-2 px-4 rounded-xl shadow-lg shadow-indigo-600/20 hover:scale-[1.02] active:scale-[0.98] transition">
                        Login
                    </a>
                @endif

                <!-- Mobile Hamburger Toggle -->
                <button type="button" id="portalMobileMenuBtn" class="md:hidden w-9 h-9 flex items-center justify-center rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition">
                    <i class="fa fa-bars text-base" id="portalMobileMenuIcon"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Portal Menu -->
        <div id="portalMobileMenu" class="hidden md:hidden border-t border-slate-900 bg-slate-950/95 backdrop-blur-xl px-4 py-4 space-y-4 shadow-inner">
            <a href="{{ route('home') }}" class="block p-2.5 rounded-xl hover:bg-slate-900 hover:text-white text-xs font-semibold text-slate-400 transition">
                <i class="fa fa-home mr-1"></i> Public Site
            </a>
        </div>
    </header>

    <!-- Content slot -->
    <main class="flex-grow w-full z-10 relative">
        @yield('content')
    </main>

    <!-- Simple, Clean Footer -->
    <footer class="relative z-10 py-6 border-t border-slate-900 glassmorphism">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-xs text-slate-500">
                &copy; {{ date('Y') }} SITS Ethiopia. All Rights Reserved.
            </p>
            <div class="flex items-center space-x-6 text-xs text-slate-500">
                <a href="#" class="hover:text-slate-300 transition duration-200">Privacy Policy</a>
                <a href="#" class="hover:text-slate-300 transition duration-200">Terms of Service</a>
                <a href="{{ route('contacts.index') }}" class="hover:text-slate-300 transition duration-200">Help Center</a>
            </div>
        </div>
    </footer>

    <!-- Interactive Scripts -->
    <script>
        // Mobile Menu toggle
        const portalMobileBtn = document.getElementById('portalMobileMenuBtn');
        const portalMobileMenu = document.getElementById('portalMobileMenu');
        const portalMobileIcon = document.getElementById('portalMobileMenuIcon');

        if (portalMobileBtn && portalMobileMenu) {
            portalMobileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                portalMobileMenu.classList.toggle('hidden');
                if (portalMobileIcon) {
                    if (portalMobileMenu.classList.contains('hidden')) {
                        portalMobileIcon.className = 'fa fa-bars text-base';
                    } else {
                        portalMobileIcon.className = 'fa fa-times text-base';
                    }
                }
            });
            window.addEventListener('click', function() {
                portalMobileMenu.classList.add('hidden');
                if (portalMobileIcon) portalMobileIcon.className = 'fa fa-bars text-base';
            });
        }
    </script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    @yield('scripts')
</body>

</html>
