<x-admin-layout>
    <div class="container mt-5 pt-5">
        <!-- Trainer Details Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Trainer Details</h3>
                <a href="{{ route('trainers.list') }}" class="btn btn-primary btn-sm float-end">Back to Trainers</a>
            </div>
            <div class="card-body">
                <!-- Trainer Name -->
                <h4 class="mb-3"><i class="fas fa-book"></i> {{ $trainer->name }}</h4>
                
                <!-- Trainer Details -->
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Position:</strong> {{ $trainer->position }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Description:</strong> {{ $trainer->description }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Image:</strong> <img src="{{ asset('storage/' . $trainer->image) }}" alt="{{ $trainer->name }}" style="max-width: 100px; max-height: 100px;"></p>
                    </div>
                </div>

                <!-- Edit and Delete buttons -->
                <div class="mt-4">
                    <a href="{{ route('trainers.edit', $trainer->id) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit Trainer
                    </a>

                    <form action="{{ route('trainers.destroy', $trainer->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this trainer?')">
                            <i class="fas fa-trash"></i> Delete Trainer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
