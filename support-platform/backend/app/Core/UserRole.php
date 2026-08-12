<?php

namespace App\Core;

/**
 * User Roles Enumeration
 */
class UserRole
{
    public const SUPER_ADMIN = 'super_admin';
    public const TENANT_ADMIN = 'tenant_admin';
    public const TENANT_AGENT = 'tenant_agent';
    public const L2_AGENT = 'l2_agent';
    public const L3_AGENT = 'l3_agent';
    public const CUSTOMER = 'customer';

    /**
     * Get all roles
     */
    public static function all(): array
    {
        return [
            self::SUPER_ADMIN,
            self::TENANT_ADMIN,
            self::TENANT_AGENT,
            self::L2_AGENT,
            self::L3_AGENT,
            self::CUSTOMER,
        ];
    }

    /**
     * Check if role can access multiple tenants
     */
    public static function isCrossTenant(string $role): bool
    {
        return in_array($role, [
            self::SUPER_ADMIN,
            self::L2_AGENT,
            self::L3_AGENT,
        ]);
    }

    /**
     * Check if role is an agent role
     */
    public static function isAgent(string $role): bool
    {
        return in_array($role, [
            self::TENANT_ADMIN,
            self::TENANT_AGENT,
            self::L2_AGENT,
            self::L3_AGENT,
        ]);
    }
}
