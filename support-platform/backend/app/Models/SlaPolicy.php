<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaPolicy extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'first_response_minutes',
        'resolution_minutes',
        'priority',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'first_response_minutes' => 'integer',
        'resolution_minutes' => 'integer',
    ];

    /**
     * Get the tenant this policy belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope a query to only include active policies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include policies for a specific tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include policies for a specific priority.
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Get SLA policy for a tenant and priority.
     */
    public static function getForTenantAndPriority(int $tenantId, string $priority): ?self
    {
        return self::where('tenant_id', $tenantId)
            ->where('priority', $priority)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if first response is within SLA.
     */
    public function isFirstResponseWithinSla(\Illuminate\Support\Carbon $createdAt, ?\Illuminate\Support\Carbon $respondedAt): bool
    {
        if (!$respondedAt) {
            return true; // Not yet responded, can't breach yet
        }

        $responseTime = $createdAt->diffInMinutes($respondedAt);
        return $responseTime <= $this->first_response_minutes;
    }

    /**
     * Check if resolution is within SLA.
     */
    public function isResolutionWithinSla(\Illuminate\Support\Carbon $createdAt, ?\Illuminate\Support\Carbon $resolvedAt): bool
    {
        if (!$resolvedAt) {
            return true; // Not yet resolved, can't breach yet
        }

        $resolutionTime = $createdAt->diffInMinutes($resolvedAt);
        return $resolutionTime <= $this->resolution_minutes;
    }

    /**
     * Get remaining minutes for first response.
     */
    public function getFirstResponseRemainingMinutes(\Illuminate\Support\Carbon $createdAt): int
    {
        $elapsed = $createdAt->diffInMinutes(now());
        return max(0, $this->first_response_minutes - $elapsed);
    }

    /**
     * Get remaining minutes for resolution.
     */
    public function getResolutionRemainingMinutes(\Illuminate\Support\Carbon $createdAt): int
    {
        $elapsed = $createdAt->diffInMinutes(now());
        return max(0, $this->resolution_minutes - $elapsed);
    }
}
