<x-admin-layout>
  <div class="container topCard pt-5">
      <div class="card mt-5 ">
          <h2 class="card-header text-center">List of Users</h2>
          <div class="card-body">
                  
              @if(session('success'))
                  <div class="alert alert-success" role="alert">
                      {{ session('success') }}
                  </div>
              @endif

              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                  <a class="btn btn-success btn-sm" href="{{ route('users.create') }}"> 
                      <i class="fa fa-plus"></i> Create New User
                  </a>
              </div>

              <div class="table-responsive mt-4">
                  <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th width="80px">No</th>
                              <th>Name</th>
                              <th>Email</th>
                              <th width="250px">Role</th>
                          </tr>
                      </thead>
                      <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <!-- Display the user's role -->
                                    {{ $user->getRoleNames()->first() ?? 'No Role Assigned' }}
                                </td>
                                <td class="d-flex">
                                    <a class="btn btn-info btn-sm mx-2" href="{{ route('users.show', $user->id) }}">
                                        <i class="fa-solid fa-list"></i> Show
                                    </a>
                                    <a class="btn btn-primary btn-sm mx-2" href="{{ route('users.edit', $user->id) }}">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </a>
                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="mx-2">
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
                                <td colspan="5">There are no users available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    
                  </table>
              </div>
              
              {!! $users->links() !!}
          
          </div>
      </div>
  </div>
</x-admin-layout>
