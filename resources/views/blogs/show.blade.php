@extends('layouts.app')

@section('main_content')
    <!--================ Home Banner Area =================-->
    <section class="relative h-96 flex items-center justify-center bg-gray-800" style="background: url('{{ asset('img/banner/blog.png') }}') no-repeat center center; background-size: cover;">
        <!-- Dark Transparent Overlay -->
        <div class="absolute inset-0 bg-black opacity-50"></div>

        <!-- Content Overlay -->
        <div class="relative text-center px-6">
            <h2 class="text-4xl md:text-5xl font-bold text-white drop-shadow-md">
                {{ $blog->title }}
            </h2>
        </div>
    </section>
    <!--================ End Home Banner Area =================-->

    <!--================ Blog Content Area =================-->
    <section class="py-16 bg-gray-900">
        <div class="container mx-auto px-6">
            <!-- Blog Banner Image -->
            @if ($blog->image)
                <div class="mb-8">
                    <img src="{{ asset('storage/' . $blog->image) }}" alt="{{ $blog->title }}" class="w-full h-96 object-cover rounded-lg shadow-lg">
                </div>
            @endif

            <!-- Blog Content -->
            <article class="prose prose-lg prose-invert max-w-none text-gray-300">
                {!! $blog->content !!}
            </article>
        </div>
    </section>
    <!--================ End Blog Content Area =================-->
@endsection