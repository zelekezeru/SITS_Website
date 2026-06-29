<x-admin-layout>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="font-outfit text-3xl font-extrabold text-white tracking-tight">Edit Program</h1>
                <p class="text-sm text-slate-400">Modify the details of an existing academic program.</p>
            </div>
            <a class="inline-flex items-center space-x-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-sm font-semibold rounded-xl transition duration-200" href="{{ route('programs.list') }}">
                <i class="fa fa-arrow-left text-xs"></i>
                <span>Back</span>
            </a>
        </div>

        <!-- Form Card -->
        <div class="rounded-3xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 shadow-2xl p-6 sm:p-8">
            <form action="{{ route('programs.update', $program->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')
                
                @include('programs.form')

                <!-- Submit Buttons -->
                <div class="flex justify-end gap-3 border-t border-slate-900/60 pt-6 mt-8">
                    <a href="{{ route('programs.list') }}" 
                       class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white transition duration-200">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/20 hover:scale-[1.02] active:scale-[0.98] transition duration-200">
                        <i class="fa-solid fa-floppy-disk mr-1.5 text-xs"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
