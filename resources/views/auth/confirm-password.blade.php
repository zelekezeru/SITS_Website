<x-guest-layout>
    <div class="relative max-w-lg mx-auto">
        <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-indigo-500 to-purple-600 opacity-20 blur-xl"></div>
        
        <div class="relative glass-card rounded-3xl p-8 sm:p-10 border border-white/5 shadow-2xl space-y-6">
            <div class="text-center space-y-2">
                <a href="{{ url('/') }}" class="inline-block mb-2">
                    <img src="{{ asset('img/logo.png') }}" alt="SITS Logo" class="h-12 w-auto" />
                </a>
                <h2 class="text-2xl font-bold text-white font-outfit">Confirm Password</h2>
                <p class="text-xs text-slate-450 leading-relaxed max-w-xs mx-auto">
                    For your security, please confirm your password to continue.
                </p>
            </div>

            @if ($errors->any())
                <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs space-y-1">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
                @csrf
                
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Password</label>
                    <input type="password" id="password" name="password" required autofocus placeholder="••••••••"
                           class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow" />
                </div>

                <button type="submit" 
                        class="btn-glow w-full py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/25 transition duration-300">
                    Confirm Password
                </button>
            </form>

            @if (Route::has('password.request'))
                <div class="text-center pt-2 border-t border-slate-900/60">
                    <a href="{{ route('password.request') }}" class="text-xs text-indigo-400 hover:underline">
                        Forgot your password?
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-guest-layout>
