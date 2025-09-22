<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'reservation_number',
        'customer_id',
        'assigned_to',
        'created_by',
        'subject',
        'description',
        'priority',
        'status',
        'category',
        'source',
        'email_message_id',
        'email_thread_id',
        'resolved_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'resolved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (!$ticket->ticket_number) {
                $ticket->ticket_number = 'TK-' . date('Y') . '-' . str_pad(
                    (static::whereYear('created_at', now()->year)->count() ?? 0) + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    // Relaciones
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at');
    }

    public function visibleReplies(): HasMany
    {
        return $this->hasMany(TicketReply::class)
            ->where('is_customer_visible', true)
            ->orderBy('created_at');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress', 'pending']);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeByReservation($query, $reservationNumber)
    {
        return $query->where('reservation_number', $reservationNumber);
    }

    // Métodos de utilidad
    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress', 'pending']);
    }

    public function canBeAssignedTo(User $user): bool
    {
        return in_array($user->role, ['manager', 'customer_service']);
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'red',
            'high' => 'orange',
            'normal' => 'blue',
            'low' => 'gray',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'red',
            'in_progress' => 'yellow',
            'pending' => 'orange',
            'resolved' => 'green',
            'closed' => 'gray',
        };
    }
}
