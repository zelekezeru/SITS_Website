<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="background-color: #090d16;">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SITS') }} — Portal Hub</title>

        <!-- Fonts & Icons -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/themify-icons.css') }}">
        <!-- Tailwind CSS (Vite compiled) -->
        @vite(['resources/css/app.css'])

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
                top: -5%;
                right: -5%;
            }
            .glow-blob-2 {
                background: radial-gradient(circle, #f59e0b 0%, #ef4444 100%);
                width: 600px;
                height: 600px;
                bottom: -10%;
                left: -10%;
                animation-delay: -5s;
                animation-duration: 25s;
            }

            @keyframes float-blob {
                0% { transform: translate(0px, 0px) scale(1); }
                50% { transform: translate(60px, -40px) scale(1.15); }
                100% { transform: translate(-40px, 50px) scale(0.9); }
            }

            /* Default dark theme overrides to prevent FOUC white flash */
            html, body {
                background-color: #090d16 !important;
                color: #cbd5e1 !important;
            }
            .glass-card {
                background: #0f172a !important;
                background: rgba(15, 23, 42, 0.45) !important;
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.05) !important;
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
                /* Prevent hardware acceleration paint blink on backdrop-filter repaints */
                transform: translate3d(0, 0, 0);
                backface-visibility: hidden;
            }
            input, select, textarea {
                background-color: #0f172a !important;
                color: #ffffff !important;
                border: 1px solid #1e293b !important;
            }
            /* Autocomplete/Autofill styles to prevent bright yellow/white flash */
            input:-webkit-autofill,
            input:-webkit-autofill:hover, 
            input:-webkit-autofill:focus, 
            input:-webkit-autofill:active {
                -webkit-box-shadow: 0 0 0 30px #090d16 inset !important;
                -webkit-text-fill-color: #ffffff !important;
                transition: background-color 5000s ease-in-out 0s;
            }
            label {
                color: #94a3b8 !important;
            }
            button[type="submit"] {
                background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%) !important;
                color: #ffffff !important;
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

            /* Text Gradients */
            .text-gradient {
                background: linear-gradient(135deg, #ffffff 30%, #a5b4fc 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
        </style>
    </head>
    <body style="background-color: #090d16; color: #cbd5e1;" class="bg-[#090d16] text-slate-300 font-jakarta min-h-screen relative overflow-x-hidden flex flex-col justify-center py-12">
        <!-- Background elements -->
        <div class="glow-blob glow-blob-1"></div>
        <div class="glow-blob glow-blob-2"></div>
        <div class="absolute inset-0 grid-overlay pointer-events-none"></div>

        <!-- Slot Content -->
        <div class="relative z-10 w-full max-w-5xl mx-auto px-6">
            {{ $slot }}
        </div>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </body>
</html>
