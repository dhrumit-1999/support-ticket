<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'subject',
        'body_html',
        'body_text',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Available template variables.
     */
    public const VARIABLES = [
        '{{customer_name}}' => 'Customer name',
        '{{ticket_number}}' => 'Ticket number',
        '{{ticket_subject}}' => 'Ticket subject',
        '{{ticket_url}}' => 'Ticket URL',
        '{{company_name}}' => 'Tenant company name',
        '{{agent_name}}' => 'Assigned agent name',
        '{{message_content}}' => 'Message/reply content',
        '{{created_at}}' => 'Ticket creation date',
        '{{updated_at}}' => 'Last update date',
    ];

    /**
     * Get the tenant this template belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include templates for a specific tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Get template by name for a tenant.
     */
    public static function getForTenant(int $tenantId, string $name): ?self
    {
        return self::where('tenant_id', $tenantId)
            ->where('name', $name)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Render template with variables.
     */
    public function render(array $variables): array
    {
        $subject = $this->subject;
        $bodyHtml = $this->body_html;
        $bodyText = $this->body_text ?? strip_tags($this->body_html);

        foreach ($variables as $key => $value) {
            $searchKey = str_starts_with($key, '{{') ? $key : "{{{$key}}}";
            $subject = str_replace($searchKey, $value, $subject);
            $bodyHtml = str_replace($searchKey, $value, $bodyHtml);
            $bodyText = str_replace($searchKey, $value, $bodyText);
        }

        return [
            'subject' => $subject,
            'html' => $bodyHtml,
            'text' => $bodyText,
        ];
    }
}
