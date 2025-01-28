<x-admin-layout>
    <div class="container topCard pt-5">
        <div class="card mt-5 ">
          <h2 class="card-header text-center">List Of Blogs</h2>
          <div class="card-body">

              @if(session('success'))
                  <div class="alert alert-success" role="alert">
                      {{ session('success') }}
                  </div>
              @endif

              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                  <a class="btn btn-success btn-sm" href="{{ route('blogs.create') }}">
                      <i class="fa fa-plus"></i> Create New Blog
                  </a>
              </div>

              <div class="table-responsive mt-4">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="80px">No</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Date</th>
                            <th>Comments</th>
                            <th width="250px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($blogs as $blog)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $blog->title }}</td>
                                <td>{{ $blog->category }}</td>
                                <td>{{ $blog->author }}</td>
                                <td>{{ $blog->date }}</td>
                                <td>{{ $blog->comments }}</td>
                                <td class="d-flex">
                                    <a class="btn btn-info btn-sm mx-2" href="{{ route('blogs.show', $blog->id) }}">
                                        <i class="fa-solid fa-list"></i> Show
                                    </a>
                                    <a class="btn btn-primary btn-sm mx-2" href="{{ route('blogs.edit', $blog->id) }}">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </a>
                                    <form action="{{ route('blogs.destroy', $blog->id) }}" method="POST" class="mx-2">
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
                                <td colspan="11">There are no data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>


              {!! $blogs->links() !!}

          </div>
      </div>
  </div>
</x-admin-layout>
