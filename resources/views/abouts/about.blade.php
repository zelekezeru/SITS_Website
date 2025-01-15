@extends('layouts.app')

@section('maps')
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCjCGmQ0Uq4exrzdcL6rvxywDDOvfAu6eE"></script>
@endsection

@section('main_content')

    <!--================Home Banner Area =================-->
    <section class="banner_area" data-aos="fade-up" data-aos-delay="200">
      <div class="banner_inner d-flex align-items-center">
        <div class="overlay"></div>
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-lg-6">
              <div class="banner_content text-center">
                <h2>About Us</h2>
                <div class="page_link">
                  <a href="{{url("/")}}">Home</a>
                  <a href="{{ route('abouts.index')}}">About Us</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!--================End Home Banner Area =================-->

    <!--================ Start About Area =================-->
    <section class="about_area section_gap">
      <div class="container">
        <div class="row h_blog_item">
          <div class="col-lg-6">
            <div class="h_blog_img" data-aos="fade-right" data-aos-delay="200">
              <img class="img-fluid" src="img/about.png" alt="" />
            </div>
          </div>
          <div class="col-lg-6">
            <div class="h_blog_text">
              <div class="h_blog_text_inner left right" data-aos="fade-up" data-aos-delay="700">
                <h4>About SITS</h4>
                <p>
                  Shiloh International Theological Seminary (SITS) is a globally recognized institution dedicated to
                  equipping individuals with a deep understanding of biblical principles. At SITS, we are committed to fostering spiritual growth and academic excellence,
                  empowering students to lead with integrity, wisdom, and faith.
                </p>
                <p>
                    With state-of-the-art facilities and innovative learning approaches and an extensive online library, we make theological
                    education accessible and impactful. connecting students to diverse perspectives and international networks.
                </p>
                <p> At SITS, we believe in transforming lives and communities through the power of the Word of God. Whether
                    you are preparing for ministry, enhancing your theological knowledge, or deepening your personal faith,
                    SITS is your partner in fulfilling your divine calling.

                </p>
                <a class="primary-btn" href="#">
                  Learn More <i class="ti-arrow-right ml-1"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!--================ End About Area =================-->

    <!--================ Start Feature Area =================-->
    <section class="feature_area section_gap_top title-bg" data-aos="zoom-in" data-aos-delay="200">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-6">
            <div class="main_title" data-aos="fade-up" data-aos-delay="200">
              <h2 class="mb-3 text-white">Our Distinctive Edge</h2>
              <p>
                We deliver innovative, tailored solutions that set our clients up for success.
              </p>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-4 col-md-6">
            <div class="single_feature" data-aos="fade-up" data-aos-delay="300">
                <div class="icon text-center"><img src="{{ asset('img/features/state.png') }}" alt="" width="50" /></span></div>
              <div class="desc">
                <h4 class="mt-3 mb-2 text-center"> State of the Art <br>Academics </h4><br>

                <h6 class=""> . Hybridized Courses for Flexibility </h6> <br>
                <h6 class=""> . Asynchronous Training for Self-Paced </h6> <br>
                <h6 class=""> . Comprehensive Online Library </h6> <br>
                <h6 class=""> . Global Partnerships Across Continents </h6> <br>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="450">
            <div class="single_feature">
                <div class="icon text-center"><img src="{{ asset('img/features/actea.png') }}" alt="" width="50" /></span></div>
              <div class="desc">
                <h4 class="mt-3 mb-2 text-center"> ACTEA Accredited <br> (English) </h4><br>

                <h6 class=""> . Advanced Deploma </h6> <br>
                <h6 class=""> . BA in Theology </h6> <br>
                <h6 class=""> . MA in Biblical & Theological Studies </h6> <br>
                <h6 class=""> . Masters of Devinity </h6> <br>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
            <div class="single_feature">
                <div class="icon text-center"><img src="{{ asset('img/features/language.jpg') }}" alt="" width="50" /></span></div>
              <div class="desc">
                <h4 class="mt-3 mb-2 text-center"> Other Languages <br> Amharic, Affan Oromo <br>  </h4><br>

                <h6 class=""> . BA in Theology - Amharic </h6> <br>
                <h6 class=""> . MA in Biblical & Theological Studies - Amharic </h6> <br>
                <h6 class=""> . Masters of Devinity - Amharic </h6> <br>
                <h6 class=""> . Pioneering educational model - Amharic </h6> <br>

              </div>
            </div>
          </div>

        </div>
      </div>
    </section>
    <!--================ End Feature Area =================-->

    <!--================ Start Testimonial Area =================-->

    @include('contacts.testimonials')

    <!--================ End Testimonial Area =================-->
@endsection
