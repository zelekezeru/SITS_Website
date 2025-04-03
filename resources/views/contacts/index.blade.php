@extends('layouts.app')

@section('main_content')

    <!-- Home Banner Area -->
    <section class="bg-gray-900 py-16">
        <div class="container mx-auto px-6 text-center mt-[190px]" data-aos="fade-up" data-aos-delay="100">
            <h2 class="text-4xl font-bold text-white mb-4">Contact SITS</h2>
            <div class="flex justify-center space-x-4 text-gray-400">
                <a href="{{ url('/') }}" class="hover:text-white transition">Home</a>
                <span>/</span>
                <a href="{{ route('contacts.index') }}" class="hover:text-white transition">Contact</a>
            </div>
        </div>
    </section>

    <!-- Contact Area -->
    <section class="bg-gray-800 py-16">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12" data-aos="fade-up" data-aos-delay="200">
                <h3 class="text-2xl font-bold text-white mb-4">You can Send us a Message Here!</h3>
                <p class="text-gray-400">
                    We would like to hear from you, please send us your query and we will get back to you as soon as
                    possible.
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <!-- Contact Info -->
                <div class="space-y-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="bg-gray-900 p-6 rounded-lg shadow-lg"data-aos="fade-up" data-aos-delay="300">
                        <i class="ti-home text-blue-500 text-3xl mb-4"></i>
                        <h6 class="text-white font-bold mb-2">Hawassa, Ethiopia</h6>
                        <p class="text-gray-400">Welde Amanuel Avenue</p>
                    </div>
                    <div class="bg-gray-900 p-6 rounded-lg shadow-lg"data-aos="fade-up" data-aos-delay="300">
                        <i class="ti-headphone text-blue-500 text-3xl mb-4"></i>
                        <h6 class="text-white font-bold mb-2">Phone Numbers</h6>
                        <p class="text-gray-400">+251 (0)46 212 7821 — SITS Admin</p>
                        <p class="text-gray-400">+251 (0)46 212 9156 — Registrar</p>
                        <p class="text-gray-400">+251 (0)46 212 8708 — ODEL</p>
                    </div>
                    <div class="bg-gray-900 p-6 rounded-lg shadow-lg"data-aos="fade-up" data-aos-delay="300">
                        <i class="ti-email text-blue-500 text-3xl mb-4"></i>
                        <h6 class="text-white font-bold mb-2">Email Address</h6>
                        <p class="text-gray-400">
                            <a href="mailto:support@sits.com" class="hover:underline">support@sits.com</a>
                        </p>
                        <p class="text-gray-400">Send us your query anytime!</p>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="md:col-span-2" data-aos="fade-up" data-aos-delay="300">
                    <form class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-gray-900 p-4 rounded-lg shadow-lg"
                        action="{{ route('contacts.store') }}" method="POST">
                        @csrf
                        <div>
                            <label for="name" class="block text-gray-400 text-sm mb-2">Your Name</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}"
                                class="w-full bg-gray-800 text-gray-300 border border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                                placeholder="Enter your name" required />
                        </div>
                        <div>
                            <label for="email" class="block text-gray-400 text-sm mb-2">Your Email</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                class="w-full bg-gray-800 text-gray-300 border border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                                placeholder="Enter your email address" required />
                        </div>
                        <div>
                            <label for="phone" class="block text-gray-400 text-sm mb-2">Your Phone</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                                class="w-full bg-gray-800 text-gray-300 border border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                                placeholder="Enter your phone number" required />
                        </div>
                        <div>
                            <label for="title" class="block text-gray-400 text-sm mb-2">Subject</label>
                            <input type="text" id="title" name="title" value="{{ old('title') }}"
                                class="w-full bg-gray-800 text-gray-300 border border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                                placeholder="Enter the subject" required />
                        </div>
                        <div class="sm:col-span-2">
                            <label for="message" class="block text-gray-400 text-sm mb-2">Your Message</label>
                            <textarea id="message" name="message" rows="4"
                                class="w-full bg-gray-800 text-gray-300 border border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                                placeholder="Enter your message" required>{{ old('message') }}</textarea>
                        </div>
                        <div class="sm:col-span-2 text-right">
                            <button type="submit"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded-lg shadow transition">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
