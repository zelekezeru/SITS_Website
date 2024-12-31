<x-guest-layout>
    <div class="container d-flex justify-content-center align-items-center vh-100 full-height ">
        <div class="card shadow w-50 p-4">
            <h2 class="mb-4 text-info text-center">Forgot Password</h2>
            <p class="text-center">Enter your email address, and we'll send you a reset link.</p>
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
            </form>
            <div class="text-center mt-3">
                <a href="{{ route('login') }}">Back to Login</a>
            </div>
        </div>
    </div>
</x-guest-layout>
