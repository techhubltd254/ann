<?php

namespace App\Workflows;

use App\Events\ProviderServiceChanged;
use Illuminate\Support\Facades\Log;

/**
 * Provider Certification Workflow — LangGraph-style state machine.
 *
 * States:
 *   submitted → under_review → approved | denied
 *   denied → appeal → under_review (re-review)
 *   approved → revoked → denied
 *
 * Transitions are triggered by domain events (ProviderServiceChanged).
 * Human-in-the-loop: admin approval/denial triggers the transition via
 * the KICC admin dashboard (approveService/denyService actions).
 */
class ProviderCertificationWorkflow
{
    public const STATE_SUBMITTED    = 'submitted';
    public const STATE_UNDER_REVIEW = 'under_review';
    public const STATE_APPROVED     = 'approved';
    public const STATE_DENIED       = 'denied';
    public const STATE_APPEAL       = 'appeal';
    public const STATE_REVOKED      = 'revoked';

    private const TRANSITIONS = [
        self::STATE_SUBMITTED    => [self::STATE_UNDER_REVIEW],
        self::STATE_UNDER_REVIEW => [self::STATE_APPROVED, self::STATE_DENIED],
        self::STATE_APPROVED     => [self::STATE_REVOKED],
        self::STATE_DENIED       => [self::STATE_APPEAL],
        self::STATE_APPEAL       => [self::STATE_UNDER_REVIEW],
        self::STATE_REVOKED      => [self::STATE_DENIED],
    ];

    /**
     * Get allowed transitions FROM a given state.
     */
    public static function allowedTransitions(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    /**
     * Determine if a transition is valid.
     */
    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::allowedTransitions($from), true);
    }

    /**
     * Execute a state transition triggered by a ProviderServiceChanged event.
     */
    public static function apply(ProviderServiceChanged $event): string
    {
        $from = $event->payload['previous_state'] ?? self::STATE_SUBMITTED;
        $to   = match ($event->payload['action'] ?? '') {
            'approved' => self::STATE_APPROVED,
            'denied'   => self::STATE_DENIED,
            'appeal'   => self::STATE_APPEAL,
            'revoked'  => self::STATE_REVOKED,
            default    => self::STATE_UNDER_REVIEW,
        };

        if (!self::canTransition($from, $to)) {
            Log::warning("workflow: invalid transition {$from} → {$to} for provider {$event->table}#{$event->id}");
            return $from;
        }

        Log::info("workflow: provider {$event->table}#{$event->id} {$from} → {$to}");
        return $to;
    }

    /**
     * Get the full workflow graph for the admin UI.
     */
    public static function graph(): array
    {
        return [
            'states' => [
                self::STATE_SUBMITTED    => ['label' => 'Submitted',    'color' => 'gray'],
                self::STATE_UNDER_REVIEW => ['label' => 'Under Review', 'color' => 'blue'],
                self::STATE_APPROVED     => ['label' => 'Approved',     'color' => 'green'],
                self::STATE_DENIED       => ['label' => 'Denied',       'color' => 'red'],
                self::STATE_APPEAL       => ['label' => 'Appeal',       'color' => 'yellow'],
                self::STATE_REVOKED      => ['label' => 'Revoked',      'color' => 'red'],
            ],
            'transitions' => self::TRANSITIONS,
        ];
    }
}