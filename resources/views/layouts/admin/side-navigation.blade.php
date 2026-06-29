<aside class="w-full md:w-64 bg-slate-950/85 backdrop-blur-md border-r border-slate-900 flex flex-col z-30 shrink-0">
    <!-- Brand Logo -->
    <div class="h-20 flex items-center px-6 border-b border-slate-900">
        <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-cyan-400 p-0.5 shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-300">
                <div class="w-full h-full bg-slate-900 rounded-[10px] flex items-center justify-center overflow-hidden">
                    <img src="{{ asset('img/logo.png') }}" alt="SITS Logo" class="h-7 w-auto object-contain" />
                </div>
            </div>
            <div>
                <span class="font-outfit text-lg font-bold tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent">
                    SITS ADMIN
                </span>
            </div>
        </a>
    </div>

    <!-- Navigation Menu Links -->
    <div class="flex-grow overflow-y-auto p-4 space-y-6">
        <!-- Main Links -->
        <div class="space-y-1.5">
            <a href="{{ url('/portal') }}" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-white/5 text-slate-300 hover:text-white transition duration-200">
                <i class="fas fa-home text-indigo-400 w-5"></i>
                <span class="text-sm font-medium">Dashboard</span>
            </a>
        </div>

        <!-- Management Sections -->
        <div class="space-y-2">
            <span class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Management</span>
            
            <div class="space-y-1">
                <!-- Course -->
                <details class="group" {{ request()->routeIs('courses.*') ? 'open' : '' }}>
                    <summary class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 cursor-pointer text-slate-300 hover:text-white transition duration-200">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-book-open text-violet-400 w-5"></i>
                            <span class="text-sm font-medium">Courses</span>
                        </div>
                        <i class="fa fa-chevron-down text-[10px] text-slate-500 transition-transform group-open:rotate-180"></i>
                    </summary>
                    <ul class="pl-8 mt-1 space-y-1 border-l border-slate-900 ml-5">
                        <li>
                            <a href="{{ route('courses.list') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('courses.list') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Manage Courses
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('courses.create') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('courses.create') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Add Course
                            </a>
                        </li>
                    </ul>
                </details>

                <!-- Program -->
                <details class="group" {{ request()->routeIs('programs.*') ? 'open' : '' }}>
                    <summary class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 cursor-pointer text-slate-300 hover:text-white transition duration-200">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-university text-cyan-400 w-5"></i>
                            <span class="text-sm font-medium">Programs</span>
                        </div>
                        <i class="fa fa-chevron-down text-[10px] text-slate-500 transition-transform group-open:rotate-180"></i>
                    </summary>
                    <ul class="pl-8 mt-1 space-y-1 border-l border-slate-900 ml-5">
                        <li>
                            <a href="{{ route('programs.list') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('programs.list') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Manage Programs
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('programs.create') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('programs.create') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Add Program
                            </a>
                        </li>
                    </ul>
                </details>

                <!-- Event -->
                <details class="group" {{ request()->routeIs('events.*') ? 'open' : '' }}>
                    <summary class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 cursor-pointer text-slate-300 hover:text-white transition duration-200">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-calendar-check text-emerald-400 w-5"></i>
                            <span class="text-sm font-medium">Events</span>
                        </div>
                        <i class="fa fa-chevron-down text-[10px] text-slate-500 transition-transform group-open:rotate-180"></i>
                    </summary>
                    <ul class="pl-8 mt-1 space-y-1 border-l border-slate-900 ml-5">
                        <li>
                            <a href="{{ route('events.list') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('events.list') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Manage Events
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('events.create') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('events.create') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Add Event
                            </a>
                        </li>
                    </ul>
                </details>

                <!-- Gallery -->
                <details class="group" {{ request()->routeIs('galleries.*') ? 'open' : '' }}>
                    <summary class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 cursor-pointer text-slate-300 hover:text-white transition duration-200">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-image text-pink-400 w-5"></i>
                            <span class="text-sm font-medium">Gallery</span>
                        </div>
                        <i class="fa fa-chevron-down text-[10px] text-slate-500 transition-transform group-open:rotate-180"></i>
                    </summary>
                    <ul class="pl-8 mt-1 space-y-1 border-l border-slate-900 ml-5">
                        <li>
                            <a href="{{ route('galleries.list') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('galleries.list') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Manage Gallery
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('galleries.create') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('galleries.create') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Add Gallery
                            </a>
                        </li>
                    </ul>
                </details>

                <!-- Trainer -->
                <details class="group" {{ request()->routeIs('trainers.*') ? 'open' : '' }}>
                    <summary class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 cursor-pointer text-slate-300 hover:text-white transition duration-200">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-chalkboard-user text-rose-400 w-5"></i>
                            <span class="text-sm font-medium">Trainers</span>
                        </div>
                        <i class="fa fa-chevron-down text-[10px] text-slate-500 transition-transform group-open:rotate-180"></i>
                    </summary>
                    <ul class="pl-8 mt-1 space-y-1 border-l border-slate-900 ml-5">
                        <li>
                            <a href="{{ route('trainers.list') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('trainers.list') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Manage Trainers
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('trainers.create') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('trainers.create') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Add Trainer
                            </a>
                        </li>
                    </ul>
                </details>

                <!-- Blog -->
                <details class="group" {{ request()->routeIs('blogs.*') ? 'open' : '' }}>
                    <summary class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 cursor-pointer text-slate-300 hover:text-white transition duration-200">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-newspaper text-amber-400 w-5"></i>
                            <span class="text-sm font-medium">Blogs</span>
                        </div>
                        <i class="fa fa-chevron-down text-[10px] text-slate-500 transition-transform group-open:rotate-180"></i>
                    </summary>
                    <ul class="pl-8 mt-1 space-y-1 border-l border-slate-900 ml-5">
                        <li>
                            <a href="{{ route('blogs.list') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('blogs.list') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Manage Blogs
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('blogs.create') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('blogs.create') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Add Blog
                            </a>
                        </li>
                    </ul>
                </details>

                <!-- Library -->
                <details class="group" {{ request()->routeIs('libraries.*') ? 'open' : '' }}>
                    <summary class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 cursor-pointer text-slate-300 hover:text-white transition duration-200">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-book-reader text-teal-400 w-5"></i>
                            <span class="text-sm font-medium">Library</span>
                        </div>
                        <i class="fa fa-chevron-down text-[10px] text-slate-500 transition-transform group-open:rotate-180"></i>
                    </summary>
                    <ul class="pl-8 mt-1 space-y-1 border-l border-slate-900 ml-5">
                        <li>
                            <a href="{{ route('libraries.list') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('libraries.list') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Manage Library
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('libraries.create') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('libraries.create') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Add Library
                            </a>
                        </li>
                    </ul>
                </details>

                <!-- User -->
                <details class="group" {{ request()->routeIs('users.*') ? 'open' : '' }}>
                    <summary class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 cursor-pointer text-slate-300 hover:text-white transition duration-200">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-users text-blue-400 w-5"></i>
                            <span class="text-sm font-medium">Users</span>
                        </div>
                        <i class="fa fa-chevron-down text-[10px] text-slate-500 transition-transform group-open:rotate-180"></i>
                    </summary>
                    <ul class="pl-8 mt-1 space-y-1 border-l border-slate-900 ml-5">
                        <li>
                            <a href="{{ route('users.list') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('users.list') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Manage Users
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('users.create') }}" class="block p-2 rounded-lg text-xs {{ request()->routeIs('users.create') ? 'text-indigo-400 font-semibold bg-white/5' : 'text-slate-400 hover:text-white hover:bg-white/5' }} transition">
                                Add User
                            </a>
                        </li>
                    </ul>
                </details>

                <!-- Contact -->
                <a href="{{ route('contacts.list') }}" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-white/5 text-slate-300 hover:text-white transition duration-200 {{ request()->routeIs('contacts.*') ? 'bg-white/5 text-white font-semibold' : '' }}">
                    <i class="fas fa-address-book text-orange-400 w-5"></i>
                    <span class="text-sm font-medium">Contact Messages</span>
                </a>

                <!-- Subscription -->
                <a href="{{ route('subscriptions.index') }}" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-white/5 text-slate-300 hover:text-white transition duration-200 {{ request()->routeIs('subscriptions.*') ? 'bg-white/5 text-white font-semibold' : '' }}">
                    <i class="fas fa-envelope-open-text text-purple-400 w-5"></i>
                    <span class="text-sm font-medium">Subscriptions</span>
                </a>
            </div>
        </div>
    </div>
</aside>
