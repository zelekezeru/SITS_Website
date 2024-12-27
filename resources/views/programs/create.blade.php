<x-admin-layout>
    <div class="container mt-5 pt-5">
        <div class="card mt-5">
            <!-- Display success message -->
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            <h2 class="card-header text-center">Add New Program</h2>
            <div class="card-body">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a class="btn btn-primary btn-sm" href="{{ route('programs.index') }}">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>
                <form action="{{ route('programs.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <!-- Program Title -->
                        <div class="col-md-6 mb-3">
                            <label for="inputTitle" class="form-label"><strong>Program Title:</strong></label>
                            <input type="text" name="title"
                                class="form-control @error('title') is-invalid @enderror" id="inputTitle"
                                placeholder="Program Title" required>
                            @error('title')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Program Name -->
                        <div class="col-md-6 mb-3">
                            <label for="inputProgramName" class="form-label"><strong>Program Name:</strong></label>
                            <input type="text" name="name"
                                class="form-control @error('program_name') is-invalid @enderror" id="inputProgramName"
                                placeholder="Program Name" required>
                            @error('program_name')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="inputEmail" class="form-label"><strong>Email:</strong></label>
                            <input type="email" name="email"
                                class="form-control @error('email') is-invalid @enderror" id="inputEmail"
                                placeholder="Email" required>
                            @error('email')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6 mb-3">
                            <label for="inputPhone" class="form-label"><strong>Phone:</strong></label>
                            <input type="text" name="phone"
                                class="form-control @error('phone') is-invalid @enderror" id="inputPhone"
                                placeholder="Phone" required>
                            @error('phone')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Message -->
                        <div class="col-md-6 mb-3">
                            <label for="inputMessage" class="form-label"><strong>Message:</strong></label>
                            <textarea name="message" class="form-control @error('message') is-invalid @enderror" id="inputMessage"
                                placeholder="Message" required></textarea>
                            @error('message')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-floppy-disk"></i> Submit
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
