<!-- Sidebar -->
<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
            <a href="{{ url('/') }}" class="logo">
                <img src="{{ asset('img/logo.png') }}" alt="navbar brand" class="navbar-brand" height="50" />
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
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Components</h4>
                </li>

                {{-- ONLY FOR ADMIN USERS --}}
                @if(request()->user()->hasRole('ADMIN'))
                    <li class="nav-item">
                        <a href="{{route('users.list')}}">
                            <i class="fas fa-users-cog"></i>
                            <p>Users</p>
                        </a>
                    </li>
                @endif

                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Course">
                        <i class="fas fa-book-open"></i>
                        <p>Course</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Course">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('courses.list') }}">
                                    <i class="fas fa-th-list"></i> Manage Course
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('courses.create') }}">
                                    <i class="fas fa-file-circle-plus"></i> Add Course
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Programs">
                        <i class="fas fa-university"></i>
                        <p>Program</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Programs">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('programs.list') }}">
                                    <i class="fas fa-th-list"></i> Manage Programs
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('programs.create') }}">
                                    <i class="fas fa-folder-plus"></i> Add Programs
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Events">
                        <i class="fas fa-calendar-check"></i>
                        <p>Event</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Events">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('events.list') }}">
                                    <i class="fas fa-calendar-day"></i> Manage Events
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('events.create') }}">
                                    <i class="fas fa-calendar-plus"></i> Add Events
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Trainers">
                        <i class="fas fa-chalkboard-user"></i>
                        <p>Trainer</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Trainers">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('trainers.list') }}">
                                    <i class="fas fa-users"></i> Manage Trainers
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('trainers.create') }}">
                                    <i class="fas fa-user-plus"></i> Add Trainers
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Blog">
                        <i class="fas fa-newspaper"></i>
                        <p>Blog</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Blog">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('blogs.list') }}">
                                    <i class="fas fa-clipboard-list"></i> Manage Blog
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('blogs.create') }}">
                                    <i class="fas fa-pen-square"></i> Add Blog
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Library">
                        <i class="fas fa-book-reader"></i>
                        <p>Library</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Library">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('libraries.list') }}">
                                    <i class="fas fa-folder-open"></i> Manage Libraries
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('libraries.create') }}">
                                    <i class="fas fa-plus-square"></i> Add Library
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#User">
                        <i class="fas fa-book-reader"></i>
                        <p>User</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="User">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('users.list') }}">
                                    <i class="fas fa-folder-open"></i> Manage Users
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('users.create') }}">
                                    <i class="fas fa-plus-square"></i> Add User
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="{{ route('contacts.list') }}">
                        <i class="fas fa-address-book"></i>
                        <p>Contact</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('subscriptions.index') }}">
                        <i class="fas fa-envelope-open-text"></i>
                        <p>Subscription</p>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- End Sidebar -->
