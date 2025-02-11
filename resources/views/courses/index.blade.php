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
                                <a href="{{ url('/') }}">Home</a>
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
                            <div class="course_head">
                                <img class="img-fluid" src="{{ asset('storage/' . $course->banner) }}"
                                    alt="{{ $course->title }}"
                                    style="width: 100%; height: 250px; object-fit: cover;" />
                            </div>
                            <div class="course_content">
                                <span class="price">{{ $course->amount_paid }} Br</span>
                                <span class="tag mb-3 d-inline-block">{{ $course->category }}</span>
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
                    </div>
                @endforeach
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
                                Take the first step toward success by registering with SITS!
                                Whether you're looking for top-quality training, expert-led courses, or
                                career-enhancing opportunities, SITS is here to equip you with the skills you need.
                            </p>
                        </div>
                        <div class="col clockinner1 clockinner">
                            <h4 class="days">🔹 Don’t wait! Register now and unlock your potential with SITS.</h4>

                            <ul class="smalltext align-left">
                                ✅ Easy Registration – Sign up in just a few clicks.<br>
                                ✅ Exclusive Access – Get personalized learning and Library resources.<br>
                                ✅ Expert-Led Programs – Learn from industry professionals.<br>
                                ✅ Flexible Learning – Study at your own pace, anytime, anywhere.<br>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 offset-lg-1">
                    <div class="register_form">
                        <h3>Enroll Now!</h3>
                        <p>Online Theological Education at SITS</p>

                        <form action="{{ route('subscriptions.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-12 form_group">
                                    <input name="name" placeholder="Your Name" required="" type="text" />
                                    <input name="phone" placeholder="Your Phone Number" required="" type="tel" />
                                    <input name="email" placeholder="Your Email Address"
                                        pattern="[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{1,63}$" required=""
                                        type="email" />
                                        <input name="address" placeholder="Your Living Address" required="" type="text" />
                                    <input type="text" name="type" required="" value="subscribe" hidden/>
                                </div>
                                <div class="col-lg-12 text-center">
                                    <button class="primary-btn" type="submit">Submit</button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--================ End Registration Area =================-->

@endsection
