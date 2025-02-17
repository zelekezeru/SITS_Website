@extends('layouts.app')

@section('main_content')

<div class="row" data-aos="fade-right" data-aos-delay="200">
    <div class="testi_slider owl-carousel">
        <div class="testi_item">
            <div class="row">
                
                @forelse ($galleries as $gallery)
                    <div class="col-lg-12 col-md-12">
                        <!-- Add zoom effect on hover -->
                        <div class="img-container">
                            <img src="{{ asset('storage/' . $gallery->image) }}" alt="Testimonial Image" class="img-fluid testi-img" style="max-width: 100%; height: auto;"/>
                        </div>

                        <div class="testi_text">
                            <h4>{{ $gallery->title }}</h4>
                            <p class="testi-desc">{{ $gallery->description }}</p>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="text-center">There are no galleries yet.</p>
                    </div>
                @endforelse


            </div>
        </div>
        <!-- Repeat for other testimonials -->
    </div>
</div>
@endsection
<!-- Add CSS for cool gallery effects -->
<style>
    .testi_slider .testi_item {
        position: relative;
        transition: transform 0.3s ease;
    }

    .testi_slider .testi_item:hover {
        transform: scale(1.05); /* Slight zoom effect */
    }

    .img-container {
        position: relative;
        overflow: hidden;
        border-radius: 10px;
    }

    .testi-img {
        transition: transform 0.4s ease-in-out;
        border-radius: 10px;
    }

    .img-container:hover .testi-img {
        transform: scale(1.1); /* Zoom effect on hover */
    }

    .testi_text {
        background-color: rgba(0, 0, 0, 0.6);
        color: white;
        padding: 20px;
        border-radius: 10px;
        position: absolute;
        bottom: 10px;
        left: 10px;
        width: calc(100% - 20px);
        text-align: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .testi_item:hover .testi_text {
        opacity: 1;
    }

    .testi_desc {
        font-size: 14px;
        font-style: italic;
        margin-top: 10px;
    }
</style>

<!-- Add JS for Owl Carousel if not already included -->
<script>
    $(document).ready(function() {
        $(".testi_slider").owlCarousel({
            items: 1,
            loop: true,
            margin: 10,
            autoplay: true,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            nav: false,
            dots: true,
            responsive: {
                0: {
                    items: 1
                },
                600: {
                    items: 2
                },
                1000: {
                    items: 3
                }
            }
        });
    });
</script>
