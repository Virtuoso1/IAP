@extends('layouts.app')

@section('content')
<div class="moderation-container">
    <!-- Header -->
    <div class="moderation-header">
        <div class="container mx-auto px-6">
            <h1 class="moderation-title">Report Details</h1>
            <p class="moderation-subtitle">Review report information and take action</p>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="moderation-card slide-in">
                    <div class="moderation-card-header">
                        <h6 class="moderation-card-title">Report Information</h6>
                        <a href="{{ route('moderation.reports') }}" class="moderation-btn moderation-btn-secondary moderation-btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Reports
                        </a>
                    </div>
                    <div class="moderation-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <strong>Report ID:</strong> #{{ $report->id }}
                            </div>
                            <div>
                                <strong>Status:</strong>
                                <span class="moderation-badge {{ $report->status }}">
                                    {{ ucfirst($report->status) }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <strong>Reporter:</strong> {{ $report->reporter->username ?? 'N/A' }}
                            </div>
                            <div>
                                <strong>Reported User:</strong> {{ $report->reportedUser->username ?? 'N/A' }}
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <strong>Category:</strong> {{ $report->category->name ?? 'N/A' }}
                            </div>
                            <div>
                                <strong>Created:</strong> {{ $report->created_at->format('M j, Y H:i') }}
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <strong>Description:</strong>
                            <p class="mt-2 text-gray-700 leading-relaxed">{{ $report->description }}</p>
                        </div>
                        
                        @if($report->evidence->count() > 0)
                        <div class="mb-6">
                            <strong>Evidence:</strong>
                            <div class="mt-3 space-y-3">
                                @foreach($report->evidence as $evidence)
                                    <div class="evidence-item">
                                        <div class="evidence-header">
                                            <span class="evidence-type">{{ ucfirst($evidence->type) }}</span>
                                            <span class="evidence-timestamp">{{ $evidence->created_at->format('M j, Y H:i') }}</span>
                                        </div>
                                        <div class="evidence-content">
                                            @if($evidence->type == 'screenshot')
                                                <img src="{{ asset('storage/' . $evidence->file_path) }}" class="evidence-image" alt="Evidence">
                                            @else
                                                <a href="{{ asset('storage/' . $evidence->file_path) }}" target="_blank" class="moderation-btn moderation-btn-outline moderation-btn-sm">
                                                    <i class="fas fa-download"></i> Download Evidence
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
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
                        @if($report->status == 'pending')
                            <form method="POST" action="{{ route('api.moderation.reports.resolve', $report->id) }}" class="space-y-4">
                                @csrf
                                <div class="moderation-form-group">
                                    <label for="action" class="moderation-form-label">Action:</label>
                                    <select name="action" id="action" class="moderation-form-control" required>
                                        <option value="">Select Action</option>
                                        <option value="warn">Issue Warning</option>
                                        <option value="restrict">Apply Restriction</option>
                                        <option value="dismiss">Dismiss Report</option>
                                    </select>
                                </div>
                                <div class="moderation-form-group">
                                    <label for="reason" class="moderation-form-label">Reason:</label>
                                    <textarea name="reason" id="reason" class="moderation-form-control" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="moderation-btn moderation-btn-warning moderation-btn-block">
                                    <i class="fas fa-gavel"></i> Resolve Report
                                </button>
                            </form>
                        @else
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                    <span class="text-blue-700">This report has been {{ $report->status }}.</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="moderation-card slide-in">
                    <div class="moderation-card-header">
                        <h6 class="moderation-card-title">Reported User Info</h6>
                    </div>
                    <div class="moderation-card-body">
                        @if($report->reportedUser)
                            <div class="user-info-card">
                                <img src="{{ $report->reportedUser->avatar_url }}" class="user-avatar" alt="Avatar">
                                <div class="user-name">{{ $report->reportedUser->username }}</div>
                                <div class="user-email">{{ $report->reportedUser->email }}</div>
                                
                                <div class="user-stats">
                                    <div class="user-stat">
                                        <div class="user-stat-value">{{ $report->reportedUser->warnings()->where('is_active', true)->count() }}</div>
                                        <div class="user-stat-label">Active Warnings</div>
                                    </div>
                                    <div class="user-stat">
                                        <div class="user-stat-value">{{ $report->reportedUser->getActiveRestrictions()->count() }}</div>
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