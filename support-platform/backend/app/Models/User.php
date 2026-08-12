<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar_path',
        'job_title',
        'signature',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /**
     * Get the tenant this user belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get tickets created by this user (as customer).
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }

    /**
     * Get tickets assigned to this user.
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    /**
     * Get escalated tickets assigned to this user.
     */
    public function escalatedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'escalated_to');
    }

    /**
     * Get departments this user belongs to.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user')
                    ->withPivot('is_lead')
                    ->withTimestamps();
    }

    /**
     * Get messages sent by this user.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    /**
     * Get attachments uploaded by this user.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Get authored knowledge articles.
     */
    public function knowledgeArticles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class, 'author_id');
    }

    /**
     * Get audit logs for this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Check if user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user can access multiple tenants.
     */
    public function canAccessMultipleTenants(): bool
    {
        return in_array($this->role, ['super_admin', 'l2_agent', 'l3_agent']);
    }

    /**
     * Check if user is an agent (can handle tickets).
     */
    public function isAgent(): bool
    {
        return in_array($this->role, ['tenant_admin', 'tenant_agent', 'l2_agent', 'l3_agent']);
    }

    /**
     * Check if user is a customer.
     */
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include users of a specific role.
     */
    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope a query to only include users in a specific tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Get the display name with role badge.
     */
    public function getDisplayNameWithRoleAttribute(): string
    {
        $roleLabel = ucwords(str_replace('_', ' ', $this->role));
        return "{$this->name} ({$roleLabel})";
    }
}
