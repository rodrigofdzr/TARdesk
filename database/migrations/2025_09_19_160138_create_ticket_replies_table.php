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
        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // Quien responde (agente o cliente)
            $table->text('message');
            $table->enum('type', ['reply', 'internal_note', 'system'])->default('reply');
            $table->boolean('is_customer_visible')->default(true); // Si el cliente puede ver esta respuesta
            $table->string('email_message_id')->nullable(); // Para threading de emails
            $table->json('attachments')->nullable(); // Para archivos adjuntos
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
    }
};
