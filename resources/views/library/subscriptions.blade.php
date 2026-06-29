<x-admin-layout>
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Breadcrumbs & Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="font-outfit text-3xl font-extrabold text-white tracking-tight">Library Subscriptions</h1>
                <p class="text-xs sm:text-sm text-slate-400">Review subscription requests, confirm payments, and activate digital library access.</p>
            </div>
        </div>

        <!-- Main Card -->
        <div class="rounded-3xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-900 bg-slate-950/20 flex justify-between items-center">
                <h2 class="font-outfit text-lg font-bold text-white">All Subscriptions</h2>
                <span class="px-3 py-1 bg-indigo-500/10 text-indigo-400 text-xs font-semibold rounded-full border border-indigo-500/20">
                    {{ $subscriptions->total() }} Total
                </span>
            </div>
            
            <div class="p-6">
                <div class="overflow-x-auto rounded-2xl border border-slate-900 bg-slate-950/20">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-950/80 border-b border-slate-900 text-slate-400 text-xs font-bold uppercase tracking-wider">
                                <th class="py-4 px-6 w-16 text-center">No</th>
                                <th class="py-4 px-6">User</th>
                                <th class="py-4 px-6">Plan Name</th>
                                <th class="py-4 px-6">Amount</th>
                                <th class="py-4 px-6">Payment Ref</th>
                                <th class="py-4 px-6">Duration</th>
                                <th class="py-4 px-6">Status</th>
                                <th class="py-4 px-6 text-center w-36">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-900 text-sm">
                            @forelse ($subscriptions as $sub)
                                <tr class="hover:bg-white/5 transition duration-150">
                                    <td class="py-4 px-6 text-center font-medium text-slate-500">{{ $loop->iteration + ($subscriptions->currentPage() - 1) * $subscriptions->perPage() }}</td>
                                    <td class="py-4 px-6 font-semibold text-white">
                                        <div>
                                            <span class="block text-white font-semibold">{{ $sub->user->name ?? 'Deleted User' }}</span>
                                            <span class="block text-xs text-slate-500 font-medium">{{ $sub->user->email ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-slate-300 font-medium">
                                        <span class="inline-block px-2.5 py-0.5 rounded text-xs font-semibold uppercase bg-{{ $sub->planColour() }}-500/10 text-{{ $sub->planColour() }}-400 border border-{{ $sub->planColour() }}-500/20">
                                            {{ $sub->plan_name }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-slate-300 font-semibold font-mono">
                                        {{ number_format($sub->amount_paid, 2) }} ETB
                                    </td>
                                    <td class="py-4 px-6 text-slate-300 font-medium font-mono text-xs">
                                        <span class="block text-slate-300">{{ $sub->payment_method ?? '-' }}</span>
                                        <span class="block text-[10px] text-slate-500">{{ $sub->payment_reference ?? '-' }}</span>
                                    </td>
                                    <td class="py-4 px-6 text-slate-300 font-medium text-xs">
                                        <span class="block">Start: {{ $sub->start_date ? $sub->start_date->format('d M Y') : '-' }}</span>
                                        <span class="block text-slate-500">
                                            End: {{ $sub->expiry_date ? $sub->expiry_date->format('d M Y') : 'Lifetime' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        @if($sub->is_active)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/25">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                                Pending Approval
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        @if(!$sub->is_active)
                                            <form action="{{ route('library.subscriptions.activate', $sub->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition duration-200 shadow-md shadow-emerald-600/20 hover:scale-[1.02]">
                                                    <i class="fa fa-check mr-1"></i> Activate
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-500 font-medium">No actions</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 px-6 text-center text-slate-500 font-medium">No library subscriptions found in the database.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {!! $subscriptions->links() !!}
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
