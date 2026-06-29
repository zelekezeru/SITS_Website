@extends('layouts.app')

@section('main_content')
    <!-- Hero Banner Area -->
    <section class="relative py-24 pt-36 pb-16 overflow-hidden" data-aos="fade-down">
        <div class="container mx-auto px-6 text-center relative z-10">
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-6">
                Institution Insights
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold text-white font-outfit mb-4">Blogs & Articles</h1>
            <div class="flex justify-center items-center space-x-3 text-sm text-slate-500">
                <a href="{{ url('/') }}" class="hover:text-white transition">Home</a>
                <span>/</span>
                <span class="text-slate-300">Blogs</span>
            </div>
        </div>
    </section>

    <!-- Blog Area -->
    <section class="py-20 relative">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                <!-- Blog Left Sidebar -->
                <div class="lg:col-span-8">
                    <div class="space-y-10">
                        @foreach ($blogs as $blog)
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start" data-aos="fade-up">
                                <!-- Blog Info Metadata -->
                                <div class="md:col-span-3 space-y-4">
                                    <span class="inline-block px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-300 text-xs font-bold border border-indigo-500/20 uppercase tracking-wider">
                                        {{ $blog->category ?? 'Theology' }}
                                    </span>
                                    <ul class="text-xs text-slate-500 space-y-2.5">
                                        <li class="flex items-center gap-2">
                                            <i class="ti-user text-indigo-400"></i>
                                            <span>{{ $blog->author }}</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="ti-calendar text-indigo-400"></i>
                                            <span>{{ $blog->date }}</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="ti-eye text-indigo-400"></i>
                                            <span>1.2K Views</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Blog Card Content -->
                                <div class="md:col-span-9">
                                    <div class="glass-card glass-card-hover rounded-2xl overflow-hidden border border-white/5 p-6 space-y-4">
                                        @if($blog->image)
                                            <div class="h-64 rounded-xl overflow-hidden bg-slate-950">
                                                <img src="{{ asset('storage/' . $blog->image) }}" alt="{{ $blog->title }}"
                                                    class="w-full h-full object-cover hover:scale-102 transition duration-500" />
                                            </div>
                                        @endif
                                        <h3 class="text-xl font-bold text-white font-outfit hover:text-indigo-400 transition">
                                            <a href="{{ route('blogs.show', $blog) }}">
                                                {{ $blog->title }}
                                            </a>
                                        </h3>
                                        <p class="text-sm text-slate-400 leading-relaxed line-clamp-3">
                                            {{ Str::limit(strip_tags($blog->content), 200) }}
                                        </p>
                                        <div class="pt-2">
                                            <a href="{{ route('blogs.show', $blog) }}"
                                                class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 font-bold transition group">
                                                <span>Read Article</span>
                                                <i class="ti-arrow-right text-[10px] group-hover:translate-x-0.5 transition duration-300"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-16">
                        {{ $blogs->links() }}
                    </div>
                </div>

                <!-- Blog Right Sidebar -->
                <div class="lg:col-span-4 space-y-8">
                    <!-- Search Widget -->
                    <div class="glass-card rounded-2xl p-6 border border-white/5" data-aos="fade-up">
                        <h4 class="text-md font-bold text-white font-outfit mb-4">Search Posts</h4>
                        <div class="flex gap-2">
                            <input type="text"
                                class="flex-1 bg-slate-900 border border-slate-800 text-white placeholder-slate-500 px-4 py-2.5 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow"
                                placeholder="Search here..." />
                            <button class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2.5 rounded-xl transition">
                                <i class="ti-search text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Popular Posts Widget -->
                    <div class="glass-card rounded-2xl p-6 border border-white/5" data-aos="fade-up" data-aos-delay="100">
                        <h4 class="text-md font-bold text-white font-outfit mb-4">Popular Posts</h4>
                        <div class="space-y-5">
                            @foreach ($blogs->take(3) as $popBlog)
                                <div class="flex gap-4 items-center group">
                                    @if($popBlog->image)
                                        <img src="{{ asset('storage/' . $popBlog->image) }}" alt="{{ $popBlog->title }}"
                                            class="w-16 h-16 rounded-xl object-cover shrink-0 bg-slate-950 border border-white/5" />
                                    @else
                                        <div class="w-16 h-16 rounded-xl bg-slate-900 border border-white/5 flex items-center justify-center text-slate-750 shrink-0">
                                            <i class="ti-file text-lg"></i>
                                        </div>
                                    @endif
                                    <div class="space-y-1">
                                        <h5 class="text-xs font-bold text-white font-outfit line-clamp-2 group-hover:text-indigo-400 transition">
                                            <a href="{{ route('blogs.show', $popBlog) }}">{{ $popBlog->title }}</a>
                                        </h5>
                                        <p class="text-[10px] text-slate-500">{{ $popBlog->created_at->format('M d, Y') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
