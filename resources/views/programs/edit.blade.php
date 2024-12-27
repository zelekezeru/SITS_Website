<x-admin-layout>
    <div class="container mt-5 pt-5">
        <div class="card mt-5">
            <h2 class="card-header text-center">Add New Program</h2>
            <div class="card-body">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a class="btn btn-primary btn-sm" href="{{ route('programs.index') }}"><i class="fa fa-arrow-left"></i> Back</a>
                </div>

                <form action="{{ route('programs.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <!-- Program Title -->
                        <div class="col-md-6 mb-3">
                            <label for="title" class="form-label"><strong>Program Title:</strong></label>
                            <input 
                                type="text" 
                                name="title" 
                                class="form-control @error('title') is-invalid @enderror" 
                                id="title" 
                                placeholder="Program Title" 
                                required
                            >
                            @error('title')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Program Name -->
                        <div class="col-md-6 mb-3">
                            <label for="program_name" class="form-label"><strong>Program Name:</strong></label>
                            <input 
                                type="text" 
                                name="program_name" 
                                class="form-control @error('program_name') is-invalid @enderror" 
                                id="program_name" 
                                placeholder="Program Name" 
                                required
                            >
                            @error('program_name')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label"><strong>Email:</strong></label>
                            <input 
                                type="email" 
                                name="email" 
                                class="form-control @error('email') is-invalid @enderror" 
                                id="email" 
                                placeholder="Email" 
                                required
                            >
                            @error('email')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label"><strong>Phone:</strong></label>
                            <input 
                                type="text" 
                                name="phone" 
                                class="form-control @error('phone') is-invalid @enderror" 
                                id="phone" 
                                placeholder="Phone" 
                                required
                            >
                            @error('phone')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Message -->
                        <div class="col-md-12 mb-3">
                            <label for="message" class="form-label"><strong>Message:</strong></label>
                            <textarea 
                                name="message" 
                                class="form-control @error('message') is-invalid @enderror" 
                                id="message" 
                                placeholder="Message" 
                                required
                            ></textarea>
                            @error('message')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Submit</button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
