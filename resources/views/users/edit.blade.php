<x-admin-layout>
    <div class="container d-flex justify-content-center align-items-center vh-100 full-height">
        <div class="row w-50 mt-5 shadow rounded-4 bg-white p-4">
            <h2 class="mb-4 text-info text-center">Edit User</h2>
            <form method="POST" action="{{ route('users.update', $user->id) }}">
                @csrf
                @method('PUT') <!-- Use PUT method for updates -->

                @include('users.form')

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary w-100 mb-3">Update User</button>

                <!-- Back Link -->
                <div class="text-center">
                    <a href="{{ route('users.list') }}">Back to User List</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
