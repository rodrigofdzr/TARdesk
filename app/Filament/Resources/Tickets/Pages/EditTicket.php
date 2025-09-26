<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;                 // ✅ v4
use Filament\Actions\DeleteAction;          // ✅ v4
use Filament\Forms;                         // para Forms\Components\*
use Filament\Notifications\Notification;    // ✅ v4 para notificaciones

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Responder')
                ->visible(fn () => in_array(auth()->user()->role, ['manager', 'customer_service', 'call_center']))
                ->form([
                    Forms\Components\MarkdownEditor::make('message')
                        ->label('Mensaje')
                        ->required()
                        ->rows(8)
                        ->helperText('Así verá el cliente el mensaje. Puedes editarlo antes de enviar.'),

                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->options([
                            'reply' => 'Respuesta',
                            'internal_note' => 'Nota Interna',
                            'system' => 'Sistema',
                        ])
                        ->default('reply')
                        ->required(),

                    Forms\Components\Toggle::make('is_customer_visible')
                        ->label('Visible al cliente')
                        ->default(true)
                        ->visible(fn (callable $get) => $get('type') !== 'system'),

                    // Adjuntos para la respuesta (usar mismo disco/directorio que el RelationManager)
                    Forms\Components\FileUpload::make('attachments')
                        ->label('Adjuntos')
                        ->multiple()
                        ->directory('ticket_replies')
                        ->disk('public')
                        ->visibility('public')
                        ->preserveFilenames(false)
                        ->visible(fn (callable $get) => $get('type') !== 'system'),

                    Forms\Components\Select::make('template_id')
                        ->label('Plantilla')
                        ->options(fn () => \App\Models\Template::query()->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $template = \App\Models\Template::find($state);
                                if ($template) {
                                    $set('message', $template->content);
                                }
                            }
                        })
                        ->helperText('Selecciona una plantilla para autocompletar el mensaje.'),
                ])
                ->modalWidth('lg') // ✅ en lugar de 'lg'
                ->action(function (array $data): void {
                    $ticket = $this->record;
                    $user = auth()->user();
                    $customer = $ticket->customer;
                    $variables = [
                        '{{customer_name}}' => $customer->name ?? '',
                        '{{customer_email}}' => $customer->email ?? '',
                        '{{ticket_number}}' => $ticket->id,
                        '{{ticket_subject}}' => $ticket->subject ?? '',
                        '{{agent_name}}' => $user->name ?? '',
                        '{{company_name}}' => config('app.name'),
                        '{{date}}' => now()->format('d/m/Y'),
                        '{{time}}' => now()->format('H:i'),
                    ];
                    $message = strtr($data['message'], $variables);
                    $this->record->replies()->create([
                        'user_id' => $user->id,
                        'message' => $message,
                        'type' => $data['type'] ?? 'reply',
                        'is_customer_visible' => $data['is_customer_visible'] ?? true,
                        'attachments' => $data['attachments'] ?? null,
                    ]);
                    Notification::make()
                        ->title('Respuesta creada y enviada si corresponde.')
                        ->success()
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }
}
