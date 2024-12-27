<x-admin-layout>
    <div class="container mt-5 pt-5">
        <div class="card mt-5">
            
            <!-- Display success message -->
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <h2 class="card-header text-center">Add New Event</h2>
            <div class="card-body">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a class="btn btn-primary btn-sm" href="{{ route('events.index') }}">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>

                <!-- Form to create a new event -->
                <form action="{{ route('events.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <!-- Event Title -->
                        <div class="col-md-6 mb-3">
                            <label for="inputTitle" class="form-label"><strong>Event Title:</strong></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" id="inputTitle" placeholder="Event Title" value="{{ old('title') }}" required>
                            @error('title')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Event Description -->
                        <div class="col-md-6 mb-3">
                            <label for="inputDescription" class="form-label"><strong>Description:</strong></label>
                            <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" id="inputDescription" placeholder="Event Description" value="{{ old('description') }}">
                            @error('description')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Event Date -->
                        <div class="col-md-6 mb-3">
                            <label for="inputDate" class="form-label"><strong>Event Date:</strong></label>
                            <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" id="inputDate" value="{{ old('date') }}" required>
                            @error('date')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Event Start Time -->
                        <div class="col-md-6 mb-3">
                            <label for="inputStartTime" class="form-label"><strong>Start Time:</strong></label>
                            <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" id="inputStartTime" value="{{ old('start_time') }}" required>
                            @error('start_time')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Event End Time -->
                        <div class="col-md-6 mb-3">
                            <label for="inputEndTime" class="form-label"><strong>End Time:</strong></label>
                            <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" id="inputEndTime" value="{{ old('end_time') }}" required>
                            @error('end_time')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Event Location -->
                        <div class="col-md-6 mb-3">
                            <label for="inputLocation" class="form-label"><strong>Location:</strong></label>
                            <input type="text" name="location" class="form-control @error('location') is-invalid @enderror" id="inputLocation" placeholder="Event Location" value="{{ old('location') }}" required>
                            @error('location')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Event Remarks -->
                        <div class="col-md-6 mb-3">
                            <label for="inputRemark" class="form-label"><strong>Remarks:</strong></label>
                            <input type="text" name="remark" class="form-control @error('remark') is-invalid @enderror" id="inputRemark" placeholder="Event Remarks" value="{{ old('remark') }}">
                            @error('remark')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-floppy-disk"></i> Submit
                    </button>
                </form>

            </div>
        </div>
    </div>
</x-admin-layout>
