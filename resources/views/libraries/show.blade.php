<x-admin-layout>
    <div class="container mt-5 pt-5">
        <!-- Library Details Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Library Details</h3>
                <a href="{{ route('libraries.list') }}" class="btn btn-primary btn-sm float-end">Back to Libraries</a>
            </div>
            <div class="card-body">
                <!-- Library Title -->
                <h4 class="mb-3"><i class="fas fa-book"></i> {{ $library->title }} Library</h4>

                <!-- Library Details -->
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Description:</strong> {{ $library->description }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Category:</strong> {{ $library->category }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Image:</strong> </p>
                            @if($library->banner)
                                <img src="{{ asset('storage/' . $library->banner) }}" alt="{{ $library->title }}" width="200">
                            @else
                                No Image
                            @endif
                    </div>

                    <div class="col-md-6">
                        <p><strong>File:</strong>
                            @if($library->file)
                                <a href="{{ asset('storage/' . $library->file) }}" target="_blank">{{ $library->file }}</a>
                            @else
                                No File
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Edit and Delete buttons -->
                <div class="mt-4">
                    <a href="{{ route('libraries.edit', $library->id) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit Library
                    </a>

                    <form action="{{ route('libraries.destroy', $library->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this Library?')">
                            <i class="fas fa-trash"></i> Delete Library
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
