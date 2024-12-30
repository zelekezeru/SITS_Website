<x-admin-layout>
    <div class="container mt-5 pt-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Program Details</h3>
                <a href="{{ route('programs.list') }}" class="btn btn-primary btn-sm float-end">Back to Programs</a>
            </div>
            <div class="card-body">
                <h4 class="mb-3"><i class="fas fa-cogs"></i> {{ $program->title }}</h4>
                <p><strong>Description:</strong> {{ $program->description }}</p>
                <p><strong>Code:</strong> {{ $program->code }}</p>
                <p><strong>Division:</strong> {{ $program->division }}</p>
                <p><strong>Language:</strong> {{ $program->language }}</p>
            </div>
            <div class="card-footer">
                <a href="{{ route('programs.edit', $program->id) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Edit Program
                </a>
                <form action="{{ route('programs.destroy', $program->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this program?')">
                        <i class="fas fa-trash"></i> Delete Program
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
