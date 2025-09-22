<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Support\Facades\Hash;

class AirlineSystemSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuarios del sistema con diferentes roles
        $manager = User::create([
            'name' => 'Maria González',
            'email' => 'manager@tardesk.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'department' => 'Gerencia',
            'is_active' => true,
        ]);

        $customerService1 = User::create([
            'name' => 'Juan Pérez',
            'email' => 'juan.perez@tardesk.com',
            'password' => Hash::make('password'),
            'role' => 'customer_service',
            'department' => 'Atención al Cliente',
            'is_active' => true,
        ]);

        $customerService2 = User::create([
            'name' => 'Ana Rodríguez',
            'email' => 'ana.rodriguez@tardesk.com',
            'password' => Hash::make('password'),
            'role' => 'customer_service',
            'department' => 'Atención al Cliente',
            'is_active' => true,
        ]);

        $callCenter1 = User::create([
            'name' => 'Carlos López',
            'email' => 'carlos.lopez@tardesk.com',
            'password' => Hash::make('password'),
            'role' => 'call_center',
            'department' => 'Call Center',
            'is_active' => true,
        ]);

        $callCenter2 = User::create([
            'name' => 'Laura Martínez',
            'email' => 'laura.martinez@tardesk.com',
            'password' => Hash::make('password'),
            'role' => 'call_center',
            'department' => 'Call Center',
            'is_active' => true,
        ]);

        // Crear clientes de ejemplo
        $customer1 = Customer::create([
            'first_name' => 'Roberto',
            'last_name' => 'Silva',
            'email' => 'roberto.silva@email.com',
            'phone' => '+1234567890',
            'document_type' => 'passport',
            'document_number' => 'A12345678',
            'status' => 'active',
        ]);

        $customer2 = Customer::create([
            'first_name' => 'Carmen',
            'last_name' => 'Torres',
            'email' => 'carmen.torres@email.com',
            'phone' => '+1234567891',
            'document_type' => 'dni',
            'document_number' => '12345678A',
            'status' => 'active',
        ]);

        $customer3 = Customer::create([
            'first_name' => 'Diego',
            'last_name' => 'Mendoza',
            'email' => 'diego.mendoza@email.com',
            'phone' => '+1234567892',
            'document_type' => 'passport',
            'document_number' => 'B87654321',
            'status' => 'active',
        ]);

        $customer4 = Customer::create([
            'first_name' => 'Patricia',
            'last_name' => 'Vega',
            'email' => 'patricia.vega@email.com',
            'phone' => '+1234567893',
            'document_type' => 'dni',
            'document_number' => '87654321B',
            'status' => 'active',
        ]);

        // Crear tickets de ejemplo con diferentes estados y categorías
        Ticket::create([
            'reservation_number' => 'ABC123',
            'customer_id' => $customer1->id,
            'assigned_to' => $customerService1->id,
            'created_by' => $callCenter1->id,
            'subject' => 'Problema con equipaje perdido',
            'description' => 'El cliente reporta que su equipaje no llegó en el vuelo ABC123. Necesita ubicación y entrega urgente.',
            'priority' => 'high',
            'status' => 'in_progress',
            'category' => 'baggage',
            'source' => 'phone',
        ]);

        Ticket::create([
            'reservation_number' => 'DEF456',
            'customer_id' => $customer2->id,
            'assigned_to' => $customerService2->id,
            'created_by' => $callCenter2->id,
            'subject' => 'Solicitud de reembolso por cancelación',
            'description' => 'Cliente solicita reembolso completo debido a cancelación de vuelo por parte de la aerolínea.',
            'priority' => 'normal',
            'status' => 'pending',
            'category' => 'refund',
            'source' => 'email',
            'email_thread_id' => 'thread_12345',
        ]);

        Ticket::create([
            'reservation_number' => 'GHI789',
            'customer_id' => $customer3->id,
            'assigned_to' => null,
            'created_by' => $callCenter1->id,
            'subject' => 'Cambio de fecha de vuelo',
            'description' => 'Cliente desea cambiar la fecha de su vuelo del 25 de septiembre al 30 de septiembre.',
            'priority' => 'normal',
            'status' => 'open',
            'category' => 'flight_change',
            'source' => 'manual',
        ]);

        Ticket::create([
            'reservation_number' => 'JKL012',
            'customer_id' => $customer4->id,
            'assigned_to' => $customerService1->id,
            'created_by' => $manager->id,
            'subject' => 'Reclamo por retraso de vuelo',
            'description' => 'Cliente reclama compensación por retraso de más de 3 horas en vuelo internacional.',
            'priority' => 'urgent',
            'status' => 'open',
            'category' => 'complaint',
            'source' => 'email',
            'email_thread_id' => 'thread_67890',
        ]);

        Ticket::create([
            'reservation_number' => 'MNO345',
            'customer_id' => $customer1->id,
            'assigned_to' => $customerService2->id,
            'created_by' => $callCenter2->id,
            'subject' => 'Consulta sobre política de equipaje',
            'description' => 'Cliente consulta sobre límites de peso y dimensiones para equipaje de mano en vuelos internacionales.',
            'priority' => 'low',
            'status' => 'resolved',
            'category' => 'general',
            'source' => 'phone',
            'resolved_at' => now()->subDays(2),
        ]);

        Ticket::create([
            'reservation_number' => null,
            'customer_id' => $customer2->id,
            'assigned_to' => null,
            'created_by' => $callCenter1->id,
            'subject' => 'Nueva reservación para vuelo familiar',
            'description' => 'Cliente desea hacer nueva reservación para 4 personas con destino a Europa.',
            'priority' => 'normal',
            'status' => 'open',
            'category' => 'booking',
            'source' => 'manual',
        ]);

        $this->command->info('✅ Sistema de aerolínea creado exitosamente:');
        $this->command->info('📧 Manager: manager@tardesk.com / password');
        $this->command->info('👥 Atención al Cliente: juan.perez@tardesk.com, ana.rodriguez@tardesk.com / password');
        $this->command->info('📞 Call Center: carlos.lopez@tardesk.com, laura.martinez@tardesk.com / password');
        $this->command->info('🎫 ' . Ticket::count() . ' tickets de ejemplo creados');
        $this->command->info('👤 ' . Customer::count() . ' clientes de ejemplo creados');
    }
}
