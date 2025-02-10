<x-admin-layout>
    <div class="col-md-12 pt-5 mt-5 container">
        <div class="card">
            <h2 class="card-header text-center">List of Subscriptions</h2>
            <div class="card-body">

                <div class="table-responsive">
                    <table id="add-row" class="display table table-bordered table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subscriptions as $subscription)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $subscription->name }}</td>
                                    <td>{{ $subscription->phone }}</td>
                                    <td>{{ $subscription->email }}</td>
                                    <td class="text-center">
                                        <div class="form-button-action">
                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-link btn-danger" data-bs-toggle="tooltip" title="Delete" onclick="confirmDelete({{ $subscription->id }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                            <!-- Delete Form -->
                                            <form id="delete-form-{{ $subscription->id }}" action="{{ route('subscriptions.destroy', $subscription->id) }}" method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No subscriptions found.</td>
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

                {!! $subscriptions->links() !!}
            </div>
        </div>
    </div>
</x-admin-layout>
