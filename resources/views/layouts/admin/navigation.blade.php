<header class="h-20 border-b border-slate-900 bg-slate-950/40 backdrop-blur-md px-6 flex justify-between items-center z-20 shrink-0">
    <!-- Left Side: Hamburger & Breadcrumb -->
    <div class="flex items-center space-x-4">
        <button type="button" id="adminMobileToggle" class="md:hidden w-9 h-9 flex items-center justify-center rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition">
            <i class="fa fa-bars text-base"></i>
        </button>
        <div>
            <span class="block text-[10px] text-slate-500 font-medium uppercase tracking-wider">
                SITS ADMIN
            </span>
            <span class="block text-sm font-bold text-white -mt-0.5 uppercase tracking-wide">
                @if(request()->routeIs('users.*'))
                    Users
                @elseif(request()->routeIs('courses.*'))
                    Courses
                @elseif(request()->routeIs('programs.*'))
                    Programs
                @elseif(request()->routeIs('events.*'))
                    Events
                @else
                    Dashboard
                @endif
            </span>
        </div>
    </div>

    <!-- Right Side Actions -->
    <div class="flex items-center space-x-4 ml-auto">
        <!-- Language Selector -->
        <div class="relative">
            <form action="/locale" method="POST" id="lang-switch-form-admin" class="inline-block m-0 p-0">
                @csrf
                <select name="locale" onchange="this.form.submit()" class="bg-slate-900 border border-slate-800 text-xs font-semibold text-slate-300 rounded-xl px-2.5 py-1.5 focus:outline-none cursor-pointer hover:border-slate-700 transition">
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

        <!-- User Dropdown -->
        @include('layouts.user-dropdown')
    </div>
</header>
