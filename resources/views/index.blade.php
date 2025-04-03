@extends('layouts.app')

@section('main_content')
    <!--================ Start Home Banner Area =================-->
    <div class="relative h-screen flex items-center justify-center bg-gray-800" data-aos="fade-in" data-aos-delay="100">
        <!-- Content Overlay -->
        <div class="relative text-center px-6 mt-[90px]" data-aos="zoom-in" data-aos-delay="200">
            <p class="text-lg md:text-xl text-gray-300 mb-6 drop-shadow-md">
                Convenient Theological Education
            </p>
            <h2 class="text-4xl md:text-6xl font-bold text-white mb-6 drop-shadow-lg">
                Theological Education in a Digital and Convenient Way
                <br> <span class="text-3xl font-semibold text-yellow-500">Since 1994 G.C</span>
            </h2>
            <div class="space-x-4 mt-8">
                <a href="{{ route('abouts.index') }}"
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-lg transition"
                    data-aos="fade-up" data-aos-delay="300">
                    Learn More
                </a>
                <a href="{{ route('courses.index') }}"
                    class="px-6 py-3 bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-medium rounded-lg shadow-lg transition"
                    data-aos="fade-up" data-aos-delay="400">
                    See Courses
                </a>
            </div>
        </div>
    </div>


    <!--================ End Home Banner Area =================-->

    <!--================ Start Feature Area =================-->

    @include('abouts.values')

    <!--================ End Feature Area =================-->

    <!--================ Start Popular Courses Area =================-->
    <div class="bg-gray-900 py-12">
        <div class="container mx-auto px-6">
            <!-- Heading -->
            <div class="text-center mb-12" data-aos="fade-up" data-aos-delay="200">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Our Popular Courses</h2>
                <p class="text-gray-400 text-lg">
                    Our popular courses offer cutting-edge content, practical skills, and flexible learning options,
                    designed to meet diverse student needs.
                </p>
            </div>

            <!-- Courses Grid -->
            <div class="relative">
                <div class="owl-carousel active_course">
                    @foreach ($courses as $course)
                        <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:bg-gray-700 transition-transform transform hover:scale-105 hover:shadow-xl"
                            data-aos="fade-up" data-aos-delay="300">
                            <!-- Course Image -->
                            <div class="relative">
                                <img class="w-full h-56 object-cover" src="{{ asset('storage/' . $course->banner) }}"
                                    alt="{{ $course->title }}" />
                                <div
                                    class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-gray-900 to-transparent p-4">
                                    <span
                                        class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm">{{ $course->category }}</span>
                                </div>
                            </div>

                            <!-- Course Content -->
                            <div class="p-6">
                                <h4 class="text-xl font-semibold text-white mt-4">
                                    <a href="{{ route('courses.show', $course->id) }}"
                                        class="hover:text-blue-400 transition">
                                        {{ $course->title }}
                                    </a>
                                </h4>
                                <p class="text-gray-400 text-sm mt-2 line-clamp-3">
                                    {{ $course->description }}
                                </p>
                                <div class="flex items-center justify-between mt-4">
                                    <span class="text-yellow-400 font-bold text-lg">{{ $course->amount_paid }} Br</span>
                                    <div class="flex items-center">
                                        <img src="img/courses/author1.png" alt="Author" class="w-8 h-8 rounded-full" />
                                        <span class="text-gray-400 ml-2">By Field Experts</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Custom Owl Carousel Navigation -->
                <div class="absolute top-1/2 -left-8 transform -translate-y-1/2 z-10">
                    <button class="prev bg-gray-700 text-white rounded-full p-3 hover:bg-blue-500 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                </div>
                <div class="absolute top-1/2 -right-8 transform -translate-y-1/2 z-10">
                    <button class="next bg-gray-700 text-white rounded-full p-3 hover:bg-blue-500 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>



    <!--================ End Popular Courses Area =================-->

    <!--================ Start Registration Area =================-->
    <div class="py-[90px] bg-gray-800">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-2 gap-6 items-center">
                <!-- Left Content -->
                <div class="space-y-6">
                    <div>
                        <h1 class="text-4xl text-white font-bold mb-4" data-aos="fade-right" data-aos-delay="200">Register
                            Now</h1>
                        <p class="text-gray-400" data-aos="fade-right" data-aos-delay="300">
                            Take the first step toward success by registering with SITS!
                            Whether you're looking for top-quality training, expert-led courses,
                            or career-enhancing opportunities, SITS is here to equip you with the skills you need.
                        </p>
                    </div>
                    <div data-aos="fade-up" data-aos-delay="400">
                        <h4 class="text-yellow-500 text-xl mb-4">Don’t wait! Register now and unlock your potential with
                            SITS.</h4>
                        <ul class="space-y-4">
                            <li class="flex items-center">
                                <span class="text-yellow-500 text-lg mr-2">&#10003;</span>
                                <span class="text-gray-300">Easy Registration – Sign up in just a few clicks.</span>
                            </li>
                            <li class="flex items-center">
                                <span class="text-yellow-500 text-lg mr-2">&#10003;</span>
                                <span class="text-gray-300">Exclusive Access – Personalized resources and tools.</span>
                            </li>
                            <li class="flex items-center">
                                <span class="text-yellow-500 text-lg mr-2">&#10003;</span>
                                <span class="text-gray-300">Expert-Led Programs – Learn from professionals.</span>
                            </li>
                            <li class="flex items-center">
                                <span class="text-yellow-500 text-lg mr-2">&#10003;</span>
                                <span class="text-gray-300">Flexible Learning – Anytime, anywhere.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Right Content -->
                <div class="bg-gray-900 p-6 rounded-lg shadow-lg" data-aos="zoom-in" data-aos-delay="500">
                    <h3 class="text-2xl text-white font-bold mb-4">Enroll Now!</h3>
                    <p class="text-gray-400 mb-6">Online Theological Education at SITS</p>
                    <form action="{{ route('subscriptions.store') }}" method="POST" enctype="multipart/form-data"
                        class="space-y-4">
                        @csrf
                        <!-- Name Input -->
                        <input name="name" placeholder="Your Name" required type="text"
                            class="w-full px-2 py-2 bg-transparent text-gray-300 border-b border-gray-700 focus:border-blue-500 focus:outline-none placeholder-gray-400 text-sm" />
                        <!-- Phone Input -->
                        <input name="phone" placeholder="Your Phone Number" required type="tel"
                            class="w-full px-2 py-2 bg-transparent text-gray-300 border-b border-gray-700 focus:border-blue-500 focus:outline-none placeholder-gray-400 text-sm" />
                        <!-- Email Input -->
                        <input name="email" placeholder="Your Email Address"
                            pattern="[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{1,63}$" required type="email"
                            class="w-full px-2 py-2 bg-transparent text-gray-300 border-b border-gray-700 focus:border-blue-500 focus:outline-none placeholder-gray-400 text-sm" />
                        <!-- Address Input -->
                        <input name="address" placeholder="Your Living Address" required type="text"
                            class="w-full px-2 py-2 bg-transparent text-gray-300 border-b border-gray-700 focus:border-blue-500 focus:outline-none placeholder-gray-400 text-sm" />
                        <!-- Hidden Input for Subscription Type -->
                        <input type="text" name="type" required value="subscribe" hidden />
                        <!-- Submit Button -->
                        <button type="submit"
                            class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold text-sm w-full rounded transition">
                            Submit
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!--================ End Registration Area =================-->

    <!--================ Start Trainers Area =================-->
    <section class="py-12 bg-gray-900">
        <div class="container mx-auto px-6">
            <!-- Heading -->
            <div class="text-center mb-12" data-aos="fade-up" data-aos-delay="200">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Our Expert Trainers</h2>
                <p class="text-gray-400">
                    Our expert trainers provide top-tier education, ministry experience, personalized guidance, and
                    innovative methods, ensuring student success and growth.
                </p>
            </div>

            <!-- Trainers Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @if ($trainers->isEmpty())
                    <div class="col-span-full text-center"data-aos="zoom-in" data-aos-delay="200">
                        <h3 class="text-xl text-white">No Trainers available at the moment</h3>
                    </div>
                @else
                    @foreach ($trainers as $trainer)
                        <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden transform hover:scale-105 hover:shadow-xl transition duration-300"
                            data-aos="fade-up" data-aos-delay="200">
                            <!-- Trainer Image -->
                            <div class="relative">
                                <img class="w-full h-56 object-cover" src="{{ asset('storage/' . $trainer->image) }}"
                                    alt="{{ $trainer->name }}" />
                            </div>

                            <!-- Trainer Content -->
                            <div class="p-6 text-center">
                                <h4 class="text-xl font-semibold text-white">{{ $trainer->name }}</h4>
                                <p class="text-yellow-500 text-sm mb-4">{{ $trainer->position }}</p>
                                <p class="text-gray-400 text-sm">
                                    {{ $trainer->description }}
                                </p>
                                <!-- Social Media Links -->
                                <div class="mt-4 flex justify-center space-x-4">
                                    <a href="#" class="text-gray-400 hover:text-blue-500 transition">
                                        <i class="ti-facebook"></i>
                                    </a>
                                    <a href="#" class="text-gray-400 hover:text-blue-500 transition">
                                        <i class="ti-twitter"></i>
                                    </a>
                                    <a href="#" class="text-gray-400 hover:text-blue-500 transition">
                                        <i class="ti-linkedin"></i>
                                    </a>
                                    <a href="#" class="text-gray-400 hover:text-blue-500 transition">
                                        <i class="ti-pinterest"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </section>

    <!--================ End Trainers Area =================-->

    <!--================ Start Events Area =================-->

    <section class="py-12 bg-gray-900" data-aos="zoom-in" data-aos-delay="200">
        <div class="container mx-auto px-6">
            <!-- Heading -->
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Upcoming Events</h2>
                <p class="text-gray-400">Don't miss out on our latest events!</p>
            </div>

            <!-- Events Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" data-aos="fade-up" data-aos-delay="300">
                @if ($events->isEmpty())
                    <!-- Empty State -->
                    <div class="col-span-full text-center">
                        <h3 class="text-xl text-white">No events available at the moment</h3>
                    </div>
                @else
                    @foreach ($events as $event)
                        <div
                            class="bg-gray-800 rounded-lg shadow-lg overflow-hidden transform hover:scale-105 hover:shadow-xl transition duration-300">
                            <!-- Event Thumbnail -->
                            <img src="{{ asset('storage/' . $event->banner) }}" alt="{{ $event->title }}"
                                class="w-full h-56 object-cover" />

                            <!-- Event Details -->
                            <div class="p-6">
                                <div class="flex items-center space-x-6 mb-4">
                                    <!-- Date -->
                                    <div class="text-center bg-yellow-500 text-white p-2 rounded-lg">
                                        <span
                                            class="block text-2xl font-bold">{{ \Carbon\Carbon::parse($event->date)->format('d') }}</span>
                                        <span
                                            class="block text-sm font-medium">{{ \Carbon\Carbon::parse($event->date)->format('M') }}</span>
                                    </div>

                                    <!-- Time & Location -->
                                    <div>
                                        <p class="text-gray-300 flex items-center">
                                            <span class="ti-time mr-2"></span>
                                            {{ \Carbon\Carbon::parse($event->start_time)->format('h:i A') }} -
                                            {{ \Carbon\Carbon::parse($event->end_time)->format('h:i A') }}
                                        </p>
                                        <p class="text-gray-300 flex items-center">
                                            <span class="ti-location-pin mr-2"></span>
                                            {{ $event->location }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Description -->
                                <p class="text-gray-400 text-md">{{ $event->description }}</p>
                            </div>
                        </div>
                    @endforeach

                    <!-- View All Events Link -->
                    <div class="col-span-full text-center">
                        <a href="{{ route('events.index') }}"
                            class="text-blue-400 hover:text-blue-500 font-semibold text-sm transition flex items-center justify-center space-x-2">
                            <span>View All Events</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>


    <!--================ End Events Area =================-->

    <!-- Announcement -->
    <section data-aos="zoom-in" data-aos-delay="100"
        class="dark:bg-gray-900 bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern.svg')] dark:bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern-dark.svg')]">
        <div class="py-8 px-4 mx-auto max-w-screen-xl text-center lg:py-16 z-10 relative">
            <!-- Announcement Banner -->
            <a href="#"
                class="inline-flex justify-between items-center py-1 px-1 pe-4 mb-7 text-sm text-blue-700 bg-blue-100 rounded-full dark:bg-blue-900 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-800"
                data-aos="zoom-in" data-aos-delay="100">
                <span class="text-xs bg-blue-600 rounded-full text-white px-4 py-1.5 me-3">New</span>
                <span class="text-sm font-medium">Introducing SITS Online Programs! See what's new</span>
                <svg class="w-2.5 h-2.5 ms-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 6 10">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m1 9 4-4-4-4" />
                </svg>
            </a>
            <!-- Main Heading -->
            <h1 class="mb-4 text-4xl font-extrabold tracking-tight leading-none text-gray-900 md:text-5xl lg:text-6xl dark:text-white"
                >
                Transforming Lives Through Theological Education
            </h1>
            <!-- Supporting Text -->
            <p class="mb-8 text-lg font-normal text-gray-500 lg:text-xl sm:px-16 lg:px-48 dark:text-gray-200"
                >
                At SITS, we empower individuals by delivering accessible, sustainable, and innovative theological education.
                Join
                us in equipping leaders to impact their communities and the world for Christ.
            </p>
            <!-- Sign-up Form -->
            <form class="w-full max-w-md mx-auto" data-aos="zoom-in" data-aos-delay="400">
                <label for="default-email" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Email
                    sign-up</label>
                <div class="relative">
                    <div class="absolute inset-y-0 rtl:inset-x-0 start-0 flex items-center ps-3.5 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 16">
                            <path
                                d="m10.036 8.278 9.258-7.79A1.979 1.979 0 0 0 18 0H2A1.987 1.987 0 0 0 .641.541l9.395 7.737Z" />
                            <path
                                d="M11.241 9.817c-.36.275-.801.425-1.255.427-.428 0-.845-.138-1.187-.395L0 2.6V14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V2.5l-8.759 7.317Z" />
                        </svg>
                    </div>
                    <input type="email" id="default-email"
                        class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-white focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="Enter your email here..." required />
                    <button type="submit"
                        class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Sign up
                    </button>
                </div>
            </form>
        </div>
    </section>
    <!-- Announcement Section End -->

    {{-- Testimonials --}}

    @include('contacts.testimonials')

@endsection
