@extends('layouts.app')

@section('main_content')
    <!-- Hero Banner Area -->
    <section class="relative py-24 pt-36 pb-16 overflow-hidden" data-aos="fade-down">
        <div class="container mx-auto px-6 text-center relative z-10">
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-6">
                Academic Resources
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold text-white font-outfit mb-4">Libraries</h1>
            <div class="flex justify-center items-center space-x-3 text-sm text-slate-500">
                <a href="{{ url('/') }}" class="hover:text-white transition">Home</a>
                <span>/</span>
                <span class="text-slate-300">Libraries</span>
            </div>
        </div>
    </section>


    <!-- Popular Libraries Area -->
    <div class="py-20 relative">
        <div class="container mx-auto px-6">
            <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
                <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-4">
                    Digital & Physical
                </div>
                <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">Our Popular Libraries</h2>
                <p class="text-slate-400">
                    SITS provides access to rich academic libraries and digital databases to support your research and study.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($libraries as $library)
                    <div class="glass-card glass-card-hover rounded-2xl overflow-hidden border border-white/5 flex flex-col justify-between h-full"
                        data-aos="zoom-in" data-aos-delay="100">
                        
                        <a href="{{ $library->link }}" target="_blank" rel="noopener noreferrer" class="block group">
                            @if ($library->banner)
                                <div class="flex items-center justify-center h-48 bg-slate-950 border-b border-white/5 p-6 overflow-hidden">
                                    <img src="{{ asset('storage/' . $library->banner) }}" alt="{{ $library->title }}" class="h-full object-contain group-hover:scale-105 transition duration-500" />
                                </div>
                            @else
                                <div class="w-full h-48 bg-gradient-to-br from-slate-900 to-indigo-950/60 flex items-center justify-center text-slate-700 border-b border-white/5">
                                    <i class="ti-book-open text-4xl"></i>
                                </div>
                            @endif
                        </a>

                        <div class="p-6 flex-1 flex flex-col justify-between text-center space-y-4">
                            <div class="space-y-2">
                                <h4 class="text-md font-bold text-white font-outfit line-clamp-2 hover:text-indigo-400 transition">
                                    <a href="{{ $library->link }}" target="_blank">
                                        {{ $library->title }}
                                    </a>
                                </h4>
                                <span class="inline-block px-2.5 py-1 rounded-md bg-indigo-500/10 text-indigo-300 text-[10px] font-bold border border-indigo-500/20 uppercase">
                                    {{ $library->category }}
                                </span>
                            </div>
                            
                            <p class="text-xs text-slate-450 leading-relaxed line-clamp-3">
                                {{ $library->description }}
                            </p>

                            <div class="pt-4 border-t border-slate-900/60">
                                <a href="{{ $library->link }}" target="_blank"
                                   class="inline-flex items-center gap-1 text-xs text-indigo-400 hover:text-indigo-300 font-semibold transition group">
                                    <span>Access Database</span>
                                    <i class="ti-arrow-top-right text-[10px] group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition duration-300"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Feature Area -->
    <div class="relative py-24 bg-slate-950/40 border-t border-slate-900/60">
        @include('abouts.values')
    </div>
@endsection
