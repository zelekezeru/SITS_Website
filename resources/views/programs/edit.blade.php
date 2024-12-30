<x-admin-layout>
    <div class="container mt-5 pt-5">
        <div class="card mt-5">
            <h2 class="card-header text-center">Add New Program</h2>
            <div class="card-body">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a class="btn btn-primary btn-sm" href="{{ route('programs.list') }}"><i class="fa fa-arrow-left"></i> Back</a>
                </div>

                <form action="{{ route('programs.update', $program) }}" method="POST" enctype="multipart/form-data">

                    @csrf

                    @method('patch')

                    @include('programs.form')

                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Update</button>

                </form>

            </div>
        </div>
    </div>
</x-admin-layout>
