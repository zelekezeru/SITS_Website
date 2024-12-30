<!-- resources/views/programs/index.blade.php -->

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
                            <h2>Our Programs</h2>
                            <div class="page_link">
                                <a href="{{ url('/') }}">Home</a>
                                <a href="{{ route('programs.index') }}">Programs</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--================End Home Banner Area =================-->

    <!--================ Start Popular Programs Area =================-->
    <div class="popular_courses section_gap_top">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="main_title">
                        <h2 class="mb-3">Our Popular Programs</h2>
                        <p>
                            Explore a wide range of programs to help you grow and achieve your goals.
                        </p>
                    </div>
                </div>
            </div>
            <div class="row">
                @foreach ($programs as $program)
                    <div class="col-lg-4 col-md-6">
                        <div class="single_course">
                            <div class="course_head">
                                @php
                                    // Randomly pick a banner image from the collection
                                    $randomBanner = $bannerImages->isNotEmpty() ? $bannerImages->random() : null;
                                @endphp
                                @if ($randomBanner)
                                <img class="img-fluid" 
                                src="{{ asset('images/banners/' . basename($randomBanner)) }}" 
                                alt="{{ $program->title }}" 
                                style="width: 100%; height: 200px; object-fit: cover;" />
                           
                                @else
                                    <!-- Default image if none is found -->
                                    <img class="img-fluid" src="{{ asset('assets/images/default-banner.jpg') }}"
                                        alt="Default Banner">
                                @endif
                            </div>
                            <div class="course_content">
                                <span class="price">{{ $program->code }}</span>
                                <span class="tag mb-4 d-inline-block">{{ $program->division }}</span>
                                <h4 class="mb-3">
                                    <a href="{{ route('programs.show', $program->id) }}">{{ $program->title }}</a>
                                </h4>
                                <p>
                                    {{ Str::limit($program->description, 100) }}
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
    <!--================ End Popular Programs Area =================-->
@endsection
