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

    <!-- Tailwind CSS CDN (For rapid high-fidelity UI styling) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    },
                }
            }
        }
    </script>

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
    <header class="relative z-20 w-full glassmorphism border-b border-slate-900">
        <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
            <!-- Brand Logo -->
            <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-cyan-400 p-0.5 shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-300">
                    <div class="w-full h-full bg-slate-900 rounded-[10px] flex items-center justify-center overflow-hidden">
                        <img src="{{ asset('img/logo.png') }}" alt="SITS Logo" class="h-7 w-auto object-contain" />
                    </div>
                </div>
                <div>
                    <span class="font-outfit text-xl font-bold tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent">
                        SITS ETHIOPIA
                    </span>
                    <span class="block text-[10px] text-indigo-400 font-medium tracking-wider uppercase -mt-1">
                        Portal Hub
                    </span>
                </div>
            </a>

            <!-- Navigation Links -->
            <div class="flex items-center space-x-6">
                <a href="{{ route('home') }}" class="text-sm font-medium text-slate-400 hover:text-white transition duration-200">
                    <i class="fa fa-home mr-1"></i> Public Site
                </a>

                @if(Auth::check())
                    <!-- User Dropdown Control -->
                    <div class="relative" id="userMenuContainer">
                        <button type="button" id="userMenuBtn" class="flex items-center space-x-3 p-1.5 pr-3 rounded-full hover:bg-white/5 border border-transparent hover:border-white/10 transition duration-200">
                            <img src="{{ auth()->user()->profile_image ? Storage::url(auth()->user()->profile_image) : asset('img/user.png') }}" 
                                 alt="{{ auth()->user()->name }}" 
                                 class="h-8 w-8 rounded-full object-cover ring-2 ring-indigo-500/30" />
                            <span class="text-sm font-medium text-slate-300 hidden sm:inline-block">{{ auth()->user()->name }}</span>
                            <i class="fa fa-chevron-down text-xs text-slate-500 transition-transform duration-200" id="userMenuChevron"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div id="userDropdown" class="hidden absolute right-0 mt-3 w-56 rounded-2xl glassmorphism border border-slate-800 shadow-2xl p-2 z-50 animate-fade-in">
                            <div class="px-3 py-2.5 border-b border-slate-900 mb-2">
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Logged In As</p>
                                <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                                <div class="mt-1 flex items-center">
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-400 text-[10px] font-bold border border-indigo-500/20 uppercase">
                                        {{ auth()->user()->roles->first()?->name ?? 'USER' }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Menu Links -->
                            @if(auth()->user()->hasRole('SUPERADMIN') || auth()->user()->hasRole('ADMIN') || auth()->user()->hasRole('EDITOR'))
                                <a href="{{ route('courses.list') }}" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-sm text-slate-400 hover:text-white hover:bg-white/5 transition duration-200">
                                    <i class="fa fa-sliders text-slate-500 w-4"></i>
                                    <span>Admin Panel</span>
                                </a>
                            @endif

                            @php
                                $user = auth()->user();
                                $lmsLabel = 'LMS Portal';
                                if ($user->hasRole('STUDENT')) {
                                    $lmsLabel = 'Go to Student Learning Portal';
                                } elseif ($user->hasRole('TRAINER')) {
                                    $lmsLabel = 'Instructors Portal';
                                } elseif ($user->hasAnyRole(['STAFF', 'SUPERADMIN', 'ADMIN', 'EDITOR', 'LIBRARIAN'])) {
                                    $lmsLabel = 'Staff Portal';
                                }
                            @endphp

                            <a href="https://lms.sits.edu.et" target="_blank" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-sm text-slate-400 hover:text-white hover:bg-white/5 transition duration-200">
                                <i class="fa fa-graduation-cap text-slate-500 w-4"></i>
                                <span>{{ $lmsLabel }}</span>
                            </a>

                            @if($user->hasAnyRole(['SUPERADMIN', 'ADMIN', 'EDITOR', 'TRAINER', 'STAFF', 'LIBRARIAN']))
                                <a href="https://pms.sits.edu.et" target="_blank" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-sm text-slate-400 hover:text-white hover:bg-white/5 transition duration-200">
                                    <i class="fa fa-users-cog text-slate-500 w-4"></i>
                                    <span>ERP Portal</span>
                                </a>
                            @endif

                            @if($user->hasAnyRole(['STUDENT', 'TRAINER', 'LIBRARIAN', 'ADMIN', 'SUPERADMIN', 'STAFF', 'EDITOR']))
                                <a href="https://library.sits.edu.et" target="_blank" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-sm text-slate-400 hover:text-white hover:bg-white/5 transition duration-200">
                                    <i class="fa fa-book-reader text-slate-500 w-4"></i>
                                    <span>Digital Library</span>
                                </a>
                            @endif

                            <a href="{{ route('profile.edit') }}" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-sm text-slate-400 hover:text-white hover:bg-white/5 transition duration-200">
                                <i class="fa fa-user-circle text-slate-500 w-4"></i>
                                <span>Edit Profile</span>
                            </a>
                            
                            <hr class="border-slate-900 my-1.5" />
                            
                            <!-- Logout Button -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center space-x-2.5 px-3 py-2 rounded-xl text-sm text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition duration-200 text-left">
                                    <i class="fa fa-sign-out-alt w-4"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl shadow-lg shadow-indigo-600/20 hover:scale-[1.02] active:scale-[0.98] transition duration-200">
                        Login
                    </a>
                @endif
            </div>
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
        // Profile Menu Dropdown toggle
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userDropdown = document.getElementById('userDropdown');
        const userMenuChevron = document.getElementById('userMenuChevron');

        if (userMenuBtn && userDropdown) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('hidden');
                if (userMenuChevron) {
                    userMenuChevron.classList.toggle('rotate-180');
                }
            });

            // Close dropdown if clicked outside
            window.addEventListener('click', (e) => {
                if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.add('hidden');
                    if (userMenuChevron) {
                        userMenuChevron.classList.remove('rotate-180');
                    }
                }
            });
        }
    </script>
    @yield('scripts')
</body>

</html>
