<?php

namespace App\Core;

/**
 * Escalation Level Enumeration
 */
class EscalationLevel
{
    public const L1 = 'l1';  // Client's customer care team
    public const L2 = 'l2';  // Platform internal support
    public const L3 = 'l3';  // Platform engineering/development

    /**
     * Get all levels
     */
    public static function all(): array
    {
        return [
            self::L1,
            self::L2,
            self::L3,
        ];
    }

    /**
     * Get human-readable label
     */
    public static function label(string $level): string
    {
        $labels = [
            self::L1 => 'L1 - Client Support',
            self::L2 => 'L2 - Internal Support',
            self::L3 => 'L3 - Engineering Team',
        ];

        return $labels[$level] ?? $level;
    }

    /**
     * Get next escalation level
     */
    public static function nextLevel(string $currentLevel): ?string
    {
        $order = [self::L1, self::L2, self::L3];
        $currentIndex = array_search($currentLevel, $order);

        if ($currentIndex === false || $currentIndex >= count($order) - 1) {
            return null;
        }

        return $order[$currentIndex + 1];
    }

    /**
     * Check if level can be escalated further
     */
    public static function canEscalate(string $level): bool
    {
        return self::nextLevel($level) !== null;
    }
}
