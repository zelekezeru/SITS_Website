<x-guest-layout>
    <div class="container d-flex justify-content-center align-items-center vh-100 full-height ">
        <div class="row w-75 shadow rounded-4">

            <div class="col-md-6 cardbg text-white rounded-start">
                <img src="{{asset('img/banner/register.png')}}" alt="" srcset="">
            </div>

            <div class="col-md-6 p-4 bg-white rounded-end">
                <h2 class="mb-4 text-info text-center">Sign Up</h2>

                @if ($firstUser)
                    <h6 class="mb-4 text-warning text-center">You're the first User to the System, You'll automatically be assigned to the <br> <span class="text-success">Role of Superadmin!</span></h6>
                @endif

                <form method="POST" action="{{ route('register') }}"  enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name"  value="{{ old('category') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('category') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>

                    <div class="form-group">
                        <label for="role" class="form-label">Role</label>
                        @if ($firstUser)
                            <input type="text" class="form-control" readonly name="role" value="SUPERADMIN">
                        @else
                            <select class="form-control mb-3" id="role" name="role" required>
                                <option value="" disabled selected>Choose a role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="profile_image">Profile Image (Optional)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="profile_image" id="profile_image" accept="image/*">
                            <label class="custom-file-label" for="profile_image">
                                <i class="fa fa-upload"></i> <i class="ti-twitter"></i> Choose file
                            </label>
                        </div>
                        @error('profile_image')
                            <span class="text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3 mt-3">Sign Up</button>
                    <div class="text-center">
                        <a href="{{ route('login') }}">Already have an account? Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>

