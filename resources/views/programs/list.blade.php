<x-admin-layout>
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Breadcrumbs & Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="font-outfit text-3xl font-extrabold text-white tracking-tight">Programs Management</h1>
                <p class="text-xs sm:text-sm text-slate-400">Manage academic programs, division codes, and course languages.</p>
            </div>
            <a class="inline-flex items-center space-x-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-600/20 hover:scale-[1.02] active:scale-[0.98] transition duration-200" href="{{ route('programs.create') }}">
                <i class="fa fa-plus text-xs"></i>
                <span>Create New Program</span>
            </a>
        </div>

        <!-- Main Card -->
        <div class="rounded-3xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-900 bg-slate-950/20 flex justify-between items-center">
                <h2 class="font-outfit text-lg font-bold text-white">All Programs</h2>
                <span class="px-3 py-1 bg-indigo-500/10 text-indigo-400 text-xs font-semibold rounded-full border border-indigo-500/20">
                    {{ $programs->total() }} Total
                </span>
            </div>
            
            <div class="p-6">
                <div class="overflow-x-auto rounded-2xl border border-slate-900 bg-slate-950/20">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-950/80 border-b border-slate-900 text-slate-400 text-xs font-bold uppercase tracking-wider">
                                <th class="py-4 px-6 w-16 text-center">#</th>
                                <th class="py-4 px-6">Title</th>
                                <th class="py-4 px-6 text-center">Code</th>
                                <th class="py-4 px-6 text-center">Language</th>
                                <th class="py-4 px-6 text-center w-36">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-900 text-sm">
                            @forelse ($programs as $program)
                                <tr class="hover:bg-white/5 transition duration-150">
                                    <td class="py-4 px-6 text-center font-medium text-slate-500">{{ $loop->iteration + ($programs->currentPage() - 1) * $programs->perPage() }}</td>
                                    <td class="py-4 px-6 font-semibold text-white">
                                        <div>{{ $program->title }}</div>
                                        <div class="text-xs text-slate-500 font-normal mt-0.5">{{ $program->division }} Division</div>
                                    </td>
                                    <td class="py-4 px-6 text-center font-semibold text-slate-300">
                                        <span class="px-2.5 py-1 rounded bg-slate-900 border border-slate-850 text-xs">{{ $program->code }}</span>
                                    </td>
                                    <td class="py-4 px-6 text-center font-medium text-slate-300">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                                            {{ $program->language }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <div class="flex justify-center items-center space-x-2">
                                            <!-- View -->
                                            <a href="{{ route('programs.show', $program->id) }}" class="w-8 h-8 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/25 text-emerald-400 flex items-center justify-center transition duration-150" title="View Details">
                                                <i class="fa fa-eye text-xs"></i>
                                            </a>
                                            <!-- Edit -->
                                            <a href="{{ route('programs.edit', $program->id) }}" class="w-8 h-8 rounded-lg bg-indigo-500/10 hover:bg-indigo-500/25 text-indigo-400 flex items-center justify-center transition duration-150" title="Edit Program">
                                                <i class="fa fa-edit text-xs"></i>
                                            </a>
                                            <!-- Delete -->
                                            <button type="button" class="w-8 h-8 rounded-lg bg-rose-500/10 hover:bg-rose-500/25 text-rose-400 flex items-center justify-center transition duration-150" onclick="confirmDelete({{ $program->id }})" title="Delete Program">
                                                <i class="fa fa-trash text-xs"></i>
                                            </button>
                                            <!-- Delete Form -->
                                            <form id="delete-form-{{ $program->id }}" action="{{ route('programs.destroy', $program->id) }}" method="POST" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 px-6 text-center text-slate-500 font-medium">No programs found in the database.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {!! $programs->links() !!}
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