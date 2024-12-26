@extends('layouts.app')

@section('main_content')
    <!--================Home Banner Area =================-->
    <section class="banner_area">
        <div class="banner_inner d-flex align-items-center">
            <div class="overlay"></div>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="banner_content text-center">
                            <h2>Courses</h2>
                            <div class="page_link">
                                <a href="">Home</a>
                                <a href="{{ route('courses.index') }}">Courses</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--================End Home Banner Area =================-->

    <!--================ Start Popular Courses Area =================-->
    <div class="popular_courses section_gap_top">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="main_title">
                        <h2 class="mb-3">Our Popular Courses</h2>
                        <p>
                            Replenish man have thing gathering lights yielding shall you
                        </p>
                    </div>
                </div>
            </div>
            <div class="row">
                @foreach ($courses as $course)
                    <div class="col-lg-4 col-md-6">
                        <div class="single_course">
                            <div class="course_content">
                                <span class="price">${{ $course->amount_paid }}</span>
                                <span class="tag mb-4 d-inline-block">{{ $course->category }}</span>
                                <h4 class="mb-3">
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
                                    <div class="mt-lg-0 mt-3">
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
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <!--================ End Popular Courses Area =================-->

    <!--================ Start Registration Area =================-->
    <div class="section_gap registration_area">
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

    <!--================ Start Feature Area =================-->
    <section class="feature_area section_gap">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="main_title">
                        <h2 class="mb-3">Awesome Feature</h2>
                        <p>
                            Replenish man have thing gathering lights yielding shall you
                        </p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="single_feature">
                        <div class="icon"><span class="flaticon-student"></span></div>
                        <div class="desc">
                            <h4 class="mt-3 mb-2">Scholarship Facility</h4>
                            <p>
                                One make creepeth, man bearing theira firmament won't great
                                heaven
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="single_feature">
                        <div class="icon"><span class="flaticon-book"></span></div>
                        <div class="desc">
                            <h4 class="mt-3 mb-2">Sell Online Course</h4>
                            <p>
                                One make creepeth, man bearing theira firmament won't great
                                heaven
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="single_feature">
                        <div class="icon"><span class="flaticon-earth"></span></div>
                        <div class="desc">
                            <h4 class="mt-3 mb-2">Global Certification</h4>
                            <p>
                                One make creepeth, man bearing theira firmament won't great
                                heaven
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--================ End Feature Area =================-->
@endsection
