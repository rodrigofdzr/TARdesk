<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $agents = [
            [
                'name' => 'Ana García',
                'email' => 'ana.garcia@company.com',
                'phone' => '+34 612 345 678',
                'department' => 'Soporte Técnico',
                'status' => 'active',
                'bio' => 'Especialista en soporte técnico con 5 años de experiencia. Experta en resolución de problemas de hardware y software.',
                'max_concurrent_tickets' => 15,
                'can_reassign_tickets' => true,
                'can_close_tickets' => true,
            ],
            [
                'name' => 'Carlos Rodríguez',
                'email' => 'carlos.rodriguez@company.com',
                'phone' => '+34 698 765 432',
                'department' => 'Ventas',
                'status' => 'active',
                'bio' => 'Agente de ventas especializado en atención al cliente y resolución de consultas comerciales.',
                'max_concurrent_tickets' => 12,
                'can_reassign_tickets' => false,
                'can_close_tickets' => true,
            ],
            [
                'name' => 'María López',
                'email' => 'maria.lopez@company.com',
                'phone' => '+34 654 321 987',
                'department' => 'Soporte Técnico',
                'status' => 'active',
                'bio' => 'Técnica senior con experiencia en sistemas complejos y escalación de problemas críticos.',
                'max_concurrent_tickets' => 20,
                'can_reassign_tickets' => true,
                'can_close_tickets' => true,
            ],
            [
                'name' => 'Diego Martínez',
                'email' => 'diego.martinez@company.com',
                'phone' => '+34 687 543 210',
                'department' => 'Atención al Cliente',
                'status' => 'on_break',
                'bio' => 'Especialista en atención al cliente con enfoque en satisfacción y retención de clientes.',
                'max_concurrent_tickets' => 10,
                'can_reassign_tickets' => false,
                'can_close_tickets' => true,
            ],
            [
                'name' => 'Laura Sánchez',
                'email' => 'laura.sanchez@company.com',
                'phone' => '+34 623 876 543',
                'department' => 'Soporte Técnico',
                'status' => 'inactive',
                'bio' => 'Técnica junior en periodo de formación. Especializada en tickets de baja complejidad.',
                'max_concurrent_tickets' => 8,
                'can_reassign_tickets' => false,
                'can_close_tickets' => false,
            ],
        ];

        foreach ($agents as $agentData) {
            Agent::create($agentData);
        }
    }
}
