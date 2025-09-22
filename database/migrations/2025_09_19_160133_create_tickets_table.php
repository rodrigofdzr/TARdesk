<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique(); // Número único del ticket
            $table->string('reservation_number')->nullable(); // Número de reservación (clave importante)
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // Agente asignado
            $table->foreignId('created_by')->constrained('users'); // Quien creó el ticket
            $table->string('subject');
            $table->text('description');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['open', 'in_progress', 'pending', 'resolved', 'closed'])->default('open');
            $table->enum('category', ['booking', 'cancellation', 'refund', 'baggage', 'flight_change', 'complaint', 'general'])->default('general');
            $table->string('source')->default('manual'); // manual, email, phone
            $table->string('email_message_id')->nullable(); // Para threading de emails
            $table->string('email_thread_id')->nullable(); // Para agrupar emails relacionados
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable(); // Para datos adicionales
            $table->timestamps();

            $table->index(['reservation_number', 'customer_id']);
            $table->index(['status', 'assigned_to']);
            $table->index(['email_thread_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
