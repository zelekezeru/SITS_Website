@extends('layouts.portal')

@section('title', 'SITS Portal Hub | Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-10">

    <!-- Top Greeting and Welcome Banner -->
    <div class="mb-10 relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 p-8 sm:p-10 shadow-2xl">
        <!-- Decorative subtle background pattern -->
        <div class="absolute right-0 top-0 -mt-10 -mr-10 w-64 h-64 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <h1 class="font-outfit text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-2">
                    Welcome Back, <span class="bg-gradient-to-r from-indigo-400 via-cyan-400 to-indigo-400 bg-clip-text text-transparent">{{ Auth::user()->name }}</span> 👋
                </h1>
                <p class="text-slate-400 text-sm sm:text-base max-w-xl">
                    Access SITS theological systems, check electronic resources, and manage system registries from your unified workspace.
                </p>
            </div>
            
            <!-- User Status Info Card -->
            <div class="flex items-center space-x-4 bg-slate-950/60 backdrop-blur-md px-5 py-3 rounded-2xl border border-white/5 shadow-inner">
                <div class="relative">
                    <img src="{{ Auth::user()->profile_image ? Storage::url(Auth::user()->profile_image) : asset('img/user.png') }}" 
                         alt="{{ Auth::user()->name }}" 
                         class="w-12 h-12 rounded-full object-cover ring-2 ring-indigo-500/20" />
                    <span class="absolute bottom-0 right-0 block h-3.5 w-3.5 rounded-full bg-emerald-500 ring-2 ring-slate-950 animate-pulse"></span>
                </div>
                <div>
                    <span class="block text-[10px] text-slate-500 font-bold uppercase tracking-wider">Account Role</span>
                    <span class="inline-block px-2.5 py-0.5 mt-0.5 rounded-md bg-indigo-500/10 text-indigo-400 text-xs font-bold border border-indigo-500/20 uppercase">
                        {{ Auth::user()->roles->first()?->name ?? 'USER' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Portal Systems Section -->
    <div class="mb-12">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-8">
            <div>
                <h2 class="font-outfit text-2xl font-bold text-white tracking-tight">System Access Portals</h2>
                <p class="text-xs sm:text-sm text-slate-400">Select a system to launch or request access permissions.</p>
            </div>
            <span class="h-1 w-20 bg-gradient-to-r from-indigo-500 to-cyan-500 rounded-full"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach ($portals as $key => $portal)
                <!-- Portal Card -->
                <div class="relative rounded-3xl glassmorphism glass-card-hover overflow-hidden flex flex-col justify-between h-full group">
                    <!-- Top accent line -->
                    @if($portal['color'] === 'indigo')
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
                    @elseif($portal['color'] === 'cyan')
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-cyan-500 via-teal-500 to-emerald-500"></div>
                    @elseif($portal['color'] === 'amber')
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 via-orange-500 to-yellow-500"></div>
                    @endif

                    <div class="p-6">
                        <!-- Icon Header -->
                        <div class="flex justify-between items-start mb-6">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-inner
                                @if($portal['color'] === 'indigo') bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 @endif
                                @if($portal['color'] === 'cyan') bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 @endif
                                @if($portal['color'] === 'amber') bg-amber-500/10 text-amber-400 border border-amber-500/20 @endif">
                                <i class="{{ $portal['icon'] }}"></i>
                            </div>

                            <!-- Authorization Status Badge -->
                            @if ($portal['authorized'])
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 shadow-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Authorized
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/25 shadow-sm">
                                    <i class="fa fa-lock text-[10px]"></i>
                                    Restricted
                                </span>
                            @endif
                        </div>

                        <!-- Content -->
                        <h3 class="font-outfit text-xl font-bold text-white mb-2 group-hover:text-indigo-300 transition-colors duration-300">
                            {{ $portal['name'] }}
                        </h3>
                        <p class="text-sm text-slate-400 leading-relaxed mb-6">
                            {{ $portal['description'] }}
                        </p>

                        <!-- Features checklist -->
                        <div class="border-t border-slate-900/60 pt-4 mb-6">
                            <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-3">Key Features</h4>
                            <ul class="space-y-2.5">
                                @foreach ($portal['features'] as $feature)
                                    <li class="flex items-start text-xs text-slate-300">
                                        <i class="fa-solid fa-circle-check mt-0.5 mr-2
                                            @if($portal['color'] === 'indigo') text-indigo-500 @endif
                                            @if($portal['color'] === 'cyan') text-cyan-500 @endif
                                            @if($portal['color'] === 'amber') text-amber-500 @endif"></i>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <!-- Action Button Container -->
                    <div class="p-6 pt-0 border-t border-slate-900/40 mt-auto">
                        @if ($portal['authorized'])
                            <a href="{{ $portal['url'] }}" target="_blank" 
                               class="w-full flex items-center justify-center py-3 px-4 rounded-xl text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]
                                @if($portal['color'] === 'indigo') bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 shadow-indigo-600/20 neon-glow-indigo @endif
                                @if($portal['color'] === 'cyan') bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-500 hover:to-teal-500 shadow-cyan-600/20 neon-glow-cyan @endif
                                @if($portal['color'] === 'amber') bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 shadow-amber-600/20 neon-glow-amber @endif">
                                <span>Launch Portal</span>
                                <i class="fa-solid fa-arrow-up-right-from-square ml-2 text-xs"></i>
                            </a>
                        @else
                            @if ($key === 'library')
                                <a href="{{ route('library.plans') }}"
                                   class="w-full flex items-center justify-center py-3 px-4 rounded-xl text-sm font-semibold bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 shadow-lg shadow-amber-500/20 transition duration-300 hover:scale-[1.02] active:scale-[0.98]">
                                    <i class="fa-solid fa-credit-card mr-2 text-xs"></i>
                                    <span>Subscribe / View Plans</span>
                                </a>
                            @else
                                <button type="button" 
                                        onclick="openRequestModal('{{ $portal['key'] }}', '{{ $portal['name'] }}', '{{ $portal['color'] }}')"
                                        class="w-full flex items-center justify-center py-3 px-4 rounded-xl text-sm font-semibold bg-slate-900 border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800/80 transition duration-300 hover:scale-[1.02] active:scale-[0.98]">
                                    <i class="fa-solid fa-lock mr-2 text-xs"></i>
                                    <span>Request Access</span>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Administrative Management Panel (Only visible to Authorized Admin/Editor Roles) -->
    @if(Auth::user()->hasRole('SUPERADMIN') || Auth::user()->hasRole('ADMIN') || Auth::user()->hasRole('EDITOR'))
        <div class="mt-16 border-t border-slate-900 pt-12">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-8">
                <div>
                    <h2 class="font-outfit text-2xl font-bold text-white tracking-tight flex items-center">
                        <i class="fa-solid fa-shield-halved text-indigo-500 mr-3"></i>
                        Administrative Management Console
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400">Review metrics, edit database registries, and maintain application entities.</p>
                </div>
                <div class="flex items-center space-x-3 mt-2 sm:mt-0">
                    <a href="{{ route('courses.list') }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-xl text-xs font-semibold text-slate-300 hover:text-white transition duration-200">
                        <i class="fa-solid fa-sliders mr-1.5"></i> Manage Console
                    </a>
                    <a href="{{ route('courses.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-xl text-xs font-semibold text-white shadow-md shadow-indigo-600/10 hover:scale-[1.02] active:scale-[0.98] transition duration-200">
                        <i class="fa-solid fa-circle-plus mr-1.5"></i> Add Course
                    </a>
                </div>
            </div>

            <!-- Stats Widgets Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                
                <!-- Users Counter -->
                <div class="rounded-2xl glassmorphism border border-slate-800/60 p-5 flex items-center space-x-4 hover:border-slate-700 transition duration-300 group">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/15 flex items-center justify-center text-lg group-hover:scale-105 transition-transform duration-300">
                        <i class="fa fa-user"></i>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Users</span>
                        <div class="flex items-baseline space-x-2">
                            <span class="text-2xl font-bold text-white font-outfit">{{ $usersCount }}</span>
                        </div>
                        <a href="{{ route('users.list') }}" class="text-[10px] text-blue-400 hover:underline mt-0.5 block">View users &rarr;</a>
                    </div>
                </div>

                <!-- Courses Counter -->
                <div class="rounded-2xl glassmorphism border border-slate-800/60 p-5 flex items-center space-x-4 hover:border-slate-700 transition duration-300 group">
                    <div class="w-10 h-10 rounded-xl bg-violet-500/10 text-violet-400 border border-violet-500/15 flex items-center justify-center text-lg group-hover:scale-105 transition-transform duration-300">
                        <i class="fa fa-book"></i>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Courses</span>
                        <div class="flex items-baseline space-x-2">
                            <span class="text-2xl font-bold text-white font-outfit">{{ $coursesCount }}</span>
                        </div>
                        <a href="{{ route('courses.list') }}" class="text-[10px] text-violet-400 hover:underline mt-0.5 block">View list &rarr;</a>
                    </div>
                </div>

                <!-- Programs Counter -->
                <div class="rounded-2xl glassmorphism border border-slate-800/60 p-5 flex items-center space-x-4 hover:border-slate-700 transition duration-300 group">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/15 flex items-center justify-center text-lg group-hover:scale-105 transition-transform duration-300">
                        <i class="fa fa-graduation-cap"></i>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Programs</span>
                        <div class="flex items-baseline space-x-2">
                            <span class="text-2xl font-bold text-white font-outfit">{{ $programsCount }}</span>
                        </div>
                        <a href="{{ route('programs.list') }}" class="text-[10px] text-cyan-400 hover:underline mt-0.5 block">View programs &rarr;</a>
                    </div>
                </div>

                <!-- Trainers Counter -->
                <div class="rounded-2xl glassmorphism border border-slate-800/60 p-5 flex items-center space-x-4 hover:border-slate-700 transition duration-300 group">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/15 flex items-center justify-center text-lg group-hover:scale-105 transition-transform duration-300">
                        <i class="fa fa-chalkboard-user"></i>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Trainers</span>
                        <div class="flex items-baseline space-x-2">
                            <span class="text-2xl font-bold text-white font-outfit">{{ $trainersCount }}</span>
                        </div>
                        <a href="{{ route('trainers.list') }}" class="text-[10px] text-rose-400 hover:underline mt-0.5 block">View trainers &rarr;</a>
                    </div>
                </div>

                <!-- Events Counter -->
                <div class="rounded-2xl glassmorphism border border-slate-800/60 p-5 flex items-center space-x-4 hover:border-slate-700 transition duration-300 group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/15 flex items-center justify-center text-lg group-hover:scale-105 transition-transform duration-300">
                        <i class="fa fa-calendar-alt"></i>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Events</span>
                        <div class="flex items-baseline space-x-2">
                            <span class="text-2xl font-bold text-white font-outfit">{{ $eventsCount }}</span>
                        </div>
                        <a href="{{ route('events.list') }}" class="text-[10px] text-emerald-400 hover:underline mt-0.5 block">View events &rarr;</a>
                    </div>
                </div>

                <!-- Blogs Counter -->
                <div class="rounded-2xl glassmorphism border border-slate-800/60 p-5 flex items-center space-x-4 hover:border-slate-700 transition duration-300 group">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/15 flex items-center justify-center text-lg group-hover:scale-105 transition-transform duration-300">
                        <i class="fa fa-newspaper"></i>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Blogs</span>
                        <div class="flex items-baseline space-x-2">
                            <span class="text-2xl font-bold text-white font-outfit">{{ $blogsCount }}</span>
                        </div>
                        <a href="{{ route('blogs.list') }}" class="text-[10px] text-amber-400 hover:underline mt-0.5 block">View blogs &rarr;</a>
                    </div>
                </div>

                <!-- Libraries Counter -->
                <div class="rounded-2xl glassmorphism border border-slate-800/60 p-5 flex items-center space-x-4 hover:border-slate-700 transition duration-300 group">
                    <div class="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-400 border border-teal-500/15 flex items-center justify-center text-lg group-hover:scale-105 transition-transform duration-300">
                        <i class="fa fa-book-open"></i>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Libraries</span>
                        <div class="flex items-baseline space-x-2">
                            <span class="text-2xl font-bold text-white font-outfit">{{ $librariesCount }}</span>
                        </div>
                        <a href="{{ route('libraries.list') }}" class="text-[10px] text-teal-400 hover:underline mt-0.5 block">View library &rarr;</a>
                    </div>
                </div>

                <!-- Subscriptions Counter -->
                <div class="rounded-2xl glassmorphism border border-slate-800/60 p-5 flex items-center space-x-4 hover:border-slate-700 transition duration-300 group">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/15 flex items-center justify-center text-lg group-hover:scale-105 transition-transform duration-300">
                        <i class="fa fa-envelope-open-text"></i>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Subscriptions</span>
                        <div class="flex items-baseline space-x-2">
                            <!-- Let's render subscriber count if any, otherwise fallback -->
                            <span class="text-2xl font-bold text-white font-outfit">{{ \App\Models\Subscription::count() }}</span>
                        </div>
                        <a href="{{ route('subscriptions.index') }}" class="text-[10px] text-purple-400 hover:underline mt-0.5 block">View emails &rarr;</a>
                    </div>
                </div>

            </div>
        </div>
    @endif
</div>

<!-- Access Request Modal Structure -->
<div id="requestModal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4 overflow-y-auto">
    <!-- Overlay backdrop -->
    <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" onclick="closeRequestModal()"></div>
    
    <!-- Modal Dialog -->
    <div class="relative w-full max-w-lg rounded-3xl glassmorphism border border-slate-800 shadow-2xl p-6 sm:p-8 overflow-hidden z-10">
        <!-- Colored light flare top edge -->
        <div id="modalAccentLine" class="absolute top-0 left-0 right-0 h-1"></div>

        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="font-outfit text-xl font-bold text-white flex items-center">
                    <i class="fa-solid fa-lock-open mr-2.5 text-indigo-400"></i>
                    Request Portal Access
                </h3>
                <p class="text-xs text-slate-400 mt-1" id="modalPortalSubtitle">Learning Management System Access Request</p>
            </div>
            <button type="button" onclick="closeRequestModal()" class="w-8 h-8 rounded-lg bg-white/5 border border-white/5 hover:bg-white/10 flex items-center justify-center text-slate-400 hover:text-white transition duration-200">
                <i class="fa fa-times"></i>
            </button>
        </div>

        <form id="requestAccessForm" onsubmit="submitAccessRequest(event)">
            <input type="hidden" id="requestPortalKey" name="portal_key" />
            
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">User Identity</label>
                    <div class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 text-sm">
                        <i class="fa fa-user text-xs"></i>
                        <span class="truncate">{{ Auth::user()->name }} ({{ Auth::user()->email }})</span>
                    </div>
                </div>

                <div>
                    <label for="requestReason" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Justification for Access</label>
                    <textarea id="requestReason" name="reason" rows="4" required
                              class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
                              placeholder="Please explain why you need access to this system portal (e.g. course registration number, department info, or staff role context)..."></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-slate-900/60 pt-4">
                <button type="button" onclick="closeRequestModal()" 
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white transition duration-200">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/20 hover:scale-[1.02] active:scale-[0.98] transition duration-200">
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openRequestModal(key, name, color) {
        document.getElementById('requestPortalKey').value = key;
        document.getElementById('modalPortalSubtitle').textContent = name + ' Access Authorization Request';
        
        // Update top line color accent
        const accent = document.getElementById('modalAccentLine');
        accent.className = 'absolute top-0 left-0 right-0 h-1';
        if (color === 'indigo') accent.classList.add('bg-gradient-to-r', 'from-indigo-500', 'to-purple-500');
        if (color === 'cyan') accent.classList.add('bg-gradient-to-r', 'from-cyan-500', 'to-teal-500');
        if (color === 'amber') accent.classList.add('bg-gradient-to-r', 'from-amber-500', 'to-orange-500');

        const modal = document.getElementById('requestModal');
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeRequestModal() {
        const modal = document.getElementById('requestModal');
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        document.getElementById('requestAccessForm').reset();
    }

    function submitAccessRequest(event) {
        event.preventDefault();
        const key = document.getElementById('requestPortalKey').value;
        const reason = document.getElementById('requestReason').value;

        // Simulate success with SweetAlert
        Swal.fire({
            title: 'Request Submitted!',
            text: 'Your request for access has been logged. The SITS administration will review your request shortly.',
            icon: 'success',
            background: '#0f172a',
            color: '#f8fafc',
            confirmButtonColor: '#4f46e5',
            customClass: {
                popup: 'rounded-3xl border border-slate-800 shadow-2xl backdrop-blur-md',
                confirmButton: 'rounded-xl px-5 py-2.5 text-sm font-semibold'
            }
        });

        closeRequestModal();
    }
</script>
@endsection