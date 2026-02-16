<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'Requests';
    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Notification::where('user_id', auth()->id())->where('read', false)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('read')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-bell-alert')
                    ->trueColor('gray')
                    ->falseColor('primary')
                    ->label(''),

                Tables\Columns\TextColumn::make('title')
                    ->weight(fn (Notification $record) => $record->read ? 'normal' : 'bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('message')
                    ->limit(60)
                    ->wrap(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => match (true) {
                        str_contains($state, 'approved') => 'success',
                        str_contains($state, 'denied') => 'danger',
                        str_contains($state, 'submitted') => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('read')
                    ->label('Read Status')
                    ->trueLabel('Read')
                    ->falseLabel('Unread'),
            ])
            ->actions([
                Tables\Actions\Action::make('markRead')
                    ->label('Mark Read')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Notification $record) => !$record->read)
                    ->action(fn (Notification $record) => $record->update(['read' => true])),

                Tables\Actions\Action::make('view_related')
                    ->label('View')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn (Notification $record) => $record->related_type && $record->related_id)
                    ->url(function (Notification $record) {
                        $record->update(['read' => true]);
                        if ($record->related_type === 'CashRequisition') {
                            return CashRequisitionResource::getUrl('view', ['record' => $record->related_id]);
                        } elseif ($record->related_type === 'LeaveRequest') {
                            return LeaveRequestResource::getUrl('view', ['record' => $record->related_id]);
                        }
                        return null;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('markAllRead')
                    ->label('Mark as Read')
                    ->icon('heroicon-o-check')
                    ->action(fn ($records) => $records->each(fn ($r) => $r->update(['read' => true])))
                    ->deselectRecordsAfterCompletion(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('markAllRead')
                    ->label('Mark All Read')
                    ->icon('heroicon-o-check-circle')
                    ->action(fn () => Notification::where('user_id', auth()->id())->where('read', false)->update(['read' => true]))
                    ->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
        ];
    }
}
