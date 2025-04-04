@extends('layouts.app')

@section('main_content')
    <!-- Home Banner Area -->
    <section class="bg-gray-900 py-16 pt-[260px] relative" data-aos="fade-down" data-aos-delay="200" style="background: url('{{ asset('img/banner/library.png') }}') no-repeat right bottom; background-size: 50%; background-attachment: local;">
        <!-- Dark Transparent Overlay -->
        <div class="absolute inset-0 bg-black opacity-20"></div>

        <div class="container mx-auto px-6 text-center relative">
            <div class="text-white">
                <h2 class="text-4xl font-bold mb-4">Libraries</h2>
                <div class="flex justify-center space-x-4 text-gray-400">
                    <a href="{{ url('/') }}" class="hover:text-white transition">Home</a>
                    <span>/</span>
                    <a href="{{ route('libraries.index') }}" class="hover:text-white transition">Libraries</a>
                </div>
            </div>
        </div>
    </section>


    <!-- Popular Libraries Area -->
    <div class="bg-gray-900 py-16">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12" data-aos="fade-up" data-aos-delay="200">
                <h2 class="text-4xl font-bold text-white mb-4">Our Popular Libraries</h2>
                <p class="text-gray-400">
                    SITS is a place where you can find rich libraries you need to learn and grow.
                </p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($libraries as $library)
                    <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition hover:-translate-y-3"
                        data-aos="zoom-in" data-aos-delay="300">
                        <a href="{{ $library->link }}" target="_blank" rel="noopener noreferrer">
                            @if ($library->banner)
                                <img src="{{ asset('storage/' . $library->banner) }}" alt="{{ $library->title }}"
                                    class="w-full h-48 object-cover" />
                            @else
                                <img src="{{ asset('assets/images/default-banner.jpg') }}" alt="No Image"
                                    class="w-full h-48 object-cover" />
                            @endif
                        </a>
                        <div class="p-6">
                            <h4 class="text-lg font-bold text-white mb-2">
                                <a href="{{ $library->link }}" target="_blank" class="hover:text-blue-400 transition">
                                    {{ $library->title }}
                                </a>
                            </h4>
                            <hr>
                            <span class="block text-white text-sm px-2 py-1 rounded-full mb-3">
                                {{ $library->category }}
                            </span>
                            <p class="text-gray-400 text-sm">
                                {{ Str::limit($library->description, 100) }}
                            </p>
                            <div class="flex justify-between items-center mt-4">
                                <div class="flex items-center">
                                    <img src="img/libraries/author1.png" alt="Author" class="w-8 h-8 rounded-full">
                                    <span class="text-gray-300 ml-2">Cameron</span>
                                </div>
                                <div class="flex space-x-4">
                                    <span class="text-yellow-400 flex items-center"><i class="ti-user mr-2"></i>25</span>
                                    <span class="text-yellow-400 flex items-center"><i class="ti-heart mr-2"></i>35</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Feature Area -->
    @include('abouts.values')
@endsection
