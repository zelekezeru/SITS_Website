<x-admin-layout>
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Breadcrumbs & Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="font-outfit text-3xl font-extrabold text-white tracking-tight">Courses Management</h1>
                <p class="text-xs sm:text-sm text-slate-400">Manage curriculum, credit hours, and course categories.</p>
            </div>
            <a class="inline-flex items-center space-x-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-600/20 hover:scale-[1.02] active:scale-[0.98] transition duration-200" href="{{ route('courses.create') }}">
                <i class="fa fa-plus text-xs"></i>
                <span>Create New Course</span>
            </a>
        </div>

        <!-- Main Card -->
        <div class="rounded-3xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-900 bg-slate-950/20 flex justify-between items-center">
                <h2 class="font-outfit text-lg font-bold text-white">All Courses</h2>
                <span class="px-3 py-1 bg-indigo-500/10 text-indigo-400 text-xs font-semibold rounded-full border border-indigo-500/20">
                    {{ $courses->total() }} Total
                </span>
            </div>
            
            <div class="p-6">
                <div class="overflow-x-auto rounded-2xl border border-slate-900 bg-slate-950/20">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-950/80 border-b border-slate-900 text-slate-400 text-xs font-bold uppercase tracking-wider">
                                <th class="py-4 px-6 w-16 text-center">#</th>
                                <th class="py-4 px-6">Title</th>
                                <th class="py-4 px-6">Category</th>
                                <th class="py-4 px-6 text-center">Credit Hours</th>
                                <th class="py-4 px-6 text-center w-36">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-900 text-sm">
                            @forelse ($courses as $course)
                                <tr class="hover:bg-white/5 transition duration-150">
                                    <td class="py-4 px-6 text-center font-medium text-slate-500">{{ $loop->iteration + ($courses->currentPage() - 1) * $courses->perPage() }}</td>
                                    <td class="py-4 px-6 font-semibold text-white">{{ $course->title }}</td>
                                    <td class="py-4 px-6">
                                        <span class="inline-block px-2.5 py-1 rounded-md text-xs font-semibold
                                            @if(strtolower($course->category) === 'theology') bg-violet-500/10 text-violet-400 border border-violet-500/20
                                            @elseif(strtolower($course->category) === 'ministry') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                            @else bg-blue-500/10 text-blue-400 border border-blue-500/20 @endif">
                                            {{ $course->category }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-center font-bold text-slate-300">
                                        <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800">{{ $course->credit_hours }} Hrs</span>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <div class="flex justify-center items-center space-x-2">
                                            <!-- View -->
                                            <a href="{{ route('courses.show', $course->id) }}" class="w-8 h-8 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/25 text-emerald-400 flex items-center justify-center transition duration-150" title="View Details">
                                                <i class="fa fa-eye text-xs"></i>
                                            </a>
                                            <!-- Edit -->
                                            <a href="{{ route('courses.edit', $course->id) }}" class="w-8 h-8 rounded-lg bg-indigo-500/10 hover:bg-indigo-500/25 text-indigo-400 flex items-center justify-center transition duration-150" title="Edit Course">
                                                <i class="fa fa-edit text-xs"></i>
                                            </a>
                                            <!-- Delete -->
                                            <button type="button" class="w-8 h-8 rounded-lg bg-rose-500/10 hover:bg-rose-500/25 text-rose-400 flex items-center justify-center transition duration-150" onclick="confirmDelete({{ $course->id }})" title="Delete Course">
                                                <i class="fa fa-trash text-xs"></i>
                                            </button>
                                            <!-- Delete Form -->
                                            <form id="delete-form-{{ $course->id }}" action="{{ route('courses.destroy', $course->id) }}" method="POST" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 px-6 text-center text-slate-500 font-medium">No courses found in the database.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {!! $courses->links() !!}
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