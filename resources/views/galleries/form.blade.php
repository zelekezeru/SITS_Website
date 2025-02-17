<div class="row">
    <div class="col-md-6 mb-3">
        <label for="inputTitle" class="form-label"><strong>Gallery Title:</strong></label>
        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" id="inputTitle" value="{{ old('title', $gallery->title ?? '') }}" placeholder="Gallery Title" required>
        @error('title')
            <div class="form-text text-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="inputDescription" class="form-label"><strong>Description:</strong></label>
        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" id="inputDescription" value="{{ old('description', $gallery->description ?? '') }}" placeholder="Description" required>
        @error('description')
            <div class="form-text text-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="inputImage" class="form-label"><strong>Gallery Image:</strong></label>
        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" id="inputImage">
        @error('image')
            <div class="form-text text-danger">{{ $message }}</div>
        @enderror

        @if(isset($gallery) && $gallery->image)
            <div class="mt-2">
                <p class="text-info">Current Image:</p>
                <img src="{{ asset('storage/' . $gallery->image) }}" alt="Gallery Image" style="max-width: 100px; max-height: 100px;">
            </div>
        @endif
    </div>
</div>
