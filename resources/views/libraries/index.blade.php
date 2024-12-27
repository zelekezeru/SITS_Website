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
                <h2>Libraries</h2>
                <div class="page_link">
                  <a href="">Home</a>
                  <a href="{{ route('libraries.index')}}">Libraries</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!--================End Home Banner Area =================-->

    <!--================ Start Popular libraries Area =================-->
    <div class="popular_libraries section_gap_top">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-5">
            <div class="main_title">
              <h2 class="mb-3">Our Popular libraries</h2>
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
              <div class="single_course">
                <div class="course_head">
                  <img class="img-fluid" src="img/libraries/c1.jpg" alt="" />
                </div>
                <div class="course_content">
                  <span class="price">$25</span>
                  <span class="tag mb-4 d-inline-block">design</span>
                  <h4 class="mb-3">
                    <a href="course-details.html">Custom Product Design</a>
                  </h4>
                  <p>
                    One make creepeth man bearing their one firmament won't fowl
                    meat over sea
                  </p>
                  <div
                    class="course_meta d-flex justify-content-lg-between align-items-lg-center flex-lg-row flex-column mt-4"
                  >
                    <div class="authr_meta">
                      <img src="img/libraries/author1.png" alt="" />
                      <span class="d-inline-block ml-2">Cameron</span>
                    </div>
                    <div class="mt-lg-0 mt-3">
                      <span class="meta_info mr-4">
                        <a href="#"> <i class="ti-user mr-2"></i>25 </a>
                      </span>
                      <span class="meta_info"
                        ><a href="#"> <i class="ti-heart mr-2"></i>35 </a></span
                      >
                    </div>
                  </div>
                </div>
              </div>

              <div class="single_course">
                <div class="course_head">
                  <img class="img-fluid" src="img/libraries/c2.jpg" alt="" />
                </div>
                <div class="course_content">
                  <span class="price">$25</span>
                  <span class="tag mb-4 d-inline-block">design</span>
                  <h4 class="mb-3">
                    <a href="course-details.html">Social Media Network</a>
                  </h4>
                  <p>
                    One make creepeth man bearing their one firmament won't fowl
                    meat over sea
                  </p>
                  <div
                    class="course_meta d-flex justify-content-lg-between align-items-lg-center flex-lg-row flex-column mt-4"
                  >
                    <div class="authr_meta">
                      <img src="img/libraries/author2.png" alt="" />
                      <span class="d-inline-block ml-2">Cameron</span>
                    </div>
                    <div class="mt-lg-0 mt-3">
                      <span class="meta_info mr-4">
                        <a href="#"> <i class="ti-user mr-2"></i>25 </a>
                      </span>
                      <span class="meta_info"
                        ><a href="#"> <i class="ti-heart mr-2"></i>35 </a></span
                      >
                    </div>
                  </div>
                </div>
              </div>

              <div class="single_course">
                <div class="course_head">
                  <img class="img-fluid" src="img/libraries/c3.jpg" alt="" />
                </div>
                <div class="course_content">
                  <span class="price">$25</span>
                  <span class="tag mb-4 d-inline-block">design</span>
                  <h4 class="mb-3">
                    <a href="course-details.html">Computer Engineering</a>
                  </h4>
                  <p>
                    One make creepeth man bearing their one firmament won't fowl
                    meat over sea
                  </p>
                  <div
                    class="course_meta d-flex justify-content-lg-between align-items-lg-center flex-lg-row flex-column mt-4"
                  >
                    <div class="authr_meta">
                      <img src="img/libraries/author3.png" alt="" />
                      <span class="d-inline-block ml-2">Cameron</span>
                    </div>
                    <div class="mt-lg-0 mt-3">
                      <span class="meta_info mr-4">
                        <a href="#"> <i class="ti-user mr-2"></i>25 </a>
                      </span>
                      <span class="meta_info"
                        ><a href="#"> <i class="ti-heart mr-2"></i>35 </a></span
                      >
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--================ End Popular libraries Area =================-->

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
                <h3>libraries for Free</h3>
                <p>It is high time for learning</p>
                <form
                  class="form_area"
                  id="myForm"
                  action="mail.html"
                  method="post"
                >
                  <div class="row">
                    <div class="col-lg-12 form_group">
                      <input
                        name="name"
                        placeholder="Your Name"
                        required=""
                        type="text"
                      />
                      <input
                        name="name"
                        placeholder="Your Phone Number"
                        required=""
                        type="tel"
                      />
                      <input
                        name="email"
                        placeholder="Your Email Address"
                        pattern="[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{1,63}$"
                        required=""
                        type="email"
                      />
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

      @include('abouts.values')

    <!--================ End Feature Area =================-->

@endsection
