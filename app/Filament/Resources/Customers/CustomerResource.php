<?php
namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use BackedEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Clientes';

    // Solo mostrar en navegación para managers y atención al cliente
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['manager', 'customer_service']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Forms\Components\TextInput::make('customer_number')
                ->label('Número de Cliente')
                ->disabled()
                ->dehydrated(false)
                ->placeholder('Se generará automáticamente'),

            \Filament\Forms\Components\TextInput::make('first_name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),

            \Filament\Forms\Components\TextInput::make('last_name')
                ->label('Apellido')
                ->required()
                ->maxLength(255),

            \Filament\Forms\Components\TextInput::make('email')
                ->label('Correo Electrónico')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            \Filament\Forms\Components\TextInput::make('phone')
                ->label('Teléfono')
                ->tel()
                ->maxLength(255),

            \Filament\Forms\Components\Select::make('document_type')
                ->label('Tipo de Documento')
                ->options([
                    'passport' => 'Pasaporte',
                    'dni' => 'DNI/Cédula',
                    'driver_license' => 'Licencia de Conducir',
                    'other' => 'Otro',
                ])
                ->placeholder('Seleccione el tipo de documento'),

            \Filament\Forms\Components\TextInput::make('document_number')
                ->label('Número de Documento')
                ->maxLength(255),

            \Filament\Forms\Components\Select::make('status')
                ->label('Estado')
                ->options([
                    'active' => 'Activo',
                    'inactive' => 'Inactivo',
                ])
                ->default('active')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('customer_number')
                    ->label('N° Cliente')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                \Filament\Tables\Columns\TextColumn::make('full_name')
                    ->label('Nombre Completo')
                    ->getStateUsing(fn ($record) => $record->full_name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                \Filament\Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone'),

                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),

                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                    ]),
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
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
