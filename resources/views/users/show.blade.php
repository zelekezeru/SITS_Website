<x-admin-layout>    
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white text-center">
                <h3>User Details</h3>
            </div>
            <div class="card-body">
                <!-- Success Message -->
                @if(session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="mb-4">
                    <strong>Full Name:</strong>
                    <p>{{ $user->name }}</p>
                </div>
                <div class="mb-4">
                    <strong>Email Address:</strong>
                    <p>{{ $user->email }}</p>
                </div>
                <div class="mb-4">
                    <strong>Assigned Role(s):</strong>
                    <ul>
                        @forelse ($user->getRoleNames() as $role)
                            <li>{{ $role }}</li>
                        @empty
                            <li>No roles assigned.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Back to List
                    </a>
                    <div class="d-flex">
                        <a class="btn btn-primary mx-2" href="{{ route('users.edit', $user->id) }}">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </a>
                        <form action="{{ route('users.destroy', $user->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
