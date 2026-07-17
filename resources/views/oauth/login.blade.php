<!DOCTYPE html>
<html lang="en" style="background:#090d16;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In — SITS Portal</title>

    {{-- ═══════════════════════════════════════════════════════
         IFRAME / SANDBOX FRAME-BREAKER
         When Moodle (or any embed) opens this in a sandboxed
         iframe we break out to the top window so form submission,
         session cookies and the OAuth redirect chain all work.
    ═══════════════════════════════════════════════════════ --}}
    <script>
        try {
            if (window !== window.top) {
                window.top.location.href = window.location.href;
            }
        } catch (e) {
            // Automatic break-out blocked — reveal the manual link on load.
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('frame-notice');
                if (el) el.style.display = 'block';
            });
        }
    </script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: #090d16;
            color: #cbd5e1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .shell { position: relative; width: 100%; max-width: 860px; }
        .shell-glow {
            position: absolute; inset: -1px;
            border-radius: 26px;
            background: linear-gradient(120deg, #4f46e5, #7c3aed);
            opacity: 0.18; filter: blur(22px); z-index: 0;
        }

        .card {
            position: relative; z-index: 1;
            display: grid;
            grid-template-columns: 5fr 7fr;
            background: #0f172a;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
        }

        /* Left branding column */
        .brand {
            background: #0b1120;
            border-right: 1px solid rgba(255,255,255,0.05);
            padding: 40px 32px;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-align: center; gap: 20px;
        }
        .brand img { height: 60px; width: auto; }
        .brand h1 { color: #fff; font-size: 20px; font-weight: 800; }
        .brand p { color: #64748b; font-size: 12px; line-height: 1.6; max-width: 210px; }
        .brand .since { margin-top: 6px; color: #475569; font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }

        /* Right form column */
        .panel { padding: 44px 40px; }
        .panel h2 { color: #fff; font-size: 24px; font-weight: 800; }
        .panel .subtitle { color: #64748b; font-size: 13px; margin-top: 4px; margin-bottom: 26px; }

        .frame-notice {
            display: none;
            background: #1e3a5f; border: 1px solid #3b82f6; border-radius: 10px;
            padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #93c5fd;
        }
        .frame-notice a { color: #60a5fa; font-weight: 700; text-decoration: underline; }

        .status {
            background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2);
            border-radius: 10px; padding: 12px 16px; margin-bottom: 20px;
            font-size: 13px; color: #34d399; font-weight: 600;
        }
        .error {
            background: #2d1515; border: 1px solid #7f1d1d; border-radius: 10px;
            padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #f87171;
        }

        .field { margin-bottom: 18px; }
        .field-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 7px; }
        label { display: block; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; }
        .forgot { font-size: 11px; color: #818cf8; text-decoration: none; text-transform: none; letter-spacing: 0; }
        .forgot:hover { text-decoration: underline; }
        input[type=email], input[type=password] {
            width: 100%; background: #0a0f1f; border: 1px solid #1e293b; border-radius: 10px;
            padding: 12px 14px; color: #f1f5f9; font-size: 14px; outline: none; transition: border-color .2s;
        }
        input:focus { border-color: #4f46e5; }

        .remember { display: flex; align-items: center; gap: 8px; margin: 4px 0 22px; }
        .remember input { width: 15px; height: 15px; accent-color: #4f46e5; cursor: pointer; }
        .remember label { text-transform: none; letter-spacing: 0; color: #94a3b8; font-weight: 500; font-size: 13px; cursor: pointer; }

        .btn {
            width: 100%; border: none; cursor: pointer;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff; padding: 13px; border-radius: 10px;
            font-weight: 700; font-size: 15px; transition: opacity .2s;
        }
        .btn:hover { opacity: 0.9; }

        .foot { text-align: center; margin-top: 22px; font-size: 13px; color: #64748b; }
        .foot a { color: #818cf8; text-decoration: none; font-weight: 600; }
        .foot a:hover { text-decoration: underline; }

        @media (max-width: 640px) {
            .card { grid-template-columns: 1fr; }
            .brand { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 32px; }
            .panel { padding: 32px 24px; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="shell-glow"></div>
        <div class="card">
            <!-- Branding -->
            <aside class="brand">
                <a href="{{ url('/') }}"><img src="{{ asset('img/logo.png') }}" alt="SITS Logo"></a>
                <div>
                    <h1>SITS Portal Hub</h1>
                    <p>Access theological systems, resource databases, and institutional registries.</p>
                </div>
                <div class="since">Since 1994 G.C</div>
            </aside>

            <!-- Form -->
            <main class="panel">
                <h2>Welcome Back</h2>
                <p class="subtitle">Please sign in to access your portal dashboard.</p>

                {{-- Shown only if the frame-breaker script fails --}}
                <div id="frame-notice" class="frame-notice">
                    ⚠️ This login page is loading inside a restricted frame.
                    <a href="{{ url()->current() }}" target="_top">Click here to open it fully →</a>
                </div>

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="error">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="field">
                        <div class="field-head">
                            <label for="email">Email Address</label>
                        </div>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               required autofocus autocomplete="username" placeholder="name@example.com">
                    </div>

                    <div class="field">
                        <div class="field-head">
                            <label for="password">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="forgot">Forgot Password?</a>
                            @endif
                        </div>
                        <input type="password" id="password" name="password"
                               required autocomplete="current-password" placeholder="••••••••">
                    </div>

                    <div class="remember">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <label for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="btn">Sign In →</button>

                    @if (Route::has('register'))
                        <p class="foot">
                            Don't have an account yet?
                            <a href="{{ route('register') }}">Sign Up</a>
                        </p>
                    @endif
                    <p class="foot"><a href="{{ url('/') }}">← Back to SITS Homepage</a></p>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
