<nav class="fixed top-0 w-full bg-gray-800 py-4 shadow-lg z-50">
    <div class="container mx-auto flex justify-between items-center px-4">
        <!-- Logo -->
        <a href="/" class="flex items-center">
            <img src="/img/logo.png" alt="SITS Logo" class="h-10" />
        </a>

        <!-- Navigation Menu -->
        <ul class="hidden md:flex space-x-6 text-gray-300">
            <li><a href="{{ route('home') }}" class="hover:text-white">Home</a></li>
            <li class="relative">
                <!-- Dropdown Trigger -->
                <button id="dropdownTrigger" class="hover:text-white flex items-center">
                    Programs
                    <svg class="w-3 h-3 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div id="dropdownMenu" class="hidden absolute bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700 mt-2">
                    <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                        <li><a href="{{ route('courses.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Courses</a></li>
                        <li><a href="{{ route('programs.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Post Grad Programs</a></li>
                    </ul>
                </div>
            </li>
            <li><a href="{{ route('libraries.index') }}" class="hover:text-white">Libraries</a></li>
            <li><a href="{{ route('blogs.index') }}" class="hover:text-white">Blog</a></li>
            <li><a href="{{ route('abouts.index') }}" class="hover:text-white">About</a></li>
            <li><a href="{{ route('contacts.index') }}" class="hover:text-white">Contact</a></li>
        </ul>

        <!-- User Actions -->
        <div class="flex items-center space-x-4">
            @if (Auth::check())
            <div class="relative">
                <div class="flex items-center cursor-pointer">
                    <img src="{{ auth()->user()->profile_image ? Storage::url(auth()->user()->profile_image) : '/img/user.png' }}" alt="Profile Image" class="h-10 w-10 rounded-full object-cover" />
                    <span class="ml-2 text-gray-300">{{ Auth::user()->name }}</span>
                </div>
                <ul class="absolute hidden bg-gray-700 text-sm rounded shadow-lg mt-2 right-0">
                    <li><a href="{{ route('dashboard') }}" class="block px-4 py-2 hover:bg-gray-600">Dashboard</a></li>
                    <li><a href="{{ route('users.show', Auth::user()->id) }}" class="block px-4 py-2 hover:bg-gray-600">View Profile</a></li>
                    <li><a href="https://pms.sits.edu.et/" target="_blank" class="block px-4 py-2 hover:bg-gray-600">Goto PMS</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="block w-full text-left px-4 py-2 hover:bg-gray-600">Logout</button>
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
=
