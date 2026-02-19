<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditEventResource\Pages;
use App\Models\AuditEvent;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditEventResource extends Resource
{
    protected static ?string $model = AuditEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Audit Trail';

    protected static ?int $navigationSort = 11;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('actor')
            ->latest('id');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('entity_type')
                    ->label('Entity')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('entity_id')
                    ->label('Entity ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains(strtolower($state), 'deny') => 'danger',
                        str_contains(strtolower($state), 'approve') => 'success',
                        str_contains(strtolower($state), 'submit') => 'info',
                        str_contains(strtolower($state), 'close') => 'gray',
                        default => 'warning',
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('actor.name')
                    ->label('Actor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('metadata')
                    ->formatStateUsing(function ($state): string {
                        if (blank($state)) {
                            return '-';
                        }

                        $json = json_encode($state, JSON_UNESCAPED_SLASHES);

                        return is_string($json) ? $json : '-';
                    })
                    ->limit(70)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('entity_type')
                    ->label('Entity')
                    ->options(fn (): array => AuditEvent::query()
                        ->select('entity_type')
                        ->distinct()
                        ->orderBy('entity_type')
                        ->pluck('entity_type', 'entity_type')
                        ->all()),

                Tables\Filters\SelectFilter::make('action')
                    ->options(fn (): array => AuditEvent::query()
                        ->select('action')
                        ->distinct()
                        ->orderBy('action')
                        ->pluck('action', 'action')
                        ->all()),

                Tables\Filters\SelectFilter::make('actor_id')
                    ->label('Actor')
                    ->relationship('actor', 'name'),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditEvents::route('/'),
        ];
    }
}
