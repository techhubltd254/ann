<?php

namespace App\Events;

/**
 * Fired on user registration, login success/failure, password reset, logout.
 * Replaces N8nService::fire('user_registered', ...) and audit logging.
 */
class UserEvent extends DomainEvent
{
    public function __construct(
        public int $userId,
        public string $action,  // 'registered', 'login_success', 'login_failed', 'password_reset', 'logout'
        public ?string $ip = null,
    ) {
        parent::__construct([
            'user_id' => $userId,
            'action' => $action,
            'ip' => $ip ?? request()->ip(),
        ]);
    }

    public function eventName(): string { return "user.{$this->action}"; }
    public function auditLabel(): string { return "User #{$this->userId} {$this->action}"; }
    public function n8nEvent(): ?string { return $this->action === 'registered' ? 'user_registered' : null; }
}