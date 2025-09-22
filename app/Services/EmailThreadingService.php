<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Mailable;

class EmailThreadingService
{
    /**
     * Envía una respuesta por email manteniendo el threading correcto
     */
    public function sendTicketReply(Ticket $ticket, TicketReply $reply): bool
    {
        try {
            $customer = $ticket->customer;
            $agent = $reply->user;

            // Crear headers de threading para mantener la conversación
            $headers = $this->buildThreadingHeaders($ticket, $reply);

            // Crear el subject con el número de ticket para threading
            $subject = $this->buildThreadedSubject($ticket);

            $emailData = [
                'ticket' => $ticket,
                'reply' => $reply,
                'customer' => $customer,
                'agent' => $agent,
                'subject' => $subject,
                'headers' => $headers
            ];

            // Enviar email al cliente
            Mail::to($customer->email)
                ->send(new TicketReplyMail($emailData));

            Log::info('Email de respuesta enviado', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'customer_email' => $customer->email,
                'reply_id' => $reply->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error enviando email de respuesta', [
                'ticket_id' => $ticket->id,
                'reply_id' => $reply->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envía notificación de nuevo ticket por email
     */
    public function sendNewTicketNotification(Ticket $ticket): bool
    {
        try {
            $customer = $ticket->customer;

            // Headers iniciales para el thread
            $headers = [
                'Message-ID' => $this->generateMessageId($ticket),
                'X-Ticket-ID' => $ticket->ticket_number,
                'X-Thread-ID' => $ticket->email_thread_id,
            ];

            $subject = "[{$ticket->ticket_number}] {$ticket->subject}";

            $emailData = [
                'ticket' => $ticket,
                'customer' => $customer,
                'subject' => $subject,
                'headers' => $headers
            ];

            // Enviar confirmación al cliente
            Mail::to($customer->email)
                ->send(new NewTicketConfirmationMail($emailData));

            // Actualizar el ticket con el Message-ID generado
            $ticket->update([
                'email_message_id' => $headers['Message-ID']
            ]);

            Log::info('Email de confirmación de nuevo ticket enviado', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'customer_email' => $customer->email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error enviando confirmación de nuevo ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Construye los headers de threading para mantener la conversación
     */
    private function buildThreadingHeaders(Ticket $ticket, TicketReply $reply): array
    {
        $headers = [
            'Message-ID' => $this->generateMessageId($ticket, $reply->id),
            'X-Ticket-ID' => $ticket->ticket_number,
            'X-Thread-ID' => $ticket->email_thread_id,
        ];

        // In-Reply-To: referencia al mensaje original del ticket o al último reply
        if ($ticket->email_message_id) {
            $headers['In-Reply-To'] = $ticket->email_message_id;
        }

        // References: cadena completa de mensajes en el thread
        $references = $this->buildReferencesChain($ticket);
        if (!empty($references)) {
            $headers['References'] = implode(' ', $references);
        }

        return $headers;
    }

    /**
     * Construye la cadena de referencias para threading
     */
    private function buildReferencesChain(Ticket $ticket): array
    {
        $references = [];

        // Mensaje original del ticket
        if ($ticket->email_message_id) {
            $references[] = $ticket->email_message_id;
        }

        // Mensajes de respuestas anteriores
        $previousReplies = $ticket->replies()
            ->whereNotNull('email_message_id')
            ->orderBy('created_at')
            ->pluck('email_message_id')
            ->toArray();

        $references = array_merge($references, $previousReplies);

        return array_unique($references);
    }

    /**
     * Construye el subject manteniendo el threading
     */
    private function buildThreadedSubject(Ticket $ticket): string
    {
        $subject = $ticket->subject;

        // Si no tiene el prefijo del ticket, agregarlo
        if (!str_contains($subject, $ticket->ticket_number)) {
            $subject = "[{$ticket->ticket_number}] {$subject}";
        }

        // Agregar Re: si no lo tiene
        if (!preg_match('/^Re:\s/i', $subject)) {
            $subject = "Re: {$subject}";
        }

        return $subject;
    }

    /**
     * Genera un Message-ID único para el email
     */
    private function generateMessageId(Ticket $ticket, ?int $replyId = null): string
    {
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: 'tardesk.local';
        $timestamp = time();
        $ticketId = $ticket->id;

        if ($replyId) {
            return "<ticket-{$ticketId}-reply-{$replyId}-{$timestamp}@{$domain}>";
        } else {
            return "<ticket-{$ticketId}-{$timestamp}@{$domain}>";
        }
    }
}

/**
 * Mailable para respuestas de tickets
 */
class TicketReplyMail extends Mailable
{
    public array $emailData;

    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;
    }

    public function build()
    {
        $mail = $this->subject($this->emailData['subject'])
            ->view('emails.ticket-reply')
            ->with($this->emailData);

        // Agregar headers de threading
        foreach ($this->emailData['headers'] as $header => $value) {
            $mail->withSwiftMessage(function ($message) use ($header, $value) {
                $message->getHeaders()->addTextHeader($header, $value);
            });
        }

        return $mail;
    }
}

/**
 * Mailable para confirmación de nuevo ticket
 */
class NewTicketConfirmationMail extends Mailable
{
    public array $emailData;

    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;
    }

    public function build()
    {
        $mail = $this->subject($this->emailData['subject'])
            ->view('emails.new-ticket-confirmation')
            ->with($this->emailData);

        // Agregar headers de threading
        foreach ($this->emailData['headers'] as $header => $value) {
            $mail->withSwiftMessage(function ($message) use ($header, $value) {
                $message->getHeaders()->addTextHeader($header, $value);
            });
        }

        return $mail;
    }
}
