
<div class="row">

    <!-- Full Name -->
    <div class="form-group mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required>
    </div>

    <!-- Email -->
    <div class="form-group mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="text" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}" required>
    </div>

    <!-- Phone -->
    <div class="form-group mb-3">
        <label for="phone" class="form-label">Phone</label>
        <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $user->email) }}" required>
    </div>

    <!-- Role -->
    <div class="form-group mb-4">
        <label for="role" class="form-label">Role</label>
        <select class="form-control" id="role" name="role" required>
            <option value="" disabled>Select a role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

