<?php

namespace App\Core;

/**
 * Ticket Priority Enumeration
 */
class TicketPriority
{
    public const LOW = 'low';
    public const MEDIUM = 'medium';
    public const HIGH = 'high';
    public const URGENT = 'urgent';

    /**
     * Get all priorities
     */
    public static function all(): array
    {
        return [
            self::LOW,
            self::MEDIUM,
            self::HIGH,
            self::URGENT,
        ];
    }

    /**
     * Get human-readable label
     */
    public static function label(string $priority): string
    {
        $labels = [
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        ];

        return $labels[$priority] ?? $priority;
    }

    /**
     * Get numeric level for sorting
     */
    public static function level(string $priority): int
    {
        $levels = [
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::URGENT => 4,
        ];

        return $levels[$priority] ?? 0;
    }
}
