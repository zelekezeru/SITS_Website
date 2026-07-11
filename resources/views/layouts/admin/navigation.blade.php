<header class="sticky top-0 z-30 h-16 flex items-center gap-1.5 sm:gap-3 px-3 sm:px-6 border-b border-slate-900 bg-slate-950/80 backdrop-blur-md shrink-0">

    {{-- Mobile hamburger --}}
    <button type="button" id="adminMobileToggle"
            class="md:hidden w-9 h-9 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-900 transition">
        <i class="fa fa-bars text-base"></i>
    </button>

    {{-- Breadcrumb --}}
    <div class="hidden sm:block min-w-0">
        <p class="text-[11px] text-slate-500 font-medium uppercase tracking-wider truncate">SITS Website Admin</p>
        <h1 class="text-sm font-bold text-white truncate -mt-0.5">
            @if(request()->routeIs('website.admin.dashboard'))
                Overview
            @elseif(request()->routeIs('courses.*'))
                Courses
            @elseif(request()->routeIs('blogs.*'))
                Blogs
            @elseif(request()->routeIs('programs.*'))
                Programs
            @elseif(request()->routeIs('events.*'))
                Events
            @elseif(request()->routeIs('galleries.*'))
                Gallery
            @elseif(request()->routeIs('trainers.*'))
                Trainers
            @elseif(request()->routeIs('libraries.*'))
                Libraries
            @elseif(request()->routeIs('library.subscriptions'))
                Library Subscriptions
            @elseif(request()->routeIs('users.*'))
                Users
            @elseif(request()->routeIs('contacts.*'))
                Contacts
            @elseif(request()->routeIs('subscriptions.*'))
                Subscriptions
            @else
                Dashboard
            @endif
        </h1>
    </div>

    <div class="flex-1"></div>

    {{-- Language Selector --}}
    <div class="relative">
        <form action="/locale" method="POST" id="lang-switch-form-admin" class="inline-block m-0 p-0">
            @csrf
            <select name="locale" onchange="this.form.submit()"
                    class="bg-slate-900/50 border border-slate-800 text-xs font-semibold text-slate-300 rounded-xl px-2.5 py-1.5 h-9 focus:outline-none cursor-pointer hover:border-slate-700 transition">
                <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>EN</option>
                <option value="am" {{ app()->getLocale() === 'am' ? 'selected' : '' }}>አማ</option>
                <option value="om" {{ app()->getLocale() === 'om' ? 'selected' : '' }}>ORM</option>
                <option value="ti" {{ app()->getLocale() === 'ti' ? 'selected' : '' }}>ትግ</option>
                <option value="so" {{ app()->getLocale() === 'so' ? 'selected' : '' }}>SOM</option>
                <option value="sw" {{ app()->getLocale() === 'sw' ? 'selected' : '' }}>SWA</option>
                <option value="zh" {{ app()->getLocale() === 'zh' ? 'selected' : '' }}>中文</option>
                <option value="fr" {{ app()->getLocale() === 'fr' ? 'selected' : '' }}>FR</option>
                <option value="es" {{ app()->getLocale() === 'es' ? 'selected' : '' }}>ES</option>
                <option value="ku" {{ app()->getLocale() === 'ku' ? 'selected' : '' }}>KRD</option>
                <option value="ur" {{ app()->getLocale() === 'ur' ? 'selected' : '' }}>URD</option>
            </select>
        </form>
    </div>

    <div class="hidden sm:block w-px h-6 bg-slate-800"></div>

    {{-- User Dropdown --}}
    @include('layouts.user-dropdown')
</header>
