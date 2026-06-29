@extends('layouts.app')

@section('title', 'Digital Library — Subscription Plans | SITS')

@section('main_content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap');
    body { font-family: 'Plus Jakarta Sans', sans-serif; }

    .plan-card {
        background: rgba(15,23,42,0.55);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 1.5rem;
        transition: all 0.35s cubic-bezier(.16,1,.3,1);
    }
    .plan-card:hover {
        transform: translateY(-8px);
        border-color: rgba(99,102,241,0.35);
        box-shadow: 0 30px 60px rgba(99,102,241,0.12);
    }
    .plan-card.popular {
        border-color: rgba(139,92,246,0.4);
        box-shadow: 0 0 0 1px rgba(139,92,246,0.15), 0 20px 60px rgba(139,92,246,0.15);
    }
    .badge-popular {
        background: linear-gradient(135deg,#7c3aed,#a855f7);
        font-family: 'Outfit', sans-serif;
    }
    .price-text {
        font-family: 'Outfit', sans-serif;
        background: linear-gradient(135deg,#fff 30%,#a5b4fc 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .price-text.amber {
        background: linear-gradient(135deg,#fbbf24 0%,#f59e0b 100%);
        -webkit-background-clip: text;
        background-clip: text;
    }
    .check-icon { color: #818cf8; }
    .glow-blob {
        position:absolute; border-radius:50%;
        filter:blur(120px); opacity:0.12;
        pointer-events:none;
    }
</style>

<div class="relative bg-[#090d16] min-h-screen pb-24 overflow-hidden">
    <!-- Blobs -->
    <div class="glow-blob w-[500px] h-[500px] bg-indigo-600 top-[-80px] right-[-100px]"></div>
    <div class="glow-blob w-[400px] h-[400px] bg-violet-700 bottom-0 left-[-80px]"></div>

    <!-- Header -->
    <div class="relative pt-28 pb-16 text-center px-6">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-500/10 border border-amber-500/25 text-amber-300 text-xs font-semibold mb-6">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25"/></svg>
            Digital Library — JSTORE Subscription
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-white font-outfit leading-tight mb-4">
            Unlock the Full<br><span style="background:linear-gradient(135deg,#818cf8,#c084fc);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">SITS Digital Library</span>
        </h1>
        <p class="text-slate-400 max-w-xl mx-auto text-lg">
            Access thousands of theological books, study materials, lecture notes and audio resources with a single subscription.
        </p>
    </div>

    <!-- Active Subscription Banner -->
    @if($activeSubscription)
    <div class="max-w-3xl mx-auto px-6 mb-10">
        <div class="flex items-center gap-4 p-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/25 text-emerald-300">
            <svg class="w-8 h-8 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="flex-1">
                <p class="font-bold text-emerald-200">You have an active {{ $activeSubscription->plan_name }}!</p>
                <p class="text-sm text-emerald-400/80 mt-0.5">
                    @if($activeSubscription->plan_type === 'lifetime')
                        Lifetime access — never expires.
                    @else
                        Expires: {{ $activeSubscription->expiry_date->format('d M Y') }}
                        ({{ $activeSubscription->daysRemaining() }} days remaining)
                    @endif
                </p>
            </div>
            <a href="{{ route('library.portal') }}" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-white font-bold rounded-xl text-sm transition">
                Open Portal →
            </a>
        </div>
    </div>
    @endif

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="max-w-3xl mx-auto px-6 mb-8">
        <div class="p-4 rounded-2xl bg-indigo-500/10 border border-indigo-500/25 text-indigo-300 text-sm">
            {{ session('success') }}
        </div>
    </div>
    @endif

    <!-- Plan Cards -->
    <div class="max-w-5xl mx-auto px-6 grid md:grid-cols-3 gap-6">
        @foreach ($plans as $plan)
        @php $popular = $plan['popular'] ?? false; @endphp
        <div class="plan-card relative flex flex-col {{ $popular ? 'popular' : '' }}">
            @if($popular)
            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                <span class="badge-popular text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-lg">⭐ Most Popular</span>
            </div>
            @endif

            <div class="p-7 flex-1 flex flex-col">
                <!-- Header -->
                <div class="mb-6">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4
                        @if($plan['colour']==='indigo') bg-indigo-500/10 text-indigo-400
                        @elseif($plan['colour']==='violet') bg-violet-500/10 text-violet-400
                        @else bg-amber-500/10 text-amber-400 @endif">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white font-outfit">{{ $plan['name'] }}</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ $plan['duration'] }}</p>
                </div>

                <!-- Price -->
                <div class="mb-7">
                    <div class="flex items-end gap-1">
                        <span class="text-4xl font-black price-text {{ $plan['colour'] === 'amber' ? 'amber' : '' }}">{{ number_format($plan['price']) }}</span>
                        <span class="text-slate-400 text-sm mb-1.5">ETB</span>
                    </div>
                    <p class="text-xs text-slate-500">
                        @if($plan['key'] === 'monthly') per month
                        @elseif($plan['key'] === 'annual') per year
                        @else one-time payment @endif
                    </p>
                </div>

                <!-- Features -->
                <ul class="space-y-3 mb-8 flex-1">
                    @foreach ($plan['features'] as $feature)
                    <li class="flex items-start gap-2.5 text-sm text-slate-300">
                        <svg class="w-4 h-4 check-icon flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>

                <!-- CTA -->
                @auth
                    @if($activeSubscription && $activeSubscription->plan_type === $plan['key'])
                    <div class="w-full py-3.5 rounded-xl border border-emerald-500/25 bg-emerald-500/10 text-emerald-400 font-semibold text-sm text-center">
                        ✓ Current Plan
                    </div>
                    @else
                    <button onclick="openSubscribeModal('{{ $plan['key'] }}', '{{ $plan['name'] }}', {{ $plan['price'] }})"
                        class="w-full py-3.5 rounded-xl font-bold text-sm transition
                        @if($plan['colour']==='indigo') bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/20
                        @elseif($plan['colour']==='violet') bg-violet-600 hover:bg-violet-500 text-white shadow-lg shadow-violet-600/20
                        @else bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 shadow-lg shadow-amber-500/20 @endif">
                        Subscribe Now →
                    </button>
                    @endif
                @else
                <a href="{{ route('login') }}" class="block w-full py-3.5 rounded-xl font-bold text-sm text-center bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition">
                    Login to Subscribe
                </a>
                @endauth
            </div>
        </div>
        @endforeach
    </div>

    <!-- Payment Instructions -->
    <div class="max-w-3xl mx-auto px-6 mt-14">
        <div class="plan-card p-8">
            <h2 class="text-xl font-bold text-white font-outfit mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                How to Pay
            </h2>
            <div class="grid sm:grid-cols-2 gap-5 text-sm text-slate-400">
                <div>
                    <p class="text-white font-semibold mb-1.5">Commercial Bank of Ethiopia (CBE)</p>
                    <p>Account Number: <span class="text-slate-200 font-mono">1000XXXXXXXXXX</span></p>
                    <p>Account Name: <span class="text-slate-200">Shiloh International Theological Seminary</span></p>
                </div>
                <div>
                    <p class="text-white font-semibold mb-1.5">Telebirr Mobile Money</p>
                    <p>Merchant ID: <span class="text-slate-200 font-mono">SITS-LIB</span></p>
                    <p>Phone: <span class="text-slate-200">+251 XXX XXX XXX</span></p>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-5 border-t border-slate-800 pt-4">
                After making payment, click Subscribe and enter your transaction reference number. Our team will verify and activate your account within 24 hours.
            </p>
        </div>
    </div>
</div>

<!-- Subscribe Modal -->
<div id="subscribe-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 backdrop-blur-sm px-4">
    <div class="relative bg-[#0f172a] border border-white/10 rounded-2xl p-8 w-full max-w-md shadow-2xl">
        <button onclick="closeSubscribeModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <h3 id="modal-title" class="text-xl font-bold text-white font-outfit mb-1">Subscribe</h3>
        <p class="text-sm text-slate-400 mb-6">Enter your payment reference to complete the subscription.</p>

        <form action="{{ route('library.subscribe') }}" method="POST" class="space-y-5">
            @csrf
            <input type="hidden" name="plan_type" id="modal-plan-type">

            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Payment Method</label>
                <select name="payment_method" required
                    class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-indigo-500">
                    <option value="CBE">CBE Bank Transfer</option>
                    <option value="Telebirr">Telebirr</option>
                    <option value="Dashen">Dashen Bank</option>
                    <option value="Awash">Awash Bank</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Transaction Reference Number</label>
                <input type="text" name="payment_reference" required placeholder="e.g. TRN-123456789"
                    class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
            </div>

            <button type="submit"
                class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-sm transition shadow-lg shadow-indigo-600/25">
                Submit Subscription Request
            </button>
        </form>
    </div>
</div>

<script>
function openSubscribeModal(planKey, planName, price) {
    document.getElementById('modal-plan-type').value = planKey;
    document.getElementById('modal-title').textContent = 'Subscribe — ' + planName;
    const modal = document.getElementById('subscribe-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeSubscribeModal() {
    const modal = document.getElementById('subscribe-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
// Close on backdrop click
document.getElementById('subscribe-modal').addEventListener('click', function(e) {
    if (e.target === this) closeSubscribeModal();
});
</script>
@endsection
