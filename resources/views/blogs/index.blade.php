@extends('layouts.app')

@section('main_content')
    <!-- Home Banner Area -->
    <section class="bg-gray-900 py-16 mt-[170px]" data-aos="zoom-in" data-aos-delay="100">
        <div class="container mx-auto px-6 text-center">
            <div class="text-white">
                <h2 class="text-4xl font-bold mb-4">Blogs</h2>
                <div class="flex justify-center space-x-4 text-gray-400">
                    <a href="{{ url('/') }}" class="hover:text-white transition">Home</a>
                    <span>/</span>
                    <a href="{{ route('blogs.index') }}" class="hover:text-white transition">Blogs</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Blog Area -->
    <section class="bg-gray-900 py-16">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                <!-- Blog Left Sidebar -->
                <div class="lg:col-span-8">
                    <div class="space-y-12">
                        @foreach ($blogs as $blog)
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-6 items-start" data-aos="zoom-in"
                                data-aos-delay="200">
                                <!-- Blog Info -->
                                <div class="sm:col-span-1">
                                    <div class="text-gray-400 space-y-2">
                                        <div>
                                            <a href="#" class="text-sm hover:text-yellow-400">Food</a>,
                                            <a href="#" class="text-sm hover:text-yellow-400">Technology</a>,
                                            <a href="#" class="text-sm hover:text-yellow-400">Politics</a>,
                                            <a href="#" class="text-sm hover:text-yellow-400">Lifestyle</a>
                                        </div>
                                        <ul class="text-sm space-y-2">
                                            <li><i class="ti-user text-yellow-400"></i> Mark Wiens</li>
                                            <li><i class="ti-calendar text-yellow-400"></i> 12 Dec, 2017</li>
                                            <li><i class="ti-eye text-yellow-400"></i> 1.2M Views</li>
                                            <li><i class="ti-comment text-yellow-400"></i> 06 Comments</li>
                                        </ul>
                                    </div>
                                </div>
                                <!-- Blog Content -->
                                <div class="sm:col-span-3">
                                    <div
                                        class="bg-gray-800 p-6 rounded-lg shadow-lg hover:shadow-xl transition transform hover:-translate-y-2">
                                        <img src="{{ asset('storage/' . $blog->image) }}" alt="{{ $blog->title }}"
                                            class="w-full h-52 object-cover rounded-lg mb-4" />
                                        <h3 class="text-xl font-bold text-white hover:text-blue-400">
                                            <a href="{{ route('blogs.show', $blog) }}">
                                                {{ $blog->title }}
                                            </a>
                                        </h3>
                                        <p class="text-gray-400 text-sm mt-2">
                                            {{ Str::limit('MCSE boot camps have its supporters and its detractors. Some people do not understand why you should have to spend money on boot camp when you can get the MCSE study materials yourself at a fraction.', 120) }}
                                        </p>
                                        <a href="{{ route('blogs.show', $blog) }}"
                                            class="mt-4 inline-block text-blue-500 hover:text-blue-600 font-bold text-sm">
                                            View More
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-12">
                        {{ $blogs->links() }}
                    </div>
                </div>

                <!-- Blog Right Sidebar -->
                <div class="lg:col-span-4">
                    <div class="space-y-12">
                        <!-- Search Widget -->
                        <div class="bg-gray-800 p-6 rounded-lg shadow-lg" data-aos="bounce" data-aos-delay="300">
                            <h4 class="text-lg font-bold text-white mb-4">Search Posts</h4>
                            <div class="flex">
                                <input type="text"
                                    class="w-full bg-transparent border border-gray-700 text-gray-400 px-3 py-2 rounded-lg focus:outline-none focus:border-blue-500"
                                    placeholder="Search Posts" />
                                <button class="ml-3 bg-yellow-500 hover:bg-yellow-600 text-gray-900 px-4 py-2 rounded-lg">
                                    <i class="ti-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Popular Posts Widget -->
                        <div class="bg-gray-800 p-6 rounded-lg shadow-lg" data-aos="zoom-in" data-aos-delay="400">
                            <h4 class="text-lg font-bold text-white mb-4">Popular Posts</h4>
                            <div class="space-y-6">
                                @foreach ($blogs as $blog)
                                    <div class="flex space-x-4 items-center">
                                        <img src="{{ asset('storage/' . $blog->image) }}" alt="{{ $blog->title }}"
                                            class="w-16 h-16 rounded-lg object-cover" />
                                        <div>
                                            <h5 class="text-md font-bold text-white hover:text-blue-400">
                                                <a href="{{ route('blogs.show', $blog) }}">{{ $blog->title }}</a>
                                            </h5>
                                            <p class="text-gray-400 text-sm">{{ $blog->created_at->format('d-m-Y') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
