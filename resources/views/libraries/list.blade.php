<x-admin-layout>
    <div class="col-md-12 pt-5 mt-5 container">
        <div class="card">
            <h2 class="card-header text-center">List Of Libraries</h2>
            <div class="card-body">

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-3">
                    <a class="btn btn-success btn-sm" href="{{ route('libraries.create') }}">
                        <i class="fa fa-plus"></i> Create New Library
                    </a>
                </div>

                <div class="table-responsive">
                    <table id="add-row" class="display table table-bordered table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Image</th>
                                <th>Category</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($libraries as $library)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $library->title }}</td>
                                    <td>
                                        @if($library->banner)
                                            <img src="{{ asset('storage/' . $library->banner) }}" alt="{{ $library->title }}" width="60">
                                        @else
                                            No Image
                                        @endif
                                    </td>
                                    <td>{{ $library->category }}</td>
                                    <td class="text-center">
                                        <div class="form-button-action">
                                            <!-- Show Button -->
                                            <a href="{{ route('libraries.show', $library->id) }}" class="btn btn-link btn-info btn-lg" data-bs-toggle="tooltip" title="Show">
                                                <i class="fa fa-list"></i>
                                            </a>
                                            <!-- Edit Button -->
                                            <a href="{{ route('libraries.edit', $library->id) }}" class="btn btn-link btn-primary btn-lg" data-bs-toggle="tooltip" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-link btn-danger" data-bs-toggle="tooltip" title="Delete" onclick="confirmDelete({{ $library->id }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                            <!-- Delete Form -->
                                            <form id="delete-form-{{ $library->id }}" action="{{ route('libraries.destroy', $library->id) }}" method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No libraries found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- SweetAlert Success Notifications -->
                @if (session('status'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ ucfirst(session('status')) }}',
                            text: '{{ session('status') }}.',
                            confirmButtonText: 'Okay'
                        });
                    });
                </script>
                @endif

                {!! $libraries->links() !!}
            </div>
        </div>
    </div>
</x-admin-layout>
