<x-admin-layout>
    <div class="container mt-5 pt-5">
        <div class="card mt-5">

            <!-- Display success message -->
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <h2 class="card-header text-center">Edit Gallery</h2>
            <div class="card-body">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a class="btn btn-primary btn-sm" href="{{ route('galleries.index') }}">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>

                <!-- Form to edit the gallery -->
                <form action="{{ route('galleries.update', $gallery->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <!-- Gallery Title -->
                        <div class="col-md-6 mb-3">
                            <label for="inputTitle" class="form-label"><strong>Title:</strong></label>
                            <input type="text" name="title"
                                class="form-control @error('title') is-invalid @enderror" id="inputTitle"
                                value="{{ old('title', $gallery->title) }}" required>
                            @error('title')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Gallery Description -->
                        <div class="col-md-6 mb-3">
                            <label for="inputDescription" class="form-label"><strong>Description:</strong></label>
                            <input type="text" name="description"
                                class="form-control @error('description') is-invalid @enderror" id="inputDescription"
                                value="{{ old('description', $gallery->description) }}">
                            @error('description')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Gallery Image -->
                        <div class="col-md-6 mb-3">
                            <label for="inputImage" class="form-label"><strong>Image:</strong></label>
                            <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" id="inputImage">
                            
                            <!-- Show current image -->
                            @if ($gallery->image)
                                <div class="form-text">Current Image:</div>
                                <img src="{{ asset('storage/' . $gallery->image) }}" alt="{{ $gallery->title }}" style="max-width: 100px; max-height: 100px;">
                            @endif
                            
                            @error('image')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> Update
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
