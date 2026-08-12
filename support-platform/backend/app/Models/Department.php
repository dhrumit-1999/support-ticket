<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'email',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the tenant this department belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get all users in this department.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_user')
                    ->withPivot('is_lead')
                    ->withTimestamps();
    }

    /**
     * Get all tickets for this department.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get all knowledge articles for this department.
     */
    public function knowledgeArticles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class);
    }

    /**
     * Get the lead agent for this department.
     */
    public function getLeadAttribute(): ?User
    {
        return $this->users()->wherePivot('is_lead', true)->first();
    }

    /**
     * Scope a query to only include active departments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include departments for a specific tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
