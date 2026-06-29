@extends('layouts.app')

@section('title', 'Digital Library Portal | SITS')

@section('main_content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap');
    body { font-family: 'Plus Jakarta Sans', sans-serif; }

    .glass-card {
        background: rgba(15,23,42,0.55);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 1.5rem;
    }
    .resource-card {
        background: rgba(15,23,42,0.5);
        border: 1px solid rgba(255,255,255,0.05);
        border-radius: 1rem;
        transition: all 0.3s ease;
    }
    .resource-card:hover {
        transform: translateY(-4px);
        border-color: rgba(99,102,241,0.25);
        box-shadow: 0 20px 40px rgba(99,102,241,0.1);
    }
    .glow-blob { position:absolute; border-radius:50%; filter:blur(120px); opacity:0.1; pointer-events:none; }
</style>

<div class="relative bg-[#090d16] min-h-screen pb-24 overflow-hidden">
    <div class="glow-blob w-[500px] h-[500px] bg-violet-600 top-0 right-[-100px]"></div>
    <div class="glow-blob w-[400px] h-[400px] bg-amber-600 bottom-0 left-[-80px]"></div>

    <!-- Portal Header -->
    <div class="relative pt-28 pb-10 px-6 border-b border-slate-800/60">
        <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-between gap-6">
            <div>
                @if($subscription)
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/25 text-emerald-400 text-xs font-semibold mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Active Subscription
                    </div>
                @else
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/25 text-indigo-400 text-xs font-semibold mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                        Administrative Access
                    </div>
                @endif
                <h1 class="text-3xl font-extrabold text-white font-outfit">SITS Digital Library</h1>
                <p class="text-slate-400 mt-1 text-sm">Access all theological books, lecture notes, and study resources.</p>
            </div>

            <!-- Subscription Badge -->
            @if($subscription)
                <div class="glass-card px-6 py-4 flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-{{ $subscription->planColour() }}-500/10 flex items-center justify-center text-{{ $subscription->planColour() }}-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-white font-bold text-sm font-outfit">{{ $subscription->plan_name }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            @if($subscription->plan_type === 'lifetime')
                                Never expires — Lifetime Access
                            @else
                                Expires {{ $subscription->expiry_date->format('d M Y') }}
                                · {{ $subscription->daysRemaining() }} days left
                            @endif
                        </p>
                    </div>
                </div>
            @else
                <div class="glass-card px-6 py-4 flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-white font-bold text-sm font-outfit">Administrative Access</p>
                        <p class="text-xs text-slate-400 mt-0.5">Bypass Subscription</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Search / Filter Bar -->
    <div class="max-w-6xl mx-auto px-6 mt-8">
        <div class="flex flex-wrap gap-4 items-center">
            <div class="flex-1 min-w-[200px] relative">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="text" id="search-input" placeholder="Search books, resources..."
                    class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 transition">
            </div>
            <span class="text-xs text-slate-500">{{ $libraries->total() }} resources available</span>
        </div>
    </div>

    <!-- Library Resources Grid -->
    <div class="max-w-6xl mx-auto px-6 mt-6" id="resources-grid">
        @if($libraries->isEmpty())
            <div class="text-center py-20">
                <div class="w-16 h-16 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto text-slate-600 mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25"/></svg>
                </div>
                <h3 class="text-lg font-bold text-white font-outfit">No resources yet</h3>
                <p class="text-sm text-slate-500 mt-1">The library is being populated. Check back soon.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @foreach ($libraries as $item)
                <div class="resource-card overflow-hidden" data-title="{{ strtolower($item->title ?? '') }}">
                    <!-- Cover / Banner -->
                    <div class="h-44 bg-slate-950 relative overflow-hidden">
                        @if($item->banner)
                            <img src="{{ asset('storage/' . $item->banner) }}" alt="{{ $item->title }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-slate-900 to-violet-950/40 flex items-center justify-center text-slate-700">
                                <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25"/></svg>
                            </div>
                        @endif
                        <!-- File type badge -->
                        @if($item->file)
                        <div class="absolute top-3 right-3">
                            <span class="bg-indigo-600/90 backdrop-blur text-white text-[10px] font-bold px-2 py-1 rounded-md uppercase tracking-wide">PDF</span>
                        </div>
                        @endif
                    </div>

                    <!-- Info -->
                    <div class="p-4">
                        <h4 class="text-sm font-bold text-white font-outfit line-clamp-2 mb-1">{{ $item->title ?? 'Untitled' }}</h4>
                        @if(isset($item->author))
                        <p class="text-xs text-slate-500 mb-3">{{ $item->author }}</p>
                        @endif
                        @if(isset($item->description))
                        <p class="text-xs text-slate-400 line-clamp-2 mb-3">{{ $item->description }}</p>
                        @endif

                        <!-- Actions -->
                        <div class="flex gap-2 pt-2 border-t border-slate-800">
                            @if($item->file)
                            <a href="{{ asset('storage/' . $item->file) }}" target="_blank"
                                class="flex-1 py-2 rounded-lg bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-xs font-semibold text-center border border-indigo-600/20 transition flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                Download
                            </a>
                            @endif
                            <a href="{{ route('libraries.show', $item->id) }}"
                                class="flex-1 py-2 rounded-lg bg-slate-800/60 hover:bg-slate-800 text-slate-300 text-xs font-semibold text-center border border-slate-700/50 transition">
                                View
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-10">
                {{ $libraries->links() }}
            </div>
        @endif
    </div>
</div>

<script>
// Live search filter
document.getElementById('search-input').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#resources-grid [data-title]').forEach(function(card) {
        const title = card.getAttribute('data-title');
        card.style.display = title.includes(q) ? '' : 'none';
    });
});
</script>
@endsection
