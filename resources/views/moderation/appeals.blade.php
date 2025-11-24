@extends('layouts.app')

@section('content')
<div class="moderation-container">
    <!-- Header -->
    <div class="moderation-header">
        <div class="container mx-auto px-6">
            <h1 class="moderation-title">Review Appeals</h1>
            <p class="moderation-subtitle">Process user appeals and requests</p>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8">
        <div class="moderation-card slide-in">
            <div class="moderation-card-header">
                <h6 class="moderation-card-title">All Appeals</h6>
                <a href="{{ route('moderation.dashboard') }}" class="moderation-btn moderation-btn-secondary moderation-btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="moderation-card-body">
                @if($appeals->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="moderation-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($appeals as $appeal)
                                <tr>
                                    <td>{{ $appeal->id }}</td>
                                    <td>{{ $appeal->user->username ?? 'N/A' }}</td>
                                    <td>
                                        <span class="moderation-badge {{ $appeal->appealable_type == 'App\\Models\\Warning' ? 'warning' : 'danger' }}">
                                            {{ $appeal->appealable_type == 'App\\Models\\Warning' ? 'Warning' : 'Restriction' }}
                                        </span>
                                    </td>
                                    <td>{{ Str::limit($appeal->reason, 50) }}</td>
                                    <td>
                                        <span class="moderation-badge {{ $appeal->status }}">
                                            {{ ucfirst($appeal->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $appeal->created_at->format('M j, Y H:i') }}</td>
                                    <td>
                                        <div class="flex gap-2">
                                            <button class="moderation-btn moderation-btn-success moderation-btn-sm"
                                                    onclick="updateAppealStatus({{ $appeal->id }}, 'approved')"
                                                    {{ $appeal->status != 'pending' ? 'disabled' : '' }}>
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="moderation-btn moderation-btn-danger moderation-btn-sm"
                                                    onclick="updateAppealStatus({{ $appeal->id }}, 'rejected')"
                                                    {{ $appeal->status != 'pending' ? 'disabled' : '' }}>
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="flex justify-center mt-6">
                        {{ $appeals->links() }}
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-gavel"></i>
                        </div>
                        <div class="empty-state-title">No appeals found</div>
                        <div class="empty-state-description">There are no appeals to review at this time</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function updateAppealStatus(id, status) {
    if (confirm('Are you sure you want to ' + status + ' this appeal?')) {
        // Show loading state
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.innerHTML = '<span class="loading-spinner"></span> Processing...';
        button.disabled = true;
        
        fetch(`/api/moderation/appeals/${id}/${status}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
                button.innerHTML = originalContent;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating appeal status.');
            button.innerHTML = originalContent;
            button.disabled = false;
        });
    }
}
</script>
@endsection