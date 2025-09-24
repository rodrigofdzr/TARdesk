<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $reply;
    public $customer;
    public $agent;
    public $subjectLine;
    public $headers;

    public function __construct(array $emailData)
    {
        $this->ticket = $emailData['ticket'];
        $this->reply = $emailData['reply'];
        $this->customer = $emailData['customer'];
        $this->agent = $emailData['agent'];
        $this->subjectLine = $emailData['subject'];
        $this->headers = $emailData['headers'];
    }

    public function build()
    {
        $mail = $this->subject($this->subjectLine)
            ->view('emails.ticket-reply')
            ->with([
                'ticket' => $this->ticket,
                'reply' => $this->reply,
                'customer' => $this->customer,
                'agent' => $this->agent
            ]);

        // Adjuntar archivos
        if (!empty($this->reply->attachments) && is_array($this->reply->attachments)) {
            foreach ($this->reply->attachments as $attachment) {
                if (!empty($attachment['path'])) {
                    $mail->attach(storage_path('app/' . $attachment['path']), [
                        'as' => $attachment['name'] ?? basename($attachment['path'])
                    ]);
                }
            }
        }

        // Agregar headers de threading
        if (!empty($this->headers) && is_array($this->headers)) {
            foreach ($this->headers as $key => $value) {
                $mail->withSwiftMessage(function ($message) use ($key, $value) {
                    $message->getHeaders()->addTextHeader($key, $value);
                });
            }
        }

        return $mail;
    }
}

