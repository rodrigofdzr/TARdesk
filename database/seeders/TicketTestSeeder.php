<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Support\Str;

class TicketTestSeeder extends Seeder
{
    public function run(): void
    {
        // Use or create a test user (agent)
        // Use firstOrCreate to avoid UNIQUE constraint violations when seeding multiple times
        $agent = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'role' => 'customer_service',
                'is_active' => true,
                // ensure a password exists when creating; bcrypt is fine for a seeder
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create a test customer (idempotent)
        $customer = Customer::firstOrCreate(
            ['email' => 'test-customer@example.com'],
            [
                'first_name' => 'Cliente',
                'last_name' => 'Prueba',
                'phone' => '+1234567890',
                'status' => 'active',
            ]
        );

        // Generate IDs once so we can reference them (and avoid recreating items on rerun)
        $threadId = 'THREAD-' . Str::random(8);
        $messageId = 'MSG-' . Str::random(12);

        // Create or get a ticket that originated from email with a thread id
        $ticket = Ticket::firstOrCreate(
            [
                'reservation_number' => 'ABC123',
                'customer_id' => $customer->id,
            ],
            [
                'created_by' => $agent->id,
                'assigned_to' => $agent->id,
                'subject' => 'Problema con la reservación',
                'description' => 'Este es un ticket de prueba creado desde un seeder. El cliente reporta una incidencia con su reserva.',
                'priority' => 'normal',
                'status' => 'open',
                'category' => 'booking',
                'source' => 'email',
                'email_message_id' => $messageId,
                'email_thread_id' => $threadId,
            ]
        );

        // Add replies: one customer email reply, one internal note, one agent email reply
        $customerReplyMessage = 'Respuesta del cliente vía email: Gracias por la ayuda.';
        TicketReply::firstOrCreate(
            [
                'ticket_id' => $ticket->id,
                'type' => 'reply',
                'message' => $customerReplyMessage,
            ],
            [
                // Migration requires user_id non-null; use the test agent as the actor for seeder-created replies
                'user_id' => $agent->id,
                'is_customer_visible' => true,
                'email_message_id' => 'MSG-' . Str::random(12),
                'attachments' => [],
            ]
        );

        $internalNoteMessage = 'Nota interna: revisar póliza de reembolso antes de responder.';
        TicketReply::firstOrCreate(
            [
                'ticket_id' => $ticket->id,
                'type' => 'internal_note',
                'message' => $internalNoteMessage,
            ],
            [
                'user_id' => $agent->id,
                'is_customer_visible' => false,
                'email_message_id' => null,
                'attachments' => [],
            ]
        );

        $agentReplyMessage = 'Respuesta del agente enviada por email: Hemos iniciado el proceso.';
        TicketReply::firstOrCreate(
            [
                'ticket_id' => $ticket->id,
                'type' => 'reply',
                'message' => $agentReplyMessage,
            ],
            [
                'user_id' => $agent->id,
                'is_customer_visible' => true,
                'email_message_id' => 'MSG-' . Str::random(12),
                'attachments' => ['itinerary.pdf'],
            ]
        );

        $this->command->info("Created test ticket: ID={$ticket->id}, ticket_number={$ticket->ticket_number}, thread={$ticket->email_thread_id}");
    }
}
