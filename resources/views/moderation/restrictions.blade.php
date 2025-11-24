@extends('layouts.app')

@section('content')
<div class="moderation-container">
    <!-- Header -->
    <div class="moderation-header">
        <div class="container mx-auto px-6">
            <h1 class="moderation-title">Manage User Restrictions</h1>
            <p class="moderation-subtitle">Apply and lift user restrictions</p>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8">
        <div class="moderation-card slide-in">
            <div class="moderation-card-header">
                <h6 class="moderation-card-title">Active Restrictions</h6>
                <a href="{{ route('moderation.dashboard') }}" class="moderation-btn moderation-btn-secondary moderation-btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="moderation-card-body">
                @if($restrictions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="moderation-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Expires</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($restrictions as $restriction)
                                <tr>
                                    <td>{{ $restriction->id }}</td>
                                    <td>{{ $restriction->user->username ?? 'N/A' }}</td>
                                    <td>
                                        <span class="moderation-badge {{ $restriction->type }}">
                                            {{ ucfirst($restriction->type) }}
                                        </span>
                                    </td>
                                    <td>{{ Str::limit($restriction->reason, 50) }}</td>
                                    <td>
                                        <span class="moderation-badge {{ $restriction->is_active ? 'active' : 'inactive' }}">
                                            {{ $restriction->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($restriction->expires_at)
                                            {{ $restriction->expires_at->format('M j, Y H:i') }}
                                        @else
                                            Permanent
                                        @endif
                                    </td>
                                    <td>{{ $restriction->created_at->format('M j, Y H:i') }}</td>
                                    <td>
                                        <button class="moderation-btn moderation-btn-{{ $restriction->is_active ? 'secondary' : 'success' }} moderation-btn-sm"
                                                onclick="toggleRestriction({{ $restriction->id }}, {{ $restriction->is_active ? 'false' : 'true' }})">
                                            <i class="fas fa-{{ $restriction->is_active ? 'ban' : 'check' }}"></i>
                                            {{ $restriction->is_active ? 'Lift' : 'Reinstate' }}
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="flex justify-center mt-6">
                        {{ $restrictions->links() }}
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="empty-state-title">No active restrictions</div>
                        <div class="empty-state-description">There are no active restrictions at this time</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function toggleRestriction(id, activate) {
    if (confirm('Are you sure you want to ' + (activate ? 'reinstate' : 'lift') + ' this restriction?')) {
        // Show loading state
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.innerHTML = '<span class="loading-spinner"></span> Processing...';
        button.disabled = true;
        
        fetch(`/api/moderation/restrictions/${id}/${activate ? 'reinstate' : 'lift'}`, {
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
            alert('An error occurred while updating the restriction.');
            button.innerHTML = originalContent;
            button.disabled = false;
        });
    }
}
</script>
@endsection