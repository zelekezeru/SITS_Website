<x-admin-layout>
<div class="container mt-5 pt-5">
    <div class="card mt-5">
        <h2 class="card-header text-center">Edit Trainer</h2>
        <div class="card-body">
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a class="btn btn-primary btn-sm" href="{{ route('trainers.list') }}"><i class="fa fa-arrow-left"></i> Back</a>
            </div>

            <form action="{{ route('trainers.update', $trainer->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="inputName" class="form-label"><strong>Trainer Name:</strong></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="inputName" value="{{ old('name', $trainer->name) }}" placeholder="Trainer Name" required>
                        @error('name')
                            <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="inputposition" class="form-label"><strong>Trainer Position:</strong></label>
                        <input type="text" name="position" class="form-control @error('position') is-invalid @enderror" id="inputposition" value="{{ old('position', $trainer->position) }}" placeholder="Trainer Name" required>
                        @error('position')
                            <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="inputDescription" class="form-label"><strong>Description:</strong></label>
                        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" id="inputDescription" value="{{ old('description', $trainer->description) }}" placeholder="Description" required>
                        @error('description')
                            <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                    </div>


                    <div class="col-md-6 mb-3">
                        <label for="inputImage" class="form-label"><strong>Image:</strong></label>
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" id="inputImage">
                        <div class="form-text">Current image:</div>
                        <img src="{{ asset('storage/' . $trainer->image) }}" alt="{{ $trainer->name }}" style="max-width: 100px; max-height: 100px;">
                        @error('image')
                            <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
            </form>
        </div>
    </div>
</div>
</x-admin-layout>