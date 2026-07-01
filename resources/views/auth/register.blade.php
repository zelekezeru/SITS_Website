<x-guest-layout>
    <div class="relative max-w-4xl mx-auto">
        <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-indigo-500 to-purple-600 opacity-20 blur-xl"></div>
        
        <div class="relative glass-card rounded-3xl overflow-hidden grid md:grid-cols-12 border border-white/5 shadow-2xl">
            <!-- Left Branding Column -->
            <div class="md:col-span-5 bg-slate-950/60 p-8 flex flex-col justify-between items-center text-center border-r border-white/5 relative">
                <div class="absolute inset-0 bg-gradient-to-b from-indigo-950/20 to-transparent pointer-events-none"></div>
                
                <div class="relative z-10 w-full flex flex-col items-center justify-center my-auto space-y-6">
                    <a href="{{ url('/') }}" class="block">
                        <img src="{{ asset('img/logo.png') }}" alt="SITS Logo" class="h-16 w-auto hover:scale-105 transition duration-300" />
                    </a>
                    <div class="space-y-2">
                        <h3 class="text-xl font-bold text-white font-outfit">Join SITS Portal</h3>
                        <p class="text-xs text-slate-500 max-w-[200px] mx-auto leading-relaxed">
                            Create your account to register for courses, access the digital library, and manage your profile.
                        </p>
                    </div>
                </div>

                <div class="relative z-10 text-[10px] text-slate-650 font-semibold uppercase tracking-wider mt-6">
                    Since 1994 G.C
                </div>
            </div>

            <!-- Right Form Column -->
            <div class="md:col-span-7 p-8 sm:p-10 space-y-6">
                <div>
                    <h2 class="text-2xl font-bold text-white font-outfit mb-1">Create Account</h2>
                    <p class="text-xs text-slate-450">Fill in the fields below to sign up for SITS systems.</p>
                </div>

                @if ($firstUser)
                    <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/25 text-amber-300 text-xs leading-relaxed space-y-1">
                        <div class="flex items-center gap-1.5 font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                            System Notice
                        </div>
                        <p>You are the first user registering on SITS. The system will automatically grant you the <strong class="text-white uppercase">Superadmin</strong> role.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label for="name" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Full Name</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus placeholder="Abebe Kebede"
                                   class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow" />
                            @error('name')
                                <span class="text-xs text-rose-500 mt-1.5 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="abebe@example.com"
                                   class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow" />
                            @error('email')
                                <span class="text-xs text-rose-500 mt-1.5 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label for="password" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Password</label>
                            <input type="password" id="password" name="password" required placeholder="••••••••"
                                   class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow" />
                            @error('password')
                                <span class="text-xs text-rose-500 mt-1.5 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Confirm Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="••••••••"
                                   class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow" />
                        </div>
                    </div>

                    <div>
                        <label for="role" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Account Role</label>
                        @if ($firstUser)
                            <div class="px-4 py-3 rounded-xl bg-slate-950 border border-slate-850 text-indigo-400 text-sm font-bold uppercase tracking-wider">
                                Superadmin
                            </div>
                            <input type="hidden" name="role" value="SUPERADMIN" />
                        @else
                            <select id="role" name="role" required
                                    class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-650 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition input-glow cursor-pointer">
                                <option value="" disabled selected>Select registration role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <span class="text-xs text-rose-500 mt-1.5 block">{{ $message }}</span>
                            @enderror
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Profile Image (Optional)</label>
                        <label for="profile_image" class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-xl bg-slate-900/60 border border-dashed border-slate-800 hover:border-indigo-500/50 hover:bg-slate-900 text-slate-400 hover:text-white cursor-pointer transition">
                            <i class="ti-upload"></i>
                            <span class="text-sm font-medium" id="file-label-text">Choose file</span>
                            <input type="file" class="hidden" name="profile_image" id="profile_image" accept="image/*"
                                   onchange="document.getElementById('file-label-text').textContent = this.files[0] ? this.files[0].name : 'Choose file'">
                        </label>
                        @error('profile_image')
                            <span class="text-xs text-rose-500 mt-1.5 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" 
                            class="btn-glow w-full py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/25 transition duration-300">
                        Sign Up
                    </button>

                    <div class="text-center pt-2">
                        <p class="text-xs text-slate-500">
                            Already have an account? 
                            <a href="{{ route('login') }}" class="text-indigo-400 hover:underline font-semibold ml-1">Login</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    
                    const formData = new FormData(form);
                    const button = form.querySelector('button[type="submit"]');
                    const originalText = button.textContent;
                    button.textContent = 'Registering...';
                    button.disabled = true;

                    // Remove previous error messages
                    form.querySelectorAll('.text-rose-500').forEach(el => el.remove());

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                            },
                            body: formData
                        });

                        if (response.ok) {
                            window.location.href = '/portal';
                        } else {
                            const data = await response.json();
                            button.textContent = originalText;
                            button.disabled = false;

                            if (data.errors) {
                                Object.entries(data.errors).forEach(([field, messages]) => {
                                    const input = form.querySelector(`[name="${field}"]`);
                                    if (input) {
                                        const errSpan = document.createElement('span');
                                        errSpan.className = 'text-xs text-rose-500 mt-1.5 block';
                                        errSpan.textContent = messages[0];
                                        input.closest('div').appendChild(errSpan);
                                    }
                                });
                            } else {
                                alert(data.message || 'Registration failed.');
                            }
                        }
                    } catch (error) {
                        console.error(error);
                        button.textContent = originalText;
                        button.disabled = false;
                        alert('An error occurred. Please try again.');
                    }
                });
            }
        });
    </script>
</x-guest-layout>
