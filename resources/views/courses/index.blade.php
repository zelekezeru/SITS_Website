@extends('layouts.app')

@section('main_content')
    <!-- Hero Banner Area -->
    <section class="relative py-24 pt-36 pb-16 overflow-hidden" data-aos="fade-down">
        <div class="container mx-auto px-6 text-center relative z-10">
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-6">
                Academic Catalog
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold text-white font-outfit mb-4">Courses</h1>
            <div class="flex justify-center items-center space-x-3 text-sm text-slate-500">
                <a href="{{ url('/') }}" class="hover:text-white transition">Home</a>
                <span>/</span>
                <span class="text-slate-300">Courses</span>
            </div>
        </div>
    </section>

    <!-- Popular Courses Area -->
    <div class="py-20 relative">
        <div class="container mx-auto px-6">
            <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
                <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-4">
                    Explore Programs
                </div>
                <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">Our Popular Courses</h2>
                <p class="text-slate-400">
                    SITS offers a comprehensive theological curriculum to prepare you for ministry leadership.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($courses as $course)
                    <div class="glass-card glass-card-hover rounded-2xl overflow-hidden h-full flex flex-col justify-between border border-white/5"
                        data-aos="zoom-in" data-aos-delay="100">
                        
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
                                    <span class="text-xs text-slate-400 font-medium">{{ $course->instructor ?? 'Field Experts' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Registration Area -->
    <div class="py-24 bg-slate-950/40 border-t border-slate-900/60">
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
@endsection
