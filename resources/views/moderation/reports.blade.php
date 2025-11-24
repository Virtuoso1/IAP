@extends('layouts.app')

@section('content')
<div class="moderation-container">
    <!-- Header -->
    <div class="moderation-header">
        <div class="container mx-auto px-6">
            <h1 class="moderation-title">Manage Reports</h1>
            <p class="moderation-subtitle">Review and resolve user reports</p>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8">
        <div class="moderation-card slide-in">
            <div class="moderation-card-header">
                <h6 class="moderation-card-title">All Reports</h6>
                <a href="{{ route('moderation.dashboard') }}" class="moderation-btn moderation-btn-secondary moderation-btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="moderation-card-body">
                @if($reports->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="moderation-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Reporter</th>
                                    <th>Reported User</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reports as $report)
                                <tr>
                                    <td>{{ $report->id }}</td>
                                    <td>{{ $report->reporter->username ?? 'N/A' }}</td>
                                    <td>{{ $report->reportedUser->username ?? 'N/A' }}</td>
                                    <td>{{ $report->category->name ?? 'N/A' }}</td>
                                    <td>{{ Str::limit($report->description, 50) }}</td>
                                    <td>
                                        <span class="moderation-badge {{ $report->status }}">
                                            {{ ucfirst($report->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $report->created_at->format('M j, Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('moderation.reports.show', $report->id) }}" class="moderation-btn moderation-btn-primary moderation-btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="flex justify-center mt-6">
                        {{ $reports->links() }}
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div class="empty-state-title">No reports found</div>
                        <div class="empty-state-description">There are no reports to review at this time</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection