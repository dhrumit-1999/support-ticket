<?php

namespace App\Core;

/**
 * Ticket Status Enumeration
 */
class TicketStatus
{
    public const OPEN = 'open';
    public const IN_PROGRESS = 'in_progress';
    public const PENDING_CUSTOMER = 'pending_customer';
    public const RESOLVED = 'resolved';
    public const CLOSED = 'closed';

    /**
     * Get all statuses
     */
    public static function all(): array
    {
        return [
            self::OPEN,
            self::IN_PROGRESS,
            self::PENDING_CUSTOMER,
            self::RESOLVED,
            self::CLOSED,
        ];
    }

    /**
     * Get human-readable label
     */
    public static function label(string $status): string
    {
        $labels = [
            self::OPEN => 'Open',
            self::IN_PROGRESS => 'In Progress',
            self::PENDING_CUSTOMER => 'Pending Customer',
            self::RESOLVED => 'Resolved',
            self::CLOSED => 'Closed',
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Check if ticket is considered active
     */
    public static function isActive(string $status): bool
    {
        return in_array($status, [
            self::OPEN,
            self::IN_PROGRESS,
            self::PENDING_CUSTOMER,
        ]);
    }
}
