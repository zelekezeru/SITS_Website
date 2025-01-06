@extends('layouts.app')

@section('main_content')
    <!--================ Start Home Banner Area =================-->
    <section class="home_banner_area">
        <div class="banner_inner">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="banner_content text-center animate__animated animate__backInUp">
                            <p class="text-uppercase">
                                Best online education service In the world
                            </p>
                            <h2 class="text-uppercase mt-4 mb-5">
                                One Step Ahead This Season
                            </h2>
                            <div>
                                <a href="#" class="primary-btn2 mb-3 mb-sm-0">learn more</a>
                                <a href="#" class="primary-btn ml-sm-3 ml-0">see course</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--================ End Home Banner Area =================-->

    <!--================ Start Feature Area =================-->

    @include('abouts.values')

    <!--================ End Feature Area =================-->

    <!--================ Start Popular Courses Area =================-->
    <div class="popular_courses">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="main_title" data-aos="fade-up" data-aos-delay="200">
                        <h2 class="mb-3">Our Popular Courses</h2>
                        <p>
                            Replenish man have thing gathering lights yielding shall you
                        </p>
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- single course -->
                <div class="col-lg-12">
                    <div class="owl-carousel active_course">
                        @if ($courses->isEmpty())
                            <div class="col-lg-12">
                                <div class="text-center">
                                    <h3>No courses available at the moment</h3>
                                </div>
                            @else
                                @foreach ($courses as $course)
                                    <div class="single_course">
                                        <div class="course_head">
                                            <img class="img-fluid" src="{{ asset('storage/' . $course->banner) }}"
                                                alt="{{ $course->title }}"
                                                style="width: 100%; height: 250px; object-fit: cover;" />
                                        </div>
                                        <div class="course_content">
                                            <span class="price">${{ $course->amount_paid }}</span>
                                            <span class="tag mb-2 d-inline-block">{{ $course->category }}</span>
                                            <h4 class="mb-2">
                                                <a href="{{ route('courses.show', $course->id) }}">{{ $course->title }}</a>
                                            </h4>
                                            <p>
                                                {{ $course->description }}
                                            </p>
                                            <div
                                                class="course_meta d-flex justify-content-lg-between align-items-lg-center flex-lg-row flex-column mt-4">
                                                <div class="authr_meta">
                                                    <img src="img/courses/author1.png" alt="" />
                                                    <span class="d-inline-block ml-2">Instructor</span>
                                                </div>
                                                <div class="mt-lg-0 mt-2">
                                                    <span class="meta_info mr-4">
                                                        <a href="#"> <i class="ti-user mr-2"></i>25 </a>
                                                    </span>
                                                    <span class="meta_info">
                                                        <a href="#"> <i class="ti-heart mr-2"></i>35 </a>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--================ End Popular Courses Area =================-->

    <!--================ Start Registration Area =================-->
    <div class="section_gap registration_area" data-aos="zoom-in">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="row clock_sec clockdiv" id="clockdiv">
                        <div class="col-lg-12">
                            <h1 class="mb-3">Register Now</h1>
                            <p>
                                There is a moment in the life of any aspiring astronomer that
                                it is time to buy that first telescope. It’s exciting to think
                                about setting up your own viewing station.
                            </p>
                        </div>
                        <div class="col clockinner1 clockinner">
                            <h1 class="days">150</h1>
                            <span class="smalltext">Days</span>
                        </div>
                        <div class="col clockinner clockinner1">
                            <h1 class="hours">23</h1>
                            <span class="smalltext">Hours</span>
                        </div>
                        <div class="col clockinner clockinner1">
                            <h1 class="minutes">47</h1>
                            <span class="smalltext">Mins</span>
                        </div>
                        <div class="col clockinner clockinner1">
                            <h1 class="seconds">59</h1>
                            <span class="smalltext">Secs</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 offset-lg-1">
                    <div class="register_form">
                        <h3>Courses for Free</h3>
                        <p>It is high time for learning</p>
                        <form class="form_area" id="myForm" action="mail.html" method="post">
                            <div class="row">
                                <div class="col-lg-12 form_group">
                                    <input name="name" placeholder="Your Name" required="" type="text" />
                                    <input name="name" placeholder="Your Phone Number" required="" type="tel" />
                                    <input name="email" placeholder="Your Email Address"
                                        pattern="[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{1,63}$" required=""
                                        type="email" />
                                </div>
                                <div class="col-lg-12 text-center">
                                    <button class="primary-btn">Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--================ End Registration Area =================-->

    <!--================ Start Trainers Area =================-->
    <section class="trainer_area section_gap_top">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="main_title" data-aos="fade-up" data-aos-delay="200">
                        <h2 class="mb-3">Our Expert Trainers</h2>
                        <p>
                            Replenish man have thing gathering lights yielding shall you
                        </p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center d-flex align-items-center">
                @if ($trainers->isEmpty())
                    <div class="col-lg-12">
                        <div class="text-center">
                            <h3>No Trainers available at the moment</h3>
                        </div>
                    @else
                        @foreach ($trainers as $trainer)
                            <div class="col-lg-3 col-md-6 col-sm-12 single-trainer" data-aos="fade-up"
                                data-aos-delay="200">
                                <div class="thumb d-flex justify-content-sm-center">
                                    <img class="img-fluid" src="{{ asset('storage/' . $trainer->image) }}"
                                        alt="{{ $trainer->name }}" alt="" />
                                </div>
                                <div class="meta-text text-sm-center">
                                    <h4>{{ $trainer->name }}</h4>
                                    <p class="designation">{{ $trainer->position }}</p>
                                    <div class="mb-4">
                                        <p>
                                            {{ $trainer->description }}
                                        </p>
                                    </div>
                                    <div class="align-items-center justify-content-center d-flex">
                                        <a href="#"><i class="ti-facebook"></i></a>
                                        <a href="#"><i class="ti-twitter"></i></a>
                                        <a href="#"><i class="ti-linkedin"></i></a>
                                        <a href="#"><i class="ti-pinterest"></i></a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                @endif

            </div>
        </div>
    </section>
    <!--================ End Trainers Area =================-->

    <!--================ Start Events Area =================-->

    <div class="events_area" data-aos="zoom-in" data-aos-delay="200">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="main_title">
                        <h2 class="mb-3 text-white">Upcoming Events</h2>
                        <p>Don't miss out on our latest events!</p>
                    </div>
                </div>
            </div>
            <div class="row" data-aos="fade-up" data-aos-delay="300">
                @if ($events->isEmpty())
                    <div class="col-lg-12">
                        <div class="text-center">
                            <h3 class=" text-white">No events available at the moment</h3>
                        </div>
                    </div>
                @else
                    @foreach ($events as $event)
                        <div class="col-lg-6 col-md-6">
                            <div class="single_event position-relative">
                                <!-- Event Thumbnail -->
                                <div class="event_thumb">
                                    <img src="{{ asset('storage/' . $event->banner) }}" alt="{{ $event->title }}"
                                        class="img-fluid" style="height: 250px; object-fit: cover;" />
                                </div>

                                <!-- Event Details -->
                                <div class="event_details">
                                    <div class="d-flex mb-4">
                                        <div class="date">
                                            <span>{{ \Carbon\Carbon::parse($event->date)->format('d') }}</span>
                                            {{ \Carbon\Carbon::parse($event->date)->format('M') }}
                                        </div>
                                        <div class="time-location">
                                            <p>
                                                <span class="ti-time mr-2"></span>
                                                {{ \Carbon\Carbon::parse($event->start_time)->format('h:i A') }} -
                                                {{ \Carbon\Carbon::parse($event->end_time)->format('h:i A') }}
                                            </p>
                                            <p>
                                                <span class="ti-location-pin mr-2"></span>
                                                {{ $event->location }}
                                            </p>
                                        </div>
                                    </div>
                                    <p>{{ $event->description }}</p>
                                    <a href="{{ route('events.show', $event->id) }}"
                                        class="primary-btn rounded-0 mt-3">View Details</a>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <!-- View All Events Link -->
                    <div class="col-lg-12">
                        <div class="text-center pt-lg-5 pt-3">
                            <a href="{{ route('events.index') }}" class="event-link">
                                View All Events <img src="img/next.png" alt="" />
                            </a>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <!--================ End Events Area =================-->



    {{-- Testimonials --}}

    @include('contacts.testimonials')

@endsection
