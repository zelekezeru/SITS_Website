<x-admin-layout>
<div class="container mt-5 pt-5">
    <div class="card mt-5">
        <h2 class="card-header text-center">Edit Course</h2>
        <div class="card-body">
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a class="btn btn-primary btn-sm" href="{{ route('courses.index') }}"><i class="fa fa-arrow-left"></i> Back</a>
            </div>

            <form action="{{ route('courses.update', $course->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="inputTitle" class="form-label"><strong>Course Title:</strong></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" id="inputTitle" value="{{ old('title', $course->title) }}" placeholder="Course Title" required>
                        @error('title')
                            <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="inputDescription" class="form-label"><strong>Description:</strong></label>
                        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" id="inputDescription" value="{{ old('description', $course->description) }}" placeholder="Description" required>
                        @error('description')
                            <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="inputCategory" class="form-label"><strong>Category:</strong></label>
                        <input type="text" name="category" class="form-control @error('category') is-invalid @enderror" id="inputCategory" value="{{ old('category', $course->category) }}" placeholder="Category" required>
                        @error('category')
                            <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="inputCredit_hours" class="form-label"><strong>Credit Hours:</strong></label>
                        <input type="number" name="credit_hours" class="form-control @error('credit_hours') is-invalid @enderror" id="inputCredit_hours" value="{{ old('credit_hours', $course->credit_hours) }}" placeholder="Credit Hours" required>
                        @error('credit_hours')
                            <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="inputAmount_paid" class="form-label"><strong>Amount Paid:</strong></label>
                        <input type="number" name="amount_paid" class="form-control @error('amount_paid') is-invalid @enderror" id="inputAmount_paid" value="{{ old('amount_paid', $course->amount_paid) }}" placeholder="Amount Paid" required>
                        @error('amount_paid')
                            <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
            </form>
        </div>
    </div>
</div>
</x-admin-layout>