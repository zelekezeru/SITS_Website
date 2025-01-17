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
                <div class="col-lg-6">
                    <div class="main_title">
                        <h2 class="mb-3">Our Popular libraries</h2>
                        <p>
                            SITS is a place where you can find rich libraries you need to learn and grow.
                        </p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="owl-carousel active_course">
                        @foreach ($libraries as $library)
                            <div class="single_course">
                                <div class="course_head">
                                    <a href="{{ $library->link }}" target="_blank" rel="noopener noreferrer">
                                        @if($library->banner)
                                            <img src="{{ asset('storage/' . $library->banner) }}" alt="{{ $library->title }}"  style="max-width: 500px; max-height: 500px;">
                                        @else
                                            No Image
                                        @endif
                                    </a>
                                </div>
                                <div class="course_content">
                                    <span class="tag mb-4 d-inline-block">{{ $library->category }}</span>
                                    <h4 class="mb-3">
                                        <a href="course-details.html">{{ $library->title }}</a>
                                    </h4>
                                    <p>
                                        {{ $library->description }}
                                    </p>
                                    <div class="course_meta d-flex justify-content-lg-between align-items-lg-center flex-lg-row flex-column mt-4">
                                        <div class="authr_meta">
                                            <img src="img/libraries/author1.png" alt="" />
                                            <span class="d-inline-block ml-2">Cameron</span>
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
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--================ End Popular libraries Area =================-->

    <!--================ Start Feature Area =================-->
    @include('abouts.values')
    <!--================ End Feature Area =================-->

@endsection
