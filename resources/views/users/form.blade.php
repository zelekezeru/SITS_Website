<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Full Name -->
    <div class="space-y-2">
        <label for="name" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Full Name</label>
        <input type="text" name="name" id="name" required
               class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
               placeholder="Full Name" value="{{ old('name', $user->name) }}">
        @error('name')
            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Email -->
    <div class="space-y-2">
        <label for="email" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Email Address</label>
        <input type="email" name="email" id="email" required
               class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
               placeholder="Email Address" value="{{ old('email', $user->email) }}">
        @error('email')
            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Phone -->
    <div class="space-y-2">
        <label for="phone" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Phone Number</label>
        <input type="text" name="phone" id="phone" required
               class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
               placeholder="Phone Number" value="{{ old('phone', $user->phone ?? $user->email) }}">
        @error('phone')
            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Role -->
    <div class="space-y-2">
        <label for="role" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">System Role</label>
        <select name="role" id="role" required
                class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none cursor-pointer">
            <option value="" disabled>Select a role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
        @error('role')
            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
