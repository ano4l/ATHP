<?php

namespace App\Filament\Widgets;

use App\Enums\LeaveReason;
use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestLeaves extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $query = LeaveRequest::query()->latest();
        if (!auth()->user()->isAdmin()) {
            $query->where('employee_id', auth()->id());
        }

        return $table
            ->query($query->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')->label('Employee'),
                Tables\Columns\TextColumn::make('reason')
                    ->formatStateUsing(fn (LeaveReason $state) => $state->label()),
                Tables\Columns\TextColumn::make('start_date')->date(),
                Tables\Columns\TextColumn::make('end_date')->date(),
                Tables\Columns\TextColumn::make('days')->suffix(' day(s)'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (LeaveStatus $state) => $state->label())
                    ->color(fn (LeaveStatus $state) => $state->color()),
            ])
            ->heading('Latest Leave Requests')
            ->paginated(false);
    }
}
