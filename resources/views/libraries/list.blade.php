<x-admin-layout>
    <div class="container topCard pt-5">
        <div class="card mt-5 ">
            <h2 class="card-header text-center">List Of Libraries</h2>
            <div class="card-body">

                @if(session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a class="btn btn-success btn-sm" href="{{ route('libraries.create') }}">
                        <i class="fa fa-plus"></i> Create New library
                    </a>
                </div>

                <div class="table-responsive mt-4">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="80px">No</th>
                                <th>Title</th>
                                <th>Image</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th width="250px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($libraries as $library)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $library->title }}</td>
                                    <td>
                                        @if($library->banner)
                                            <img src="{{ asset('storage/' . $library->banner) }}" alt="{{ $library->title }}" width="60">
                                        @else
                                            No Image
                                        @endif
                                    </td>
                                    <td>{{ $library->category }}</td>
                                    <td>{{ $library->description }}</td>
                                    <td class="d-flex">
                                        <a class="btn btn-info btn-sm mx-2" href="{{ route('libraries.show', $library->id) }}">
                                            <i class="fa-solid fa-list"></i> Show
                                        </a>
                                        <a class="btn btn-primary btn-sm mx-2" href="{{ route('libraries.edit', $library->id) }}">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <form action="{{ route('libraries.destroy', $library->id) }}" method="POST" class="mx-2">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">There are no data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {!! $libraries->links() !!}

            </div>
        </div>
    </div>
  </x-admin-layout>
