<x-admin-layout>
    <div class="container mt-5 pt-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Task Details</h3>
                <a href="{{ route('tasks.list') }}" class="btn btn-primary btn-sm float-end">Back to Tasks</a>
            </div>
            <div class="card-body">
                <h4 class="mb-3">
                    <i class="fas fa-tasks"></i> {{ $task->title }}
                </h4>
                <p>
                    <strong>Description:</strong><br>
                    {{ $task->description }}
                </p>
                <p>
                    <strong>Status:</strong>
                    <span class="badge bg-{{ $task->status == 'completed' ? 'success' : ($task->status == 'pending' ? 'warning' : 'secondary') }}">
                        {{ ucfirst($task->status) }}
                    </span>
                </p>
                <p>
                    <strong>Duration:</strong> 
                    {{ $task->duration }} Days
                </p>
            </div>
            <div class="card-footer">
                <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Edit Task
                </a>
                <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this task?')">
                        <i class="fas fa-trash"></i> Delete Task
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
