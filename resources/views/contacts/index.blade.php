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
                <h2>Contact SITS</h2>
                <div class="page_link">
                  <a href="">Home</a>
                  <a href="{{ route('contacts.index')}}">Contact</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!--================End Home Banner Area =================-->

    <!--================Contact Area =================-->
    <section class="contact_area section_gap">
        <div class="container">

          <div class="container">
            <h3 class="text-center mb-6">You can Send us a Message Here!</h3>
            <p class="text-center">We would like to hear from you, please send us your query and we will get back to you as soon as possible.</p>
          </div>

          <div class="row">
            <div class="col-lg-3">
              <div class="contact_info">
                <div class="info_item">
                  <i class="ti-home"></i>
                  <h6>Hawassa, Ethiopia</h6>
                  <p>Welde Amanuel Avanue</p>
                </div>
                <div class="info_item">
                  <i class="ti-headphone"></i>
                  <h6>+251 (0)46 212 7821</h6>
                  <p>SITS Admin</p>
                  <h6>+251 (0)46 212 9156</h6>
                  <p>SITS Registrar</p>
                  <h6>+251 (0)46 212 8708</h6>
                  <p>SITS ODEL</p>
                </div>
                <div class="info_item">
                  <i class="ti-email"></i>
                  <h6><a href="#">support@sits.com</a></h6>
                  <p>Send us your query anytime!</p>

                  <h6><a href="#">support@sits.com</a></h6>
                  <p>Send us your query anytime!</p>
                </div>
              </div>
            </div>
            <div class="col-lg-9">
              <form class="row contact_form" action="{{ route('contacts.store') }}" method="post" id="contactForm" novalidate="novalidate">
                @csrf
                <div class="col-md-6">
                  <div class="form-group">
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Enter your name"
                      onfocus="this.placeholder = ''" onblur="this.placeholder = 'Enter your name'" required=""/>
                  </div>
                  <div class="form-group">
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="Enter email address" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Enter email address'" required="" />
                  </div>
                  <div class="form-group">
                    <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}" placeholder="Enter phone address"
                      onfocus="this.placeholder = ''" onblur="this.placeholder = 'Enter phone Number'" required=""/>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" placeholder="Enter Subject"
                      onfocus="this.placeholder = ''" onblur="this.placeholder = 'Enter Subject'" required="" />
                  </div>
                  <div class="form-group">
                    <textarea class="form-control" name="message" id="message" rows="3" value="{{ old('message') }}" placeholder="Enter Message"
                      onfocus="this.placeholder = ''" onblur="this.placeholder = 'Enter Message'" required=""></textarea>
                  </div>
                </div>
                <div class="col-md-12 text-right">
                  <button type="submit" value="submit" class="btn primary-btn">
                    Send Message
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </section>
    <!--================Contact Area =================-->

    <!--================ About Area =================-->

    @include('abouts.values')

    <!--================End About Area =================-->


@endsection
