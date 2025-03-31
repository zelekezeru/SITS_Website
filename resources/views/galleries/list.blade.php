<x-admin-layout>
    <div class="col-md-12 pt-5 mt-5 container">
        <div class="card">
            <h2 class="card-header text-center">List Of Galleries</h2>
            <div class="card-body">

                <!-- Create New Gallery Button -->
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-3">
                    <a class="btn btn-success btn-sm" href="{{ route('galleries.create') }}">
                        <i class="fa fa-plus"></i> Create New Gallery
                    </a>
                </div>

                <!-- Table for List of Galleries -->
                <div class="table-responsive">
                    <table id="add-row" class="display table table-bordered table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Description</th>
                                <th>Image</th>
                                <th width="250px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($galleries as $gallery)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $gallery->description }}</td>
                                    <td>
                                        <img src="{{ asset('storage/'.$gallery->image) }}" alt="{{ $gallery->description }}" width="100">
                                    </td>
                                    <td class="text-center">
                                        <!-- Show Button -->
                                        <a href="{{ route('galleries.show', $gallery->id) }}" class="btn btn-link btn-info btn-lg" data-bs-toggle="tooltip" title="Show">
                                            <i class="fa fa-list"></i>
                                        </a>
                                        <!-- Edit Button -->
                                        <a href="{{ route('galleries.edit', $gallery->id) }}" class="btn btn-link btn-primary btn-lg" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <!-- Delete Button -->
                                        <button type="button" class="btn btn-link btn-danger" data-bs-toggle="tooltip" title="Delete" onclick="confirmDelete({{ $gallery->id }})">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                        <!-- Delete Form (Hidden) -->
                                        <form id="delete-form-{{ $gallery->id }}" action="{{ route('galleries.destroy', $gallery->id) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No galleries found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- SweetAlert Success Notification -->
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

                <!-- Pagination Links -->
                {!! $galleries->links() !!}
            </div>
        </div>
    </div>
</x-admin-layout>
