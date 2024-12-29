
<div class="row">
    <!-- Program Title -->
    <div class="col-md-6 mb-3">
        <label for="inputTitle" class="form-label"><strong>Program Title:</strong></label>
        <input type="text" name="title"
                class="form-control @error('title') is-invalid @enderror" id="inputTitle"
                placeholder="Program Title" value="{{ old('title', isset($program) ? $program->title : '') }}" required>
        @error('title')
            <div class="form-text text-danger">{{ $message }}</div>
        @enderror
    </div>

    <!-- Description -->
    <div class="col-md-6 mb-3">
        <label for="inputDescription" class="form-label"><strong>Description:</strong></label>
        <textarea name="description" class="form-control @error('description') is-invalid @enderror" id="inputDescription"
                  placeholder="Description" required>{{ old('description', isset($program) ? $program->description : '') }}</textarea>
        @error('description')
            <div class="form-text text-danger">{{ $message }}</div>
        @enderror
    </div>

    <!-- Code -->
    <div class="col-md-6 mb-3">
        <label for="inputCode" class="form-label"><strong>Code:</strong></label>
        <input type="text" name="code"
                class="form-control @error('code') is-invalid @enderror" id="inputCode"
                placeholder="Code" value="{{ old('code', isset($program) ? $program->code : '') }}" required>
        @error('code')
            <div class="form-text text-danger">{{ $message }}</div>
        @enderror
    </div>

    <!-- Division -->
    <div class="col-md-6 mb-3">
        <label for="inputDivision" class="form-label"><strong>Division:</strong></label>
        <input type="text" name="division"
                class="form-control @error('division') is-invalid @enderror" id="inputDivision"
                placeholder="Division" value="{{ old('division', isset($program) ? $program->division : '') }}" required>
        @error('division')
            <div class="form-text text-danger">{{ $message }}</div>
        @enderror
    </div>

    <!-- Language -->
    <div class="col-md-6 mb-3">
        <label for="inputLanguage" class="form-label"><strong>Language:</strong></label>
        <input type="text" name="language"
                class="form-control @error('language') is-invalid @enderror" id="inputLanguage"
                placeholder="Language" value="{{ old('language', isset($program) ? $program->language : '') }}" required>
        @error('language')
            <div class="form-text text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>

