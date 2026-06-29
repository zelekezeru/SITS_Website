<x-guest-layout>
    <div class="relative max-w-4xl mx-auto">
        <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-indigo-500 to-purple-600 opacity-20 blur-xl"></div>
        
        <div class="relative glass-card rounded-3xl overflow-hidden grid md:grid-cols-12 border border-white/5 shadow-2xl">
            <!-- Left Branding Column -->
            <div class="md:col-span-5 bg-slate-950/60 p-8 flex flex-col justify-between items-center text-center border-r border-white/5 relative">
                <div class="absolute inset-0 bg-gradient-to-b from-indigo-950/20 to-transparent pointer-events-none"></div>
                
                <div class="relative z-10 w-full flex flex-col items-center justify-center my-auto space-y-6">
                    <a href="{{ url('/') }}" class="block">
                        <img src="{{ asset('img/logo.png') }}" alt="SITS Logo" class="h-16 w-auto hover:scale-105 transition duration-300" />
                    </a>
                    <div class="space-y-2">
                        <h3 class="text-xl font-bold text-white font-outfit">SITS Portal Hub</h3>
                        <p class="text-xs text-slate-500 max-w-[200px] mx-auto leading-relaxed">
                            Access theological systems, resource databases, and institutional registries.
                        </p>
                    </div>
                </div>

                <div class="relative z-10 text-[10px] text-slate-650 font-semibold uppercase tracking-wider mt-6">
                    Since 1994 G.C
                </div>
            </div>

            <!-- Right Form Column -->
            <div class="md:col-span-7 p-8 sm:p-10 space-y-6">
                <div>
                    <h2 class="text-2xl font-bold text-white font-outfit mb-1">Welcome Back</h2>
                    <p class="text-xs text-slate-450">Please sign in to access your portal dashboard.</p>
                </div>

                @if (session('status'))
                    <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
                        <div class="relative">
                            <input type="email" id="email" name="email" required autofocus placeholder="name@example.com"
                                   class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow" />
                        </div>
                        @error('email')
                            <span class="text-xs text-rose-500 mt-1.5 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="password" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-[10px] text-indigo-400 hover:underline">Forgot Password?</a>
                            @endif
                        </div>
                        <div class="relative">
                            <input type="password" id="password" name="password" required placeholder="••••••••"
                                   class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow" />
                        </div>
                        @error('password')
                            <span class="text-xs text-rose-500 mt-1.5 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center">
                        <input class="w-4 h-4 rounded bg-slate-900 border-slate-800 text-indigo-600 focus:ring-0 focus:ring-offset-0 cursor-pointer" 
                               type="checkbox" name="remember" id="remember">
                        <label class="ml-2 text-xs text-slate-450 cursor-pointer select-none" for="remember">Remember Me</label>
                    </div>

                    <button type="submit" 
                            class="btn-glow w-full py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/25 transition duration-300">
                        Sign In
                    </button>

                    <div class="text-center pt-2">
                        <p class="text-xs text-slate-500">
                            Don't have an account yet? 
                            <a href="{{ route('register') }}" class="text-indigo-400 hover:underline font-semibold ml-1">Sign Up</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
