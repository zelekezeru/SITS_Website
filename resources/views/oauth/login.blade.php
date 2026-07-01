<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In — SITS</title>
    {{-- Break out of any sandboxed iframe immediately --}}
    <script>
        try {
            if (window !== window.top) {
                window.top.location.href = window.location.href;
            }
        } catch(e) {
            // If cross-origin sandbox blocks top navigation, show the manual link below
            document.addEventListener('DOMContentLoaded', function() {
                var el = document.getElementById('frame-notice');
                if (el) el.style.display = 'block';
            });
        }
    </script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #090d16; color: #cbd5e1; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #0f172a; border: 1px solid #1e293b; border-radius: 16px; padding: 40px; max-width: 400px; width: 100%; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
        .logo { text-align: center; margin-bottom: 28px; }
        .logo-icon { width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #4f46e5, #7c3aed); display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800; color: #fff; margin: 0 auto 12px; }
        h2 { color: #fff; font-size: 22px; font-weight: 800; text-align: center; }
        .subtitle { color: #64748b; font-size: 13px; text-align: center; margin-top: 4px; margin-bottom: 28px; }
        .frame-notice { display: none; background: #1e3a5f; border: 1px solid #3b82f6; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #93c5fd; }
        .frame-notice a { color: #60a5fa; font-weight: 700; text-decoration: underline; }
        .error { background: #2d1515; border: 1px solid #7f1d1d; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #f87171; }
        .field { margin-bottom: 18px; }
        label { display: block; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 7px; }
        input[type=email], input[type=password] { width: 100%; background: #0a0f1f; border: 1px solid #1e293b; border-radius: 10px; padding: 12px 14px; color: #f1f5f9; font-size: 14px; outline: none; transition: border-color .2s; }
        input:focus { border-color: #4f46e5; }
        .btn { width: 100%; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; border: none; padding: 13px; border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer; margin-top: 8px; transition: opacity .2s; }
        .btn:hover { opacity: 0.88; }
        .back { display: block; text-align: center; margin-top: 18px; font-size: 13px; color: #64748b; }
        .back a { color: #818cf8; text-decoration: none; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-icon">S</div>
        <h2>Welcome Back</h2>
        <p class="subtitle">Sign in to continue to SITS</p>
    </div>

    {{-- Show if frame-breaker fails --}}
    <div id="frame-notice" class="frame-notice">
        This page is loading in a restricted frame.
        <a href="{{ url()->current() }}" target="_top">Click here to open it fully</a>.
    </div>

    @if ($errors->any())
    <div class="error">
        {{ $errors->first() }}
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="field">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@sits.edu.et">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="••••••••">
        </div>
        <input type="hidden" name="remember" value="1">
        <button type="submit" class="btn">Sign In →</button>
    </form>

    <span class="back"><a href="{{ url('/') }}">← Back to SITS Homepage</a></span>
</div>
</body>
</html>
