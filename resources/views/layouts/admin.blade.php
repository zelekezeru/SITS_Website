<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png" />
    <title>SITS Admin Dashboard</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    
    <!-- Tailwind CSS (Vite compiled) -->
    @vite(['resources/css/app.css'])
    
    <!-- Bootstrap 5 CSS (for legacy pages compatibility) -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}" />
    
    <style>
        /* Ambient glowing background blobs */
        .glow-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.12;
            z-index: 0;
            pointer-events: none;
        }
        .glow-blob-1 {
            background: radial-gradient(circle, #4f46e5 0%, #06b6d4 100%);
            width: 500px;
            height: 500px;
            top: -10%;
            right: -5%;
        }
        .glow-blob-2 {
            background: radial-gradient(circle, #f59e0b 0%, #ef4444 100%);
            width: 600px;
            height: 600px;
            bottom: -10%;
            left: -10%;
        }
        .glassmorphism {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        /* Dark overrides for Bootstrap elements (legacy compatibility) */
        body {
            background-color: #090d16 !important;
            color: #cbd5e1 !important;
        }
        .card {
            background: rgba(15, 23, 42, 0.45) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            color: #f8fafc !important;
            border-radius: 1rem !important;
        }
        .card-header {
            background: rgba(15, 23, 42, 0.6) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            color: #ffffff !important;
            font-weight: 700;
        }
        .table {
            color: #cbd5e1 !important;
            border-color: rgba(255, 255, 255, 0.05) !important;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(255, 255, 255, 0.01) !important;
            color: #cbd5e1 !important;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.04) !important;
            color: #ffffff !important;
        }
        .table td, .table th {
            border-color: rgba(255, 255, 255, 0.05) !important;
            padding: 1rem 0.75rem !important;
            vertical-align: middle !important;
        }
        .thead-dark th {
            background-color: rgba(15, 23, 42, 0.8) !important;
            color: #ffffff !important;
            border-bottom: 2px solid rgba(255, 255, 255, 0.08) !important;
        }
        .form-control, .form-select {
            background-color: rgba(15, 23, 42, 0.6) !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
            border-radius: 0.5rem !important;
        }
        .form-control:focus, .form-select:focus {
            background-color: rgba(15, 23, 42, 0.8) !important;
            color: #ffffff !important;
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25) !important;
        }
        label {
            color: #94a3b8 !important;
        }
    </style>
</head>
<body style="background-color: #090d16; color: #cbd5e1;" class="bg-[#090d16] text-slate-300 font-sans min-h-screen flex flex-col relative overflow-x-hidden selection:bg-indigo-500 selection:text-white">
    <!-- Glowing Background blobs -->
    <div class="glow-blob glow-blob-1"></div>
    <div class="glow-blob glow-blob-2"></div>

    <!-- Mobile Sidebar Backdrop -->
    <div id="adminSidebarBackdrop" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-40 hidden md:hidden"></div>

    <div class="flex flex-col md:flex-row min-h-screen">
        @if (Auth::check())
            <!-- Sidebar -->
            @include('layouts.admin.side-navigation')
        @endif

        <!-- Main Content Panel -->
        <div class="flex-1 flex flex-col min-h-screen z-10">
            @if (Auth::check())
                <!-- Topbar Header -->
                @include('layouts.admin.navigation')
            @endif

            <!-- Main Content Area -->
            <main class="flex-grow p-6 md:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(ItemId) {
            Swal.fire({
                title: "Are you sure?",
                text: "Once deleted, this Item cannot be recovered!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!",
                background: '#0f172a',
                color: '#f8fafc',
                customClass: {
                    popup: 'rounded-3xl border border-slate-800 shadow-2xl backdrop-blur-md',
                    confirmButton: 'rounded-xl px-5 py-2.5 text-sm font-semibold'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + ItemId).submit();
                }
            });
        }

        // Mobile Sidebar Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const adminMobileToggle = document.getElementById('adminMobileToggle');
            const adminSidebar = document.getElementById('adminSidebar');
            const adminSidebarClose = document.getElementById('adminSidebarClose');
            const adminSidebarBackdrop = document.getElementById('adminSidebarBackdrop');

            if (adminMobileToggle && adminSidebar) {
                function openSidebar() {
                    adminSidebar.classList.remove('-translate-x-full');
                    if (adminSidebarBackdrop) adminSidebarBackdrop.classList.remove('hidden');
                }

                function closeSidebar() {
                    adminSidebar.classList.add('-translate-x-full');
                    if (adminSidebarBackdrop) adminSidebarBackdrop.classList.add('hidden');
                }

                adminMobileToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    openSidebar();
                });

                if (adminSidebarClose) {
                    adminSidebarClose.addEventListener('click', closeSidebar);
                }

                if (adminSidebarBackdrop) {
                    adminSidebarBackdrop.addEventListener('click', closeSidebar);
                }
            }
        });
    </script>
</body>
</html>
