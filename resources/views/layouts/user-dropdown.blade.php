@if (Auth::check())
<div class="relative">
    <button type="button" id="userMenuBtn" class="flex items-center space-x-2 p-1 rounded-full hover:bg-slate-900 border border-transparent hover:border-slate-800 transition">
        <img src="{{ auth()->user()->profile_image ? Storage::url(auth()->user()->profile_image) : '/img/user.png' }}" alt="Profile Image" class="h-8 w-8 rounded-full object-cover ring-2 ring-indigo-500/20" />
        <span class="text-xs font-semibold text-slate-300 hidden sm:inline-block">{{ Auth::user()->name }}</span>
        <i class="fa fa-chevron-down text-[10px] text-slate-500 hidden sm:inline-block transition-transform duration-200" id="userMenuChevron"></i>
    </button>
    @php
        $user = Auth::user();
        $lmsLabel = __('app.lms_portal');
        $rolesLower = $user->roles->pluck('name')->map(fn($r) => strtolower($r));
        if ($rolesLower->contains('student')) {
            $lmsLabel = __('app.student_portal');
        } elseif ($rolesLower->contains('trainer')) {
            $lmsLabel = __('app.trainer_portal');
        } elseif ($rolesLower->intersect(['staff', 'superadmin', 'admin', 'president / super admin', 'editor', 'librarian'])->isNotEmpty()) {
            $lmsLabel = __('app.staff_portal');
        }
    @endphp
    <ul id="userDropdown" class="absolute hidden bg-slate-900/95 backdrop-blur-xl border border-slate-800 text-xs font-semibold rounded-2xl shadow-2xl mt-2 right-0 w-56 py-2 z-50 animate-fade-in">
        <li class="px-4 py-2 border-b border-slate-800/60 mb-2 md:hidden">
            <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">Logged In As</p>
            <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name }}</p>
        </li>
        <li><a href="{{ route('portal') }}" class="block px-4 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800/40 transition">{{ __('app.dashboard') }}</a></li>
        <li><a href="{{ route('profile.edit', ['from' => 'website']) }}" class="block px-4 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800/40 transition">{{ __('app.view_profile') }}</a></li>
        <li class="border-t border-slate-800/60 my-1.5"></li>
        @if ($user->hasAnyRole(['SUPERADMIN', 'ADMIN', 'EDITOR', 'TRAINER', 'STAFF', 'LIBRARIAN']))
            <li><a href="/dashboard" class="flex items-center px-4 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800/40 transition"><i class="fa fa-dashboard w-4 mr-2 text-slate-500"></i> SITS ERP</a></li>
        @endif
        <li><a href="/library/portal" class="flex items-center px-4 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800/40 transition"><i class="fa fa-book w-4 mr-2 text-slate-500"></i> Digital Library</a></li>
        <li><a href="https://lms.sits.edu.et" target="_blank" class="flex items-center px-4 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800/40 transition"><i class="fa fa-graduation-cap w-4 mr-2 text-slate-500"></i> SITS LMS</a></li>
        <li><a href="/go/lms" target="_blank" class="flex items-center px-4 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800/40 transition"><i class="fa fa-laptop w-4 mr-2 text-slate-500"></i> Moodle</a></li>
        @if ($user->hasAnyRole(['SUPERADMIN', 'ADMIN', 'EDITOR']))
            <li><a href="{{ route('website.admin.dashboard') }}" class="flex items-center px-4 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800/40 transition"><i class="fa fa-globe w-4 mr-2 text-slate-500"></i> Website Admin</a></li>
        @endif
        <li class="border-t border-slate-800/60 my-1.5"></li>
        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="block w-full text-left px-4 py-2.5 text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition">{{ __('app.sign_out') }}</button>
            </form>
        </li>
    </ul>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userDropdown = document.getElementById('userDropdown');
        const userMenuChevron = document.getElementById('userMenuChevron');

        if (userMenuBtn && userDropdown) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('hidden');
                if (userMenuChevron) {
                    userMenuChevron.classList.toggle('rotate-180');
                }
            });

            // Close dropdown if clicked outside
            window.addEventListener('click', (e) => {
                if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.add('hidden');
                    if (userMenuChevron) {
                        userMenuChevron.classList.remove('rotate-180');
                    }
                }
            });
        }
    });
</script>
@endif
