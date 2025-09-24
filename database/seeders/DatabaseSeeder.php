<?php
// Run this seeder with: php artisan db:seed
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run custom seeders
        $this->call([
            AirlineSystemSeeder::class,
            TicketTestSeeder::class,
        ]);
    }
}
