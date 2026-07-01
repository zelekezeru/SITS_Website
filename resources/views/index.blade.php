@extends('layouts.app')

@section('title', 'SITS Ethiopia — Shiloh International Theological Seminary')


@section('main_content')
    <div class="relative bg-[#090d16] overflow-hidden min-h-screen font-jakarta text-slate-300">

        <!--================ Start Home Banner Area =================-->
        <div class="relative min-h-screen flex items-center justify-center pt-24 pb-12">
            <div class="container mx-auto px-6 z-10 grid lg:grid-cols-12 gap-12 items-center">
                <!-- Left Content -->
                <div class="lg:col-span-7 space-y-8 text-left" data-aos="fade-right" data-aos-duration="1000">
                    <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide">
                        <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                        Convenient Theological Education
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight font-outfit">
                        Theological Education <br>
                        <span class="text-gradient">In a Digital & Modern Way</span>
                    </h1>

                    <p class="text-lg text-slate-400 max-w-xl leading-relaxed">
                        Shiloh International Theological Seminary (SITS) has been empowering leaders and transforming communities since <span class="text-gradient-accent font-bold">1994 G.C.</span> Access flexible, high-quality Christian education anywhere, anytime.
                    </p>

                    <div class="flex flex-wrap gap-4 pt-4">
                        <a href="{{ route('abouts.index') }}"
                           class="btn-glow px-8 py-4 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold rounded-xl shadow-lg shadow-indigo-600/25 transition duration-300 flex items-center gap-2">
                            <span>Learn More</span>
                            <i class="ti-arrow-right text-xs"></i>
                        </a>
                        <a href="{{ route('courses.index') }}"
                           class="px-8 py-4 bg-slate-900/60 hover:bg-slate-800/80 text-white font-semibold rounded-xl border border-slate-800 hover:border-slate-700 transition duration-300">
                            See Courses
                        </a>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-3 gap-6 pt-8 border-t border-slate-900">
                        <div>
                            <span class="block text-3xl font-extrabold text-white font-outfit">30+</span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Years of Grace</span>
                        </div>
                        <div>
                            <span class="block text-3xl font-extrabold text-white font-outfit">15K+</span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Graduates</span>
                        </div>
                        <div>
                            <span class="block text-3xl font-extrabold text-white font-outfit">100%</span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Digital & Flexible</span>
                        </div>
                    </div>
                </div>

                <!-- Right Interactive Card -->
                <div class="lg:col-span-5 relative" data-aos="fade-left" data-aos-duration="1000">
                    <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-indigo-500 to-pink-500 opacity-20 blur-xl"></div>
                    <div class="relative glass-card rounded-3xl p-8 border border-white/5 space-y-6">
                        <div class="aspect-video w-full rounded-2xl overflow-hidden bg-slate-950 relative group border border-white/5">
                            <img src="{{ asset('img/banner/header.webp') }}" alt="SITS Learning Portal" width="1100" height="733" fetchpriority="high" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition duration-700" />
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent flex items-end p-6">
                                <div>
                                    <span class="text-xs text-indigo-400 font-bold uppercase tracking-wider">Next-Gen Portal</span>
                                    <h3 class="text-lg font-bold text-white font-outfit">Empowering Ministers Worldwide</h3>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-900/60 border border-white/5">
                                <div class="w-10 h-10 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                                    <i class="ti-desktop text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-white">LMS Portal Access</h4>
                                    <p class="text-xs text-slate-500">Structured online classrooms and study resources</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-900/60 border border-white/5">
                                <div class="w-10 h-10 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-400">
                                    <i class="ti-book text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-white">Accredited Programs</h4>
                                    <p class="text-xs text-slate-500">Undergraduate, Graduate & Continuing Education</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--================ End Home Banner Area =================-->

        <!--================ Start Feature Area =================-->
        <div class="relative py-24 bg-slate-950/40 border-y border-slate-900/60">
            @include('abouts.values')
        </div>
        <!--================ End Feature Area =================-->

        <!--================ Start Popular Courses Area =================-->
        <div class="relative py-24">
            <div class="container mx-auto px-6">
                <!-- Heading -->
                <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
                    <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-4">
                        Explore Academics
                    </div>
                    <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">Our Featured Courses</h2>
                    <p class="text-slate-400">
                        Our popular courses offer cutting-edge content, practical ministry skills, and flexible learning options designed to equip you for excellent service.
                    </p>
                </div>

                <!-- Courses Grid / Carousel Container -->
                <div class="relative" data-aos="fade-up" data-aos-delay="200">
                    <div class="owl-carousel active_course">
                        @foreach ($courses as $course)
                            <div class="glass-card glass-card-hover rounded-2xl overflow-hidden h-full flex flex-col justify-between border border-white/5">
                                <!-- Course Banner -->
                                <div class="relative h-48 overflow-hidden bg-slate-950">
                                    @if($course->banner)
                                        <img class="w-full h-full object-cover hover:scale-105 transition duration-500" src="{{ asset('storage/' . $course->banner) }}" alt="{{ $course->title }}" loading="lazy" />
                                    @else
                                        <div class="w-full h-full bg-gradient-to-br from-slate-900 to-indigo-950/60 flex items-center justify-center text-slate-700">
                                            <i class="ti-book text-4xl"></i>
                                        </div>
                                    @endif
                                    <div class="absolute top-4 left-4">
                                        <span class="bg-indigo-500/90 backdrop-blur-md text-white text-xs px-3 py-1.5 rounded-lg font-bold tracking-wide uppercase">
                                            {{ $course->category }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Course Content -->
                                <div class="p-6 flex-1 flex flex-col justify-between">
                                    <div class="space-y-3">
                                        <h4 class="text-lg font-bold text-white font-outfit line-clamp-2 hover:text-indigo-400 transition">
                                            <a href="{{ route('courses.show', $course->id) }}">
                                                {{ $course->title }}
                                            </a>
                                        </h4>
                                        <p class="text-sm text-slate-400 line-clamp-3 leading-relaxed">
                                            {{ $course->description }}
                                        </p>
                                    </div>

                                    <div class="pt-6 mt-6 border-t border-slate-900/60 flex items-center justify-between">
                                        <div>
                                            <span class="block text-[10px] text-slate-500 uppercase font-bold tracking-wider">Tuition Fee</span>
                                            <span class="text-gradient-accent font-extrabold text-lg font-outfit">{{ number_format($course->amount_paid, 2) }} Br</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-slate-900 border border-white/5 flex items-center justify-center text-indigo-400">
                                                <i class="ti-user text-xs"></i>
                                            </div>
                                            <span class="text-xs text-slate-400 font-medium">Field Experts</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Custom Navigation Buttons -->
                    <div class="absolute top-1/2 -left-6 lg:-left-12 transform -translate-y-1/2 z-10">
                        <button class="prev slider-btn text-white rounded-full w-12 h-12 flex items-center justify-center transition">
                            <i class="ti-angle-left"></i>
                        </button>
                    </div>
                    <div class="absolute top-1/2 -right-6 lg:-right-12 transform -translate-y-1/2 z-10">
                        <button class="next slider-btn text-white rounded-full w-12 h-12 flex items-center justify-center transition">
                            <i class="ti-angle-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!--================ End Popular Courses Area =================-->

        <!--================ Start Registration Area =================-->
        <div class="relative py-24 bg-slate-950/40 border-y border-slate-900/60">
            <div class="container mx-auto px-6">
                <div class="grid lg:grid-cols-12 gap-12 items-center">
                    <!-- Left Content -->
                    <div class="lg:col-span-6 space-y-6" data-aos="fade-right">
                        <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs font-semibold tracking-wide">
                            Join SITS Today
                        </div>
                        <h2 class="text-3xl md:text-5xl font-bold text-white font-outfit leading-tight">
                            Ready to Begin <br>
                            <span class="text-gradient">Your Theological Journey?</span>
                        </h2>
                        <p class="text-slate-400 leading-relaxed">
                            Take the first step toward spiritual and academic growth. SITS provides a streamlined enrollment process to help you access customized courses, online resources, and expert guidance.
                        </p>

                        <div class="space-y-4 pt-4">
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-500/10 border border-indigo-500/25 flex items-center justify-center text-indigo-400 mt-1">
                                    <i class="ti-check text-xs"></i>
                                </div>
                                <p class="text-sm text-slate-300"><strong class="text-white">Fast Setup:</strong> Register and create your student account in minutes.</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-500/10 border border-indigo-500/25 flex items-center justify-center text-indigo-400 mt-1">
                                    <i class="ti-check text-xs"></i>
                                </div>
                                <p class="text-sm text-slate-300"><strong class="text-white">Structured Learning:</strong> Access digital textbooks, lecture notes, and assignments.</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-500/10 border border-indigo-500/25 flex items-center justify-center text-indigo-400 mt-1">
                                    <i class="ti-check text-xs"></i>
                                </div>
                                <p class="text-sm text-slate-300"><strong class="text-white">Expert Mentors:</strong> Regular interactive sessions with highly experienced faculty.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Enrollment Form -->
                    <div class="lg:col-span-6" data-aos="zoom-in" data-aos-delay="200">
                        <div class="relative">
                            <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-amber-500 to-orange-600 opacity-15 blur-xl"></div>
                            <div class="relative glass-card rounded-3xl p-8 border border-white/5">
                                <h3 class="text-2xl font-bold text-white font-outfit mb-2">Enrollment Registration</h3>
                                <p class="text-xs text-slate-400 mb-6">Submit this form to express your interest in SITS academic programs.</p>

                                <form action="{{ route('subscriptions.store') }}" method="POST" class="space-y-5">
                                    @csrf
                                    <input type="hidden" name="type" value="subscribe" />

                                    <div>
                                        <label for="reg-name" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Your Full Name</label>
                                        <input type="text" id="reg-name" name="name" required placeholder="Abebe Kebede"
                                               class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition input-glow" />
                                    </div>

                                    <div class="grid sm:grid-cols-2 gap-5">
                                        <div>
                                            <label for="reg-phone" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Phone Number</label>
                                            <input type="tel" id="reg-phone" name="phone" required placeholder="+251 911..."
                                                   class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition input-glow" />
                                        </div>
                                        <div>
                                            <label for="reg-email" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
                                            <input type="email" id="reg-email" name="email" required placeholder="abebe@example.com"
                                                   class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition input-glow" />
                                        </div>
                                    </div>

                                    <div>
                                        <label for="reg-address" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Living Address</label>
                                        <input type="text" id="reg-address" name="address" required placeholder="Addis Ababa, Ethiopia"
                                               class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition input-glow" />
                                    </div>

                                    <button type="submit"
                                            class="btn-glow w-full py-4 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-450 hover:to-orange-550 text-slate-950 font-bold rounded-xl shadow-lg shadow-amber-500/10 transition duration-300">
                                        Submit Registration
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--================ End Registration Area =================-->

        <!--================ Start Trainers Area =================-->
        <div class="relative py-24">
            <div class="container mx-auto px-6">
                <!-- Heading -->
                <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
                    <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-4">
                        Faculty Members
                    </div>
                    <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">Our Expert Trainers</h2>
                    <p class="text-slate-400">
                        Our expert trainers provide top-tier academic guidance, real-world ministry experience, and dedicated mentorship.
                    </p>
                </div>

                <!-- Trainers Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @if ($trainers->isEmpty())
                        <div class="col-span-full text-center py-12" data-aos="zoom-in">
                            <div class="w-16 h-16 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto text-slate-600 mb-4">
                                <i class="ti-user text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-bold text-white font-outfit">No Trainers Listed Yet</h3>
                            <p class="text-sm text-slate-500 mt-1">Check back later for updated faculty profiles.</p>
                        </div>
                    @else
                        @foreach ($trainers as $trainer)
                            <div class="glass-card glass-card-hover rounded-2xl overflow-hidden border border-white/5" data-aos="fade-up" data-aos-delay="100">
                                <div class="relative h-64 bg-slate-950 overflow-hidden group">
                                    @if($trainer->image)
                                        <img class="w-full h-full object-cover group-hover:scale-105 transition duration-500" src="{{ asset('storage/' . $trainer->image) }}" alt="{{ $trainer->name }}" loading="lazy" />
                                    @else
                                        <div class="w-full h-full bg-gradient-to-br from-slate-900 to-indigo-950/40 flex items-center justify-center text-slate-700">
                                            <i class="ti-user text-5xl"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-6 text-center">
                                    <h4 class="text-lg font-bold text-white font-outfit">{{ $trainer->name }}</h4>
                                    <p class="text-amber-500 text-xs font-semibold uppercase tracking-wider mt-1">{{ $trainer->position }}</p>
                                    <p class="text-sm text-slate-400 mt-3 line-clamp-3">
                                        {{ $trainer->description }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        <!--================ End Trainers Area =================-->

        <!--================ Start Events Area =================-->
        <div class="relative py-24 bg-slate-950/40 border-y border-slate-900/60">
            <div class="container mx-auto px-6">
                <!-- Heading -->
                <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
                    <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-4">
                        Institution Calendar
                    </div>
                    <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">Upcoming Events</h2>
                    <p class="text-slate-400">Keep track of our latest seminars, webinars, and academic conferences.</p>
                </div>

                <!-- Events Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" data-aos="fade-up" data-aos-delay="150">
                    @if ($events->isEmpty())
                        <div class="col-span-full text-center py-12">
                            <div class="w-16 h-16 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto text-slate-600 mb-4">
                                <i class="ti-calendar text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-bold text-white font-outfit">No Upcoming Events</h3>
                            <p class="text-sm text-slate-500 mt-1">We are planning our next events. Stay tuned!</p>
                        </div>
                    @else
                        @foreach ($events as $event)
                            <div class="glass-card glass-card-hover rounded-2xl overflow-hidden flex flex-col border border-white/5">
                                <div class="relative h-48 bg-slate-950">
                                    @if($event->banner)
                                        <img src="{{ asset('storage/' . $event->banner) }}" alt="{{ $event->title }}" class="w-full h-full object-cover" loading="lazy" />
                                    @else
                                        <div class="w-full h-full bg-gradient-to-br from-slate-900 to-indigo-950/40 flex items-center justify-center text-slate-700">
                                            <i class="ti-gallery text-4xl"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-6 flex-1 flex flex-col justify-between">
                                    <div class="flex items-start gap-4">
                                        <!-- Date Card -->
                                        <div class="text-center bg-indigo-600 text-white px-3 py-2.5 rounded-xl shadow-md flex flex-col justify-center min-w-[55px]">
                                            <span class="block text-xl font-extrabold leading-none font-outfit">{{ \Carbon\Carbon::parse($event->date)->format('d') }}</span>
                                            <span class="block text-[10px] uppercase font-bold tracking-wider mt-1">{{ \Carbon\Carbon::parse($event->date)->format('M') }}</span>
                                        </div>

                                        <div class="space-y-2">
                                            <h4 class="text-lg font-bold text-white font-outfit">{{ $event->title }}</h4>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                                <span class="flex items-center gap-1.5">
                                                    <i class="ti-time text-indigo-400"></i>
                                                    {{ \Carbon\Carbon::parse($event->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($event->end_time)->format('h:i A') }}
                                                </span>
                                                <span class="flex items-center gap-1.5">
                                                    <i class="ti-location-pin text-indigo-400"></i>
                                                    {{ $event->location }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-sm text-slate-400 mt-4 leading-relaxed line-clamp-3">
                                        {{ $event->description }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- View All Link -->
                <div class="text-center mt-12">
                    <a href="{{ route('events.index') }}"
                       class="inline-flex items-center gap-2 text-indigo-400 hover:text-indigo-300 font-semibold text-sm transition group">
                        <span>View All Events</span>
                        <i class="ti-arrow-right text-xs group-hover:translate-x-1 transition duration-300"></i>
                    </a>
                </div>
            </div>
        </div>
        <!--================ End Events Area =================-->

        <!--================ Start Announcement / Newsletter Area =================-->
        <div class="relative py-24 overflow-hidden">
            <div class="container mx-auto px-6 relative z-10">
                <div class="max-w-4xl mx-auto text-center space-y-8">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/25 text-indigo-300 text-xs font-semibold">
                        <span class="px-2 py-0.5 rounded-full bg-indigo-600 text-white font-bold text-[10px] mr-1.5">New</span>
                        Introducing SITS Online Programs!
                    </div>

                    <h2 class="text-4xl md:text-5xl font-extrabold text-white font-outfit leading-tight">
                        Transforming Lives Through <br>
                        <span class="text-gradient">Accessible Theological Education</span>
                    </h2>

                    <p class="text-slate-400 text-lg max-w-2xl mx-auto leading-relaxed">
                        At SITS, we empower individuals by delivering sustainable, accessible, and innovative theological education. Subscribe to our newsletter to receive updates on new programs and academic news.
                    </p>

                    <!-- Newsletter Form -->
                    <div class="max-w-md mx-auto">
                        <div class="relative">
                            <div class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-indigo-500 to-purple-600 opacity-15 blur-lg"></div>
                            <form action="{{ route('subscriptions.store') }}" method="POST" class="relative flex flex-col sm:flex-row gap-3 p-2 rounded-2xl bg-slate-900 border border-white/5">
                                @csrf
                                <input type="hidden" name="name" id="newsletter-name-field-home" value="Newsletter Subscriber" />
                                <input type="hidden" name="phone" value="N/A" />
                                <input type="hidden" name="address" value="N/A" />
                                <input type="hidden" name="type" value="newsletter" />

                                <input type="email" name="email" required placeholder="Enter your email address..."
                                       class="flex-1 px-4 py-3 rounded-xl bg-transparent text-white placeholder-slate-500 text-sm focus:outline-none"
                                       oninput="document.getElementById('newsletter-name-field-home').value = this.value.split('@')[0]" />
                                <button type="submit"
                                        class="btn-glow px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-sm transition duration-300">
                                    Subscribe
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--================ End Announcement / Newsletter Area =================-->

        <!--================ Start Testimonials Area =================-->
        <div class="relative py-24 bg-slate-950/40 border-t border-slate-900/60">
            @include('contacts.testimonials')
        </div>
        <!--================ End Testimonials Area =================-->
    </div>
@endsection
