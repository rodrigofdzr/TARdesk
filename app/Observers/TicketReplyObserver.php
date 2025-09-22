<?php

namespace App\Observers;

use App\Models\TicketReply;
use App\Services\EmailThreadingService;
use Illuminate\Support\Facades\Log;

class TicketReplyObserver
{
    private EmailThreadingService $emailThreadingService;

    public function __construct(EmailThreadingService $emailThreadingService)
    {
        $this->emailThreadingService = $emailThreadingService;
    }

    /**
     * Handle the TicketReply "created" event.
     */
    public function created(TicketReply $reply): void
    {
        // Solo enviar email si:
        // 1. La respuesta es visible para el cliente
        // 2. Es una respuesta (no nota interna)
        // 3. El usuario que responde es un agente (no el cliente)
        // 4. No tiene email_message_id (no vino de email)

        if ($reply->is_customer_visible &&
            $reply->type === 'reply' &&
            $reply->user &&
            in_array($reply->user->role, ['manager', 'customer_service']) &&
            !$reply->email_message_id) {

            Log::info('Enviando respuesta por email', [
                'ticket_id' => $reply->ticket_id,
                'reply_id' => $reply->id,
                'agent' => $reply->user->name
            ]);

            $this->emailThreadingService->sendTicketReply($reply->ticket, $reply);
        }
    }
}
