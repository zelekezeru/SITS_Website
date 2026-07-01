<x-admin-layout>
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Breadcrumbs & Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="font-outfit text-3xl font-extrabold text-white tracking-tight">Users Management</h1>
                <p class="text-xs sm:text-sm text-slate-400">Manage user accounts, authentication credentials, and system roles.</p>
            </div>
            <a class="inline-flex items-center space-x-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-600/20 hover:scale-[1.02] active:scale-[0.98] transition duration-200" href="{{ route('users.create') }}">
                <i class="fa fa-plus text-xs"></i>
                <span>Create New User</span>
            </a>
        </div>

        <!-- Main Card -->
        <div class="rounded-3xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-900 bg-slate-950/20 flex justify-between items-center">
                <h2 class="font-outfit text-lg font-bold text-white">All Users</h2>
                <span class="px-3 py-1 bg-indigo-500/10 text-indigo-400 text-xs font-semibold rounded-full border border-indigo-500/20">
                    {{ $users->total() }} Total
                </span>
            </div>
            
            <div class="p-6">
                <div class="overflow-x-auto rounded-2xl border border-slate-900 bg-slate-950/20">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-950/80 border-b border-slate-900 text-slate-400 text-xs font-bold uppercase tracking-wider">
                                <th class="py-4 px-6 w-16 text-center">No</th>
                                <th class="py-4 px-6">Name</th>
                                <th class="py-4 px-6">Email</th>
                                <th class="py-4 px-6">Staff No</th>
                                <th class="py-4 px-6">Salary</th>
                                <th class="py-4 px-6">Role</th>
                                <th class="py-4 px-6 text-center w-36">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-900 text-sm">
                            @forelse ($users as $user)
                                <tr class="hover:bg-white/5 transition duration-150">
                                    <td class="py-4 px-6 text-center font-medium text-slate-500">{{ $loop->iteration + ($users->currentPage() - 1) * $users->perPage() }}</td>
                                    <td class="py-4 px-6 font-semibold text-white">
                                        <div class="flex items-center space-x-3">
                                            <img src="{{ $user->profile_image ? Storage::url($user->profile_image) : asset('img/user.png') }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover ring-1 ring-white/10">
                                            <span>{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-slate-300 font-medium">{{ $user->email }}</td>
                                    <td class="py-4 px-6 text-slate-300 font-medium font-mono text-xs">
                                        {{ $user->employee->staff_no ?? '-' }}
                                    </td>
                                    <td class="py-4 px-6 text-slate-300 font-medium">
                                        @if($user->employee && $user->employee->base_salary > 0)
                                            <span class="text-emerald-400 font-semibold font-mono">{{ number_format($user->employee->base_salary, 2) }} ETB</span>
                                        @else
                                            <span class="text-slate-500">-</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6">
                                        <form action="{{ route('users.update', $user->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('PUT')
                                            <!-- Hidden inputs to satisfy validation in UserController@update -->
                                            <input type="hidden" name="name" value="{{ $user->name }}" />
                                            <input type="hidden" name="email" value="{{ $user->email }}" />
                                            
                                            <select name="role" onchange="this.form.submit()" 
                                                    class="bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 rounded-lg px-2.5 py-1.5 text-slate-300 text-xs focus:outline-none cursor-pointer">
                                                @foreach ($roles as $r)
                                                    <option value="{{ $r->name }}" {{ $user->hasRole($r->name) ? 'selected' : '' }}>
                                                        {{ $r->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <div class="flex justify-center items-center space-x-2">
                                            <!-- View -->
                                            <a href="{{ route('users.show', $user->id) }}" class="w-8 h-8 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/25 text-emerald-400 flex items-center justify-center transition duration-150" title="View Profile">
                                                <i class="fa fa-eye text-xs"></i>
                                            </a>
                                            <!-- Edit -->
                                            <a href="{{ route('users.edit', $user->id) }}" class="w-8 h-8 rounded-lg bg-indigo-500/10 hover:bg-indigo-500/25 text-indigo-400 flex items-center justify-center transition duration-150" title="Edit Account">
                                                <i class="fa fa-edit text-xs"></i>
                                            </a>
                                            <!-- Delete -->
                                            <button type="button" class="w-8 h-8 rounded-lg bg-rose-500/10 hover:bg-rose-500/25 text-rose-400 flex items-center justify-center transition duration-150" onclick="confirmDelete({{ $user->id }})" title="Delete Account">
                                                <i class="fa fa-trash text-xs"></i>
                                            </button>
                                            <!-- Delete Form -->
                                            <form id="delete-form-{{ $user->id }}" action="{{ route('users.destroy', $user->id) }}" method="POST" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 px-6 text-center text-slate-500 font-medium">No users found in the database.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {!! $users->links() !!}
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert Success Notifications -->
    @if (session('status'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session('status') }}',
                background: '#0f172a',
                color: '#f8fafc',
                confirmButtonColor: '#4f46e5',
                customClass: {
                    popup: 'rounded-3xl border border-slate-800 shadow-2xl backdrop-blur-md',
                    confirmButton: 'rounded-xl px-5 py-2.5 text-sm font-semibold'
                }
            });
        });
    </script>
    @endif
</x-admin-layout>
