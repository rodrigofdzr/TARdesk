<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Manager de Customer Service (acceso administrativo)
        User::create([
            'name' => 'María González',
            'email' => 'manager@taraerolineas.app',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Agente de Customer Service (maneja tickets por email)
        User::create([
            'name' => 'Carlos Rodríguez',
            'email' => 'customer.service@taraerolineas.app',
            'password' => Hash::make('password123'),
            'role' => 'customer_service',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Agente de Customer Service adicional
        User::create([
            'name' => 'Laura Hernández',
            'email' => 'customer.service2@taraerolineas.app',
            'password' => Hash::make('password123'),
            'role' => 'customer_service',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Agente de Call Center (registra tickets de llamadas)
        User::create([
            'name' => 'Ana López',
            'email' => 'call.center@taraerolineas.app',
            'password' => Hash::make('password123'),
            'role' => 'call_center',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Agente adicional de Call Center
        User::create([
            'name' => 'Patricia Silva',
            'email' => 'call.center2@taraerolineas.app',
            'password' => Hash::make('password123'),
            'role' => 'call_center',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Supervisor de Call Center
        User::create([
            'name' => 'Roberto Jiménez',
            'email' => 'call.center.supervisor@taraerolineas.app',
            'password' => Hash::make('password123'),
            'role' => 'call_center',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
