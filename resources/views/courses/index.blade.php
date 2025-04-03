@extends('layouts.app')

@section('main_content')

    <!-- Home Banner Area -->
    <section class="bg-gray-900 py-16 mt-[190px]" data-aos="fade-down" data-aos-delay="200">
        <div class="container mx-auto px-6 text-center">
            <div class="text-white">
                <h2 class="text-4xl font-bold mb-4">Courses</h2>
                <div class="flex justify-center space-x-4 text-gray-400">
                    <a href="{{ url('/') }}" class="hover:text-white transition">Home</a>
                    <span>/</span>
                    <a href="{{ route('courses.index') }}" class="hover:text-white transition">Courses</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Courses Area -->
    <div class="bg-gray-900 py-16">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12" data-aos="fade-up" data-aos-delay="200">
                <h2 class="text-4xl font-bold text-white mb-4">Our Popular Courses</h2>
                <p class="text-gray-400">
                    Replenish man have thing gathering lights yielding shall you.
                </p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($courses as $course)
                    <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition hover:-translate-y-3"
                        data-aos="zoom-in" data-aos-delay="300">
                        <img src="{{ asset('storage/' . $course->banner) }}" alt="{{ $course->title }}"
                            class="w-full h-56 object-cover hover:scale-105 transition" />
                        <div class="p-6">
                            <h4 class="text-lg font-bold text-white mb-2">
                                <a href="{{ route('courses.show', $course->id) }}" class="hover:text-blue-400 transition">
                                    {{ $course->title }}
                                </a>
                            </h4>
                            <hr>
                            <span class="block text-white text-sm px-2 py-1 rounded-full mb-3 transition hover:bg-blue-600">
                                {{ $course->category }}
                            </span>
                            <p class="text-gray-400 text-sm">
                                {{ $course->description }}
                            </p>
                            <div class="flex justify-between items-center mt-4">
                                <span class="text-yellow-400 font-bold">{{ $course->amount_paid }} Br</span>
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

    <!-- Registration Area -->
    <div class="bg-gray-900 py-16">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-2 gap-6 items-center" data-aos="fade-right" data-aos-delay="300">
                <!-- Left Content -->
                <div>
                    <h1 class="text-4xl font-bold text-white mb-4">Register Now</h1>
                    <p class="text-gray-400">
                        Take the first step toward success by registering with SITS!
                        Whether you're looking for top-quality training, expert-led courses, or
                        career-enhancing opportunities, SITS is here to equip you with the skills you need.
                    </p>
                    <ul class="space-y-4 mt-4 text-gray-400">
                        <li>✅ Easy Registration – Sign up in just a few clicks.</li>
                        <li>✅ Exclusive Access – Get personalized learning and Library resources.</li>
                        <li>✅ Expert-Led Programs – Learn from industry professionals.</li>
                        <li>✅ Flexible Learning – Study at your own pace, anytime, anywhere.</li>
                    </ul>
                </div>
                <!-- Right Form -->
                <div class="bg-gray-800 p-6 rounded-lg shadow-lg hover:shadow-xl transition hover:-translate-y-3"
                    data-aos="fade-left" data-aos-delay="400">
                    <h3 class="text-2xl font-bold text-white mb-4">Enroll Now!</h3>
                    <p class="text-gray-400 mb-6">Online Theological Education at SITS</p>
                    <form action="{{ route('subscriptions.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <!-- Input Fields -->
                        <input type="text" name="name" placeholder="Your Name" required
                            class="w-full bg-transparent border-b border-gray-600 focus:border-blue-400 text-sm text-gray-300 py-2 focus:outline-none" />
                        <input type="tel" name="phone" placeholder="Your Phone Number" required
                            class="w-full bg-transparent border-b border-gray-600 focus:border-blue-400 text-sm text-gray-300 py-2 focus:outline-none" />
                        <input type="email" name="email" placeholder="Your Email Address"
                            pattern="[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{1,63}$" required
                            class="w-full bg-transparent border-b border-gray-600 focus:border-blue-400 text-sm text-gray-300 py-2 focus:outline-none" />
                        <input type="text" name="address" placeholder="Your Living Address" required
                            class="w-full bg-transparent border-b border-gray-600 focus:border-blue-400 text-sm text-gray-300 py-2 focus:outline-none" />
                        <!-- Hidden Input -->
                        <input type="hidden" name="type" value="subscribe" />
                        <!-- Submit Button -->
                        <button type="submit"
                            class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold text-sm py-2 rounded transition">
                            Submit
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
