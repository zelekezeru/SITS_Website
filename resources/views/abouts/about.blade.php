@extends('layouts.app')

@section('maps')
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCjCGmQ0Uq4exrzdcL6rvxywDDOvfAu6eE"></script>
@endsection

@section('main_content')

<!-- Home Banner Area -->
<section class="bg-gray-900 py-16 mt-[200px] mb-[10px]" data-aos="fade-up" data-aos-delay="200">
  <div class="container mx-auto px-6 text-center">
    <div class="text-white">
      <h2 class="text-4xl font-bold mb-4">About Us</h2>
      <div class="flex justify-center space-x-4 text-gray-400">
        <a href="{{url("/")}}" class="hover:text-white transition">Home</a>
        <span>/</span>
        <a href="{{ route('abouts.index') }}" class="hover:text-white transition">About Us</a>
      </div>
    </div>
  </div>
</section>

<!-- About Area -->
<section class="bg-gray-900 py-16">
  <div class="container mx-auto px-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
      <!-- Image Section -->
      <div data-aos="zoom-in" data-aos-delay="300">
        <img
          src="{{ asset('img/about.png') }}"
          alt="About Us"
          class="w-[80%] rounded-lg shadow-lg"
        />
      </div>
      <!-- Text Section -->
      <div data-aos="fade-up" data-aos-delay="400">
        <h4 class="text-3xl font-bold text-white mb-6">About SITS</h4>
        <p class="text-gray-400 text-lg mb-4">
          Shiloh International Theological Seminary (SITS) is a globally recognized institution dedicated to equipping individuals with a deep understanding of biblical principles. At SITS, we are committed to fostering spiritual growth and academic excellence, empowering students to lead with integrity, wisdom, and faith.
        </p>
        <p class="text-gray-400 text-lg mb-4">
          With state-of-the-art facilities and innovative learning approaches, we make theological education accessible and impactful—connecting students to diverse perspectives and international networks.
        </p>
        <p class="text-gray-400 text-lg mb-4">
          At SITS, we believe in transforming lives and communities through the power of the Word of God. Whether you are preparing for ministry, enhancing your theological knowledge, or deepening your personal faith, SITS is your partner in fulfilling your divine calling.
        </p>
        <a
          href="#"
          class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold px-6 py-3 rounded transition"
        >
          Learn More
          <i class="ti-arrow-right ml-2"></i>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Feature Area -->
<section class="bg-gray-800 py-20">
  <div class="container mx-auto px-6">
    <div class="text-center mb-12" data-aos="fade-up" data-aos-delay="300">
      <h2 class="text-4xl font-bold text-white mb-6">Our Distinctive Edge</h2>
      <p class="text-gray-400 text-lg">
        We deliver innovative, tailored solutions that set our clients up for success.
      </p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <!-- Feature 1 -->
      <div class="bg-gray-900 p-8 rounded-xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-4" data-aos="zoom-in" data-aos-delay="400">
        <img src="{{ asset('img/features/state.png') }}" alt="Feature Icon" class="w-16 h-16 mx-auto mb-6">
        <h4 class="text-xl font-bold text-white text-center mb-6">State of the Art Academics</h4>
        <ul class="text-gray-400 space-y-3 text-base">
          <li class="flex items-center"><i class="fas fa-check-circle text-blue-500 mr-3"></i> Hybridized Courses for Flexibility</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-blue-500 mr-3"></i> Asynchronous Training for Self-Paced Learning</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-blue-500 mr-3"></i> Comprehensive Online Library</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-blue-500 mr-3"></i> Global Partnerships Across Continents</li>
        </ul>
      </div>
      <!-- Feature 2 -->
      <div class="bg-gray-900 p-8 rounded-xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-4" data-aos="zoom-in" data-aos-delay="500">
        <img src="{{ asset('img/features/actea.png') }}" alt="Feature Icon" class="w-16 h-16 mx-auto mb-6">
        <h4 class="text-xl font-bold text-white text-center mb-6">ACTEA Accredited (English)</h4>
        <ul class="text-gray-400 space-y-3 text-base">
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-3"></i> Advanced Diploma</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-3"></i> BA in Theology</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-3"></i> MA in Biblical & Theological Studies</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-3"></i> Masters of Divinity</li>
        </ul>
      </div>
      <!-- Feature 3 -->
      <div class="bg-gray-900 p-8 rounded-xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-4" data-aos="zoom-in" data-aos-delay="600">
        <img src="{{ asset('img/features/language.jpg') }}" alt="Feature Icon" class="w-16 h-16 mx-auto mb-6">
        <h4 class="text-xl font-bold text-white text-center mb-6">Other Languages: Amharic, Affan Oromo</h4>
        <ul class="text-gray-400 space-y-3 text-base">
          <li class="flex items-center"><i class="fas fa-check-circle text-yellow-500 mr-3"></i> BA in Theology - Amharic</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-yellow-500 mr-3"></i> MA in Biblical & Theological Studies - Amharic</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-yellow-500 mr-3"></i> Masters of Divinity - Amharic</li>
          <li class="flex items-center"><i class="fas fa-check-circle text-yellow-500 mr-3"></i> Pioneering Educational Model - Amharic</li>
        </ul>
      </div>
    </div>
  </div>
</section>



<!-- Gallery and Testimonials -->
@include('abouts.gallery')
@include('contacts.testimonials')

@endsection
