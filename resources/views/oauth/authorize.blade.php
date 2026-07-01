<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Authorize — SITS</title>
    {{-- Break out of any sandboxed iframe (e.g. Moodle OAuth2 SSO flow) --}}
    <script>try{if(window!==window.top){window.top.location.href=window.location.href;}}catch(e){}</script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #090d16; color: #cbd5e1; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #0f172a; border: 1px solid #1e293b; border-radius: 16px; padding: 40px; max-width: 420px; width: 100%; margin: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
        .logo { text-align: center; margin-bottom: 28px; }
        .logo h1 { color: #fff; font-size: 20px; font-weight: 800; letter-spacing: -0.5px; }
        .logo p { color: #64748b; font-size: 13px; margin-top: 4px; }
        h2 { color: #fff; font-size: 16px; font-weight: 700; margin-bottom: 6px; }
        .client { color: #818cf8; }
        .user-info { background: #1e293b; border-radius: 10px; padding: 12px 16px; margin: 16px 0; font-size: 13px; color: #94a3b8; }
        .user-info strong { color: #fff; }
        .scopes { background: #1e293b; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; }
        .scopes p { font-size: 11px; color: #94a3b8; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; }
        .scopes ul { list-style: none; }
        .scopes li { font-size: 13px; color: #cbd5e1; padding: 4px 0; display: flex; align-items: center; gap: 8px; }
        .scopes li::before { content: "✓"; color: #4ade80; font-weight: 800; }
        .actions { display: flex; gap: 12px; }
        .btn-approve { flex: 1; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; border: none; padding: 13px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; transition: opacity .2s; }
        .btn-deny { flex: 1; background: #1e293b; color: #94a3b8; border: 1px solid #334155; padding: 13px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; transition: background .2s; }
        .btn-approve:hover { opacity: 0.88; }
        .btn-deny:hover { background: #273344; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <h1>🎓 SITS Ethiopia</h1>
        <p>Shiloh International Theological Seminary</p>
    </div>

    <h2><span class="client">{{ $client->name }}</span> is requesting access to your account</h2>

    <div class="user-info">
        Authorizing as <strong>{{ $user->name }}</strong> &mdash; {{ $user->email }}
    </div>

    @if (count($scopes) > 0)
    <div class="scopes">
        <p>This application will be able to:</p>
        <ul>
            @foreach ($scopes as $scope)
            <li>{{ $scope->description }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="actions">
        {{-- Approve --}}
        <form method="post" action="{{ route('passport.authorizations.approve') }}" style="flex:1">
            @csrf
            <input type="hidden" name="state"      value="{{ $request->state }}">
            <input type="hidden" name="client_id"  value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn-approve" style="width:100%">✓ Authorize</button>
        </form>
        {{-- Deny --}}
        <form method="post" action="{{ route('passport.authorizations.deny') }}" style="flex:1">
            @csrf
            @method('DELETE')
            <input type="hidden" name="state"      value="{{ $request->state }}">
            <input type="hidden" name="client_id"  value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn-deny" style="width:100%">✗ Deny</button>
        </form>
    </div>
</div>
</body>
</html>
