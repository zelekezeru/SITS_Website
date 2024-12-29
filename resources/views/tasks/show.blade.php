<x-admin-layout>
    <div class="container mt-5 pt-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Task Details</h3>
                <a href="{{ route('tasks.list') }}" class="btn btn-primary btn-sm float-end">Back to Tasks</a>
            </div>
            <div class="card-body">
                <h4 class="mb-3"><i class="fas fa-tasks"></i> {{ $task->title }}</h4>
                <p><strong>Description:</strong> {{ $task->description }}</p>
                <p><strong>Status:</strong> {{ ucfirst($task->status) }}</p>
                {{-- <p><strong>Due Date:</strong> {{ $task->due_date->format('F j, Y') }}</p> --}}
            </div>
        </div>

        <!-- Add New KPI Form (Collapsible) -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Add New KPI</h3>
                <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse"
                    data-bs-target="#addKPIForm">
                    Add KPI
                </button>
            </div>
            <div id="addKPIForm" class="collapse">
                <div class="card-body">
                    <form action="{{ route('kpis.store', $task->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="performance_indicators" class="form-label">Performance Indicators</label>
                            <textarea name="performance_indicators" id="performance_indicators" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="qualitative" class="form-label">Qualitative</label>
                            <textarea name="qualitative" id="qualitative" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="quantitative" class="form-label">Quantitative</label>
                            <input type="number" name="quantitative" id="quantitative" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="ratings" class="form-label">Ratings (0-5)</label>
                            <input type="number" name="ratings" id="ratings" class="form-control" step="0.1"
                                min="0" max="5" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm">Submit KPI</button>
                    </form>
                </div>
            </div>
        </div>
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- List of Existing KPIs -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Key Performance Indicators (KPIs)</h3>
            </div>
            <div class="card-body">
                @if ($task->kpis->isEmpty())
                    <p>No KPIs have been added yet for this task.</p>
                @else
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Performance Indicators</th>
                                <th>Qualitative</th>
                                <th>Quantitative</th>
                                <th>Ratings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($task->kpis as $kpi)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <!-- Editable fields -->
                                    <form action="{{ route('kpis.update', $kpi->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <td>
                                            <textarea name="performance_indicators" class="form-control" rows="2" required>{{ $kpi->performance_indicators }}</textarea>
                                        </td>
                                        <td>
                                            <textarea name="qualitative" class="form-control" rows="2" required>{{ $kpi->qualitative }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" name="quantitative" class="form-control"
                                                value="{{ $kpi->quantitative }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="ratings" class="form-control"
                                                value="{{ $kpi->ratings }}" step="0.1" min="0"
                                                max="5" required>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <button type="submit"
                                                    class="btn btn-success btn-sm me-2">Update</button>
                                            </div>
                                        </td>
                                    </form>
                                    <!-- Delete Form -->
                                    <td>
                                        <form action="{{ route('kpis.destroy', $kpi->id) }}" method="POST"
                                            style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Are you sure you want to delete this KPI?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
