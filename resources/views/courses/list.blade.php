<x-admin-layout>
    <div class="container topCard pt-5">
        <div class="card mt-5 ">
            <h2 class="card-header text-center">List Of Courses</h2>
            <div class="card-body">
                    
                @if(session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif
  
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a class="btn btn-success btn-sm" href="{{ route('courses.create') }}"> 
                        <i class="fa fa-plus"></i> Create New Course
                    </a>
                </div>
  
                <div class="table-responsive mt-4">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="80px">No</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Credit Hours</th>
                                <th width="250px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($courses as $course)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $course->title }}</td>
                                    <td>{{ $course->description }}</td>
                                    <td>{{ $course->category }}</td>
                                    <td>{{ $course->credit_hours }}</td>
                                    <td class="d-flex">
                                        <a class="btn btn-info btn-sm mx-2" href="{{ route('courses.show', $course->id) }}">
                                            <i class="fa-solid fa-list"></i> Show
                                        </a>
                                        <a class="btn btn-primary btn-sm mx-2" href="{{ route('courses.edit', $course->id) }}">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <form action="{{ route('courses.destroy', $course->id) }}" method="POST" class="mx-2">
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
  
                {!! $courses->links() !!}
            
            </div>
        </div>
    </div>
  </x-admin-layout>
  