@extends('layouts.app')

@section('main_content')
    <!--================Home Banner Area =================-->
    <section class="home_banner_area">
        <div class="banner_inner d-flex align-items-center">
          <div class="overlay"></div>
          <div class="container">
            <div class="row justify-content-center">
              <div class="col-lg-6">
                <div class="banner_content text-center">
                  <h2>{{ $blog->title }}</h2>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!--================End Home Banner Area =================-->

      <section class="blog_area section_gap">
        <div class="container">
          <article>
            {!! $blog->content !!}
          </article>

        </div>
      </section>
@endsection