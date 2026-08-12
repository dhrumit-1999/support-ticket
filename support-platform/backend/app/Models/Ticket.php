<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Core\TicketStatus;
use App\Core\TicketPriority;
use App\Core\EscalationLevel;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'ticket_number',
        'subject',
        'description',
        'customer_id',
        'assigned_to',
        'department_id',
        'status',
        'priority',
        'escalation_level',
        'escalated_to',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'sla_breach',
        'tags',
        'category',
        'source',
        'internal_notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tags' => 'array',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_breach' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (!$ticket->ticket_number) {
                // Generate unique ticket number
                $ticket->ticket_number = self::generateTicketNumber($ticket->tenant_id);
            }
        });
    }

    /**
     * Generate a unique ticket number.
     */
    private static function generateTicketNumber(int $tenantId): int
    {
        $lastTicket = self::where('tenant_id', $tenantId)
            ->orderBy('ticket_number', 'desc')
            ->first();

        return $lastTicket ? $lastTicket->ticket_number + 1 : 1000;
    }

    /**
     * Get the tenant this ticket belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the customer who created this ticket.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the agent assigned to this ticket.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the department handling this ticket.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the agent this ticket is escalated to.
     */
    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    /**
     * Get all messages for this ticket.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    /**
     * Get all attachments for this ticket.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Get only customer-visible messages.
     */
    public function visibleMessages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->where('is_internal', false);
    }

    /**
     * Get only internal notes.
     */
    public function internalNotes(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->where('is_internal', true);
    }

    /**
     * Check if ticket is open/active.
     */
    public function isActive(): bool
    {
        return TicketStatus::isActive($this->status);
    }

    /**
     * Check if ticket is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === TicketStatus::RESOLVED;
    }

    /**
     * Check if ticket is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === TicketStatus::CLOSED;
    }

    /**
     * Check if ticket can be escalated.
     */
    public function canEscalate(): bool
    {
        return EscalationLevel::canEscalate($this->escalation_level);
    }

    /**
     * Get the next escalation level.
     */
    public function getNextEscalationLevel(): ?string
    {
        return EscalationLevel::nextLevel($this->escalation_level);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return TicketStatus::label($this->status);
    }

    /**
     * Get priority label.
     */
    public function getPriorityLabelAttribute(): string
    {
        return TicketPriority::label($this->priority);
    }

    /**
     * Get priority level for sorting.
     */
    public function getPriorityLevelAttribute(): int
    {
        return TicketPriority::level($this->priority);
    }

    /**
     * Get escalation level label.
     */
    public function getEscalationLevelLabelAttribute(): string
    {
        return EscalationLevel::label($this->escalation_level);
    }

    /**
     * Calculate SLA breach status.
     */
    public function checkSlaBreach(): bool
    {
        $slaPolicy = $this->tenant->slaPolicies()
            ->where('priority', $this->priority)
            ->where('is_active', true)
            ->first();

        if (!$slaPolicy) {
            return false;
        }

        // Check first response SLA
        if ($this->first_response_at) {
            $responseTime = $this->created_at->diffInMinutes($this->first_response_at);
            if ($responseTime > $slaPolicy->first_response_minutes) {
                return true;
            }
        }

        // Check resolution SLA
        if ($this->resolved_at) {
            $resolutionTime = $this->created_at->diffInMinutes($this->resolved_at);
            if ($resolutionTime > $slaPolicy->resolution_minutes) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scope a query to only include tickets for a specific tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include active tickets.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', TicketStatus::all());
    }

    /**
     * Scope a query to only include tickets with a specific status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include tickets with a specific priority.
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to only include tickets at a specific escalation level.
     */
    public function scopeEscalationLevel($query, string $level)
    {
        return $query->where('escalation_level', $level);
    }

    /**
     * Scope a query to search tickets by subject or description.
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('subject', 'LIKE', "%{$searchTerm}%")
              ->orWhere('description', 'LIKE', "%{$searchTerm}%");
        });
    }
}
