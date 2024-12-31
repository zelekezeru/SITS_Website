<x-guest-layout>
    <div class="container d-flex justify-content-center align-items-center vh-100 full-height ">
        <div class="row w-75 shadow rounded-4">

            <div class="col-md-6 p-4 bg-primary text-white rounded-start">
                <a href="{{ url('/') }}" class="d-flex justify-content-center align-items-center mt-5">
                    <img src="{{ asset('img/logo.png') }}" alt="navbar brand"
                        height="100" />
                </a>
                <h1 class="form-title text-center">Welcome to Shiloh International Theological Seminary</h1>
            </div>

            <div class="col-md-6 p-4 bg-white rounded-end">
                <h2 class="mb-4 text-xl text-center">Sign Up</h2>
                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">Sign Up</button>
                    <div class="text-center">
                        <a href="{{ route('login') }}">Already have an account? Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
