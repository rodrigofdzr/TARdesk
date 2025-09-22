<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'type',
        'is_customer_visible',
        'email_message_id',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_customer_visible' => 'boolean',
    ];

    // Relaciones
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeVisible($query)
    {
        return $query->where('is_customer_visible', true);
    }

    public function scopeInternal($query)
    {
        return $query->where('type', 'internal_note');
    }

    // Métodos de utilidad
    public function isInternal(): bool
    {
        return $this->type === 'internal_note';
    }

    public function hasAttachments(): bool
    {
        return $this->attachments && count($this->attachments) > 0;
    }
}
