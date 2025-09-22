<?php

namespace App\Filament\Resources\Tickets;

use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\RelationManagers\RepliesRelationManager;
use App\Models\Ticket;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use BackedEnum;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Tickets';

    public static function form(Schema $schema): Schema
    {
        $user = auth()->user();

        return $schema->schema([
            \Filament\Forms\Components\TextInput::make('ticket_number')
                ->label('Número de Ticket')
                ->disabled()
                ->dehydrated(false)
                ->placeholder('Se generará automáticamente'),

            // Mostrar información de email si existe
            \Filament\Forms\Components\Placeholder::make('email_info')
                ->label('Información de Email')
                ->content(function ($record) {
                    if (!$record || $record->source !== 'email') {
                        return 'Este ticket no fue creado desde email';
                    }

                    $info = [];
                    if ($record->email_message_id) {
                        $info[] = "📧 Message ID: " . substr($record->email_message_id, 0, 30) . "...";
                    }
                    if ($record->email_thread_id) {
                        $info[] = "🔗 Thread ID: " . $record->email_thread_id;
                    }
                    $emailReplies = $record->replies()->whereNotNull('email_message_id')->count();
                    if ($emailReplies > 0) {
                        $info[] = "✉️ {$emailReplies} respuestas por email en el thread";
                    }

                    return implode('<br>', $info);
                })
                ->visible(fn ($record) => $record && $record->source === 'email')
                ->columnSpanFull(),

            \Filament\Forms\Components\TextInput::make('reservation_number')
                ->label('Número de Reservación')
                ->placeholder('Ej: ABC123, DEF456')
                ->helperText('Número de reservación de vuelo del cliente')
                ->suffixIcon('heroicon-m-paper-airplane'),

            \Filament\Forms\Components\Select::make('customer_id')
                ->label('Cliente')
                ->relationship('customer', 'email')
                ->getOptionLabelFromRecordUsing(fn ($record): string =>
                    "{$record->full_name} ({$record->email}) - {$record->customer_number}"
                )
                ->searchable(['first_name', 'last_name', 'email', 'customer_number'])
                ->required()
                ->suffixIcon('heroicon-m-user'),

            \Filament\Forms\Components\TextInput::make('subject')
                ->label('Asunto')
                ->required()
                ->maxLength(255)
                ->helperText(fn ($record) =>
                    $record && $record->source === 'email'
                        ? 'Este asunto proviene del email original'
                        : 'Describe brevemente el problema o consulta'
                ),

            \Filament\Forms\Components\Textarea::make('description')
                ->label('Descripción')
                ->required()
                ->rows(5)
                ->helperText(fn ($record) =>
                    $record && $record->source === 'email'
                        ? 'Este contenido fue extraído automáticamente del email'
                        : 'Describe detalladamente el problema o consulta'
                ),

            // Selector de categoría con indicador de detección automática
            \Filament\Forms\Components\Select::make('category')
                ->label('Categoría')
                ->options([
                    'booking' => '✈️ Reservas',
                    'cancellation' => '❌ Cancelaciones',
                    'refund' => '💰 Reembolsos',
                    'baggage' => '🧳 Equipaje',
                    'flight_change' => '🔄 Cambio de Vuelo',
                    'complaint' => '⚠️ Reclamos',
                    'general' => '📋 General',
                ])
                ->default('general')
                ->required()
                ->helperText(fn ($record) =>
                    $record && $record->source === 'email'
                        ? 'Categoría detectada automáticamente desde el email'
                        : 'Selecciona la categoría más apropiada'
                ),

            // Selector de prioridad con indicador de detección automática
            \Filament\Forms\Components\Select::make('priority')
                ->label('Prioridad')
                ->options([
                    'low' => '🟢 Baja',
                    'normal' => '🔵 Normal',
                    'high' => '🟡 Alta',
                    'urgent' => '🔴 Urgente',
                ])
                ->default('normal')
                ->required()
                ->helperText(fn ($record) =>
                    $record && $record->source === 'email'
                        ? 'Prioridad detectada automáticamente desde el contenido del email'
                        : 'Selecciona la prioridad apropiada'
                ),

            \Filament\Forms\Components\Select::make('status')
                ->label('Estado')
                ->options([
                    'open' => '🔴 Abierto',
                    'in_progress' => '🟡 En Progreso',
                    'pending' => '🔵 Pendiente',
                    'resolved' => '🟢 Resuelto',
                    'closed' => '⚫ Cerrado',
                ])
                ->default('open')
                ->required()
                // Call center solo puede crear tickets abiertos
                ->disabled($user->role === 'call_center'),

            \Filament\Forms\Components\Select::make('assigned_to')
                ->label('Asignado a')
                ->relationship('assignedTo', 'name')
                ->options(
                    \App\Models\User::where('role', 'customer_service')
                        ->orWhere('role', 'manager')
                        ->where('is_active', true)
                        ->pluck('name', 'id')
                )
                ->searchable()
                ->placeholder('Seleccionar agente')
                ->helperText('Solo agentes de atención al cliente y gerentes pueden ser asignados')
                // Solo managers y atención al cliente pueden asignar
                ->visible(in_array($user->role, ['manager', 'customer_service']))
                ->suffixIcon('heroicon-m-user-group'),

            \Filament\Forms\Components\Select::make('source')
                ->label('Origen del Ticket')
                ->options([
                    'manual' => '✍️ Manual',
                    'email' => '📧 Email',
                    'phone' => '📞 Teléfono',
                ])
                ->default('manual')
                ->required()
                ->disabled(fn ($record) => $record !== null) // No cambiar origen una vez creado
                ->helperText(fn ($record) =>
                    $record ? 'El origen no se puede cambiar una vez creado el ticket' : 'Selecciona cómo se creó este ticket'
                ),

            \Filament\Forms\Components\DateTimePicker::make('resolved_at')
                ->label('Fecha de Resolución')
                ->native(false)
                // Solo managers y atención al cliente pueden marcar como resuelto
                ->visible(in_array($user->role, ['manager', 'customer_service']))
                ->suffixIcon('heroicon-m-check-circle'),

            \Filament\Forms\Components\Hidden::make('created_by')
                ->default(auth()->id()),
        ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();

        $query = null;

        // Filtrar registros según el rol
        if ($user->role === 'call_center') {
            // Call center solo ve tickets que creó
            $query = fn ($query) => $query->where('created_by', $user->id);
        } elseif ($user->role === 'customer_service') {
            // Atención al cliente ve todos los tickets o puede filtrar por "mis tickets"
            $query = null; // Ve todo
        }

        $tableBuilder = $table->columns([
            \Filament\Tables\Columns\TextColumn::make('ticket_number')
                ->label('N° Ticket')
                ->searchable()
                ->sortable()
                ->copyable()
                ->weight('bold'),

            // Indicador de origen del ticket (Email vs Manual)
            \Filament\Tables\Columns\IconColumn::make('source')
                ->label('')
                ->icon(fn (string $state): string => match ($state) {
                    'email' => 'heroicon-m-envelope',
                    'phone' => 'heroicon-m-phone',
                    default => 'heroicon-m-pencil-square',
                })
                ->color(fn (string $state): string => match ($state) {
                    'email' => 'success',
                    'phone' => 'warning',
                    default => 'gray',
                })
                ->tooltip(fn (string $state): string => match ($state) {
                    'email' => 'Creado desde Email',
                    'phone' => 'Creado desde Teléfono',
                    default => 'Creado Manualmente',
                }),

            \Filament\Tables\Columns\TextColumn::make('reservation_number')
                ->label('N° Reservación')
                ->searchable()
                ->copyable()
                ->placeholder('Sin reservación')
                ->icon('heroicon-m-paper-airplane'),

            \Filament\Tables\Columns\TextColumn::make('customer.full_name')
                ->label('Cliente')
                ->searchable(['customer.first_name', 'customer.last_name'])
                ->sortable()
                ->description(fn ($record): string => $record->customer->email),

            \Filament\Tables\Columns\TextColumn::make('subject')
                ->label('Asunto')
                ->searchable()
                ->limit(40)
                ->tooltip(function (\Filament\Tables\Columns\TextColumn $column): ?string {
                    $state = $column->getState();
                    return strlen($state) > 40 ? $state : null;
                })
                ->description(fn ($record): ?string =>
                    $record->email_thread_id ? "Thread: {$record->email_thread_id}" : null
                ),

            // Mostrar thread id (si existe) de forma clara en la tabla
            \Filament\Tables\Columns\TextColumn::make('email_thread_id')
                ->label('Thread ID')
                ->limit(25)
                ->copyable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->description(fn ($record): ?string => $record->email_thread_id ? 'Creado desde email' : null),

            // Indicador de respuestas/emails en el thread
            \Filament\Tables\Columns\TextColumn::make('replies_count')
                ->label('Respuestas')
                ->counts('replies')
                ->badge()
                ->color(fn ($state): string => $state > 0 ? 'primary' : 'gray')
                ->description(function ($record): ?string {
                    $emailReplies = $record->replies()->whereNotNull('email_message_id')->count();
                    return $emailReplies > 0 ? "{$emailReplies} por email" : null;
                }),


            \Filament\Tables\Columns\TextColumn::make('category')
                ->label('Categoría')
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'booking' => 'Reservas',
                    'cancellation' => 'Cancelaciones',
                    'refund' => 'Reembolsos',
                    'baggage' => 'Equipaje',
                    'flight_change' => 'Cambio de Vuelo',
                    'complaint' => 'Reclamos',
                    'general' => 'General',
                    default => $state,
                })
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'booking' => 'success',
                    'cancellation' => 'danger',
                    'refund' => 'warning',
                    'baggage' => 'info',
                    'flight_change' => 'primary',
                    'complaint' => 'danger',
                    'general' => 'gray',
                    default => 'gray',
                }),

            \Filament\Tables\Columns\TextColumn::make('priority')
                ->label('Prioridad')
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'low' => 'Baja',
                    'normal' => 'Normal',
                    'high' => 'Alta',
                    'urgent' => 'Urgente',
                    default => $state,
                })
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'urgent' => 'danger',
                    'high' => 'warning',
                    'normal' => 'primary',
                    'low' => 'gray',
                    default => 'gray',
                }),

            \Filament\Tables\Columns\TextColumn::make('status')
                ->label('Estado')
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'open' => 'Abierto',
                    'in_progress' => 'En Progreso',
                    'pending' => 'Pendiente',
                    'resolved' => 'Resuelto',
                    'closed' => 'Cerrado',
                    default => $state,
                })
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'open' => 'danger',
                    'in_progress' => 'warning',
                    'pending' => 'info',
                    'resolved' => 'success',
                    'closed' => 'gray',
                    default => 'gray',
                }),

            \Filament\Tables\Columns\TextColumn::make('assignedTo.name')
                ->label('Asignado a')
                ->placeholder('Sin asignar')
                ->badge()
                ->color('primary'),

            // Solo mostrar quién creó el ticket para managers
            \Filament\Tables\Columns\TextColumn::make('createdBy.name')
                ->label('Creado por')
                ->visible($user->role === 'manager')
                ->description(fn ($record): ?string =>
                    $record->source === 'email' ? 'Desde Email' : null
                ),

            \Filament\Tables\Columns\TextColumn::make('created_at')
                ->label('Fecha Creación')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
        ]);

        // Aplicar filtro de query si es necesario
        if ($query) {
            $tableBuilder = $tableBuilder->modifyQueryUsing($query);
        }

        // Filtros específicos según el rol
        $filters = [
            \Filament\Tables\Filters\SelectFilter::make('source')
                ->label('Origen del Ticket')
                ->options([
                    'email' => '📧 Email',
                    'phone' => '📞 Teléfono',
                    'manual' => '✍️ Manual',
                ]),

            \Filament\Tables\Filters\SelectFilter::make('status')
                ->label('Estado')
                ->options([
                    'open' => 'Abierto',
                    'in_progress' => 'En Progreso',
                    'pending' => 'Pendiente',
                    'resolved' => 'Resuelto',
                    'closed' => 'Cerrado',
                ]),

            \Filament\Tables\Filters\SelectFilter::make('priority')
                ->label('Prioridad')
                ->options([
                    'urgent' => 'Urgente',
                    'high' => 'Alta',
                    'normal' => 'Normal',
                    'low' => 'Baja',
                ]),

            \Filament\Tables\Filters\SelectFilter::make('category')
                ->label('Categoría')
                ->options([
                    'booking' => 'Reservas',
                    'cancellation' => 'Cancelaciones',
                    'refund' => 'Reembolsos',
                    'baggage' => 'Equipaje',
                    'flight_change' => 'Cambio de Vuelo',
                    'complaint' => 'Reclamos',
                    'general' => 'General',
                ]),

            // Filtro para tickets con threading de email
            \Filament\Tables\Filters\Filter::make('email_threads')
                ->label('Con Thread de Email')
                ->query(fn (\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder =>
                    $query->whereNotNull('email_thread_id')
                ),

            // Filtro para tickets con respuestas por email
            \Filament\Tables\Filters\Filter::make('email_replies')
                ->label('Con Respuestas por Email')
                ->query(fn (\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder =>
                    $query->whereHas('replies', function ($q) {
                        $q->whereNotNull('email_message_id');
                    })
                ),
        ];

        // Solo atención al cliente y managers ven filtros de asignación
        if (in_array($user->role, ['manager', 'customer_service'])) {
            $filters[] = \Filament\Tables\Filters\SelectFilter::make('assigned_to')
                ->label('Asignado a')
                ->relationship('assignedTo', 'name')
                ->options(
                    \App\Models\User::where('role', 'customer_service')
                        ->orWhere('role', 'manager')
                        ->where('is_active', true)
                        ->pluck('name', 'id')
                );
        }

        return $tableBuilder
            ->filters($filters)
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            RepliesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'open')->count();
    }
}
