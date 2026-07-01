<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png" />
    <title>@yield('title', 'SITS Ethiopia')</title>

    <!-- DNS prefetch for external resources -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Critical CSS first -->
    <link rel="stylesheet" href="{{ asset('css/flaticon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/themify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('vendors/owl-carousel/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendors/nice-select/css/nice-select.css') }}">
    <link rel="stylesheet" href="{{ asset('css/aos.css') }}">
    <!-- Vite Pre-Compiled Tailwind CSS (CSS only — app.js is Inertia/ERP only) -->
    @vite(['resources/css/app.css'])
    <!-- main css -->
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <!-- Animate.css (non-blocking) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"></noscript>

    <!-- Google Fonts (single load) -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }
        .font-jakarta {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* Ambient glowing background blobs */
        .glow-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.15;
            z-index: 0;
            pointer-events: none;
            animation: float-blob 20s infinite alternate;
        }
        .glow-blob-1 {
            background: radial-gradient(circle, #4f46e5 0%, #06b6d4 100%);
            width: 500px;
            height: 500px;
            top: 5%;
            right: -5%;
        }
        .glow-blob-2 {
            background: radial-gradient(circle, #f59e0b 0%, #ef4444 100%);
            width: 600px;
            height: 600px;
            bottom: 10%;
            left: -10%;
            animation-delay: -5s;
            animation-duration: 25s;
        }
        .glow-blob-3 {
            background: radial-gradient(circle, #a855f7 0%, #6366f1 100%);
            width: 400px;
            height: 400px;
            top: 45%;
            right: 10%;
            animation-delay: -10s;
            animation-duration: 18s;
        }

        @keyframes float-blob {
            0% { transform: translate(0px, 0px) scale(1); }
            50% { transform: translate(60px, -40px) scale(1.15); }
            100% { transform: translate(-40px, 50px) scale(0.9); }
        }

        /* Glassmorphism Cards */
        .glass-card {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }
        .glass-card-hover {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .glass-card-hover:hover {
            transform: translateY(-6px);
            background: rgba(15, 23, 42, 0.6);
            border-color: rgba(99, 102, 241, 0.25);
            box-shadow: 0 30px 60px rgba(99, 102, 241, 0.15);
        }

        /* Text Gradients */
        .text-gradient {
            background: linear-gradient(135deg, #ffffff 30%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .text-gradient-accent {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Grid overlay */
        .grid-overlay {
            background-size: 40px 40px;
            background-image: linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
        }

        /* Button Glow */
        .btn-glow {
            position: relative;
            overflow: hidden;
        }
        .btn-glow::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.6s ease;
        }
        .btn-glow:hover::after {
            left: 100%;
        }

        /* Input focus glow */
        .input-glow:focus {
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
        }

        /* Slider Controls */
        .slider-btn {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .slider-btn:hover {
            background: #4f46e5;
            border-color: #6366f1;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.4);
        }
    </style>
</head>

<body class="bg-[#090d16] text-slate-300 font-jakarta min-h-screen relative overflow-x-hidden">
    <!-- Global Background Elements -->
    <div class="glow-blob glow-blob-1"></div>
    <div class="glow-blob glow-blob-2"></div>
    <div class="glow-blob glow-blob-3"></div>
    <div class="absolute inset-0 grid-overlay pointer-events-none"></div>

    @include('layouts.nav')

    @yield('main_content')

    @include('layouts.footer')

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->

    <script>
        function confirmDelete(ItemId) {
            if (typeof Swal === 'undefined') { return; }
            Swal.fire({
                title: "Are you sure?",
                text: "Once deleted, this Item cannot be recovered!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + ItemId).submit();
                }
            });
        }
    </script>
    <script src="{{ asset('js/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('js/popper.js') }}" defer></script>
    <script src="{{ asset('js/bootstrap.min.js') }}" defer></script>
    <script src="{{ asset('vendors/nice-select/js/jquery.nice-select.min.js') }}" defer></script>
    <script src="{{ asset('vendors/owl-carousel/owl.carousel.min.js') }}" defer></script>
    <script src="{{ asset('js/owl-carousel-thumb.min.js') }}" defer></script>
    <script src="{{ asset('js/jquery.ajaxchimp.min.js') }}" defer></script>
    <script src="{{ asset('js/mail-script.js') }}" defer></script>
    <script src="{{ asset('js/aos.js') }}" defer></script>

    {{-- Google Maps: only About page sections this --}}
    @yield('maps')
    {{-- gmaps helper only needed when maps are present --}}
    @hasSection('maps')
        <script src="{{ asset('js/gmaps.min.js') }}" defer></script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // AOS — deferred scripts run before DOMContentLoaded, so AOS is available here
            if (typeof AOS !== 'undefined') { AOS.init(); }

            // Gallery image modal
            document.querySelectorAll('.gallery-image').forEach(function(image) {
                image.addEventListener('click', function() {
                    const modalImage = document.getElementById('modalImage');
                    const modalTitle = document.getElementById('galleryModalLabel');
                    if (!modalImage || !modalTitle) return;
                    modalImage.src = this.getAttribute('data-image');
                    modalImage.alt = this.getAttribute('data-description');
                    modalTitle.textContent = this.getAttribute('data-description');
                    const modal = new bootstrap.Modal(document.getElementById('galleryModal'));
                    modal.show();
                });
            });

            // jQuery-dependent carousels (jQuery is deferred but loaded before DOMContentLoaded)
            if (typeof $ !== 'undefined') {
                // Gallery carousel (About page)
                if ($('#galleryCarousel').length) {
                    $('#galleryCarousel').owlCarousel({
                        items: 3,
                        loop: true,
                        margin: 20,
                        nav: true,
                        dots: true,
                        autoplay: true,
                        autoplayTimeout: 3000,
                        autoplayHoverPause: true,
                        smartSpeed: 800
                    });
                }

                // Active courses carousel (Home page)
                var owl = $(".active_course");
                if (owl.length) {
                    owl.owlCarousel({
                        loop: true,
                        margin: 30,
                        nav: false,
                        autoplay: true,
                        autoplayTimeout: 4000,
                        responsive: { 0: { items: 1 }, 768: { items: 2 }, 1024: { items: 3 } },
                    });
                    $(".prev").click(function() { owl.trigger("prev.owl.carousel"); });
                    $(".next").click(function() { owl.trigger("next.owl.carousel"); });
                }

                // Testimonials slider
                if ($(".testi_slider").length) {
                    $(".testi_slider").owlCarousel({
                        loop: true,
                        margin: 30,
                        nav: false,
                        autoplay: true,
                        autoplayTimeout: 5000,
                        responsive: { 0: { items: 1 }, 768: { items: 2 }, 1024: { items: 3 } },
                    });
                }
            }

            // Dropdown toggle
            const dropdownTrigger = document.getElementById('dropdownTrigger');
            const dropdownMenu = document.getElementById('dropdownMenu');
            if (dropdownTrigger && dropdownMenu) {
                dropdownTrigger.addEventListener('click', () => {
                    dropdownMenu.classList.toggle('hidden');
                });
                window.addEventListener('click', (e) => {
                    if (!dropdownTrigger.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.add('hidden');
                    }
                });
            }
        });
    </script>

    {{-- SweetAlert2: only load on pages that need it --}}
    @stack('sweetalert')

    {{-- Maps JS: only load when @yield('maps') has content --}}
    @stack('maps_js')

    <script src="{{ asset('js/theme.js') }}" defer></script>

    {{-- Page-specific scripts --}}
    @stack('scripts')
</body>

</html>
