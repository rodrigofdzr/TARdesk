<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\Ticket;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class AssignedTicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignedTickets';
    protected static ?string $title = 'Tickets Asignados';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')->label('N° Ticket')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subject')->label('Asunto')->limit(40)->searchable(),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')->colors([
                    'primary' => 'open',
                    'warning' => 'pending',
                    'success' => 'resolved',
                    'danger' => 'closed',
                ]),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
