<x-admin-layout>
    <div class="container mt-5 pt-5">
        <div class="card mt-5 col-6">
            <h2 class="card-header text-center">Add New user</h2>
            <div class="card-body">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a class="btn btn-primary btn-sm" href="{{ route('users.list') }}"><i class="fa fa-arrow-left"></i> Back</a>
                </div>

                <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @include('users.form')

                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-floppy-disk"></i> Submit
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
