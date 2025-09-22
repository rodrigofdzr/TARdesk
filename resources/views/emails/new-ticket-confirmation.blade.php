<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket creado - {{ $ticket->ticket_number }}</title>
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
            background-color: #10b981;
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
        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .priority-urgent { background-color: #fee2e2; color: #dc2626; }
        .priority-high { background-color: #fef3c7; color: #d97706; }
        .priority-normal { background-color: #dbeafe; color: #2563eb; }
        .priority-low { background-color: #f3f4f6; color: #6b7280; }
        .next-steps {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ ¡Ticket Creado Exitosamente!</h1>
        <p>Hemos recibido su solicitud de soporte</p>
    </div>

    <div class="content">
        <p>Estimado/a {{ $customer->full_name }},</p>

        <p>Gracias por contactarnos. Hemos recibido su email y creado un ticket de soporte para atender su consulta de manera eficiente.</p>

        <div class="ticket-info">
            <h2>📋 Detalles de su Ticket</h2>
            <p><strong>Número de Ticket:</strong> <span style="font-family: monospace; font-size: 16px; font-weight: bold; color: #2563eb;">{{ $ticket->ticket_number }}</span></p>
            <p><strong>Asunto:</strong> {{ $ticket->subject }}</p>
            @if($ticket->reservation_number)
            <p><strong>Número de Reservación:</strong> {{ $ticket->reservation_number }}</p>
            @endif
            <p><strong>Categoría:</strong>
                {{ $ticket->category == 'booking' ? 'Reservas' :
                   ($ticket->category == 'cancellation' ? 'Cancelaciones' :
                   ($ticket->category == 'refund' ? 'Reembolsos' :
                   ($ticket->category == 'baggage' ? 'Equipaje' :
                   ($ticket->category == 'flight_change' ? 'Cambio de Vuelo' :
                   ($ticket->category == 'complaint' ? 'Reclamos' : 'General'))))) }}
            </p>
            <p><strong>Prioridad:</strong>
                <span class="priority-badge priority-{{ $ticket->priority }}">
                    {{ $ticket->priority == 'urgent' ? 'Urgente' :
                       ($ticket->priority == 'high' ? 'Alta' :
                       ($ticket->priority == 'normal' ? 'Normal' : 'Baja')) }}
                </span>
            </p>
            <p><strong>Fecha de Creación:</strong> {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
        </div>

        <div style="background-color: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <h3>📝 Su Consulta Original</h3>
            <div style="background-color: #f8fafc; padding: 15px; border-radius: 6px; margin-top: 10px;">
                {!! nl2br(e($ticket->description)) !!}
            </div>
        </div>
as
        <div class="next-steps">
            <h3>🔄 Próximos Pasos</h3>
            <ul style="margin: 15px 0; padding-left: 20px;">
                <li><strong>Asignación:</strong> Su ticket será asignado a un agente especializado en breve.</li>
                <li><strong>Tiempo de Respuesta:</strong>
                    @if($ticket->priority == 'urgent')
                        Recibirá una respuesta dentro de las próximas 2 horas.
                    @elseif($ticket->priority == 'high')
                        Recibirá una respuesta dentro de las próximas 4 horas.
                    @else
                        Recibirá una respuesta dentro de las próximas 24 horas.
                    @endif
                </li>
                <li><strong>Seguimiento:</strong> Le notificaremos por email cada actualización en su ticket.</li>
            </ul>
        </div>

        <div style="background-color: white; padding: 20px; border-radius: 8px; border-left: 4px solid #10b981; margin-top: 20px;">
            <h4>💡 Consejos para un Mejor Servicio</h4>
            <ul style="margin: 10px 0; padding-left: 20px; font-size: 14px;">
                <li>Para responder, simplemente responda a este email.</li>
                <li>Mantenga el número de ticket <strong>{{ $ticket->ticket_number }}</strong> en el asunto.</li>
                <li>Si tiene información adicional, compártala respondiendo a este email.</li>
                <li>Cada email que envíe se agregará automáticamente a su ticket.</li>
            </ul>
        </div>
    </div>

    <div class="footer">
        <p><strong>TARdesk - Sistema de Atención al Cliente de Aerolíneas</strong></p>
        <p>Estamos aquí para ayudarle. Gracias por elegir nuestros servicios.</p>
        <p style="font-size: 12px; margin-top: 10px;">
            Ticket: {{ $ticket->ticket_number }} | Referencia: {{ $ticket->email_thread_id }}
        </p>
    </div>
</body>
</html>
