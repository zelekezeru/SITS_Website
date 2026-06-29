@extends('layouts.app')

@section('maps')
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCjCGmQ0Uq4exrzdcL6rvxywDDOvfAu6eE"></script>
@endsection

@section('main_content')

<!-- Hero Banner Area -->
<section class="relative py-24 pt-36 pb-16 overflow-hidden" data-aos="fade-down">
  <div class="container mx-auto px-6 text-center relative z-10">
    <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide mb-6">
        Our Institution
    </div>
    <h1 class="text-4xl md:text-5xl font-extrabold text-white font-outfit mb-4">About Us</h1>
    <div class="flex justify-center items-center space-x-3 text-sm text-slate-500">
      <a href="{{url("/")}}" class="hover:text-white transition">Home</a>
      <span>/</span>
      <span class="text-slate-300">About Us</span>
    </div>
  </div>
</section>

<!-- About Area -->
<section class="py-20 relative">
  <div class="container mx-auto px-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
      <!-- Image Section -->
      <div class="lg:col-span-5 relative" data-aos="zoom-in" data-aos-delay="200">
        <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-indigo-500 to-purple-500 opacity-15 blur-xl"></div>
        <div class="relative rounded-3xl overflow-hidden border border-white/5 bg-slate-950 aspect-[4/3]">
          <img
            src="{{ asset('img/about.png') }}"
            alt="About SITS"
            class="w-full h-full object-cover hover:scale-105 transition duration-700"
          />
        </div>
      </div>

      <!-- Text Section -->
      <div class="lg:col-span-7 space-y-6" data-aos="fade-up" data-aos-delay="300">
        <h2 class="text-3xl font-extrabold text-white font-outfit">Empowering Spiritual Growth & Academic Excellence</h2>
        <p class="text-slate-400 leading-relaxed text-base">
          Shiloh International Theological Seminary (SITS) is a globally recognized institution dedicated to equipping individuals with a deep understanding of biblical principles. At SITS, we are committed to fostering spiritual growth and academic excellence, empowering students with the power of the Holy Spirit to serve Jesus Christ with integrity, wisdom, and faith.
        </p>
        <p class="text-slate-400 leading-relaxed text-base">
          With state-of-the-art facilities and innovative learning approaches, we make theological education accessible and impactful—connecting students to diverse perspectives and international networks.
        </p>
        <p class="text-slate-400 leading-relaxed text-base">
          At SITS, we believe in transforming lives and communities through the power of the Word of God. Whether you are preparing for ministry, enhancing your theological knowledge, or deepening your personal faith, SITS is your partner in fulfilling your divine calling.
        </p>
        <div class="pt-4">
          <a href="{{ route('courses.index') }}"
             class="btn-glow inline-flex items-center gap-2 px-6 py-3.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl shadow-lg shadow-indigo-600/20 transition duration-300">
            <span>Explore Courses</span>
            <i class="ti-arrow-right text-xs"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Feature Area -->
<section class="py-24 bg-slate-950/40 border-y border-slate-900/60 relative">
  <div class="container mx-auto px-6">
    <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
      <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs font-semibold tracking-wide mb-4">
          Why Choose SITS
      </div>
      <h2 class="text-3xl md:text-4xl font-bold text-white font-outfit mb-4">Our Distinctive Edge</h2>
      <p class="text-slate-400">
        We deliver innovative, tailored educational programs that prepare you for impactful Christian leadership.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <!-- Feature 1 -->
      <div class="glass-card glass-card-hover p-8 rounded-2xl border border-white/5 flex flex-col justify-between" data-aos="zoom-in" data-aos-delay="400">
        <div>
          <div class="w-16 h-16 bg-indigo-500/10 rounded-2xl flex items-center justify-center border border-indigo-500/20 mb-6">
            <img src="{{ asset('img/features/state.png') }}" alt="Academics" class="w-10 h-10 object-contain">
          </div>
          <h4 class="text-xl font-bold text-white font-outfit mb-4">State of the Art Academics</h4>
          <ul class="text-slate-450 space-y-3.5 text-sm">
            <li class="flex items-start gap-2.5"><i class="ti-check text-indigo-400 mt-0.5"></i> Hybridized Courses for Flexibility</li>
            <li class="flex items-start gap-2.5"><i class="ti-check text-indigo-400 mt-0.5"></i> Asynchronous Self-Paced Training</li>
            <li class="flex items-start gap-2.5"><i class="ti-check text-indigo-400 mt-0.5"></i> Comprehensive Online Library</li>
            <li class="flex items-start gap-2.5"><i class="ti-check text-indigo-400 mt-0.5"></i> Global Partnerships Across Continents</li>
          </ul>
        </div>
      </div>

      <!-- Feature 2 -->
      <div class="glass-card glass-card-hover p-8 rounded-2xl border border-white/5 flex flex-col justify-between" data-aos="zoom-in" data-aos-delay="500">
        <div>
          <div class="w-16 h-16 bg-amber-500/10 rounded-2xl flex items-center justify-center border border-amber-500/20 mb-6">
            <img src="{{ asset('img/features/actea.png') }}" alt="Accreditation" class="w-10 h-10 object-contain">
          </div>
          <h4 class="text-xl font-bold text-white font-outfit mb-4">ACTEA Accredited (English)</h4>
          <ul class="text-slate-455 space-y-3.5 text-sm">
            <li class="flex items-start gap-2.5"><i class="ti-check text-amber-400 mt-0.5"></i> Advanced Diploma in Theology</li>
            <li class="flex items-start gap-2.5"><i class="ti-check text-amber-400 mt-0.5"></i> Bachelor of Arts in Theology</li>
            <li class="flex items-start gap-2.5"><i class="ti-check text-amber-400 mt-0.5"></i> MA in Biblical & Theological Studies</li>
            <li class="flex items-start gap-2.5"><i class="ti-check text-amber-400 mt-0.5"></i> Master of Divinity (M.Div)</li>
          </ul>
        </div>
      </div>

      <!-- Feature 3 -->
      <div class="glass-card glass-card-hover p-8 rounded-2xl border border-white/5 flex flex-col justify-between" data-aos="zoom-in" data-aos-delay="600">
        <div>
          <div class="w-16 h-16 bg-purple-500/10 rounded-2xl flex items-center justify-center border border-purple-500/20 mb-6">
            <img src="{{ asset('img/features/language.jpg') }}" alt="Languages" class="w-10 h-10 object-contain rounded-lg">
          </div>
          <h4 class="text-xl font-bold text-white font-outfit mb-4">Amharic & Afaan Oromo Programs</h4>
          <ul class="text-slate-455 space-y-3.5 text-sm">
            <li class="flex items-start gap-2.5"><i class="ti-check text-purple-400 mt-0.5"></i> BA in Theology (Amharic, Urdu)</li>
            <li class="flex items-start gap-2.5"><i class="ti-check text-purple-400 mt-0.5"></i> MA in Biblical & Theological Studies (Amharic)</li>
            <li class="flex items-start gap-2.5"><i class="ti-check text-purple-400 mt-0.5"></i> Master of Divinity (Amharic)</li>
            <li class="flex items-start gap-2.5"><i class="ti-check text-purple-400 mt-0.5"></i> Pioneering Educational Translation Models</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Gallery and Testimonials -->
@include('abouts.gallery')

@include('contacts.testimonials')

@endsection
