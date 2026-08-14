<?php

namespace App\Services;

use App\Models\UserNotification;
use App\Models\User;

class NotificationService
{
    public static function send(int $userId, string $type, string $title, ?string $body = null, ?string $actionUrl = null): void
    {
        UserNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
        ]);
    }

    public static function sendAll(array $userIds, string $type, string $title, ?string $body = null, ?string $actionUrl = null): void
    {
        foreach ($userIds as $id) {
            self::send($id, $type, $title, $body, $actionUrl);
        }
    }

    /** Fire notification + n8n webhook for multi-channel delivery */
    public static function fire(int $userId, string $type, string $title, ?string $body = null, ?string $actionUrl = null): void
    {
        self::send($userId, $type, $title, $body, $actionUrl);
        \App\Services\N8nService::fire('notification_created', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
        ]);
    }
}