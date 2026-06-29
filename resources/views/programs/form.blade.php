<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Program Title -->
    <div class="space-y-2">
        <label for="inputTitle" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Program Title</label>
        <input type="text" name="title" id="inputTitle" required
               class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
               placeholder="Program Title" value="{{ old('title', isset($program) ? $program->title : '') }}">
        @error('title')
            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Code -->
    <div class="space-y-2">
        <label for="inputCode" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Code</label>
        <input type="text" name="code" id="inputCode" required
               class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
               placeholder="e.g. TH-M" value="{{ old('code', isset($program) ? $program->code : '') }}">
        @error('code')
            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Description -->
    <div class="space-y-2 md:col-span-2">
        <label for="inputDescription" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Description</label>
        <textarea name="description" id="inputDescription" rows="4" required
                  class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
                  placeholder="Provide a detailed description of the program, curriculum, and objectives...">{{ old('description', isset($program) ? $program->description : '') }}</textarea>
        @error('description')
            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Division -->
    <div class="space-y-2">
        <label for="inputDivision" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Division</label>
        <input type="text" name="division" id="inputDivision" required
               class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
               placeholder="e.g. Graduate, Undergraduate" value="{{ old('division', isset($program) ? $program->division : '') }}">
        @error('division')
            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Language -->
    <div class="space-y-2">
        <label for="inputLanguage" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Language</label>
        <input type="text" name="language" id="inputLanguage" required
               class="w-full px-4 py-3 rounded-xl bg-slate-950/60 border border-slate-800/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 text-sm transition outline-none"
               placeholder="e.g. English, Amharic" value="{{ old('language', isset($program) ? $program->language : '') }}">
        @error('language')
            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
