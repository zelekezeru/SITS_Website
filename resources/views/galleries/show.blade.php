<x-admin-layout>
    <div class="container my-5 py-5">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center rounded-top">
                <h3 class="card-title m-0"><i class="fas fa-images"></i> Gallery Details</h3>
                <a href="{{ route('galleries.list') }}" class="btn btn-light btn-sm text-primary">
                    <i class="fas fa-arrow-left"></i> Back to Galleries
                </a>
            </div>

            <div class="card-body">
                <!-- Row layout for image and details -->
                <div class="row align-items-center">
                    <!-- Gallery Image -->
                    @if ($gallery->image)
                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <img src="{{ asset('storage/' . $gallery->image) }}" alt="{{ $gallery->description }}" class="img-fluid rounded shadow" style="max-width: 100%; height: auto;">
                        </div>
                    @endif

                    <!-- Gallery Details -->
                    <div class="col-md-8">
                        <h4 class="mb-3 text-primary fw-bold">{{ $gallery->description }}</h4>
                        <p class="mb-2"><strong><i class="fas fa-calendar-alt"></i> Upload Date:</strong> {{ \Carbon\Carbon::parse($gallery->created_at)->format('F j, Y') }}</p>

                        @if ($gallery->remark)
                            <p><strong><i class="fas fa-comment-dots"></i> Remarks:</strong> {{ $gallery->remark }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-footer bg-light d-flex justify-content-between rounded-bottom">
                <!-- Edit Button -->
                <a href="{{ route('galleries.edit', $gallery->id) }}" class="btn btn-warning btn-sm shadow">
                    <i class="fas fa-edit"></i> Edit Gallery
                </a>

                <!-- Delete Button with SweetAlert -->
                <form action="{{ route('galleries.destroy', $gallery->id) }}" method="POST" class="d-inline delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-danger btn-sm shadow delete-btn">
                        <i class="fas fa-trash"></i> Delete Gallery Item
                    </button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelector('.delete-btn').addEventListener('click', function(e) {
                Swal.fire({
                    title: "Are you sure?",
                    text: "This gallery item will be permanently deleted!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.closest('.delete-form').submit();
                    }
                });
            });
        });
    </script>
    @endpush
</x-admin-layout>
