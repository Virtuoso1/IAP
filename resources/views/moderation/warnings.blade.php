@extends('layouts.app')

@section('content')
<div class="moderation-container">
    <!-- Header -->
    <div class="moderation-header">
        <div class="container mx-auto px-6">
            <h1 class="moderation-title">Manage Warnings</h1>
            <p class="moderation-subtitle">View and manage user warnings</p>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8">
        <div class="moderation-card slide-in">
            <div class="moderation-card-header">
                <h6 class="moderation-card-title">All Warnings</h6>
                <a href="{{ route('moderation.dashboard') }}" class="moderation-btn moderation-btn-secondary moderation-btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="moderation-card-body">
                @if($warnings->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="moderation-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Moderator</th>
                                    <th>Reason</th>
                                    <th>Level</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($warnings as $warning)
                                <tr>
                                    <td>{{ $warning->id }}</td>
                                    <td>{{ $warning->user->username ?? 'N/A' }}</td>
                                    <td>{{ $warning->moderator->username ?? 'N/A' }}</td>
                                    <td>{{ Str::limit($warning->reason, 50) }}</td>
                                    <td>
                                        <span class="moderation-badge level-{{ $warning->level }}">
                                            Level {{ $warning->level }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="moderation-badge {{ $warning->is_active ? 'active' : 'inactive' }}">
                                            {{ $warning->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ $warning->created_at->format('M j, Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('moderation.warnings.show', $warning->id) }}" class="moderation-btn moderation-btn-warning moderation-btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="flex justify-center mt-6">
                        {{ $warnings->links() }}
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="empty-state-title">No warnings found</div>
                        <div class="empty-state-description">No warnings have been issued yet</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection