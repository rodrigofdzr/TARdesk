<?php

namespace App\Filament\Resources\Agents;

use App\Filament\Resources\Agents\Pages\CreateAgent;
use App\Filament\Resources\Agents\Pages\EditAgent;
use App\Filament\Resources\Agents\Pages\ListAgents;
use App\Models\Agent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Agentes';

    protected static ?string $modelLabel = 'Agente';

    protected static ?string $pluralModelLabel = 'Agentes';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('phone')
                ->label('Teléfono')
                ->tel()
                ->maxLength(255),

            FileUpload::make('avatar')
                ->label('Avatar')
                ->image()
                ->directory('agents/avatars')
                ->visibility('public'),

            Textarea::make('bio')
                ->label('Biografía')
                ->rows(3),

            TextInput::make('department')
                ->label('Departamento')
                ->maxLength(255),

            Select::make('status')
                ->label('Estado')
                ->options([
                    'active' => 'Activo',
                    'inactive' => 'Inactivo',
                    'on_break' => 'En Descanso',
                ])
                ->default('active')
                ->required(),

            TextInput::make('max_concurrent_tickets')
                ->label('Máximo de Tickets Simultáneos')
                ->numeric()
                ->default(10)
                ->minValue(1)
                ->maxValue(50),

            Toggle::make('can_reassign_tickets')
                ->label('Puede Reasignar Tickets')
                ->default(false),

            Toggle::make('can_close_tickets')
                ->label('Puede Cerrar Tickets')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn (): string => 'https://ui-avatars.com/api/?name=' . urlencode('Agent') . '&color=7F9CF5&background=EBF4FF'),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('department')
                    ->label('Departamento')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'on_break',
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'on_break' => 'En Descanso',
                    }),

                TextColumn::make('active_tickets_count')
                    ->label('Tickets Activos')
                    ->counts('tickets')
                    ->sortable(),

                TextColumn::make('max_concurrent_tickets')
                    ->label('Máx. Tickets')
                    ->sortable(),

                BooleanColumn::make('can_reassign_tickets')
                    ->label('Puede Reasignar'),

                BooleanColumn::make('can_close_tickets')
                    ->label('Puede Cerrar'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'on_break' => 'En Descanso',
                    ]),

                SelectFilter::make('department')
                    ->label('Departamento')
                    ->options(function () {
                        return Agent::query()
                            ->whereNotNull('department')
                            ->distinct()
                            ->pluck('department', 'department')
                            ->toArray();
                    }),
            ])
            ->actions([
                // Temporarily remove actions to fix the error
            ])
            ->bulkActions([
                // Temporarily remove bulk actions to fix the error
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgents::route('/'),
            'create' => CreateAgent::route('/create'),
            'edit' => EditAgent::route('/{record}/edit'),
        ];
    }
}
