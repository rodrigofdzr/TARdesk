<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema; // ✅ v4
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\TicketReply;
use Illuminate\Support\Str;

// Components de Forms en v4:
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\FileUpload;

class RepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'replies';
    protected static ?string $recordTitleAttribute = 'message';

    // ✅ Firma correcta en v4
    public function form(Schema $schema): Schema
    {
        // Retornamos los componentes directamente (evitamos contenedores que
        // el analizador estático pueda marcar como desconocidos en esta
        // configuración del proyecto). Filament acepta un arreglo plano de
        // componentes dentro de `schema()`.
        return $schema->schema([
            Textarea::make('message')
                ->label('Mensaje')
                ->required()
                ->rows(6)
                ->reactive(),

            FormSelect::make('type')
                ->label('Tipo')
                ->options([
                    'reply' => 'Respuesta',
                    'internal_note' => 'Nota Interna',
                    'system' => 'Sistema',
                ])
                ->default('reply')
                ->required(),

            Toggle::make('is_customer_visible')
                ->label('Visible al cliente')
                ->default(true)
                ->visible(fn (callable $get) => $get('type') !== 'system'),

            Hidden::make('user_id')
                ->default(fn () => auth()->id()),

            FileUpload::make('attachments')
                ->label('Adjuntos')
                ->multiple()
                ->enableReordering()
                ->directory('ticket_replies')
                ->disk('public')
                ->visibility('public')
                ->preserveFilenames(false)
                ->acceptedFileTypes([
                    'image/*',
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])
                ->maxSize(10240) // 10 MB
                ->visible(fn (callable $get) => $get('type') !== 'system'),
        ]);
    }

    /**
     * Asegurar explícitamente la query que se usa para cargar las replies en la relación.
     * Esto evita que Filament aplique filtros inesperados o scopes que oculten registros.
     */
    protected function getTableQuery(): Builder
    {
        $owner = $this->getOwnerRecord();

        if (! $owner) {
            // fallback a la relación por si no hay owner (evitar crash)
            Log::warning('RepliesRelationManager: no owner record found when building table query.');
            return TicketReply::query()->withoutGlobalScopes()->orderBy('created_at', 'desc');
        }

        Log::info('RepliesRelationManager:getTableQuery', ['owner_id' => $owner->getKey()]);

        $query = TicketReply::query()
            ->where('ticket_id', $owner->getKey())
            ->withoutGlobalScopes()
            ->orderBy('created_at', 'desc');

        try {
            $count = $query->count();
            Log::info('RepliesRelationManager: query count', ['owner_id' => $owner->getKey(), 'count' => $count]);
        } catch (\Throwable $e) {
            Log::error('RepliesRelationManager: error counting replies', ['owner_id' => $owner->getKey(), 'error' => $e->getMessage()]);
        }

        return $query;
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable()
                    ->visible(true),

                IconColumn::make('email_message_id')
                    ->label('Origen')
                    ->icon(fn ($state) => $state ? 'heroicon-m-envelope' : 'heroicon-m-annotation')
                    ->color(fn ($state) => $state ? 'success' : 'secondary')
                    ->tooltip(fn ($record) => $record->email_message_id ? 'Respuesta por Email' : 'Interna / Manual'),

                TextColumn::make('user.name')
                    ->label('Autor')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        try {
                            $s = is_null($state) ? '' : (string) $state;

                            return match ($s) {
                                'reply' => 'Respuesta',
                                'internal_note' => 'Nota Interna',
                                'system' => 'Sistema',
                                default => $s,
                            };
                        } catch (\Throwable $e) {
                            return (string) ($state ?? '');
                        }
                    })
                    ->color(function ($state): string {
                        try {
                            $s = is_null($state) ? '' : (string) $state;
                            return match ($s) {
                                'reply' => 'primary',
                                'internal_note' => 'warning',
                                'system' => 'gray',
                                default => 'gray',
                            };
                        } catch (\Throwable $e) {
                            return 'gray';
                        }
                    }),

                TextColumn::make('message')
                    ->label('Mensaje')
                    ->wrap()
                    ->limit(200),

                TextColumn::make('attachments')
                    ->label('Adjuntos')
                    ->formatStateUsing(function ($state) {
                        try {
                            // Manejar casos donde attachments está guardado como JSON string o como array
                            if (is_string($state) && trim($state) !== '') {
                                $decoded = json_decode($state, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    $state = $decoded;
                                } else {
                                    // Si es una cadena simple, tratarla como un único archivo
                                    $state = [$state];
                                }
                            }

                            if (!is_array($state) || count($state) === 0) {
                                return '';
                            }

                            return collect($state)
                                ->map(function ($path) {
                                    try {
                                        if (empty($path)) return '';

                                        // Normalizar paths: muchos seeders/usuarios pueden guardar solo el nombre
                                        // del archivo (ej: itinerary.pdf). Intentamos resolverlo contra el disco
                                        // público y, como fallback, anteponer el directorio 'ticket_replies/'.
                                        $candidate = ltrim((string) $path, '/');

                                        if (!Storage::disk('public')->exists($candidate)) {
                                            $prefixed = 'ticket_replies/' . $candidate;
                                            if (Storage::disk('public')->exists($prefixed)) {
                                                $candidate = $prefixed;
                                            }
                                        }

                                        $name = e(basename($candidate));

                                        if (Storage::disk('public')->exists($candidate)) {
                                            $url = e(Storage::disk('public')->url($candidate));
                                            return "<a href=\"{$url}\" target=\"_blank\" rel=\"noopener\">{$name}</a>";
                                        }

                                        // Si el archivo no existe en disco, mostrar el nombre para informarlo
                                        return $name;
                                    } catch (\Throwable $e) {
                                        return '';
                                    }
                                })
                                ->filter()
                                ->implode(' | ');
                        } catch (\Throwable $e) {
                            return '';
                        }
                    })
                     ->html()
                     ->wrap(),

                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'reply' => 'Respuesta',
                        'internal_note' => 'Nota Interna',
                        'system' => 'Sistema',
                    ]),

                // Hacer explícito el filtro "Por Email" y evitar que un filtro
                // con key genérica ('email') quede persistido o ambigüo en la URL.
                // Ofrecemos opciones claras: Todos / Con Email / Sin Email.
                SelectFilter::make('via_email')
                    ->label('Por Email')
                    ->options([
                        'all' => 'Todos',
                        'email' => 'Con Email',
                        'no_email' => 'Sin Email',
                    ])
                    ->default('all')
                    ->query(function (Builder $query, $value) {
                        return match ($value) {
                            'email' => $query->whereNotNull('email_message_id'),
                            'no_email' => $query->whereNull('email_message_id'),
                            default => $query,
                        };
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
