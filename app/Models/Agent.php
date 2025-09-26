<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agent extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'department',
        'status',
        'bio',
        'avatar',
        'permissions',
        'max_concurrent_tickets',
        'can_reassign_tickets',
        'can_close_tickets',
    ];

    protected $casts = [
        'permissions' => 'array',
        'can_reassign_tickets' => 'boolean',
        'can_close_tickets' => 'boolean',
        'max_concurrent_tickets' => 'integer',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')
                    ->whereHas('tickets', function($q) {
                        $q->whereIn('status', ['open', 'in_progress']);
                    }, '<', function($agent) {
                        return $agent->max_concurrent_tickets;
                    });
    }

    public function getActiveTicketsCountAttribute()
    {
        return $this->tickets()->whereIn('status', ['open', 'in_progress'])->count();
    }
}
