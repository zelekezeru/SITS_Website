<x-admin-layout>
    <div class="col-md-12 pt-5 mt-5 container">
        <div class="card">
            <h2 class="card-header text-center">List of Programs</h2>
            <div class="card-body">
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-3">
                    <a class="btn btn-success btn-sm" href="{{ route('programs.create') }}">
                        <i class="fa fa-plus"></i> Create New Program
                    </a>
                </div>

                <div class="table-responsive">
                    <table id="add-row" class="display table table-bordered table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Code</th>
                                <th>Language</th>
                                <th>Actions</th> 
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($programs as $program)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $program->title }}</td>
                                    <td>{{ $program->code }}</td>
                                    <td>{{ $program->language }}</td>
                                    <td class="text-center">
                                        <div class="form-button-action">
                                            <!-- View Button -->
                                            <a href="{{ route('programs.show', $program->id) }}" class="btn btn-link btn-info btn-lg" data-bs-toggle="tooltip" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <!-- Edit Button -->
                                            <a href="{{ route('programs.edit', $program->id) }}" class="btn btn-link btn-primary btn-lg" data-bs-toggle="tooltip" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-link btn-danger" data-bs-toggle="tooltip" title="Delete" onclick="confirmDelete({{ $program->id }})">
                                                <i class="fa fa-times"></i>
                                            </button>
                                            <!-- Delete Form -->
                                            <form id="delete-form-{{ $program->id }}" action="{{ route('programs.destroy', $program->id) }}" method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No programs found.</td>
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

                {!! $programs->links() !!}
            </div>
        </div>
    </div>
</x-admin-layout>