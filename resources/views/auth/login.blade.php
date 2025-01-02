<x-guest-layout>
    <div class="container d-flex justify-content-center align-items-center full-height">
        <div class="row w-75 shadow rounded-5">
            
            <div class="col-md-6 cardbg text-white rounded-start">
                <img src="{{asset('img/banner/knowledge.jpg')}}" alt="" srcset="">
            </div>
            

            <div class="col-md-6 p-4 bg-white rounded-end">
                <h2 class="mb-4 text-info text-center">Login</h2>
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember Me</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                    <div class="text-center">
                        <a href="{{ route('register') }}">New User? Sign Up</a> | 
                        <a href="{{ route('password.request') }}">Forgot Password?</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>

