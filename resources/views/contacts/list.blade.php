<x-admin-layout>
    <div class="col-md-12 pt-5 mt-5 container">
        <div class="card">
            <h2 class="card-header text-center">List Of Contacts</h2>
            <div class="card-body">

                {{-- Create New Contact button (commented out) --}}
                {{-- <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a class="btn btn-success btn-sm" href="{{ route('contacts.create') }}">
                        <i class="fa fa-plus"></i> Create New Contact
                    </a>
                </div> --}}

                <div class="table-responsive">
                    <table id="add-row" class="display table table-bordered table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th width="250px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contacts as $contact)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $contact->name }}</td>
                                    <td>{{ $contact->title }}</td>
                                    <td>{{ $contact->message }}</td>
                                    <td class="text-center">
                                        <div class="form-button-action">
                                            <!-- Show Button -->
                                            <a href="{{ route('contacts.show', $contact->id) }}" class="btn btn-link btn-info btn-lg" data-bs-toggle="tooltip" title="Show">
                                                <i class="fa fa-list"></i>
                                            </a>
                                            <!-- Edit Button -->
                                            <a href="{{ route('contacts.edit', $contact->id) }}" class="btn btn-link btn-primary btn-lg" data-bs-toggle="tooltip" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-link btn-danger" data-bs-toggle="tooltip" title="Delete" onclick="confirmDelete({{ $contact->id }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                            <!-- Delete Form -->
                                            <form id="delete-form-{{ $contact->id }}" action="{{ route('contacts.destroy', $contact->id) }}" method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No contacts found.</td>
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

                {!! $contacts->links() !!}
            </div>
        </div>
    </div>
</x-admin-layout>
