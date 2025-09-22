@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
                <h1 class="text-2xl font-medium text-gray-900">
                    📧 Sistema Email-to-Ticket con Threading
                </h1>
                <p class="mt-6 text-gray-500 leading-relaxed">
                    El sistema TARdesk procesa automáticamente emails entrantes y los convierte en tickets con threading perfecto para mantener las conversaciones organizadas.
                </p>
            </div>

            <div class="bg-gray-200 bg-opacity-25 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 p-6 lg:p-8">
                <!-- Estadísticas de Email-to-Ticket -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Tickets desde Email</h3>
                            <p class="text-3xl font-bold text-blue-600">
                                {{ App\Models\Ticket::where('source', 'email')->count() }}
                            </p>
                            <p class="text-sm text-gray-500">
                                de {{ App\Models\Ticket::count() }} tickets totales
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas de Threading -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Threads Activos</h3>
                            <p class="text-3xl font-bold text-green-600">
                                {{ App\Models\Ticket::whereNotNull('email_thread_id')->count() }}
                            </p>
                            <p class="text-sm text-gray-500">
                                conversaciones por email activas
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Respuestas por Email -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Respuestas por Email</h3>
                            <p class="text-3xl font-bold text-purple-600">
                                {{ App\Models\TicketReply::whereNotNull('email_message_id')->count() }}
                            </p>
                            <p class="text-sm text-gray-500">
                                respuestas enviadas/recibidas por email
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Tickets Pendientes de Email -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Tickets Abiertos (Email)</h3>
                            <p class="text-3xl font-bold text-red-600">
                                {{ App\Models\Ticket::where('source', 'email')->where('status', 'open')->count() }}
                            </p>
                            <p class="text-sm text-gray-500">
                                requieren respuesta urgente
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Threading Information -->
            <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">🔗 Cómo Funciona el Email Threading</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <svg class="h-6 w-6 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="font-semibold text-blue-900">1. Email Entrante</h3>
                        </div>
                        <p class="text-blue-800 text-sm">
                            Los emails entrantes se convierten automáticamente en tickets. Se detecta la categoría, prioridad y número de reservación.
                        </p>
                    </div>

                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <svg class="h-6 w-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                            <h3 class="font-semibold text-green-900">2. Threading Inteligente</h3>
                        </div>
                        <p class="text-green-800 text-sm">
                            Las respuestas por email se vinculan automáticamente al ticket correcto usando headers RFC-compliant.
                        </p>
                    </div>

                    <div class="bg-purple-50 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <svg class="h-6 w-6 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <h3 class="font-semibold text-purple-900">3. Respuesta Automática</h3>
                        </div>
                        <p class="text-purple-800 text-sm">
                            Cuando los agentes responden, se envía email automático al cliente manteniendo el thread.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Últimos Tickets desde Email -->
            <div class="p-6 lg:p-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">📧 Últimos Tickets desde Email</h2>

                @php
                    $emailTickets = App\Models\Ticket::where('source', 'email')
                        ->with(['customer', 'assignedTo', 'replies'])
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get();
                @endphp

                @if($emailTickets->count() > 0)
                    <div class="space-y-4">
                        @foreach($emailTickets as $ticket)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                            📧 {{ $ticket->ticket_number }}
                                        </span>
                                        @if($ticket->reservation_number)
                                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                            ✈️ {{ $ticket->reservation_number }}
                                        </span>
                                        @endif
                                        <span class="bg-{{ $ticket->priority === 'urgent' ? 'red' : ($ticket->priority === 'high' ? 'yellow' : 'blue') }}-100 text-{{ $ticket->priority === 'urgent' ? 'red' : ($ticket->priority === 'high' ? 'yellow' : 'blue') }}-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                            {{ $ticket->priority === 'urgent' ? '🔴 Urgente' : ($ticket->priority === 'high' ? '🟡 Alta' : '🔵 Normal') }}
                                        </span>
                                    </div>

                                    <h3 class="text-lg font-medium text-gray-900 mb-1">
                                        {{ $ticket->subject }}
                                    </h3>

                                    <div class="flex items-center space-x-4 text-sm text-gray-500 mb-2">
                                        <span>👤 {{ $ticket->customer->full_name }}</span>
                                        <span>📅 {{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                                        @if($ticket->assignedTo)
                                        <span>👨‍💼 Asignado a: {{ $ticket->assignedTo->name }}</span>
                                        @endif
                                    </div>

                                    @if($ticket->email_thread_id)
                                    <div class="flex items-center space-x-2 text-xs text-gray-400 mb-2">
                                        <span>🔗 Thread: {{ $ticket->email_thread_id }}</span>
                                        <span>•</span>
                                        <span>💬 {{ $ticket->replies->count() }} respuestas</span>
                                        @php $emailReplies = $ticket->replies()->whereNotNull('email_message_id')->count(); @endphp
                                        @if($emailReplies > 0)
                                        <span>•</span>
                                        <span>📧 {{ $emailReplies }} por email</span>
                                        @endif
                                    </div>
                                    @endif

                                    <p class="text-gray-700 text-sm leading-relaxed">
                                        {{ Str::limit($ticket->description, 150) }}
                                    </p>
                                </div>

                                <div class="flex flex-col space-y-2 ml-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $ticket->status === 'open' ? 'red' : ($ticket->status === 'resolved' ? 'green' : 'yellow') }}-100 text-{{ $ticket->status === 'open' ? 'red' : ($ticket->status === 'resolved' ? 'green' : 'yellow') }}-800">
                                        {{ $ticket->status === 'open' ? '🔴 Abierto' : ($ticket->status === 'resolved' ? '🟢 Resuelto' : '🟡 En Proceso') }}
                                    </span>

                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $ticket->category === 'booking' ? '✈️ Reservas' :
                                           ($ticket->category === 'baggage' ? '🧳 Equipaje' :
                                           ($ticket->category === 'complaint' ? '⚠️ Reclamos' : '📋 ' . ucfirst($ticket->category))) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <p class="mt-2 text-gray-500">No hay tickets creados desde email aún</p>
                        <p class="text-sm text-gray-400">Los emails entrantes se mostrarán aquí automáticamente</p>
                    </div>
                @endif
            </div>

            <!-- Guía de Threading -->
            <div class="p-6 lg:p-8 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">💡 Guía del Sistema Email Threading</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-2">🔍 Identificadores Visuales</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-center">
                                <span class="text-green-500 mr-2">📧</span>
                                <span><strong>Icono de Email:</strong> Ticket creado desde email</span>
                            </li>
                            <li class="flex items-center">
                                <span class="text-blue-500 mr-2">🔗</span>
                                <span><strong>Thread ID:</strong> Identificador único de conversación</span>
                            </li>
                            <li class="flex items-center">
                                <span class="text-purple-500 mr-2">💬</span>
                                <span><strong>Contador de Respuestas:</strong> Muestra emails en el hilo</span>
                            </li>
                            <li class="flex items-center">
                                <span class="text-orange-500 mr-2">✈️</span>
                                <span><strong>N° Reservación:</strong> Extraído automáticamente del email</span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-800 mb-2">🎯 Filtros de Email</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-center">
                                <span class="text-blue-500 mr-2">🔸</span>
                                <span><strong>"Con Thread de Email":</strong> Solo tickets con threading</span>
                            </li>
                            <li class="flex items-center">
                                <span class="text-green-500 mr-2">🔸</span>
                                <span><strong>"Con Respuestas por Email":</strong> Tickets con emails de ida y vuelta</span>
                            </li>
                            <li class="flex items-center">
                                <span class="text-purple-500 mr-2">🔸</span>
                                <span><strong>"Origen del Ticket":</strong> Filtra por email, teléfono o manual</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
