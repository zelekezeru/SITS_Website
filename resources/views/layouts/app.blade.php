<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png" />
        <title>@yield('title', 'SITS Ethiopia')</title>
        
        <link rel="stylesheet" href="{{ asset('css/aos.css') }}">
        <link rel="stylesheet" href="{{ asset('css/bootstrap.css') }}">
        <link rel="stylesheet" href="{{ asset('css/flaticon.css') }}">
        <link rel="stylesheet" href="{{ asset('css/themify-icons.css') }}">
        <link rel="stylesheet" href="{{ asset('css/ti-tiktok.css') }}">
        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
    <link rel="stylesheet" href="{{ asset('vendors/owl-carousel/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendors/nice-select/css/nice-select.css') }}">
    <!-- main css -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>

    @include('layouts.nav')

    @yield('main_content')

    @include('layouts.footer')

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        
    <script>
        function confirmDelete(ItemId) {
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
    <script src="{{ asset('js/aos.js') }}"></script>
    <script src="{{ asset('js/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('js/popper.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('vendors/nice-select/js/jquery.nice-select.min.js') }}"></script>
    <script src="{{ asset('vendors/owl-carousel/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('js/owl-carousel-thumb.min.js') }}"></script>
    <script src="{{ asset('js/jquery.ajaxchimp.min.js') }}"></script>
    <script src="{{ asset('js/mail-script.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    
    <!--gmaps Js-->
    @yield('maps')
    <script>
        AOS.init();

    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Owl Carousel with smooth transition
        $('#galleryCarousel').owlCarousel({
            items: 3, // Show 3 images at a time
            loop: true,
            margin: 20,
            nav: true,
            dots: true,
            autoplay: true,
            autoplayTimeout: 3000,
            autoplayHoverPause: true,
            smartSpeed: 800 // Smooth transition speed
        });
    });
        document.addEventListener('DOMContentLoaded', function () {
        // Select all images that open the modal
        document.querySelectorAll('.gallery-image').forEach(function (image) {
            image.addEventListener('click', function () {
                // Get the modal elements
                const modalImage = document.getElementById('modalImage');
                const modalTitle = document.getElementById('galleryModalLabel');

                // Update the modal content with the clicked image's attributes
                modalImage.src = this.getAttribute('data-image');
                modalImage.alt = this.getAttribute('data-description');
                modalTitle.textContent = this.getAttribute('data-description');

                // Show the modal (in case Bootstrap's auto binding doesn't work)
                const modal = new bootstrap.Modal(document.getElementById('galleryModal'));
                modal.show();
            });
        });
    });
</script>

       <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/gmaps.min.js') }}"></script>
    <script src="{{ asset('js/theme.js') }}"></script>
</body>

</html>
