<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Break out of any sandboxed iframe (e.g. Moodle OAuth2 SSO flow) --}}
        <script>try{if(window!==window.top){window.top.location.href=window.location.href;}}catch(e){}</script>

        <title>SITS ERP</title>

        <!-- Google Fonts Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

        <!-- AOS & Animate CSS -->
        <link rel="stylesheet" href="{{ asset('css/aos.css') }}">
        <link rel="stylesheet" href="{{ asset('css/animate.css') }}">

        <!-- Scripts and Styles -->
        @routes
        @vite(['resources/js/app.js', 'resources/css/app.css'])
        @inertiaHead
        <script src="{{ asset('js/aos.js') }}" defer></script>
    </head>
    <body style="background-color: #020617; color: #f8fafc;" class="font-sans antialiased text-slate-100 bg-slate-950 min-h-screen">
        @inertia
    </body>
</html>
