<x-admin-layout>
  <div class="container mt-5 pt-5">
      <div class="card mt-5">
          <h2 class="card-header text-center">Add New Blog</h2>
          <div class="card-body">
              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                  <a class="btn btn-primary btn-sm" href="{{ route('blogs.list') }}"><i class="fa fa-arrow-left"></i> Back</a>
              </div>

              <form action="{{ route('blogs.store') }}" method="POST" enctype="multipart/form-data">
                  @csrf
                  <div class="row">
                      <div class="col-md-6 mb-3">
                          <label for="inputTitle" class="form-label"><strong>Blog Title:</strong></label>
                          <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" id="inputTitle" placeholder="Blog Title" required>
                          @error('title')
                              <div class="form-text text-danger">{{ $message }}</div>
                          @enderror
                      </div>

                      <div class="col-md-6 mb-3">
                          <label for="inputCategory" class="form-label"><strong>Category:</strong></label>
                          <input type="text" name="category" class="form-control @error('category') is-invalid @enderror" id="inputCategory" placeholder="Category" required>
                          @error('category')
                              <div class="form-text text-danger">{{ $message }}</div>
                          @enderror
                      </div>

                      <div class="col-md-6 mb-3">
                          <label for="inputAuthor" class="form-label"><strong>Author:</strong></label>
                          <input type="text" name="author" class="form-control @error('author') is-invalid @enderror" id="inputAuthor" placeholder="Author" required>
                          @error('author')
                              <div class="form-text text-danger">{{ $message }}</div>
                          @enderror
                      </div>
                      
                      <div class="col-md-12 mb-3">
                        <label for="inputContent" class="form-label"><strong>Content:</strong></label>
                        <textarea name="content" id="editor"></textarea>
                        @error('content')
                            <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                      </div>

                  </div>

                  <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Submit</button>
              </form>

          </div>
      </div>
  </div>
  <script src="https://cdn.ckeditor.com/ckeditor5/34.0.0/classic/ckeditor.js">{{old('content')}}</script>

  <script>
    // Initialize CKEditor
    ClassicEditor
        .create(document.querySelector('#editor'),{ ckfinder: {
          uploadUrl: "{{ route('ckeditor.blog.upload', ['_token'=>csrf_token()]) }}"
        }})
        .then(editor => {
            console.log('Editor was initialized', editor);
        })
        .catch(error => {
            console.error('Error during initialization of the editor', error);
        });
  </script>
</x-admin-layout>
