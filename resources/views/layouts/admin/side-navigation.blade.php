<!-- Sidebar -->
<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
            <a href="{{ url('/') }}" class="logo">
                <img src="{{ asset('img/logo.png') }}" alt="navbar brand" class="navbar-brand"
                    height="50" />
            </a>
            <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                    <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>
            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
        <!-- End Logo Header -->
    </div>
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">
                <li class="nav-item active">
                    <a href="{{ url('/dashboard') }}">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p></span>
                    </a>
                </li>
                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Components</h4>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Course">
                        <i class="fas fa-layer-group"></i>
                        <p>Course</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Course">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('courses.create') }}">
                                    Add Course
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('courses.index') }}">
                                    Manage Course
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Programs">
                        <i class="fas fa-pen-square"></i>
                        <p>Programs</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Programs">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('programs.create') }}">
                                    Add Programs
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('programs.index') }}">
                                    Manage Programs
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Events">
                        <i class="fas fa-th-list"></i>
                        <p>Events</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Events">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('events.create') }}">
                                    Add Events
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('events.index') }}">
                                    Manage Events
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Conatact">
                        <i class="fas fa-phone"></i>
                        <p>Contact</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Conatact">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="">
                                    Add Conatact
                                </a>
                            </li>
                            <li>
                                <a href="">
                                    Manage Conatact
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Blog">
                        <i class="fas fa-th-list"></i>
                        <p>Blog</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Blog">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="">
                                    Add Blog
                                </a>
                            </li>
                            <li>
                                <a href="">
                                    Manage Blog
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                
                <li class="nav-item">
                    <a href="{{route('profile.edit')}}">
                        <i class="fas fa-user"></i>
                        <p>My Profile</p>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- End Sidebar -->
