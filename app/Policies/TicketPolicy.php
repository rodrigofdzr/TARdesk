<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Todos los roles pueden ver tickets
        return $user->is_active;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Todos pueden ver tickets
        // Call center solo ve los que creó
        if ($user->role === 'call_center') {
            return $ticket->created_by === $user->id;
        }

        // Atención al cliente ve todos los tickets o los asignados a él
        if ($user->role === 'customer_service') {
            return true;
        }

        // Managers ven todo
        return $user->role === 'manager';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Todos los roles pueden crear tickets
        return $user->is_active;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // Call center solo puede editar tickets que creó y que están abiertos
        if ($user->role === 'call_center') {
            return $ticket->created_by === $user->id && $ticket->status === 'open';
        }

        // Atención al cliente puede editar todos los tickets
        if ($user->role === 'customer_service') {
            return true;
        }

        // Managers pueden editar todo
        return $user->role === 'manager';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        // Solo managers pueden eliminar tickets
        return $user->role === 'manager';
    }

    /**
     * Determine whether the user can assign tickets.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        // Solo atención al cliente y managers pueden asignar tickets
        return in_array($user->role, ['manager', 'customer_service']);
    }

    /**
     * Determine whether the user can respond to tickets.
     */
    public function respond(User $user, Ticket $ticket): bool
    {
        // Solo atención al cliente y managers pueden responder tickets
        return in_array($user->role, ['manager', 'customer_service']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Ticket $ticket): bool
    {
        return $user->role === 'manager';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return $user->role === 'manager';
    }
}
