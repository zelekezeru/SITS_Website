<x-guest-layout>
    <div class="container d-flex justify-content-center align-items-center vh-100 full-height ">
        <div class="card shadow w-50 p-4">
            <h2 class="text-center mb-4">Confirm Password</h2>
            <p class="text-center mb-3">For your security, please confirm your password to proceed.</p>
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Confirm Password</button>
            </form>
            <div class="text-center mt-3">
                <a href="{{ route('password.request') }}">Forgot your password?</a>
            </div>
        </div>
    </div>
</x-guest-layout>
