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
                            <h2>Blogs</h2>
                            <div class="page_link">
                                <a href="">Home</a>
                                <a href="{{ route('blogs.index') }}">Blogs</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
      <!--================End Home Banner Area =================-->

    <!--================Blog Area =================-->
    <section class="blog_area section_gap">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="blog_left_sidebar">
                        @foreach ($blogs as $blog)
                            <article class="row blog_item">
                                <div class="col-md-3">
                                    <div class="blog_info text-right">
                                        <div class="post_tag">
                                            <a href="#">Food,</a>
                                            <a class="active" href="#">Technology,</a>
                                            <a href="#">Politics,</a>
                                            <a href="#">Lifestyle</a>
                                        </div>
                                        <ul class="blog_meta list">
                                            <li><a href="#">Mark wiens<i class="ti-user"></i></a></li>
                                            <li><a href="#">12 Dec, 2017<i class="ti-calendar"></i></a></li>
                                            <li><a href="#">1.2M Views<i class="ti-eye"></i></a></li>
                                            <li><a href="#">06 Comments<i class="ti-comment"></i></a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="blog_post">
                                        <img src="{{ asset('storage/' . $blog->image) }}" alt="post" width="400">

                                        <div class="blog_details">
                                            <a href="single-blog.html">
                                                <h2>{{ $blog->title }}</h2>
                                            </a>
                                            <p>MCSE boot camps have its supporters and its detractors. Some people do not
                                                understand why you should have to spend money on boot camp when you can get
                                                the MCSE study materials yourself at a fraction.</p>
                                            <a href="{{ route('blogs.show', $blog) }}" class="blog_btn">View More</a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach

                        <nav class="blog-pagination justify-content-center d-flex">
                            {{ $blogs->links() }}
                        </nav>

                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="blog_right_sidebar">
                        <aside class="single_sidebar_widget search_widget">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search Posts">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button"><i class="ti-search"></i></button>
                                </span>
                            </div><!-- /input-group -->
                            <div class="br"></div>
                        </aside>

                        <aside class="single_sidebar_widget popular_post_widget">
                            <h3 class="widget_title">Popular Posts</h3>
                            @foreach ($blogs as $blog)

                                <div class="media post_item">
                                    <img src="{{ asset('storage/' . $blog->image) }}" alt="post" width="60">

                                    <div class="media-body">
                                        <a href="blog-details.html">
                                            <h3>{{ $blog->title }}</h3>
                                        </a>
                                        <p>{{ $blog->created_at->format('d-m-Y') }}</p>
                                    </div>
                                </div>
                            @endforeach

                        </aside>
                        <aside class="single_sidebar_widget ads_widget">
                            <a href="#"><img class="img-fluid" src="img/blog/add.jpg" alt=""></a>
                            <div class="br"></div>
                        </aside>


                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--================Blog Area =================-->

@endsection
