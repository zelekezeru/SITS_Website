<x-admin-layout>
    <div class="container mt-5 pt-5">
        <div class="container mt-5">
            <div class="row">
                <!-- Task Details Section -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Task Details</h3>
                            <a href="#" class="btn btn-primary btn-sm float-end">Back to Tasks</a>
                        </div>
                        <div class="card-body">
                            <h4 class="mb-3"><i class="fas fa-tasks"></i> Task Title</h4>
                            <p><strong>Description:</strong> Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                            <p><strong>Budget:</strong> $500.00</p>
                            <p>
                                <strong>Status:</strong>
                                <span class="badge bg-info">In Progress</span>
                            </p>
                            <p><strong>Duration:</strong> 8 Hours</p>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-secondary btn-sm" id="toggleFeedbackBtn">
                                <i class="fas fa-comments"></i> Show Feedback
                            </button>
                            <a href="#" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit Task
                            </a>
                            <button type="button" class="btn btn-danger btn-sm" onclick="alert('Delete functionality not implemented yet!')">
                                <i class="fas fa-trash"></i> Delete Task
                            </button>
                        </div>
                    </div>
        
                    <!-- Feedback Section -->
                    <div class="card mt-3 d-none" id="feedbackSection">
                        <div class="card-header">
                            <h3 class="card-title">Feedback</h3>
                        </div>
                        <div class="card-body">
                            <!-- Feedback Form -->
                            <form class="mb-3" method="POST" action="{{ route('feedbacks.store', $task) }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="feedback" class="form-label">Your Feedback</label>
                                    <textarea class="form-control" id="feedback" name="comment" rows="4" placeholder="Write your feedback here..."></textarea>
                                    @error('comment')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Post Feedback</button>
                            </form>
        
                            <hr>
        
                            <!-- Feedback List -->
                            <div class="feedback-section" style="max-height: 300px; overflow-y: auto;">
                                <h5>Recent Feedback</h5>
                                <div class="feedback-list">
                                    <!-- Feedback Item -->
                                    @forelse ($task->feedbacks as $feedback)
                                        @if ($feedback->feedback_id == null) 
                                            <div class="feedback-item mb-3 p-3" style="border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9;">
                                                <strong>{{ $feedback->user->name }}</strong>
                                                <small class="text-muted">2 hours ago</small>
                                                <p>{{ $feedback->comment }}</p>
        
                                                <!-- Reply Toggle -->
                                                <a href="javascript:void(0);" class="text-decoration-underline toggle-reply-btn" style="cursor: pointer;">Reply</a>
        
                                                <!-- Reply Section -->
                                                <div class="reply-section d-none mt-3 ps-4">
                                                    <!-- Reply Form -->
                                                    <form action="{{ route('feedbacks.store', $task) }}" method="POST">
                                                        @csrf
                                                        <textarea class="form-control" name="comment" rows="2" placeholder="Reply to this feedback..."></textarea>
                                                        @error('comment')
                                                            <div class="text-danger">{{ $message }}</div>
                                                        @enderror
                                                        <input type="hidden" value="{{ $feedback->id}}" name="reply_to">
                                                        <button type="submit" class="btn btn-primary btn-sm">Post Reply</button>
                                                    </form>
        
                                                    <!-- Replies -->
                                                    <div class="mt-3">
                                                        <h6>Replies:</h6>
                                                        @forelse ($feedback->replies as $reply)
                                                            <div class="reply-item border-start ps-3 mb-2">
                                                                <strong>{{ $reply->user->name }}</strong>
                                                                <small class="text-muted">1 hour ago</small>
                                                                <p>{{ $reply->comment }}</p>
                                                            </div>
                                                        @empty
                                                            <p>No replies yet!</p>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @empty
                                        <p>No feedbacks available yet.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Toggle feedback section visibility
            document.getElementById('toggleFeedbackBtn').addEventListener('click', function () {
                const feedbackSection = document.getElementById('feedbackSection');
                feedbackSection.classList.toggle('d-none');
                this.textContent = feedbackSection.classList.contains('d-none') ? 'Show Feedback' : 'Hide Feedback';
            });

            // Toggle reply section visibility
            document.querySelectorAll('.toggle-reply-btn').forEach(link => {
                link.addEventListener('click', function () {
                    const replySection = this.nextElementSibling; // Target the reply section
                    replySection.classList.toggle('d-none');
                });
            });
        </script>
        
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
                            @error('performance_indicators')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="qualitative" class="form-label">Qualitative</label>
                            <textarea name="qualitative" id="qualitative" class="form-control" rows="2" required></textarea>
                            @error('qualitative')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="quantitative" class="form-label">Quantitative</label>
                            <input type="number" name="quantitative" id="quantitative" class="form-control" required>
                            @error('quantitative')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="ratings" class="form-label">Ratings (0-5)</label>
                            <input type="number" name="ratings" id="ratings" class="form-control" step="0.1"
                                min="0" max="5" required>
                            @error('ratings')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
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
                                            <input type="text" name="performance_indicators" class="form-control" value="{{ $kpi->performance_indicators }}" required>
                                        </td>
                                        <td>
                                            <input type="text" name="qualitative" class="form-control" value="{{ $kpi->qualitative }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="quantitative" class="form-control" value="{{ $kpi->quantitative }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="ratings" class="form-control" value="{{ $kpi->ratings }}" step="0.1" min="0" max="5" required>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <button type="submit" class="btn btn-success btn-sm me-2">Update</button>
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
