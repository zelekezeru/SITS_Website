<aside id="adminSidebar" class="fixed md:sticky top-0 inset-y-0 left-0 z-50 w-56 bg-slate-950/95 border-r border-slate-900 flex flex-col shrink-0 transition-transform duration-300 -translate-x-full md:translate-x-0 h-screen">
    <!-- Brand Logo -->
    <div class="h-20 flex items-center justify-between px-6 border-b border-slate-900 shrink-0">
        <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-slate-700 to-slate-500 p-0.5 shadow-lg group-hover:scale-105 transition-transform duration-300">
                <div class="w-full h-full bg-slate-900 rounded-[10px] flex items-center justify-center overflow-hidden">
                    <img src="{{ asset('img/logo.png') }}" alt="SITS Logo" class="h-7 w-auto object-contain" />
                </div>
            </div>
            <div>
                <span class="block font-outfit text-sm font-bold tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent uppercase leading-none">
                    SITS CMS
                </span>
                <span class="text-[9px] text-slate-400 font-semibold uppercase tracking-wider block mt-0.5">Website Console</span>
            </div>
        </a>
        <button type="button" id="adminSidebarClose" class="md:hidden w-8 h-8 flex items-center justify-center rounded-xl bg-slate-900 border border-slate-800 text-slate-500 hover:text-white transition">
            <i class="fa fa-times text-sm"></i>
        </button>
    </div>

    <!-- Navigation Menu Links -->
    <nav class="flex-1 flex flex-col min-h-0 px-2">
        <!-- Overview -->
        @php
            $isOverview = request()->routeIs('website.admin.dashboard');
        @endphp
        <a href="{{ route('website.admin.dashboard') }}" 
           class="flex-1 flex items-center gap-2.5 px-3 text-xs font-bold tracking-wide transition-all duration-150 border-b border-slate-900/40 {{ $isOverview ? 'text-white bg-slate-900/70' : 'text-slate-200 hover:text-white hover:bg-slate-900/20' }}">
            <i class="fas fa-chart-line text-sm {{ $isOverview ? 'text-white' : 'text-slate-400' }} w-4 shrink-0"></i>
            <span>Overview</span>
        </a>

        <!-- Courses -->
        @php
            $isCourses = request()->routeIs('courses.*') || request()->is('courses/*') || request()->is('courses');
        @endphp
        <a href="{{ route('courses.list') }}" 
           class="flex-1 flex items-center gap-2.5 px-3 text-xs font-bold tracking-wide transition-all duration-150 border-b border-slate-900/40 {{ $isCourses ? 'text-white bg-slate-900/70' : 'text-slate-200 hover:text-white hover:bg-slate-900/20' }}">
            <i class="fas fa-book-open text-sm {{ $isCourses ? 'text-white' : 'text-slate-400' }} w-4 shrink-0"></i>
            <span>Courses</span>
        </a>

        <!-- Blogs -->
        @php
            $isBlogs = request()->routeIs('blogs.*') || request()->is('blogs/*') || request()->is('blogs');
        @endphp
        <a href="{{ route('blogs.list') }}" 
           class="flex-1 flex items-center gap-2.5 px-3 text-xs font-bold tracking-wide transition-all duration-150 border-b border-slate-900/40 {{ $isBlogs ? 'text-white bg-slate-900/70' : 'text-slate-200 hover:text-white hover:bg-slate-900/20' }}">
            <i class="fas fa-newspaper text-sm {{ $isBlogs ? 'text-white' : 'text-slate-400' }} w-4 shrink-0"></i>
            <span>Blogs</span>
        </a>

        <!-- Programs -->
        @php
            $isPrograms = request()->routeIs('programs.*') || request()->is('programs/*') || request()->is('programs');
        @endphp
        <a href="{{ route('programs.list') }}" 
           class="flex-1 flex items-center gap-2.5 px-3 text-xs font-bold tracking-wide transition-all duration-150 border-b border-slate-900/40 {{ $isPrograms ? 'text-white bg-slate-900/70' : 'text-slate-200 hover:text-white hover:bg-slate-900/20' }}">
            <i class="fas fa-graduation-cap text-sm {{ $isPrograms ? 'text-white' : 'text-slate-400' }} w-4 shrink-0"></i>
            <span>Programs</span>
        </a>

        <!-- Events -->
        @php
            $isEvents = request()->routeIs('events.*') || request()->is('events/*') || request()->is('events');
        @endphp
        <a href="{{ route('events.list') }}" 
           class="flex-1 flex items-center gap-2.5 px-3 text-xs font-bold tracking-wide transition-all duration-150 border-b border-slate-900/40 {{ $isEvents ? 'text-white bg-slate-900/70' : 'text-slate-200 hover:text-white hover:bg-slate-900/20' }}">
            <i class="fas fa-calendar-days text-sm {{ $isEvents ? 'text-white' : 'text-slate-400' }} w-4 shrink-0"></i>
            <span>Events</span>
        </a>

        <!-- Gallery -->
        @php
            $isGallery = request()->routeIs('galleries.*') || request()->is('galleries/*') || request()->is('galleries');
        @endphp
        <a href="{{ route('galleries.list') }}" 
           class="flex-1 flex items-center gap-2.5 px-3 text-xs font-bold tracking-wide transition-all duration-150 border-b border-slate-900/40 {{ $isGallery ? 'text-white bg-slate-900/70' : 'text-slate-200 hover:text-white hover:bg-slate-900/20' }}">
            <i class="fas fa-image text-sm {{ $isGallery ? 'text-white' : 'text-slate-400' }} w-4 shrink-0"></i>
            <span>Gallery</span>
        </a>

        <!-- Trainers -->
        @php
            $isTrainers = request()->routeIs('trainers.*') || request()->is('trainers/*') || request()->is('trainers');
        @endphp
        <a href="{{ route('trainers.list') }}" 
           class="flex-1 flex items-center gap-2.5 px-3 text-xs font-bold tracking-wide transition-all duration-150 border-b border-slate-900/40 {{ $isTrainers ? 'text-white bg-slate-900/70' : 'text-slate-200 hover:text-white hover:bg-slate-900/20' }}">
            <i class="fas fa-briefcase text-sm {{ $isTrainers ? 'text-white' : 'text-slate-400' }} w-4 shrink-0"></i>
            <span>Trainers</span>
        </a>

        <!-- Libraries -->
        @php
            $isLibraries = request()->routeIs('libraries.*') || request()->is('libraries/*') || request()->is('libraries');
        @endphp
        <a href="{{ route('libraries.list') }}" 
           class="flex-1 flex items-center gap-2.5 px-3 text-xs font-bold tracking-wide transition-all duration-150 border-b border-slate-900/40 {{ $isLibraries ? 'text-white bg-slate-900/70' : 'text-slate-200 hover:text-white hover:bg-slate-900/20' }}">
            <i class="fas fa-book text-sm {{ $isLibraries ? 'text-white' : 'text-slate-400' }} w-4 shrink-0"></i>
            <span>Libraries</span>
        </a>

        <!-- Library Subscriptions -->
        @php
            $isLibSub = request()->routeIs('library.subscriptions') || request()->is('library/subscriptions*');
        @endphp
        <a href="{{ route('library.subscriptions') }}" 
           class="flex-1 flex items-center gap-2.5 px-3 text-xs font-bold tracking-wide transition-all duration-150 border-b border-slate-900/40 {{ $isLibSub ? 'text-white bg-slate-900/70' : 'text-slate-200 hover:text-white hover:bg-slate-900/20' }}">
            <i class="fas fa-id-card text-sm {{ $isLibSub ? 'text-white' : 'text-slate-400' }} w-4 shrink-0"></i>
            <span>Library Subscriptions</span>
        </a>

        <!-- Users -->
        @php
            $isUsers = request()->routeIs('users.*') || request()->is('users/*') || request()->is('users');
        @endphp
        <a href="{{ route('users.list') }}" 
           class="flex-1 flex items-center gap-2.5 px-3 text-xs font-bold tracking-wide transition-all duration-150 border-b border-slate-900/40 {{ $isUsers ? 'text-white bg-slate-900/70' : 'text-slate-200 hover:text-white hover:bg-slate-900/20' }}">
            <i class="fas fa-users text-sm {{ $isUsers ? 'text-white' : 'text-slate-400' }} w-4 shrink-0"></i>
            <span>Users</span>
        </a>

        <!-- Contacts -->
        @php
            $isContacts = request()->routeIs('contacts.*') || request()->is('contacts/*') || request()->is('contacts');
        @endphp
        <a href="{{ route('contacts.list') }}" 
           class="flex-1 flex items-center gap-2.5 px-3 text-xs font-bold tracking-wide transition-all duration-150 border-b border-slate-900/40 {{ $isContacts ? 'text-white bg-slate-900/70' : 'text-slate-200 hover:text-white hover:bg-slate-900/20' }}">
            <i class="fas fa-envelope text-sm {{ $isContacts ? 'text-white' : 'text-slate-400' }} w-4 shrink-0"></i>
            <span>Contacts</span>
        </a>

        <!-- Subscriptions -->
        @php
            $isSubscriptions = request()->routeIs('subscriptions.*') || request()->is('subscriptions/*') || request()->is('subscriptions');
        @endphp
        <a href="{{ route('subscriptions.index') }}" 
           class="flex-1 flex items-center gap-2.5 px-3 text-xs font-bold tracking-wide transition-all duration-150 {{ $isSubscriptions ? 'text-white bg-slate-900/70' : 'text-slate-200 hover:text-white hover:bg-slate-900/20' }}">
            <i class="fas fa-bell text-sm {{ $isSubscriptions ? 'text-white' : 'text-slate-400' }} w-4 shrink-0"></i>
            <span>Subscriptions</span>
        </a>
    </nav>

    <!-- Back to Portal Hub -->
    <div class="p-3 border-t border-slate-900 shrink-0">
        <a href="{{ url('/portal') }}" class="w-full flex items-center justify-center gap-2 py-2 rounded-lg bg-slate-900/50 text-[11px] font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition">
            <i class="fas fa-arrow-left text-[9px]"></i>
            <span>Dashboard Hub</span>
        </a>
    </div>
</aside>
