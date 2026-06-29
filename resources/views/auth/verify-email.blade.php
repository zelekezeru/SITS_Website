<x-guest-layout>
    <div class="relative max-w-lg mx-auto">
        <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-indigo-500 to-purple-600 opacity-20 blur-xl"></div>
        
        <div class="relative glass-card rounded-3xl p-8 sm:p-10 border border-white/5 shadow-2xl space-y-6">
            <div class="text-center space-y-2">
                <a href="{{ url('/') }}" class="inline-block mb-2">
                    <img src="{{ asset('img/logo.png') }}" alt="SITS Logo" class="h-12 w-auto" />
                </a>
                <h2 class="text-2xl font-bold text-white font-outfit">Verify Your Email</h2>
                <p class="text-xs text-slate-450 leading-relaxed max-w-xs mx-auto">
                    Before proceeding, please verify your email by clicking the link we just sent to you.
                </p>
            </div>

            @if (session('status') == 'verification-link-sent')
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
                    A new verification link has been sent to your email address.
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" 
                        class="btn-glow w-full py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/25 transition duration-300">
                    Resend Verification Link
                </button>
            </form>

            <div class="text-center pt-2 border-t border-slate-900/60">
                <a href="{{ route('logout') }}" 
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="text-xs text-slate-500 hover:text-white transition">
                    Logout
                </a>
                <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
