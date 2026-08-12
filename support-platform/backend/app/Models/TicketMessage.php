<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'message_type',
        'is_internal',
        'attachments',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_internal' => 'boolean',
        'attachments' => 'array',
    ];

    /**
     * Get the ticket this message belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who sent this message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if message is from an agent.
     */
    public function isFromAgent(): bool
    {
        return $this->user->isAgent();
    }

    /**
     * Check if message is from customer.
     */
    public function isFromCustomer(): bool
    {
        return $this->user->isCustomer();
    }

    /**
     * Scope a query to only include internal messages.
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope a query to only include customer-visible messages.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_internal', false);
    }
}
