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
                ->form(function () {
                    $ticket = $this->record;
                    $user = auth()->user();
                    $customer = $ticket->customer;
                    $customerName = ($customer && isset($customer->full_name) && trim($customer->full_name) !== '') ? $customer->full_name : 'Cliente';
                    return [
                        Forms\Components\RichEditor::make('message')
                            ->label('Mensaje')
                            ->required()
                            ->helperText('Así verá el cliente el mensaje. Puedes editarlo antes de enviar.'),

                        Forms\Components\Select::make('template_id')
                            ->label('Plantilla')
                            ->options(fn () => \App\Models\Template::query()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) use ($ticket, $user, $customer, $customerName) {
                                if ($state) {
                                    $template = \App\Models\Template::find($state);
                                    if ($template) {
                                        $variables = [
                                            '/{{\\s*customer_name\\s*}}/i' => $customerName,
                                            '/{{\\s*customer_email\\s*}}/i' => $customer?->email ?? '',
                                            '/{{\\s*ticket_number\\s*}}/i' => $ticket?->id ?? '',
                                            '/{{\\s*ticket_subject\\s*}}/i' => $ticket?->subject ?? '',
                                            '/{{\\s*agent_name\\s*}}/i' => $user?->name ?? '',
                                            '/{{\\s*company_name\\s*}}/i' => config('app.name'),
                                            '/{{\\s*date\\s*}}/i' => now()->format('d/m/Y'),
                                            '/{{\\s*time\\s*}}/i' => now()->format('H:i'),
                                        ];
                                        $content = $template->content;
                                        $subject = $template->subject ?? '';
                                        foreach ($variables as $pattern => $replacement) {
                                            $content = preg_replace($pattern, $replacement, $content);
                                            $subject = preg_replace($pattern, $replacement, $subject);
                                        }
                                        $set('message', $content);
                                        $set('subject', $subject);
                                    }
                                }
                            })
                            ->helperText('Selecciona una plantilla para autocompletar el mensaje.'),

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
                    ];
                })
                ->modalWidth('lg') // ✅ en lugar de 'lg'
                ->action(function (array $data): void {
                    $ticket = $this->record;
                    $user = auth()->user();
                    $customer = $ticket->customer;
                    $customerName = ($customer && isset($customer->full_name) && trim($customer->full_name) !== '') ? $customer->full_name : 'Cliente';
                    $variables = [
                        '/{{\s*customer_name\s*}}/i' => $customerName,
                        '/{{\s*customer_email\s*}}/i' => $customer->email ?? '',
                        '/{{\s*ticket_number\s*}}/i' => $ticket->id,
                        '/{{\s*ticket_subject\s*}}/i' => $ticket->subject ?? '',
                        '/{{\s*agent_name\s*}}/i' => $user->name ?? '',
                        '/{{\s*company_name\s*}}/i' => config('app.name'),
                        '/{{\s*date\s*}}/i' => now()->format('d/m/Y'),
                        '/{{\s*time\s*}}/i' => now()->format('H:i'),
                    ];
                    $message = $data['message'];
                    foreach ($variables as $pattern => $replacement) {
                        $message = preg_replace($pattern, $replacement, $message);
                    }
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
