<section class="bg-gray-900 py-16">
    <div class="container mx-auto px-6">
        <!-- Header Section -->
        <div class="text-center mb-12" data-aos="fade-up" data-aos-delay="200">
            <h2 class="text-4xl font-bold text-white mb-4">Some Pictures at SITS</h2>
            <p class="text-gray-400">
                Explore our gallery showcasing memorable moments at SITS.
            </p>
        </div>

        <!-- Carousel Section -->
        <div class="grid grid-cols-1" data-aos="fade-right" data-aos-delay="300">
            <div class="owl-carousel testi_slider">
                @foreach ($galleries as $gallery)
                    <div class="item bg-gray-800 p-4 rounded-xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-2"
                        data-aos="zoom-in" data-aos-delay="400">
                        <div class="relative">
                            <img src="{{ asset('storage/' . $gallery->image) }}" alt="{{ $gallery->description }}"
                                class="w-full h-80 rounded-lg object-cover shadow" data-bs-toggle="modal"
                                data-bs-target="#galleryModal" data-image="{{ asset('storage/' . $gallery->image) }}"
                                data-description="{{ $gallery->description }}" />
                            <!-- Modified Gallery Description -->
                            <div
                                class="absolute bottom-0 left-0 bg-black bg-opacity-70 text-white w-full px-3 py-4 rounded-b-lg text-center text-sm shadow-lg">
                                {{ $gallery->description }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
