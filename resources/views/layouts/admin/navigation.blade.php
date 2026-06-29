<header class="h-20 border-b border-slate-900 bg-slate-950/40 backdrop-blur-md px-6 flex justify-between items-center z-20">
    <!-- Search Bar -->
    <div class="hidden md:flex items-center space-x-2 bg-slate-900/60 border border-slate-800/80 rounded-xl px-3 py-1.5 w-64 focus-within:border-indigo-500/50 transition">
        <i class="fa fa-search text-slate-500 text-sm"></i>
        <input type="text" placeholder="Search..." class="bg-transparent border-none text-white text-sm focus:outline-none placeholder-slate-500 w-full" />
    </div>

    <!-- Right Side Actions -->
    <div class="flex items-center space-x-4 ml-auto">
        <!-- User Dropdown -->
        <div class="relative" id="adminUserMenuContainer">
            <button type="button" id="adminUserMenuBtn" class="flex items-center space-x-3 p-1.5 pr-3 rounded-full hover:bg-white/5 border border-transparent hover:border-white/10 transition duration-200">
                <img src="{{ auth()->user()->profile_image ? Storage::url(auth()->user()->profile_image) : asset('img/user.png') }}" 
                     alt="{{ auth()->user()->name }}" 
                     class="h-8 w-8 rounded-full object-cover ring-2 ring-indigo-500/30" />
                <span class="text-sm font-medium text-slate-300 hidden sm:inline-block">{{ auth()->user()->name }}</span>
                <i class="fa fa-chevron-down text-xs text-slate-500 transition-transform duration-200" id="adminUserMenuChevron"></i>
            </button>
            
            <!-- Dropdown Menu -->
            <div id="adminUserDropdown" class="hidden absolute right-0 mt-3 w-56 rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-2 z-50 animate-fade-in">
                <div class="px-3 py-2.5 border-b border-slate-800/60 mb-2">
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Logged In As</p>
                    <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                    <div class="mt-1 flex items-center">
                        <span class="inline-block px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-400 text-[10px] font-bold border border-indigo-500/20 uppercase">
                            {{ auth()->user()->roles->first()?->name ?? 'ADMIN' }}
                        </span>
                    </div>
                </div>
                
                <!-- Menu Links -->
                <a href="{{ route('profile.edit') }}" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-sm text-slate-400 hover:text-white hover:bg-white/5 transition duration-200">
                    <i class="fa fa-user-circle text-slate-500 w-4"></i>
                    <span>Edit Profile</span>
                </a>
                <a href="{{ route('home') }}" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-sm text-slate-400 hover:text-white hover:bg-white/5 transition duration-200">
                    <i class="fa fa-home text-slate-500 w-4"></i>
                    <span>Public Site</span>
                </a>
                
                <hr class="border-slate-800/60 my-1.5" />
                
                <!-- Logout Button -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center space-x-2.5 px-3 py-2 rounded-xl text-sm text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition duration-200 text-left">
                        <i class="fa fa-sign-out-alt w-4"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const adminUserMenuBtn = document.getElementById('adminUserMenuBtn');
        const adminUserDropdown = document.getElementById('adminUserDropdown');
        const adminUserMenuChevron = document.getElementById('adminUserMenuChevron');

        if (adminUserMenuBtn && adminUserDropdown) {
            adminUserMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                adminUserDropdown.classList.toggle('hidden');
                if (adminUserMenuChevron) {
                    adminUserMenuChevron.classList.toggle('rotate-180');
                }
            });

            // Close dropdown if clicked outside
            window.addEventListener('click', (e) => {
                if (!adminUserMenuBtn.contains(e.target) && !adminUserDropdown.contains(e.target)) {
                    adminUserDropdown.classList.add('hidden');
                    if (adminUserMenuChevron) {
                        adminUserMenuChevron.classList.remove('rotate-180');
                    }
                }
            });
        }
    });
</script>
