<?php

namespace App\Filament\Resources\Templates;

use App\Filament\Resources\Templates\Pages\CreateTemplate;
use App\Filament\Resources\Templates\Pages\EditTemplate;
use App\Filament\Resources\Templates\Pages\ListTemplates;
use App\Models\Template;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Plantillas';

    protected static ?string $modelLabel = 'Plantilla';

    protected static ?string $pluralModelLabel = 'Plantillas';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Group::make()
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Ej: Respuesta de Bienvenida'),

                    Select::make('type')
                        ->label('Tipo')
                        ->options([
                            'response' => 'Respuesta',
                            'ticket_creation' => 'Creación de Ticket',
                            'closing' => 'Cierre',
                            'escalation' => 'Escalación',
                            'custom' => 'Personalizada',
                        ])
                        ->default('custom')
                        ->required(),

                    TextInput::make('category')
                        ->label('Categoría')
                        ->maxLength(255)
                        ->placeholder('Ej: Soporte Técnico, Ventas, etc.'),

                    Textarea::make('description')
                        ->label('Descripción')
                        ->rows(2)
                        ->placeholder('Breve descripción de cuándo usar esta plantilla'),
                ]),

            Group::make()
                ->schema([
                    TextInput::make('subject')
                        ->label('Asunto')
                        ->maxLength(255)
                        ->placeholder('Asunto del email (opcional)')
                        ->helperText('Puedes usar variables como {{ticket_number}}, {{customer_name}}, etc.'),

                    RichEditor::make('content')
                        ->label('Contenido')
                        ->required()
                        ->placeholder('Contenido de la plantilla...')
                        ->helperText('Puedes usar variables como {{customer_name}}, {{ticket_number}}, {{agent_name}}, etc.')
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'bulletList',
                            'orderedList',
                            'link',
                            'undo',
                            'redo',
                        ]),
                ]),

            Group::make()
                ->schema([
                    TagsInput::make('variables')
                        ->label('Variables Disponibles')
                        ->placeholder('Agrega variables como: customer_name, ticket_number, etc.')
                        ->helperText('Variables que se pueden usar en el contenido. Se reemplazarán automáticamente.')
                        ->suggestions([
                            'customer_name',
                            'customer_email',
                            'ticket_number',
                            'ticket_subject',
                            'agent_name',
                            'company_name',
                            'date',
                            'time',
                        ]),

                    Placeholder::make('variables_help')
                        ->label('Ayuda sobre Variables')
                        ->content('
                            <div class="text-sm text-gray-600">
                                <p><strong>Variables comunes:</strong></p>
                                <ul class="list-disc list-inside">
                                    <li>{{customer_name}} - Nombre del cliente</li>
                                    <li>{{customer_email}} - Email del cliente</li>
                                    <li>{{ticket_number}} - Número del ticket</li>
                                    <li>{{ticket_subject}} - Asunto del ticket</li>
                                    <li>{{agent_name}} - Nombre del agente</li>
                                    <li>{{company_name}} - Nombre de la empresa</li>
                                    <li>{{date}} - Fecha actual</li>
                                    <li>{{time}} - Hora actual</li>
                                </ul>
                            </div>
                        '),
                ]),

            Group::make()
                ->schema([
                    Toggle::make('is_active')
                        ->label('Plantilla Activa')
                        ->default(true)
                        ->helperText('Solo las plantillas activas aparecerán en los selectores'),

                    Toggle::make('is_default')
                        ->label('Plantilla por Defecto')
                        ->default(false)
                        ->helperText('Se seleccionará automáticamente para este tipo'),

                    Select::make('created_by')
                        ->label('Creado por')
                        ->relationship('creator', 'name')
                        ->default(auth()->id())
                        ->disabled()
                        ->dehydrated(true),
                ]),
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

                BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'primary' => 'response',
                        'success' => 'ticket_creation',
                        'warning' => 'closing',
                        'danger' => 'escalation',
                        'secondary' => 'custom',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'response' => 'Respuesta',
                        'ticket_creation' => 'Creación',
                        'closing' => 'Cierre',
                        'escalation' => 'Escalación',
                        'custom' => 'Personalizada',
                    }),

                TextColumn::make('category')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                BooleanColumn::make('is_active')
                    ->label('Activa')
                    ->sortable(),

                BooleanColumn::make('is_default')
                    ->label('Por Defecto')
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('Creado por')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('subject')
                    ->label('Asunto')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'response' => 'Respuesta',
                        'ticket_creation' => 'Creación de Ticket',
                        'closing' => 'Cierre',
                        'escalation' => 'Escalación',
                        'custom' => 'Personalizada',
                    ]),

                SelectFilter::make('category')
                    ->label('Categoría')
                    ->options(function () {
                        return Template::query()
                            ->whereNotNull('category')
                            ->distinct()
                            ->pluck('category', 'category')
                            ->toArray();
                    }),

                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todas')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas'),

                TernaryFilter::make('is_default')
                    ->label('Por defecto')
                    ->placeholder('Todas')
                    ->trueLabel('Solo por defecto')
                    ->falseLabel('Solo no por defecto'),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Template $record): string => $record->name)
                    ->modalContent(fn (Template $record): \Illuminate\Contracts\View\View => view(
                        'filament.resources.templates.view',
                        ['record' => $record]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (Template $record): string => static::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
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
            'index' => ListTemplates::route('/'),
            'create' => CreateTemplate::route('/create'),
            'edit' => EditTemplate::route('/{record}/edit'),
        ];
    }
}
