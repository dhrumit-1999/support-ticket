<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Disable updated_at for audit logs (immutable).
     */
    const UPDATED_AT = null;

    /**
     * Get the tenant this log belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who performed this action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include logs for a specific tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include logs for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include logs for a specific action.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include logs for a specific resource.
     */
    public function scopeForResource($query, string $resourceType, ?int $resourceId = null)
    {
        $query->where('resource_type', $resourceType);
        
        if ($resourceId !== null) {
            $query->where('resource_id', $resourceId);
        }
        
        return $query;
    }

    /**
     * Log an action.
     */
    public static function log(
        string $action,
        string $resourceType,
        ?int $resourceId,
        ?int $tenantId,
        ?int $userId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return self::create([
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
