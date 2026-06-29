<nav class="fixed top-0 w-full bg-gray-800 py-4 shadow-lg z-50">
    <div class="container mx-auto flex justify-between items-center px-4">
        <!-- Logo -->
        <a href="/" class="flex items-center">
            <img src="/img/logo.png" alt="SITS Logo" class="h-10" />
        </a>

        <!-- Navigation Menu -->
        <ul class="hidden md:flex space-x-6 text-gray-300">
            <li><a href="{{ route('home') }}" class="hover:text-white">Home</a></li>
            <li><a href="{{ route('courses.index') }}" class="hover:text-white">Courses</a></li>
            <li><a href="{{ route('libraries.index') }}" class="hover:text-white">Libraries</a></li>
            <li><a href="{{ route('blogs.index') }}" class="hover:text-white">Blog</a></li>
            <li><a href="{{ route('abouts.index') }}" class="hover:text-white">About</a></li>
            <li><a href="{{ route('contacts.index') }}" class="hover:text-white">Contact</a></li>
        </ul>

        <!-- User Actions -->
        <div class="flex items-center space-x-4">
            @if (Auth::check())
            <div class="relative">
                <div id="dropdownTrigger" class="flex items-center cursor-pointer">
                    <img src="{{ auth()->user()->profile_image ? Storage::url(auth()->user()->profile_image) : '/img/user.png' }}" alt="Profile Image" class="h-10 w-10 rounded-full object-cover" />
                    <span class="ml-2 text-gray-300">{{ Auth::user()->name }}</span>
                </div>
                @php
                    $user = Auth::user();
                    $lmsLabel = 'LMS Portal';
                    if ($user->hasRole('STUDENT')) {
                        $lmsLabel = 'Go to Student Learning Portal';
                    } elseif ($user->hasRole('TRAINER')) {
                        $lmsLabel = 'Instructors Portal';
                    } elseif ($user->hasAnyRole(['STAFF', 'SUPERADMIN', 'ADMIN', 'EDITOR', 'LIBRARIAN'])) {
                        $lmsLabel = 'Staff Portal';
                    }
                @endphp
                <ul id="dropdownMenu" class="absolute hidden bg-gray-800 border border-gray-700/60 text-sm rounded-xl shadow-xl mt-2 right-0 w-64 py-1.5 z-50">
                    <li><a href="{{ route('portal') }}" class="block px-4 py-2 text-gray-200 hover:bg-gray-700/80 hover:text-white transition duration-150">Dashboard</a></li>
                    <li><a href="{{ route('users.show', Auth::user()->id) }}" class="block px-4 py-2 text-gray-200 hover:bg-gray-700/80 hover:text-white transition duration-150">View Profile</a></li>
                    <li class="border-t border-gray-700/50 my-1"></li>
                    <li><a href="https://lms.sits.edu.et" target="_blank" class="block px-4 py-2 text-gray-200 hover:bg-gray-700/80 hover:text-white transition duration-150">{{ $lmsLabel }}</a></li>
                    @if ($user->hasAnyRole(['SUPERADMIN', 'ADMIN', 'EDITOR', 'TRAINER', 'STAFF', 'LIBRARIAN']))
                        <li><a href="https://pms.sits.edu.et" target="_blank" class="block px-4 py-2 text-gray-200 hover:bg-gray-700/80 hover:text-white transition duration-150">ERP Portal</a></li>
                    @endif
                    @if ($user->hasAnyRole(['STUDENT', 'TRAINER', 'LIBRARIAN', 'ADMIN', 'SUPERADMIN', 'STAFF', 'EDITOR']))
                        <li><a href="https://library.sits.edu.et" target="_blank" class="block px-4 py-2 text-gray-200 hover:bg-gray-700/80 hover:text-white transition duration-150">Digital Library</a></li>
                    @endif
                    <li class="border-t border-gray-700/50 my-1"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="block w-full text-left px-4 py-2 text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition duration-150">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
            @else
            <a href="{{ route('login') }}" class="bg-white text-gray-900 hover:bg-yellow-400 py-2 px-4 rounded-lg font-medium transition">Login</a>
            @endif
        </div>
    </div>
</nav>

