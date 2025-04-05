<x-admin-layout>
    <div class="container mt-5 pt-5">
        <!-- Course Details Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Course Details</h3>
                <a href="{{ route('courses.list') }}" class="btn btn-primary btn-sm float-end">Back to Courses</a>
            </div>
            <div class="card-body">
                <!-- Course Title -->
                <h4 class="mb-3"><i class="fas fa-book"></i> {{ $course->title }}</h4>

                <!-- Course Details -->
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Description:</strong> {{ $course->description }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Category:</strong> {{ $course->category }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Instructor:</strong> {{ $course->instructor }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Credit Hours:</strong> {{ $course->credit_hours }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Amount Paid:</strong> ${{ $course->amount_paid }}</p>
                    </div>
                </div>

                <!-- Edit and Delete buttons -->
                <div class="mt-4">
                    <a href="{{ route('courses.edit', $course->id) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit Course
                    </a>

                    <form action="{{ route('courses.destroy', $course->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this course?')">
                            <i class="fas fa-trash"></i> Delete Course
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
