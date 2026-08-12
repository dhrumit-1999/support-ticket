<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'ticket_id',
        'user_id',
        'file_name',
        'original_name',
        'mime_type',
        'file_size',
        'storage_path',
        'storage_disk',
        'hash',
        'is_scanned',
        'scan_clean',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'file_size' => 'integer',
        'is_scanned' => 'boolean',
        'scan_clean' => 'boolean',
    ];

    /**
     * Get the tenant this attachment belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the ticket this attachment belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who uploaded this attachment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the URL for this attachment.
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->storage_disk === 's3') {
            return \Storage::disk('s3')->url($this->storage_path);
        }

        return asset('storage/' . $this->storage_path);
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Check if attachment is safe to download.
     */
    public function isSafe(): bool
    {
        return $this->is_scanned && $this->scan_clean;
    }
}
