<x-guest-layout>
    <div class="relative max-w-lg mx-auto">
        <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-indigo-500 to-purple-600 opacity-20 blur-xl"></div>
        
        <div class="relative glass-card rounded-3xl p-8 sm:p-10 border border-white/5 shadow-2xl space-y-6">
            <div class="text-center space-y-2">
                <a href="{{ url('/') }}" class="inline-block mb-2">
                    <img src="{{ asset('img/logo.png') }}" alt="SITS Logo" class="h-12 w-auto" />
                </a>
                <h2 class="text-2xl font-bold text-white font-outfit">Forgot Password</h2>
                <p class="text-xs text-slate-450 leading-relaxed max-w-xs mx-auto">
                    Enter your email address, and we'll send you a password reset link to restore access.
                </p>
            </div>

            @if (session('status'))
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf
                
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
                    <input type="email" id="email" name="email" required autofocus placeholder="name@example.com"
                           class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow" />
                    @error('email')
                        <span class="text-xs text-rose-500 mt-1.5 block">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" 
                        class="btn-glow w-full py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/25 transition duration-300">
                    Send Reset Link
                </button>
            </form>

            <div class="text-center pt-2 border-t border-slate-900/60">
                <a href="{{ route('login') }}" class="text-xs text-indigo-400 hover:underline">
                    Back to Login
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
