@extends('layouts.app')

@section('main_content')
    <!--================Home Banner Area =================-->
    <section class="relative bg-gray-800 py-20">
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        <div class="container mx-auto relative z-10 text-center">
            <h2 class="text-4xl font-bold text-white mb-4">Events</h2>
            <div class="text-gray-300">
                <a href="{{ url('/') }}" class="hover:text-blue-400 transition">Home</a>
                <span class="mx-2">/</span>
                <a href="{{ route('events.index') }}" class="hover:text-blue-400 transition">Events</a>
            </div>
        </div>
    </section>
    <!--================End Home Banner Area =================-->

    <!--================ Start Events Area =================-->
    <div class="py-12 bg-gray-900">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Upcoming Events</h2>
                <p class="text-gray-400">Join us at one of our exciting events. Don't miss out on the latest happenings!</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($events as $event)
                    <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden transform hover:scale-105 hover:shadow-xl transition duration-300">
                        <div class="relative">
                            @if ($event->banner)
                                <img class="w-full h-56 object-cover" src="{{ asset('storage/' . $event->banner) }}" alt="{{ $event->title }}" />
                            @else
                                <img class="w-full h-56 object-cover" src="{{ asset('images/default-banner.jpg') }}" alt="Default Banner" />
                            @endif
                        </div>
                        <div class="p-6">
                            <h4 class="text-xl font-semibold text-white mb-3">
                                <a href="{{ route('events.show', $event->id) }}" class="hover:text-blue-400 transition">
                                    {{ $event->title }}
                                </a>
                            </h4>
                            <p class="text-gray-400 text-sm mb-4">{{ Str::limit($event->description, 100) }}</p>
                            <div class="flex justify-between items-center text-gray-400 text-sm">
                                <span class="flex items-center">
                                    <i class="ti-calendar mr-2"></i>{{ \Carbon\Carbon::parse($event->date)->format('F j, Y') }}
                                </span>
                                <span class="flex items-center">
                                    <i class="ti-location-pin mr-2"></i>{{ $event->location }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <!--================ End Events Area =================-->
@endsection
