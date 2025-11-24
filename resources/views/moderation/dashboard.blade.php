@extends('layouts.app')

@section('content')
<div class="moderation-container">
    <!-- Header -->
    <div class="moderation-header">
        <div class="container mx-auto px-6">
            <h1 class="moderation-title">Moderation Dashboard</h1>
            <p class="moderation-subtitle">Manage reports, warnings, restrictions, and appeals</p>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card primary fade-in">
                <div class="stat-card-content">
                    <div class="stat-card-info">
                        <div class="stat-card-title">Pending Reports</div>
                        <div class="stat-card-value">{{ $stats['pending_reports'] }}</div>
                    </div>
                    <div class="stat-card-icon">
                        <i class="fas fa-flag"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card warning fade-in">
                <div class="stat-card-content">
                    <div class="stat-card-info">
                        <div class="stat-card-title">Active Warnings</div>
                        <div class="stat-card-value">{{ $stats['active_warnings'] }}</div>
                    </div>
                    <div class="stat-card-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card danger fade-in">
                <div class="stat-card-content">
                    <div class="stat-card-info">
                        <div class="stat-card-title">Active Restrictions</div>
                        <div class="stat-card-value">{{ $stats['active_restrictions'] }}</div>
                    </div>
                    <div class="stat-card-icon">
                        <i class="fas fa-ban"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card info fade-in">
                <div class="stat-card-content">
                    <div class="stat-card-info">
                        <div class="stat-card-title">Pending Appeals</div>
                        <div class="stat-card-value">{{ $stats['pending_appeals'] }}</div>
                    </div>
                    <div class="stat-card-icon">
                        <i class="fas fa-gavel"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Reports and Warnings -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="moderation-card slide-in">
                <div class="moderation-card-header">
                    <h6 class="moderation-card-title">Recent Reports</h6>
                    <a href="{{ route('moderation.reports') }}" class="moderation-btn moderation-btn-primary moderation-btn-sm">
                        View All
                    </a>
                </div>
                <div class="moderation-card-body">
                    @if($recentReports->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="moderation-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Reporter</th>
                                        <th>Reported</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentReports as $report)
                                    <tr>
                                        <td>{{ $report->id }}</td>
                                        <td>{{ $report->reporter->username ?? 'N/A' }}</td>
                                        <td>{{ $report->reportedUser->username ?? 'N/A' }}</td>
                                        <td>{{ $report->category->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="moderation-badge {{ $report->status }}">
                                                {{ ucfirst($report->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $report->created_at->format('M j, Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-flag"></i>
                            </div>
                            <div class="empty-state-title">No recent reports</div>
                            <div class="empty-state-description">All reports have been processed</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="moderation-card slide-in">
                <div class="moderation-card-header">
                    <h6 class="moderation-card-title">Recent Warnings</h6>
                    <a href="{{ route('moderation.warnings') }}" class="moderation-btn moderation-btn-warning moderation-btn-sm">
                        View All
                    </a>
                </div>
                <div class="moderation-card-body">
                    @if($recentWarnings->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="moderation-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Moderator</th>
                                        <th>Level</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentWarnings as $warning)
                                    <tr>
                                        <td>{{ $warning->id }}</td>
                                        <td>{{ $warning->user->username ?? 'N/A' }}</td>
                                        <td>{{ $warning->moderator->username ?? 'N/A' }}</td>
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
                                        <td>{{ $warning->created_at->format('M j, Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="empty-state-title">No recent warnings</div>
                            <div class="empty-state-description">No warnings have been issued recently</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="moderation-card fade-in">
            <div class="moderation-card-header">
                <h6 class="moderation-card-title">Quick Actions</h6>
            </div>
            <div class="moderation-card-body">
                <div class="quick-actions">
                    <a href="{{ route('moderation.reports') }}" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div class="quick-action-title">Manage Reports</div>
                        <div class="quick-action-description">Review and resolve user reports</div>
                    </a>
                    
                    <a href="{{ route('moderation.warnings') }}" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="quick-action-title">Manage Warnings</div>
                        <div class="quick-action-description">View and manage user warnings</div>
                    </a>
                    
                    <a href="{{ route('moderation.restrictions') }}" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="quick-action-title">Manage Restrictions</div>
                        <div class="quick-action-description">Apply and lift user restrictions</div>
                    </a>
                    
                    <a href="{{ route('moderation.appeals') }}" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-gavel"></i>
                        </div>
                        <div class="quick-action-title">Review Appeals</div>
                        <div class="quick-action-description">Process user appeals and requests</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection