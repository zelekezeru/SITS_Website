@extends('layouts.app')

@section('main_content')

    <!-- Home Banner Area -->
    <section class="bg-gray-900 py-16  mt-[190px]" data-aos="fade-down" data-aos-delay="200">
        <div class="container mx-auto px-6 text-center">
            <div class="text-white">
                <h2 class="text-4xl font-bold mb-4">Our Programs</h2>
                <div class="flex justify-center space-x-4 text-gray-400">
                    <a href="{{ url('/') }}" class="hover:text-white transition">Home</a>
                    <span>/</span>
                    <a href="{{ route('programs.index') }}" class="hover:text-white transition">Programs</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Programs Area -->
    <div class="bg-gray-900 py-16">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12" data-aos="fade-up" data-aos-delay="300">
                <h2 class="text-4xl font-bold text-white mb-4">Our Popular Programs</h2>
                <p class="text-gray-400">
                    Explore a wide range of programs to help you grow and achieve your goals.
                </p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($programs as $program)
                    <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition hover:-translate-y-2"
                        data-aos="zoom-in" data-aos-delay="400">
                        @php
                            $randomBanner = $bannerImages->isNotEmpty() ? $bannerImages->random() : null;
                        @endphp
                        @if ($randomBanner)
                            <img src="{{ asset('images/banners/' . basename($randomBanner)) }}" alt="{{ $program->title }}"
                                class="w-full h-48 object-cover" />
                        @else
                            <img src="{{ asset('assets/images/default-banner.jpg') }}" alt="Default Banner"
                                class="w-full h-48 object-cover" />
                        @endif
                        <div class="p-6">
                            <h4 class="text-lg font-bold text-white mb-2">
                                <a href="{{ route('programs.show', $program->id) }}" class="hover:text-blue-400 transition">
                                    {{ $program->title }}
                                </a>
                            </h4>
                            <hr>
                            <span class="block text-white text-sm px-2 py-1 rounded-full mb-3">
                                {{ $program->division }}
                            </span>
                            <p class="text-gray-400 text-sm">
                                {{ Str::limit($program->description, 100) }}
                            </p>
                            <div class="flex justify-between items-center mt-4">
                                <span class="text-yellow-400 font-bold">{{ $program->code }}</span>
                                <div class="flex items-center">
                                    <img src="img/courses/author1.png" alt="Instructor" class="w-8 h-8 rounded-full">
                                    <span class="text-gray-300 ml-2">Instructor</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
