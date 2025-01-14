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
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#PerformanceDropdown" aria-expanded="false">
                        <i class="fas fa-chart-line"></i>
                        <p>Performance Management</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="PerformanceDropdown">
                        <ul class="nav nav-collapse">
                            <li class="nav-item">
                                <a data-bs-toggle="collapse" href="#TaskManagementDropdown" aria-expanded="false">
                                    <i class="fas fa-tasks"></i> Task Management
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse" id="TaskManagementDropdown">
                                    <ul class="nav nav-collapse">
                                        <li>
                                            <a href="{{ route('tasks.list') }}">
                                                <i class="fas fa-edit"></i> Manage Tasks
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('tasks.create') }}">
                                                <i class="fas fa-plus-circle"></i> Add Task
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </div>
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
                            <i class="fas fa-user-circle"></i>
                            <p>Users</p>
                        </a>
                    </li>

                @endif

                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Course">
                        <i class="fas fa-book"></i>
                        <p>Course</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Course">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('courses.list') }}">
                                    <i class="fas fa-list"></i>Manage Course
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('courses.create') }}">
                                    <i class="fas fa-plus"></i>Add Course
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Programs">
                        <i class="fas fa-graduation-cap"></i>
                        <p>Program</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Programs">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('programs.list') }}">
                                    <i class="fas fa-list"></i>Manage Programs
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('programs.create') }}">
                                    <i class="fas fa-plus"></i>Add Programs
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Events">
                        <i class="fas fa-calendar-alt"></i>
                        <p>Event</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Events">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('events.list') }}">
                                    <i class="fas fa-list"></i>Manage Events
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('events.create') }}">
                                    <i class="fas fa-plus"></i>Add Events
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Conatact">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <p>Trainers</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Conatact">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('trainers.list') }}">
                                    <i class="fas fa-list"></i>Manage Trainers
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('trainers.create') }}">
                                    <i class="fas fa-plus"></i>Add Trainers
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
                                    <i class="fas fa-list"></i>Manage Blog
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('blogs.create') }}">
                                    <i class="fas fa-plus"></i>Add Blog
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Contact">
                        <i class="fas fa-newspaper"></i>
                        <p>Contact</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Contact">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('contacts.list') }}">
                                    <i class="fas fa-list"></i>Manage Contact
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('contacts.list') }}">
                                    <i class="fas fa-plus"></i>Add Contact
                                </a>
                            </li>
                        </ul>
                    </div>
                </li><li class="nav-item">
                    <a data-bs-toggle="collapse" href="#Subscription">
                        <i class="fas fa-newspaper"></i>
                        <p>Subscription</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse" id="Subscription">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ route('subscriptions.index') }}">
                                    <i class="fas fa-list"></i>Manage Subscriptions
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- End Sidebar -->
