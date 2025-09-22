<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Services\EmailThreadingService;
use Illuminate\Support\Facades\Log;

class TicketObserver
{
    private EmailThreadingService $emailThreadingService;

    public function __construct(EmailThreadingService $emailThreadingService)
    {
        $this->emailThreadingService = $emailThreadingService;
    }

    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        // Solo enviar confirmación si el ticket se creó desde email o manualmente
        // No enviar si ya tiene un email_message_id (significa que vino de email)
        if ($ticket->source === 'email' && !$ticket->email_message_id) {
            Log::info('Enviando confirmación de nuevo ticket', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number
            ]);

            $this->emailThreadingService->sendNewTicketNotification($ticket);
        }
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        // Aquí se pueden agregar notificaciones para cambios de estado, etc.
    }
}
