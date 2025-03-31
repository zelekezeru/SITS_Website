@extends('layouts.app')

@section('main_content')
    <!--================ Home Banner Area =================-->
    <section class="banner_area">
        <div class="banner_inner d-flex align-items-center">
            <div class="overlay"></div>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="banner_content text-center">
                            <h2>Gallery</h2>
                            <div class="page_link">
                                <a href="{{ url('/') }}">Home</a>
                                <a href="{{ route('galleries.index') }}">Gallery</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--================ End Home Banner Area =================-->

    <!--================ Start Gallery Section =================-->
    <div class="section_gap_top">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="main_title text-center">
                        <h2 class="mb-3">Our Gallery</h2>
                        <p>Explore our collection of beautiful moments captured.</p>
                    </div>
                </div>
            </div>
            
            <!-- Gallery Grid -->
            <div class="row">
                @foreach ($galleries as $gallery)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="gallery_item">
                            <div class="gallery_thumb">
                                <img class="img-fluid" src="{{ $gallery->image ? asset('storage/' . $gallery->image) : asset('images/default-image.jpg') }}" 
                                     alt="{{ $gallery->title }}" />
                                <div class="gallery_overlay">
                                    <a href="#" class="btn btn-light btn-sm view-details" data-bs-toggle="modal" 
                                       data-bs-target="#galleryModal" 
                                       data-title="{{ $gallery->title }}" 
                                       data-description="{{ $gallery->description }}" 
                                       data-image="{{ asset('storage/' . $gallery->image) }}">
                                        <i class="fa fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                            <div class="gallery_content text-center mt-3">
                                <h4>{{ $gallery->title }}</h4>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="row">
                <div class="col-12 d-flex justify-content-center">
                    {{ $galleries->links() }}
                </div>
            </div>
        </div>
    </div>
    <!--================ End Gallery Section =================-->

    <!-- Gallery Detail Modal -->
    <div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="galleryModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="galleryModalImage" class="img-fluid mb-3" src="" alt="" />
                    <p id="galleryModalDescription"></p>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                let title = this.getAttribute('data-title');
                let description = this.getAttribute('data-description');
                let image = this.getAttribute('data-image');

                document.getElementById('galleryModalLabel').textContent = title;
                document.getElementById('galleryModalImage').src = image;
                document.getElementById('galleryModalDescription').textContent = description;
            });
        });
    });
</script>
@endpush
