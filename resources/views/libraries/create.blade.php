<x-admin-layout>
    <div class="container mt-5 pt-5">
        <div class="card mt-5">
            <h2 class="card-header text-center">Add New Library</h2>
            <div class="card-body">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a class="btn btn-primary btn-sm" href="{{ route('libraries.index') }}"><i class="fa fa-arrow-left"></i> Back</a>
                </div>

                <form action="{{ route('libraries.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
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

                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Submit</button>
                </form>

            </div>
        </div>
    </div>
</x-admin-layout>
