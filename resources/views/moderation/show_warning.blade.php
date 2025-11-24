@extends('layouts.app')

@section('content')
<div class="moderation-container">
    <!-- Header -->
    <div class="moderation-header">
        <div class="container mx-auto px-6">
            <h1 class="moderation-title">Warning Details</h1>
            <p class="moderation-subtitle">View warning information and manage user</p>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="moderation-card slide-in">
                    <div class="moderation-card-header">
                        <h6 class="moderation-card-title">Warning Information</h6>
                        <a href="{{ route('moderation.warnings') }}" class="moderation-btn moderation-btn-secondary moderation-btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Warnings
                        </a>
                    </div>
                    <div class="moderation-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <strong>Warning ID:</strong> #{{ $warning->id }}
                            </div>
                            <div>
                                <strong>Status:</strong>
                                <span class="moderation-badge {{ $warning->is_active ? 'active' : 'inactive' }}">
                                    {{ $warning->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <strong>User:</strong> {{ $warning->user->username ?? 'N/A' }}
                            </div>
                            <div>
                                <strong>Moderator:</strong> {{ $warning->moderator->username ?? 'N/A' }}
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <strong>Level:</strong>
                                <span class="moderation-badge level-{{ $warning->level }}">
                                    Level {{ $warning->level }}
                                </span>
                            </div>
                            <div>
                                <strong>Created:</strong> {{ $warning->created_at->format('M j, Y H:i') }}
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <strong>Reason:</strong>
                            <p class="mt-2 text-gray-700 leading-relaxed">{{ $warning->reason }}</p>
                        </div>
                        
                        @if($warning->expires_at)
                        <div class="mb-6">
                            <strong>Expires:</strong> {{ $warning->expires_at->format('M j, Y H:i') }}
                            <br>
                            <small class="text-gray-500">
                                {{ $warning->expires_at->diffForHumans() }}
                            </small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="space-y-6">
                <div class="moderation-card slide-in">
                    <div class="moderation-card-header">
                        <h6 class="moderation-card-title">Actions</h6>
                    </div>
                    <div class="moderation-card-body">
                        @if($warning->is_active)
                            <form method="POST" action="{{ route('api.moderation.warnings.deactivate', $warning->id) }}" class="mb-4">
                                @csrf
                                <button type="submit" class="moderation-btn moderation-btn-warning moderation-btn-block">
                                    <i class="fas fa-times"></i> Deactivate Warning
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('api.moderation.warnings.activate', $warning->id) }}" class="mb-4">
                                @csrf
                                <button type="submit" class="moderation-btn moderation-btn-success moderation-btn-block">
                                    <i class="fas fa-check"></i> Reactivate Warning
                                </button>
                            </form>
                        @endif
                        
                        <hr class="my-4">
                        
                        <form method="POST" action="{{ route('api.moderation.warnings.extend', $warning->id) }}" class="space-y-4">
                            @csrf
                            <div class="moderation-form-group">
                                <label for="days" class="moderation-form-label">Extend by (days):</label>
                                <input type="number" name="days" id="days" class="moderation-form-control" min="1" max="365" value="30" required>
                            </div>
                            <button type="submit" class="moderation-btn moderation-btn-info moderation-btn-block">
                                <i class="fas fa-clock"></i> Extend Warning
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="moderation-card slide-in">
                    <div class="moderation-card-header">
                        <h6 class="moderation-card-title">User Info</h6>
                    </div>
                    <div class="moderation-card-body">
                        @if($warning->user)
                            <div class="user-info-card">
                                <img src="{{ $warning->user->avatar_url }}" class="user-avatar" alt="Avatar">
                                <div class="user-name">{{ $warning->user->username }}</div>
                                <div class="user-email">{{ $warning->user->email }}</div>
                                
                                <div class="user-stats">
                                    <div class="user-stat">
                                        <div class="user-stat-value">{{ $warning->user->warnings()->count() }}</div>
                                        <div class="user-stat-label">Total Warnings</div>
                                    </div>
                                    <div class="user-stat">
                                        <div class="user-stat-value">{{ $warning->user->warnings()->where('is_active', true)->count() }}</div>
                                        <div class="user-stat-label">Active Warnings</div>
                                    </div>
                                    <div class="user-stat">
                                        <div class="user-stat-value">{{ $warning->user->getActiveRestrictions()->count() }}</div>
                                        <div class="user-stat-label">Active Restrictions</div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-muted text-center">User information not available.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection