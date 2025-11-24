<?php

namespace App\Services;

use App\Models\Appeal;
use App\Models\Warning;
use App\Models\UserRestriction;
use App\Models\ModerationAuditLog;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AppealTimelineService
{
    /**
     * Get comprehensive appeal timeline with all events.
     */
    public function getAppealTimeline($appealId): array
    {
        try {
            $appeal = Appeal::with(['user', 'reviewer', 'appealable'])
                ->findOrFail($appealId);

            $timeline = $this->buildTimeline($appeal);
            $milestones = $this->calculateMilestones($appeal, $timeline);
            $deadlines = $this->calculateDeadlines($appeal);
            $violations = $this->checkTimelineViolations($appeal, $timeline);

            return [
                'appeal_id' => $appealId,
                'appeal' => $appeal->toArray(),
                'timeline' => $timeline,
                'milestones' => $milestones,
                'deadlines' => $deadlines,
                'violations' => $violations,
                'current_status' => $appeal->status,
                'next_steps' => $this->getNextSteps($appeal, $timeline),
                'generated_at' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate appeal timeline', [
                'appeal_id' => $appealId,
                'error' => $e->getMessage()
            ]);

            return [
                'error' => 'Timeline generation failed',
                'appeal_id' => $appealId,
                'generated_at' => now()->toISOString()
            ];
        }
    }

    /**
     * Build comprehensive timeline from multiple sources.
     */
    protected function buildTimeline(Appeal $appeal): array
    {
        $timeline = [];

        // Appeal creation
        $timeline[] = [
            'event_type' => 'appeal_created',
            'timestamp' => $appeal->created_at->toISOString(),
            'description' => 'Appeal submitted by user',
            'actor' => [
                'type' => 'user',
                'id' => $appeal->user_id,
                'name' => $appeal->user->name ?? 'Unknown User'
            ],
            'data' => [
                'reason' => $appeal->reason,
                'description' => $appeal->description,
                'appealable_type' => $appeal->appealable_type,
                'appealable_id' => $appeal->appealable_id
            ],
            'duration_from_start' => 0
        ];

        // Original action that led to appeal
        $originalAction = $this->getOriginalActionTimeline($appeal);
        if ($originalAction) {
            $timeline[] = $originalAction;
        }

        // Evidence submissions
        $evidenceEvents = $this->getEvidenceTimeline($appeal);
        $timeline = array_merge($timeline, $evidenceEvents);

        // Status changes
        $statusEvents = $this->getStatusChangeTimeline($appeal);
        $timeline = array_merge($timeline, $statusEvents);

        // Review events
        $reviewEvents = $this->getReviewTimeline($appeal);
        $timeline = array_merge($timeline, $reviewEvents);

        // Communication events
        $communicationEvents = $this->getCommunicationTimeline($appeal);
        $timeline = array_merge($timeline, $communicationEvents);

        // System events
        $systemEvents = $this->getSystemTimeline($appeal);
        $timeline = array_merge($timeline, $systemEvents);

        // Sort timeline by timestamp
        usort($timeline, function ($a, $b) {
            return strcmp($a['timestamp'], $b['timestamp']);
        });

        // Calculate durations
        $startTime = $appeal->created_at;
        foreach ($timeline as &$event) {
            $eventTime = Carbon::parse($event['timestamp']);
            $event['duration_from_start'] = $startTime->diffInMinutes($eventTime);
        }

        return $timeline;
    }

    /**
     * Get original action timeline event.
     */
    protected function getOriginalActionTimeline(Appeal $appeal): ?array
    {
        $appealable = $appeal->appealable;

        if (!$appealable) {
            return null;
        }

        if ($appealable instanceof Warning) {
            return [
                'event_type' => 'warning_issued',
                'timestamp' => $appealable->created_at->toISOString(),
                'description' => 'Original warning issued',
                'actor' => [
                    'type' => 'moderator',
                    'id' => $appealable->moderator_id,
                    'name' => $this->getModeratorName($appealable->moderator_id)
                ],
                'data' => [
                    'level' => $appealable->level,
                    'type' => $appealable->type,
                    'reason' => $appealable->reason,
                    'expires_at' => $appealable->expires_at?->toISOString()
                ]
            ];
        } elseif ($appealable instanceof UserRestriction) {
            return [
                'event_type' => 'restriction_applied',
                'timestamp' => $appealable->created_at->toISOString(),
                'description' => 'Original restriction applied',
                'actor' => [
                    'type' => 'moderator',
                    'id' => $appealable->moderator_id,
                    'name' => $this->getModeratorName($appealable->moderator_id)
                ],
                'data' => [
                    'type' => $appealable->type,
                    'reason' => $appealable->reason,
                    'is_permanent' => $appealable->is_permanent,
                    'expires_at' => $appealable->expires_at?->toISOString()
                ]
            ];
        }

        return null;
    }

    /**
     * Get evidence timeline events.
     */
    protected function getEvidenceTimeline(Appeal $appeal): array
    {
        $events = [];

        if ($appeal->evidence && is_array($appeal->evidence)) {
            foreach ($appeal->evidence as $index => $evidence) {
                $events[] = [
                    'event_type' => 'evidence_submitted',
                    'timestamp' => $appeal->created_at->addMinutes($index + 1)->toISOString(),
                    'description' => 'Evidence submitted: ' . $evidence['type'],
                    'actor' => [
                        'type' => 'user',
                        'id' => $appeal->user_id,
                        'name' => $appeal->user->name ?? 'Unknown User'
                    ],
                    'data' => $evidence
                ];
            }
        }

        return $events;
    }

    /**
     * Get status change timeline events.
     */
    protected function getStatusChangeTimeline(Appeal $appeal): array
    {
        $events = [];

        // Under review
        if ($appeal->status === 'under_review' || $appeal->status === 'approved' || $appeal->status === 'denied') {
            $events[] = [
                'event_type' => 'status_under_review',
                'timestamp' => $appeal->created_at->addHours(1)->toISOString(),
                'description' => 'Appeal moved to under review',
                'actor' => [
                    'type' => 'system',
                    'id' => null,
                    'name' => 'System'
                ],
                'data' => [
                    'previous_status' => 'pending',
                    'new_status' => 'under_review'
                ]
            ];
        }

        // Reviewed
        if ($appeal->reviewed_at) {
            $events[] = [
                'event_type' => 'appeal_reviewed',
                'timestamp' => $appeal->reviewed_at->toISOString(),
                'description' => 'Appeal reviewed and ' . $appeal->status,
                'actor' => [
                    'type' => 'moderator',
                    'id' => $appeal->reviewer_id,
                    'name' => $appeal->reviewer->name ?? 'Unknown Moderator'
                ],
                'data' => [
                    'review_notes' => $appeal->review_notes,
                    'evidence_review' => $appeal->evidence_review,
                    'final_status' => $appeal->status
                ]
            ];
        }

        return $events;
    }

    /**
     * Get review timeline events.
     */
    protected function getReviewTimeline(Appeal $appeal): array
    {
        $events = [];

        // Get audit logs related to this appeal
        $auditLogs = ModerationAuditLog::where('entity_type', 'Appeal')
            ->where('entity_id', $appeal->id)
            ->orderBy('timestamp', 'asc')
            ->get();

        foreach ($auditLogs as $log) {
            $events[] = [
                'event_type' => 'audit_log',
                'timestamp' => $log->timestamp->toISOString(),
                'description' => 'Audit action: ' . $log->action,
                'actor' => [
                    'type' => 'system',
                    'id' => $log->actor_id,
                    'name' => $this->getModeratorName($log->actor_id) ?? 'System'
                ],
                'data' => [
                    'action' => $log->action,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent
                ]
            ];
        }

        return $events;
    }

    /**
     * Get communication timeline events.
     */
    protected function getCommunicationTimeline(Appeal $appeal): array
    {
        $events = [];

        // This would integrate with notification system to get communication events
        // For now, return placeholder events

        // Notification sent to user
        $events[] = [
            'event_type' => 'notification_sent',
            'timestamp' => $appeal->created_at->addMinutes(5)->toISOString(),
            'description' => 'Appeal confirmation sent to user',
            'actor' => [
                'type' => 'system',
                'id' => null,
                'name' => 'System'
            ],
            'data' => [
                'notification_type' => 'email',
                'recipient' => $appeal->user->email ?? 'unknown'
            ]
        ];

        // Notification sent to moderators
        if ($appeal->status === 'pending') {
            $events[] = [
                'event_type' => 'moderator_notification',
                'timestamp' => $appeal->created_at->addMinutes(10)->toISOString(),
                'description' => 'Appeal notification sent to moderators',
                'actor' => [
                    'type' => 'system',
                    'id' => null,
                    'name' => 'System'
                ],
                'data' => [
                    'notification_type' => 'internal',
                    'priority' => 'normal'
                ]
            ];
        }

        return $events;
    }

    /**
     * Get system timeline events.
     */
    protected function getSystemTimeline(Appeal $appeal): array
    {
        $events = [];

        // Deadline reminders
        if ($appeal->deadline_at) {
            $reminderTime = $appeal->deadline_at->subDays(3);
            if (now()->greaterThan($reminderTime)) {
                $events[] = [
                    'event_type' => 'deadline_reminder',
                    'timestamp' => $reminderTime->toISOString(),
                    'description' => 'Deadline reminder sent',
                    'actor' => [
                        'type' => 'system',
                        'id' => null,
                        'name' => 'System'
                    ],
                    'data' => [
                        'deadline' => $appeal->deadline_at->toISOString(),
                        'days_remaining' => 3
                    ]
                ];
            }
        }

        // Escalation events
        if ($appeal->status === 'pending' && $appeal->created_at->diffInDays(now()) > 7) {
            $events[] = [
                'event_type' => 'auto_escalation',
                'timestamp' => $appeal->created_at->addDays(7)->toISOString(),
                'description' => 'Appeal auto-escalated due to inactivity',
                'actor' => [
                    'type' => 'system',
                    'id' => null,
                    'name' => 'System'
                ],
                'data' => [
                    'escalation_reason' => 'inactivity',
                    'days_pending' => 7
                ]
            ];
        }

        return $events;
    }

    /**
     * Calculate key milestones.
     */
    protected function calculateMilestones(Appeal $appeal, array $timeline): array
    {
        $milestones = [];

        // Submission milestone
        $milestones[] = [
            'type' => 'submission',
            'name' => 'Appeal Submitted',
            'timestamp' => $appeal->created_at->toISOString(),
            'completed' => true,
            'duration' => 0
        ];

        // Review start milestone
        $reviewStart = $this->findEventByType($timeline, 'status_under_review');
        if ($reviewStart) {
            $milestones[] = [
                'type' => 'review_start',
                'name' => 'Review Started',
                'timestamp' => $reviewStart['timestamp'],
                'completed' => true,
                'duration' => $appeal->created_at->diffInHours(Carbon::parse($reviewStart['timestamp']))
            ];
        } else {
            $milestones[] = [
                'type' => 'review_start',
                'name' => 'Review Started',
                'timestamp' => null,
                'completed' => false,
                'duration' => null
            ];
        }

        // Review completion milestone
        if ($appeal->reviewed_at) {
            $milestones[] = [
                'type' => 'review_completion',
                'name' => 'Review Completed',
                'timestamp' => $appeal->reviewed_at->toISOString(),
                'completed' => true,
                'duration' => $appeal->created_at->diffInHours($appeal->reviewed_at)
            ];
        } else {
            $milestones[] = [
                'type' => 'review_completion',
                'name' => 'Review Completed',
                'timestamp' => null,
                'completed' => false,
                'duration' => null
            ];
        }

        // Resolution milestone
        if (in_array($appeal->status, ['approved', 'denied'])) {
            $milestones[] = [
                'type' => 'resolution',
                'name' => 'Appeal Resolved',
                'timestamp' => $appeal->reviewed_at->toISOString(),
                'completed' => true,
                'duration' => $appeal->created_at->diffInHours($appeal->reviewed_at)
            ];
        } else {
            $milestones[] = [
                'type' => 'resolution',
                'name' => 'Appeal Resolved',
                'timestamp' => null,
                'completed' => false,
                'duration' => null
            ];
        }

        return $milestones;
    }

    /**
     * Calculate deadlines.
     */
    protected function calculateDeadlines(Appeal $appeal): array
    {
        $deadlines = [];

        // Appeal deadline
        if ($appeal->deadline_at) {
            $deadlines[] = [
                'type' => 'appeal_deadline',
                'name' => 'Appeal Deadline',
                'timestamp' => $appeal->deadline_at->toISOString(),
                'status' => now()->greaterThan($appeal->deadline_at) ? 'missed' : 'active',
                'days_remaining' => now()->lessThan($appeal->deadline_at) 
                    ? now()->diffInDays($appeal->deadline_at) 
                    : 0,
                'description' => 'Deadline for appeal submission'
            ];
        }

        // Review deadline (SLA: 7 days)
        $reviewDeadline = $appeal->created_at->addDays(7);
        $deadlines[] = [
            'type' => 'review_deadline',
            'name' => 'Review SLA Deadline',
            'timestamp' => $reviewDeadline->toISOString(),
            'status' => $appeal->reviewed_at 
                ? 'met' 
                : (now()->greaterThan($reviewDeadline) ? 'missed' : 'active'),
            'days_remaining' => !$appeal->reviewed_at && now()->lessThan($reviewDeadline) 
                ? now()->diffInDays($reviewDeadline) 
                : 0,
            'description' => 'Service Level Agreement deadline for review completion'
        ];

        // Escalation deadline (14 days)
        $escalationDeadline = $appeal->created_at->addDays(14);
        if (!$appeal->reviewed_at) {
            $deadlines[] = [
                'type' => 'escalation_deadline',
                'name' => 'Escalation Deadline',
                'timestamp' => $escalationDeadline->toISOString(),
                'status' => now()->greaterThan($escalationDeadline) ? 'missed' : 'active',
                'days_remaining' => now()->lessThan($escalationDeadline) 
                    ? now()->diffInDays($escalationDeadline) 
                    : 0,
                'description' => 'Deadline for automatic escalation to senior moderator'
            ];
        }

        return $deadlines;
    }

    /**
     * Check for timeline violations.
     */
    protected function checkTimelineViolations(Appeal $appeal, array $timeline): array
    {
        $violations = [];

        // Check review SLA violation
        if (!$appeal->reviewed_at) {
            $reviewDeadline = $appeal->created_at->addDays(7);
            if (now()->greaterThan($reviewDeadline)) {
                $violations[] = [
                    'type' => 'sla_violation',
                    'severity' => 'high',
                    'description' => 'Review SLA deadline missed',
                    'deadline' => $reviewDeadline->toISOString(),
                    'overdue_by' => now()->diffInDays($reviewDeadline)
                ];
            }
        }

        // Check for unusual gaps in timeline
        $gaps = $this->findTimelineGaps($timeline);
        foreach ($gaps as $gap) {
            if ($gap['duration_hours'] > 48) { // More than 48 hours gap
                $violations[] = [
                    'type' => 'timeline_gap',
                    'severity' => 'medium',
                    'description' => 'Unusual gap in appeal processing',
                    'gap_start' => $gap['start'],
                    'gap_end' => $gap['end'],
                    'duration_hours' => $gap['duration_hours']
                ];
            }
        }

        // Check for backdating violations
        $backdating = $this->checkBackdating($timeline);
        if ($backdating) {
            $violations[] = [
                'type' => 'backdating_violation',
                'severity' => 'critical',
                'description' => 'Timeline events appear to be backdated',
                'details' => $backdating
            ];
        }

        return $violations;
    }

    /**
     * Get next steps for the appeal.
     */
    protected function getNextSteps(Appeal $appeal, array $timeline): array
    {
        $nextSteps = [];

        switch ($appeal->status) {
            case 'pending':
                $nextSteps[] = [
                    'step' => 'awaiting_review',
                    'description' => 'Appeal is waiting for moderator assignment',
                    'responsible' => 'moderation_team',
                    'estimated_completion' => $appeal->created_at->addDays(3)->toISOString()
                ];
                break;

            case 'under_review':
                $nextSteps[] = [
                    'step' => 'review_in_progress',
                    'description' => 'Appeal is currently under review',
                    'responsible' => 'assigned_moderator',
                    'estimated_completion' => $appeal->created_at->addDays(7)->toISOString()
                ];
                break;

            case 'approved':
                $nextSteps[] = [
                    'step' => 'implementation',
                    'description' => 'Original action will be overturned',
                    'responsible' => 'system',
                    'estimated_completion' => now()->addHours(1)->toISOString()
                ];
                $nextSteps[] = [
                    'step' => 'notification',
                    'description' => 'User will be notified of approval',
                    'responsible' => 'system',
                    'estimated_completion' => now()->addHours(2)->toISOString()
                ];
                break;

            case 'denied':
                $nextSteps[] = [
                    'step' => 'notification',
                    'description' => 'User will be notified of denial',
                    'responsible' => 'system',
                    'estimated_completion' => now()->addHours(1)->toISOString()
                ];
                break;
        }

        // Check for escalation possibilities
        if ($appeal->status === 'pending' && $appeal->created_at->diffInDays(now()) > 7) {
            $nextSteps[] = [
                'step' => 'escalation',
                'description' => 'Appeal will be escalated to senior moderator',
                'responsible' => 'system',
                'estimated_completion' => $appeal->created_at->addDays(14)->toISOString()
            ];
        }

        return $nextSteps;
    }

    /**
     * Find event by type in timeline.
     */
    protected function findEventByType(array $timeline, string $eventType): ?array
    {
        foreach ($timeline as $event) {
            if ($event['event_type'] === $eventType) {
                return $event;
            }
        }
        return null;
    }

    /**
     * Find gaps in timeline.
     */
    protected function findTimelineGaps(array $timeline): array
    {
        $gaps = [];
        
        for ($i = 0; $i < count($timeline) - 1; $i++) {
            $current = Carbon::parse($timeline[$i]['timestamp']);
            $next = Carbon::parse($timeline[$i + 1]['timestamp']);
            
            $duration = $current->diffInHours($next);
            
            if ($duration > 24) { // More than 24 hours gap
                $gaps[] = [
                    'start' => $timeline[$i]['timestamp'],
                    'end' => $timeline[$i + 1]['timestamp'],
                    'duration_hours' => $duration
                ];
            }
        }

        return $gaps;
    }

    /**
     * Check for backdating violations.
     */
    protected function checkBackdating(array $timeline): ?array
    {
        $violations = [];
        
        foreach ($timeline as $event) {
            $eventTime = Carbon::parse($event['timestamp']);
            
            // Check if event timestamp is before appeal creation
            if ($eventTime->lessThan(Carbon::parse($timeline[0]['timestamp'])) && 
                $event['event_type'] !== 'warning_issued' && 
                $event['event_type'] !== 'restriction_applied') {
                $violations[] = [
                    'event_type' => $event['event_type'],
                    'event_timestamp' => $event['timestamp'],
                    'appeal_created' => $timeline[0]['timestamp']
                ];
            }
        }

        return empty($violations) ? null : $violations;
    }

    /**
     * Get moderator name by ID.
     */
    protected function getModeratorName($moderatorId): ?string
    {
        static $moderators = [];
        
        if (!isset($moderators[$moderatorId])) {
            $moderators[$moderatorId] = DB::table('users')
                ->where('id', $moderatorId)
                ->value('name');
        }
        
        return $moderators[$moderatorId];
    }

    /**
     * Get appeal statistics for reporting.
     */
    public function getAppealStatistics($dateRange = 30): array
    {
        try {
            $startDate = now()->subDays($dateRange);
            
            $totalAppeals = Appeal::where('created_at', '>=', $startDate)->count();
            $approvedAppeals = Appeal::where('created_at', '>=', $startDate)
                ->where('status', 'approved')
                ->count();
            $deniedAppeals = Appeal::where('created_at', '>=', $startDate)
                ->where('status', 'denied')
                ->count();
            $pendingAppeals = Appeal::where('created_at', '>=', $startDate)
                ->where('status', 'pending')
                ->count();

            $averageReviewTime = Appeal::whereNotNull('reviewed_at')
                ->where('created_at', '>=', $startDate)
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) as avg_hours')
                ->value('avg_hours');

            $slaCompliance = Appeal::where('created_at', '>=', $startDate)
                ->whereNotNull('reviewed_at')
                ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, reviewed_at) <= 168') // 7 days
                ->count();

            $totalReviewed = Appeal::where('created_at', '>=', $startDate)
                ->whereNotNull('reviewed_at')
                ->count();

            $slaRate = $totalReviewed > 0 ? round(($slaCompliance / $totalReviewed) * 100, 2) : 0;

            return [
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => now()->toDateString(),
                    'days' => $dateRange
                ],
                'totals' => [
                    'total_appeals' => $totalAppeals,
                    'approved' => $approvedAppeals,
                    'denied' => $deniedAppeals,
                    'pending' => $pendingAppeals,
                    'approval_rate' => $totalAppeals > 0 ? round(($approvedAppeals / $totalAppeals) * 100, 2) : 0,
                    'denial_rate' => $totalAppeals > 0 ? round(($deniedAppeals / $totalAppeals) * 100, 2) : 0
                ],
                'performance' => [
                    'average_review_time_hours' => round($averageReviewTime, 2),
                    'sla_compliance_rate' => $slaRate,
                    'on_time_reviews' => $slaCompliance,
                    'total_reviewed' => $totalReviewed
                ],
                'generated_at' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate appeal statistics', [
                'error' => $e->getMessage()
            ]);

            return [
                'error' => 'Statistics generation failed',
                'generated_at' => now()->toISOString()
            ];
        }
    }

    /**
     * Get overdue appeals.
     */
    public function getOverdueAppeals(): array
    {
        try {
            $overdue = Appeal::with(['user', 'appealable'])
                ->where('status', 'pending')
                ->where('created_at', '<', now()->subDays(7))
                ->orderBy('created_at', 'asc')
                ->get();

            $result = [];
            foreach ($overdue as $appeal) {
                $result[] = [
                    'appeal_id' => $appeal->id,
                    'user' => $appeal->user->name ?? 'Unknown',
                    'appealable_type' => $appeal->appealable_type,
                    'appealable_id' => $appeal->appealable_id,
                    'days_overdue' => $appeal->created_at->diffInDays(now()),
                    'priority' => $appeal->created_at->diffInDays(now()) > 14 ? 'critical' : 'high'
                ];
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to get overdue appeals', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }
}