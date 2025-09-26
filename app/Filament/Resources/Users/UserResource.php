<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Agentes';

    protected static ?string $modelLabel = 'Agente';

    protected static ?string $pluralModelLabel = 'Agentes';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Nombre Completo')
                ->required()
                ->maxLength(255)
                ->placeholder('Ej: María González'),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->placeholder('Ej: maria.gonzalez@taraerolineas.app'),

            Select::make('role')
                ->label('Rol')
                ->options([
                    'manager' => 'Manager',
                    'customer_service' => 'Servicio al Cliente',
                    'call_center' => 'Call Center',
                ])
                ->required()
                ->default('call_center')
                ->helperText('Manager: Acceso completo | Customer Service: Gestiona tickets por email | Call Center: Registra tickets de llamadas'),

            TextInput::make('password')
                ->label('Contraseña')
                ->password()
                ->required(fn (string $context): bool => $context === 'create')
                ->minLength(8)
                ->dehydrated(fn ($state) => filled($state))
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->placeholder('Mínimo 8 caracteres'),

            Toggle::make('is_active')
                ->label('Usuario Activo')
                ->default(true)
                ->helperText('Solo usuarios activos pueden acceder a la plataforma'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copiado'),

                BadgeColumn::make('role')
                    ->label('Rol')
                    ->colors([
                        'danger' => 'manager',
                        'warning' => 'customer_service',
                        'success' => 'call_center',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'manager' => 'Manager',
                        'customer_service' => 'Customer Service',
                        'call_center' => 'Call Center',
                        default => $state,
                    }),

                BooleanColumn::make('is_active')
                    ->label('Activo')
                    ->sortable(),

                // TextColumn::make('assignedTickets')
                //     ->label('Tickets Asignados')
                //     ->counts('assignedTickets')
                //     ->badge()
                //     ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('email_verified_at')
                    ->label('Email Verificado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rol')
                    ->options([
                        'manager' => 'Manager',
                        'customer_service' => 'Customer Service',
                        'call_center' => 'Call Center',
                    ]),

                SelectFilter::make('is_active')
                    ->label('Estado')
                    ->options([
                        true => 'Activos',
                        false => 'Inactivos',
                    ])
                    ->default(true),
            ])
            ->actions([
                // Temporarily remove actions to avoid errors
            ])
            ->bulkActions([
                // Temporarily remove bulk actions to avoid errors
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
