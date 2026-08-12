<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDomain extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'domain',
        'is_primary',
        'ssl_enabled',
        'verified_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'ssl_enabled' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns this domain.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if domain is verified.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Scope a query to only include primary domains.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope a query to only include verified domains.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }
}
