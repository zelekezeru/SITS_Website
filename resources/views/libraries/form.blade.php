<div class="col-md-6 mb-3">
    <label for="inputTitle" class="form-label"><strong>Library Title:</strong></label>
    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" id="inputtitle" value="{{ old('title') }}"  placeholder="Library Title" required>
    @error('title')
        <div class="form-text text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-6 mb-3">
    <label for="inputDescription" class="form-label"><strong>Description:</strong></label>
    <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" id="inputDescription" value="{{ old('description') }}" placeholder="Description" required>
    @error('description')
        <div class="form-text text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-6 mb-3">
    <label for="inputCategory" class="form-label"><strong>Category:</strong></label>
    <input type="text" name="category" class="form-control @error('category') is-invalid @enderror" id="inputCategory" value="{{ old('category') }}" placeholder="Category" required>
    @error('category')
        <div class="form-text text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-6 mb-3">
    <label for="inputLink" class="form-label"><strong>Link:</strong></label>
    <input type="text" name="link" class="form-control @error('link') is-invalid @enderror" id="inputlink" value="{{ old('link') }}" placeholder="Insert Library link" required>
    @error('link')
        <div class="form-text text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-6 mb-3">
    <label for="inputBanner" class="form-label"><strong>Banner Image:</strong></label>
    <input type="file" name="banner" value="{{ old('banner') }}" class="form-control @error('banner') is-invalid @enderror" id="inputBanner">
    @error('banner')
        <div class="form-text text-danger">{{ $message }}</div>
    @enderror
</div>
{{-- <div class="col-md-6 mb-3">
    <label for="inputFile" class="form-label"><strong>Upload File (Optional):</strong></label>
    <input type="file" name="file" value="{{ old('file') }}" class="form-control @error('file') is-invalid @enderror" id="inputFile">
    @error('file')
        <div class="form-text text-danger">{{ $message }}</div>
    @enderror
</div> --}}

</div>
