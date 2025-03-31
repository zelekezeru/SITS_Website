<!--================ Start Testimonial Area =================-->
<div class="testimonial_area mt-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="main_title text-center" data-aos="fade-up" data-aos-delay="200">
                    <h2 class="mb-3">Some Pictures at SITS</h2>
                    <p class="text-muted">Explore our gallery showcasing memorable moments at SITS.</p>
                </div>
            </div>
        </div>

        <div class="row g-4" data-aos="fade-right" data-aos-delay="200">
            <!-- Carousel -->
            <div id="galleryCarousel" class="owl-carousel owl-theme">
                @foreach ($galleries as $gallery)
                    <div class="item text-center">
                        <img src="{{ asset('storage/' . $gallery->image) }}" alt="{{ $gallery->description }}"
                            class="img-fluid rounded shadow gallery-image"
                            style="width: 100%; height: 250px; object-fit: cover;" data-bs-toggle="modal"
                            data-bs-target="#galleryModal" data-image="{{ asset('storage/' . $gallery->image) }}"
                            data-description="{{ $gallery->description }}" />
                        <p class="mt-2 text-muted">{{ $gallery->description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="background-color: rgba(0, 0, 0, 0.8); border-radius: 15px; color: white;">
                <div class="modal-header border-0">
                    <!-- Modal Title -->
                    <h5 class="modal-title" id="galleryModalLabel" style="color: white;">Image Description</h5>
                    <!-- Visible Close Button -->
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="" class="img-fluid rounded shadow"
                        style="max-height: 400px; object-fit: contain;" />
                </div>
            </div>
        </div>
    </div>

</div>
<!--================ End Testimonial Area =================-->
