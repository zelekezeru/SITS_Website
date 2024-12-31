<x-guest-layout>
    <div class="container d-flex justify-content-center align-items-center vh-100 full-height ">
        <div class="card shadow w-50 p-4">
            <h2 class="text-center mb-4">Verify Your Email</h2>
            @if (session('status') == 'verification-link-sent')
                <div class="alert alert-success">
                    A new verification link has been sent to your email.
                </div>
            @endif
            <p class="text-center mb-4">Before proceeding, please check your email for a verification link.</p>
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-primary w-100 mb-3">Resend Verification Link</button>
            </form>
            <div class="text-center">
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                <form id="logout-form" method="POST" action="{{ route('logout') }}" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
