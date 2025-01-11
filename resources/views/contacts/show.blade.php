<x-admin-layout>
    <div class="container mt-5 pt-5">
        <!-- Contact Details Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Contact Details</h3>
                <a href="{{ route('contacts.list') }}" class="btn btn-primary btn-sm float-end">Back to contacts</a>
            </div>
            <div class="card-body">
                <!-- Contact Title -->
                <h4 class="mb-3"><i class="fas fa-book"></i> {{ $contact->name }}</h4>

                <!-- Contact Details -->

                    <div class="col-md-6">
                        <p><strong>Email:</strong> {{ $contact->email  }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Phone:</strong> {{ $contact->phone }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Title:</strong> {{ $contact->title }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Message:</strong> {{ $contact->message }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Reply:</strong> {{ $contact->reply }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> {{ $contact->status }}</p>
                    </div>

                <!-- Edit and Delete buttons -->
                <div class="mt-4">
                    <a href="{{ route('contacts.edit', $contact->id) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit Contact
                    </a>

                    <form action="{{ route('contacts.destroy', $contact->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this Contact?')">
                            <i class="fas fa-trash"></i> Delete Contact
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
