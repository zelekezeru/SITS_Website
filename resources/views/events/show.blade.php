<x-admin-layout>
    <div class="container mt-5 pt-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Event Details</h3>
                <a href="{{ route('events.list') }}" class="btn btn-primary btn-sm float-end">Back to Events</a>
            </div>
            <div class="card-body">
                <h4 class="mb-3"><i class="fas fa-calendar-alt"></i> {{ $event->title }}</h4>
                <p><strong>Description:</strong> {{ $event->description }}</p>
                <p><strong>Date:</strong> {{ $event->date }}</p>
                <p><strong>Location:</strong> {{ $event->location }}</p>
                <p><strong>Status:</strong> 
                    <span class="badge @if($event->status === 'upcoming') bg-info 
                                        @elseif($event->status === 'completed') bg-success 
                                        @elseif($event->status === 'cancelled') bg-danger 
                                        @else bg-secondary @endif">
                        {{ ucfirst($event->status) }}
                    </span>
                </p>
            </div>
            <div class="card-footer">
                <a href="{{ route('events.edit', $event->id) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Edit Event
                </a>
                <form action="{{ route('events.destroy', $event->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this event?')">
                        <i class="fas fa-trash"></i> Delete Event
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
