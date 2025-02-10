<!--================ Start Header Menu Area =================-->
<header class="header_area">
    <div class="main_menu">
        <div class="search_input" id="search_input_box">
            <div class="container">
            <form class="d-flex justify-content-between" method="" action="">
                <input
                type="text"
                class="form-control"
                id="search_input"
                placeholder="Search Here"
                />
                <button type="submit" class="btn"></button>
                <span
                class="ti-close"
                id="close_search"
                title="Close Search"
                ></span>
            </form>
            </div>
        </div>

        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <!-- Brand and toggle get grouped for better mobile display -->
                <a class="navbar-brand logo_h" href="{{url("/")}}"><img src="{{ asset('img/logo.png') }}" alt="SITS"/></a>
                <button class="navbar-toggler" type="button"
                    data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="icon-bar"></span> <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <!-- Collect the nav links, forms, and other content for toggling -->
                <div class="collapse navbar-collapse offset" id="navbarSupportedContent">

                    <ul class="nav navbar-nav menu_nav ml-auto">
                        <li class="nav-item active">
                            <a class="nav-link" href="{{ route('home')}}">Home</a>
                        </li>
                        <li class="nav-item submenu dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" >Programs</a>
                            <ul class="dropdown-menu">
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('courses.index')}}">Courses</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('programs.index')}}" >Post Grad Programs</a>
                            </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('libraries.index')}}" >Libraries</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('blogs.index')}}">Blog</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('abouts.index')}}" class="nav-link" >About SITS</a>

                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('contacts.index')}}">Contact Us</a>
                        </li>

                        <li class="nav-item">
<<<<<<< Updated upstream
                            <a href="#" class="nav-link search" id="search">
                            <i class="ti-search"></i>
                            </a>
=======
                            <a class="nav-link" href="{{ route('elements.index')}}">Elements</a>
>>>>>>> Stashed changes
                        </li>

                        @if (Auth::check())
                            <li class="nav-item submenu dropdown">
                                <a class="nav-link dropdown-toggle profile-pic" data-toggle="dropdown" href="#" aria-expanded="false">
                                    <div class="avatar-sm">
                                        <img src="{{ auth()->user()->profile_image ? Storage::url(auth()->user()->profile_image) : asset('img/user.png') }}" alt="Profile Image" class="avatar-img rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" />
                                    </div>
                                    <span class="profile-username">
                                        <span class="fw-bold">{{ Auth::user()->name }}</span>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <li>
                                            <div class="user-box">
                                                <div class="avatar-lg">
                                                    <img class="avatar-img rounded" src="{{ auth()->user()->profile_image ? Storage::url(auth()->user()->profile_image) : asset('img/user.png') }}" alt="Profile Image" style="width: 80px; height: 80px; object-fit: cover;">
                                                </div>
                                                <div class="u-text">
                                                    <h4>{{ Auth::user()->name }}</h4>
                                                    <p class="text-muted">{{ Auth::user()->email }}</p>
                                                    <a href="{{ route('users.show', Auth::user()->id) }}" class="btn btn-xs btn-secondary btn-sm">View Profile</a>
                                                </div>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="{{ route('profile.edit') }}">My Profile</a>
                                            <a class="dropdown-item" href="#">Inbox</a>
                                            <a class="dropdown-item" href="#">Account Setting</a>
                                            <div class="dropdown-divider"></div>
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <x-responsive-nav-link :href="route('logout')"
                                                    onclick="event.preventDefault();
                                                                this.closest('form').submit();">
                                                    <div class="btn btn-sm btn-danger">
                                                        {{ __('Log Out') }}
                                                    </div>
                                                </x-responsive-nav-link>
                                            </form>
                                        </li>
                                    </div>
                                </ul>
                            </li>
                        @else
                            <li class="nav-item mt-3">
                                <a class="btn btn-warning btn-sm" href="{{ route('login') }}">Login</a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</header>
<!--================ End Header Menu Area =================-->
