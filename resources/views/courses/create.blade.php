<x-admin-layout>
    <div class="container mt-5 pt-5">
        <div class="card mt-5">
            <h2 class="card-header text-center">Add New Course</h2>
            <div class="card-body">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a class="btn btn-primary btn-sm" href="{{ route('courses.index') }}"><i class="fa fa-arrow-left"></i> Back</a>
                </div>

                <form action="{{ route('courses.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="inputName" class="form-label"><strong>Course Title:</strong></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" id="inputTitle" placeholder="Course Title" required>
                            @error('title')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="inputEmail" class="form-label"><strong>Description:</strong></label>
                            <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" id="inputDescription" placeholder="Description" required>
                            @error('description')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="inputLocation" class="form-label"><strong>Category:</strong></label>
                            <input type="text" name="category" class="form-control @error('category') is-invalid @enderror" id="inputCategory" placeholder="Category" required>
                            @error('category')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="inputContact" class="form-label"><strong>Credit Hours:</strong></label>
                            <input type="number" name="credit_hours" class="form-control @error('credit_hours') is-invalid @enderror" id="inputCredit_hours" placeholder="Credit_hours" required>
                            @error('credit_hours')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="inputContact" class="form-label"><strong>Amount paid:</strong></label>
                            <input type="number" name="amount_paid" class="form-control @error('amount_paid') is-invalid @enderror" id="inputAmount_paid" placeholder="Amount_paid" required>
                            @error('amount_paid')
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
