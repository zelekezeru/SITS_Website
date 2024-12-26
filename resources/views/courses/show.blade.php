<x-admin-layout>
    <div class="container topCard">
        <div class="card mt-5 pt-5">
            <h2 class="card-header text-center">Show Course</h2>
            <div class="card-body">

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a class="btn btn-primary btn-sm" href="{{ route('courses.index') }}"><i class="fa fa-arrow-left"></i> Back</a>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <span>Title:</span>
                                    <p style="font-size: 1.4rem">{{ $course->title }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <span>description:</span>
                                    <p style="font-size: 1.4rem">{{ $course->description }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <span>Category:</span>
                                    <p style="font-size: 1.4rem">{{ $course->category }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <span>Credit Hours:</span>
                                    <p style="font-size: 1.4rem">{{ $course->credit_hours }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <span>Amount Paid:</span>
                                    <p style="font-size: 1.4rem">{{ $course->amount_paid }}</p>
                                </div>
                            </div>
                           
                            <div class="col-6">
                                <a class="btn btn-primary btn-md" href="{{ route('courses.edit', $course->id) }}"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-admin-layout>
