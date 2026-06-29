<x-admin-layout>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="font-outfit text-3xl font-extrabold text-white tracking-tight">Add New Course</h1>
                <p class="text-sm text-slate-400">Create a new course entry in the curriculum registry.</p>
            </div>
            <a class="inline-flex items-center space-x-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-sm font-semibold rounded-xl transition duration-200" href="{{ route('courses.list') }}">
                <i class="fa fa-arrow-left text-xs"></i>
                <span>Back</span>
            </a>
        </div>

        <!-- Form Card -->
        <div class="rounded-3xl bg-slate-900/40 backdrop-blur-md border border-slate-800/80 shadow-2xl p-6 sm:p-8">
            <form action="{{ route('courses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Title -->
                    <div class="space-y-2">
                        <label for="inputTitle" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Course Title</label>
                        <input type="text" name="title" id="inputTitle" required
                               class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
                               placeholder="e.g. Systematic Theology I">
                        @error('title')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div class="space-y-2">
                        <label for="inputCategory" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Category</label>
                        <input type="text" name="category" id="inputCategory" required
                               class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
                               placeholder="e.g. Theology, Ministry">
                        @error('category')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="space-y-2 md:col-span-2">
                        <label for="inputDescription" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Description</label>
                        <input type="text" name="description" id="inputDescription" required
                               class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
                               placeholder="Brief overview of the course curriculum and objectives">
                        @error('description')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Credit Hours -->
                    <div class="space-y-2">
                        <label for="inputCredit_hours" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Credit Hours</label>
                        <input type="number" name="credit_hours" id="inputCredit_hours" required
                               class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
                               placeholder="e.g. 3">
                        @error('credit_hours')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Instructor -->
                    <div class="space-y-2">
                        <label for="inputInstructor" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Instructor</label>
                        <input type="text" name="instructor" id="inputInstructor" required
                               class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
                               placeholder="Instructor Name">
                        @error('instructor')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Amount Paid -->
                    <div class="space-y-2">
                        <label for="inputAmount_paid" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Amount / Fee (ETB)</label>
                        <input type="number" name="amount_paid" id="inputAmount_paid" required
                               class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
                               placeholder="e.g. 1500">
                        @error('amount_paid')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Banner File -->
                    <div class="space-y-2">
                        <label for="inputBanner" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Course Banner Image</label>
                        <input type="file" name="banner" id="inputBanner"
                               class="w-full px-4 py-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-slate-400 text-sm transition outline-none file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-500/10 file:text-indigo-400 hover:file:bg-indigo-500/20 file:cursor-pointer">
                        @error('banner')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end gap-3 border-t border-slate-900/60 pt-6 mt-8">
                    <a href="{{ route('courses.list') }}" 
                       class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white transition duration-200">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/20 hover:scale-[1.02] active:scale-[0.98] transition duration-200">
                        <i class="fa-solid fa-floppy-disk mr-1.5 text-xs"></i>
                        <span>Save Course</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
