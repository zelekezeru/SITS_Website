@extends('layouts.app')

@section('main_content')

    <!-- Hero Banner Area -->
    <section class="relative py-24 pt-36 pb-16 overflow-hidden" data-aos="fade-down">
        <div class="container mx-auto px-6 text-center relative z-10">
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-6">
                Get in Touch
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold text-white font-outfit mb-4">Contact SITS</h1>
            <div class="flex justify-center items-center space-x-3 text-sm text-slate-500">
                <a href="{{ url('/') }}" class="hover:text-white transition">Home</a>
                <span>/</span>
                <span class="text-slate-300">Contact</span>
            </div>
        </div>
    </section>

    <!-- Contact Area -->
    <section class="py-20 relative">
        <div class="container mx-auto px-6">
            <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
                <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-4">
                    Send a Message
                </div>
                <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">You Can Send Us a Message Here!</h2>
                <p class="text-slate-400">
                    We would love to hear from you. Please send us your query and we will get back to you as soon as possible.
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                <!-- Contact Info -->
                <div class="lg:col-span-4 space-y-6" data-aos="fade-right">
                    <div class="glass-card p-6 rounded-2xl border border-white/5 flex gap-4 items-start">
                        <div class="w-12 h-12 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-400 shrink-0 border border-indigo-500/15">
                            <i class="ti-home text-lg"></i>
                        </div>
                        <div>
                            <h5 class="text-white font-bold font-outfit mb-1">Hawassa, Ethiopia</h5>
                            <p class="text-xs text-slate-450">Welde Amanuel Avenue, SITS Campus</p>
                        </div>
                    </div>
                    
                    <div class="glass-card p-6 rounded-2xl border border-white/5 flex gap-4 items-start">
                        <div class="w-12 h-12 bg-amber-500/10 rounded-xl flex items-center justify-center text-amber-400 shrink-0 border border-amber-500/15">
                            <i class="ti-headphone text-lg"></i>
                        </div>
                        <div>
                            <h5 class="text-white font-bold font-outfit mb-2">Phone Numbers</h5>
                            <div class="space-y-1 text-xs text-slate-450">
                                <p><strong class="text-slate-300">Admin Office:</strong> +251 (0)46 212 7821</p>
                                <p><strong class="text-slate-300">Registrar:</strong> +251 (0)46 212 9156</p>
                                <p><strong class="text-slate-300">ODEL Dept:</strong> +251 (0)46 212 8708</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card p-6 rounded-2xl border border-white/5 flex gap-4 items-start">
                        <div class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-400 shrink-0 border border-purple-500/15">
                            <i class="ti-email text-lg"></i>
                        </div>
                        <div>
                            <h5 class="text-white font-bold font-outfit mb-1">Email Address</h5>
                            <p class="text-xs text-slate-450 mb-2">
                                <a href="mailto:support@sits.com" class="text-indigo-400 hover:underline">support@sits.com</a>
                            </p>
                            <p class="text-[10px] text-slate-500">Send us your query anytime!</p>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="lg:col-span-8" data-aos="fade-left" data-aos-delay="100">
                    <div class="relative">
                        <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-indigo-500 to-purple-600 opacity-15 blur-xl"></div>
                        <form class="relative grid grid-cols-1 sm:grid-cols-2 gap-5 bg-slate-900/65 p-8 rounded-3xl border border-white/5"
                            action="{{ route('contacts.store') }}" method="POST">
                            @csrf
                            <div>
                                <label for="name" class="block text-xs font-semibold text-slate-450 uppercase tracking-wider mb-2">Your Name</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}"
                                    class="w-full bg-slate-950 border border-slate-800 text-white placeholder-slate-550 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow"
                                    placeholder="Abebe Kebede" required />
                            </div>
                            <div>
                                <label for="email" class="block text-xs font-semibold text-slate-455 uppercase tracking-wider mb-2">Your Email</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}"
                                    class="w-full bg-slate-950 border border-slate-800 text-white placeholder-slate-555 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow"
                                    placeholder="abebe@example.com" required />
                            </div>
                            <div>
                                <label for="phone" class="block text-xs font-semibold text-slate-455 uppercase tracking-wider mb-2">Your Phone</label>
                                <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                                    class="w-full bg-slate-950 border border-slate-800 text-white placeholder-slate-555 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow"
                                    placeholder="+251 911..." required />
                            </div>
                            <div>
                                <label for="title" class="block text-xs font-semibold text-slate-455 uppercase tracking-wider mb-2">Subject</label>
                                <input type="text" id="title" name="title" value="{{ old('title') }}"
                                    class="w-full bg-slate-950 border border-slate-800 text-white placeholder-slate-555 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow"
                                    placeholder="Query / Subject" required />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="message" class="block text-xs font-semibold text-slate-455 uppercase tracking-wider mb-2">Your Message</label>
                                <textarea id="message" name="message" rows="5"
                                    class="w-full bg-slate-950 border border-slate-800 text-white placeholder-slate-555 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow"
                                    placeholder="Enter your message details here..." required>{{ old('message') }}</textarea>
                            </div>
                            <div class="sm:col-span-2 text-right">
                                <button type="submit"
                                    class="btn-glow bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold py-3.5 px-8 rounded-xl shadow-lg shadow-indigo-600/20 transition duration-300">
                                    Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
