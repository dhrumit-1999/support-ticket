<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantBranding extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'company_name',
        'logo_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'email_sender_name',
        'email_sender_address',
        'support_email',
        'portal_title',
        'custom_css',
        'welcome_message',
    ];

    /**
     * Get the tenant that owns this branding.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        if (str_starts_with($this->logo_path, 'http')) {
            return $this->logo_path;
        }

        return asset('storage/' . $this->logo_path);
    }

    /**
     * Get the favicon URL.
     */
    public function getFaviconUrlAttribute(): ?string
    {
        if (!$this->favicon_path) {
            return null;
        }

        if (str_starts_with($this->favicon_path, 'http')) {
            return $this->favicon_path;
        }

        return asset('storage/' . $this->favicon_path);
    }

    /**
     * Get the effective company name.
     */
    public function getEffectiveCompanyNameAttribute(): string
    {
        return $this->company_name ?? $this->tenant->name;
    }

    /**
     * Get the effective portal title.
     */
    public function getEffectivePortalTitleAttribute(): string
    {
        return $this->portal_title ?? ($this->company_name ?? $this->tenant->name) . ' Support';
    }
}
