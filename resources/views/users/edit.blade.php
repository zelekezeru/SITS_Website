<x-admin-layout>
    <div class="container d-flex justify-content-center align-items-center vh-100 full-height">
        <div class="row w-50 shadow rounded-4 bg-white p-4">
            <h2 class="mb-4 text-info text-center">Edit User</h2>
            <form method="POST" action="{{ route('users.update', $user->id) }}">
                @csrf
                @method('PUT') <!-- Use PUT method for updates -->

                <!-- Full Name -->
                <div class="form-group mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                </div>

                <!-- Email -->
                <div class="form-group mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}" required>
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

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary w-100 mb-3">Update User</button>
                
                <!-- Back Link -->
                <div class="text-center">
                    <a href="{{ route('users.list') }}">Back to User List</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
