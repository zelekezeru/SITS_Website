<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
        <script src="https://cdn.tailwindcss.com"></script>

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

            /* Glassmorphism Card */
            .glass-card {
                background: rgba(15, 23, 42, 0.45);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.05);
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
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
    <body class="bg-[#090d16] text-slate-300 font-jakarta min-h-screen relative overflow-x-hidden flex flex-col justify-center py-12">
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
