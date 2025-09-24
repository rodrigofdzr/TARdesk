<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuesta a su ticket - {{ $ticket->ticket_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2563eb;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border: 1px solid #e2e8f0;
        }
        .ticket-info {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2563eb;
        }
        .reply-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #10b981;
        }
        .footer {
            background-color: #374151;
            color: white;
            padding: 20px;
            border-radius: 0 0 8px 8px;
            text-align: center;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-open { background-color: #fee2e2; color: #dc2626; }
        .status-in_progress { background-color: #fef3c7; color: #d97706; }
        .status-pending { background-color: #dbeafe; color: #2563eb; }
        .status-resolved { background-color: #d1fae5; color: #059669; }
        .status-closed { background-color: #f3f4f6; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>✈️ TARdesk - Atención al Cliente</h1>
        <p>Respuesta a su ticket de soporte</p>
    </div>

    <div class="content">
        <div class="ticket-info">
            <h2>📋 Información del Ticket</h2>
            <p><strong>Número de Ticket:</strong> {{ $ticket->ticket_number }}</p>
            <p><strong>Asunto:</strong> {{ $ticket->subject }}</p>
            @if($ticket->reservation_number)
            <p><strong>Número de Reservación:</strong> {{ $ticket->reservation_number }}</p>
            @endif
            <p><strong>Estado:</strong>
                <span class="status-badge status-{{ $ticket->status }}">
                    {{ $ticket->status == 'open' ? 'Abierto' :
                       ($ticket->status == 'in_progress' ? 'En Progreso' :
                       ($ticket->status == 'pending' ? 'Pendiente' :
                       ($ticket->status == 'resolved' ? 'Resuelto' : 'Cerrado'))) }}
                </span>
            </p>
            <p><strong>Categoría:</strong>
                {{ $ticket->category == 'booking' ? 'Reservas' :
                   ($ticket->category == 'cancellation' ? 'Cancelaciones' :
                   ($ticket->category == 'refund' ? 'Reembolsos' :
                   ($ticket->category == 'baggage' ? 'Equipaje' :
                   ($ticket->category == 'flight_change' ? 'Cambio de Vuelo' :
                   ($ticket->category == 'complaint' ? 'Reclamos' : 'General'))))) }}
            </p>
        </div>

        <div class="reply-content">
            <h3>💬 Respuesta de {{ $agent->name }}</h3>
            <div style="border-top: 2px solid #e2e8f0; padding-top: 15px; margin-top: 15px;">
                {!! nl2br(e($reply->message)) !!}
            </div>
            <p style="margin-top: 20px; font-size: 14px; color: #6b7280;">
                <strong>Respondido el:</strong> {{ $reply->created_at->format('d/m/Y H:i') }}
            </p>
        </div>

        <div style="background-color: white; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b;">
            <h4>📧 ¿Necesita responder?</h4>
            <p>Simplemente responda a este email y su mensaje se agregará automáticamente al ticket.</p>
            <p><strong>Importante:</strong> Mantenga el número de ticket {{ $ticket->ticket_number }} en el asunto para que podamos procesar su respuesta correctamente.</p>
        </div>
    </div>

    <div class="footer">
        <p><strong>TARdesk - Sistema de Atención al Cliente</strong></p>
        <p>Este es un email automático. Para obtener ayuda, responda a este mensaje o visite nuestro sitio web.</p>
        <p style="font-size: 12px; margin-top: 10px;">
            Ticket ID: {{ $ticket->ticket_number }} | Thread ID: {{ $ticket->email_thread_id }}
        </p>
    </div>
</body>
</html>
