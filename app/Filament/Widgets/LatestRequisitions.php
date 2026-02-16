<?php

namespace App\Filament\Widgets;

use App\Enums\Branch;
use App\Enums\RequisitionStatus;
use App\Models\CashRequisition;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestRequisitions extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $query = CashRequisition::query()->latest();
        if (!auth()->user()->isAdmin()) {
            $query->where('requester_id', auth()->id());
        }

        return $table
            ->query($query->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('requester.name')->label('Requester'),
                Tables\Columns\TextColumn::make('branch')
                    ->formatStateUsing(fn (Branch $state) => $state->label()),
                Tables\Columns\TextColumn::make('amount')
                    ->formatStateUsing(fn ($record) => $record->formattedAmount()),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (RequisitionStatus $state) => $state->label())
                    ->color(fn (RequisitionStatus $state) => $state->color()),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y H:i'),
            ])
            ->heading('Latest Requisitions')
            ->paginated(false);
    }
}
