<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'contact_email',
        'contact_phone',
        'address',
        'is_active',
        'email_verified_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the branding configuration for this tenant.
     */
    public function branding(): HasOne
    {
        return $this->hasOne(TenantBranding::class);
    }

    /**
     * Get the domains for this tenant.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    /**
     * Get all users for this tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all tickets for this tenant.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get all departments for this tenant.
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Get all knowledge articles for this tenant.
     */
    public function knowledgeArticles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class);
    }

    /**
     * Get all attachments for this tenant.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Get all audit logs for this tenant.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get all email templates for this tenant.
     */
    public function emailTemplates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class);
    }

    /**
     * Get all SLA policies for this tenant.
     */
    public function slaPolicies(): HasMany
    {
        return $this->hasMany(SlaPolicy::class);
    }

    /**
     * Get the primary domain for this tenant.
     */
    public function getPrimaryDomainAttribute(): ?string
    {
        $primary = $this->domains()->where('is_primary', true)->first();
        return $primary?->domain;
    }

    /**
     * Get the display name (branding company name or fallback to tenant name).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->branding?->company_name ?? $this->name;
    }

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Scope a query to only include active tenants.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
