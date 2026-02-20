<?php

namespace App\Filament\Resources;

use App\Enums\LeaveReason;
use App\Enums\LeaveStatus;
use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\AuditEvent;
use App\Models\LeaveRequest;
use App\Models\Notification as AppNotification;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Requests';
    protected static ?int $navigationSort = 2;

    public static function canEdit(Model $record): bool
    {
        return $record instanceof LeaveRequest
            && $record->employee_id === auth()->id()
            && $record->status === LeaveStatus::SUBMITTED;
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof LeaveRequest
            && $record->employee_id === auth()->id()
            && $record->status === LeaveStatus::SUBMITTED;
    }

    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()?->isAdmin()) {
            $count = LeaveRequest::where('status', LeaveStatus::SUBMITTED->value)->count();
            return $count > 0 ? (string) $count : null;
        }
        return null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (!auth()->user()?->isAdmin()) {
            $query->where('employee_id', auth()->id());
        }
        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Leave Details')
                    ->schema([
                        Forms\Components\Select::make('reason')
                            ->options(collect(LeaveReason::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()]))
                            ->required(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('start_date')
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                    $end = $get('end_date');
                                    if ($state && $end) {
                                        $days = LeaveRequest::countBusinessDays(
                                            \Carbon\Carbon::parse($state),
                                            \Carbon\Carbon::parse($end)
                                        );
                                        $set('days', $days);
                                    }
                                }),

                            Forms\Components\DatePicker::make('end_date')
                                ->required()
                                ->afterOrEqual('start_date')
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                    $start = $get('start_date');
                                    if ($start && $state) {
                                        $days = LeaveRequest::countBusinessDays(
                                            \Carbon\Carbon::parse($start),
                                            \Carbon\Carbon::parse($state)
                                        );
                                        $set('days', $days);
                                    }
                                }),
                        ]),

                        Forms\Components\TextInput::make('days')
                            ->label('Business Days')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason')
                    ->formatStateUsing(fn (LeaveReason $state) => $state->label())
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('days')
                    ->label('Days')
                    ->suffix(' day(s)')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (LeaveStatus $state) => $state->label())
                    ->color(fn (LeaveStatus $state) => $state->color()),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(LeaveStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('reason')
                    ->options(collect(LeaveReason::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('comment')->label('Comment (optional)')->rows(2),
                    ])
                    ->visible(fn (LeaveRequest $record) => auth()->user()?->isAdmin() && $record->status === LeaveStatus::SUBMITTED)
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status' => LeaveStatus::APPROVED,
                            'decided_by_id' => auth()->id(),
                            'decided_at' => now(),
                            'decision_comment' => $data['comment'] ?? null,
                        ]);
                        AuditEvent::log('LeaveRequest', $record->id, 'APPROVED', auth()->id());
                        AppNotification::notify($record->employee, 'leave_approved', 'Leave Approved',
                            'Your ' . $record->reason->label() . ' for ' . $record->days . ' day(s) has been approved.',
                            'LeaveRequest', $record->id);
                        Notification::make()->title('Leave request approved')->success()->send();
                    }),

                Tables\Actions\Action::make('deny')
                    ->label('Deny')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('comment')->label('Reason for denial')->required()->rows(2),
                    ])
                    ->visible(fn (LeaveRequest $record) => auth()->user()?->isAdmin() && $record->status === LeaveStatus::SUBMITTED)
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status' => LeaveStatus::DENIED,
                            'decided_by_id' => auth()->id(),
                            'decided_at' => now(),
                            'decision_comment' => $data['comment'],
                        ]);
                        AuditEvent::log('LeaveRequest', $record->id, 'DENIED', auth()->id());
                        AppNotification::notify($record->employee, 'leave_denied', 'Leave Denied',
                            'Your ' . $record->reason->label() . ' for ' . $record->days . ' day(s) has been denied. Reason: ' . $data['comment'],
                            'LeaveRequest', $record->id);
                        Notification::make()->title('Leave request denied')->danger()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Leave Details')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('reason')
                                ->formatStateUsing(fn (LeaveReason $state) => $state->label()),
                            Infolists\Components\TextEntry::make('days')->suffix(' business day(s)'),
                            Infolists\Components\TextEntry::make('status')
                                ->badge()
                                ->formatStateUsing(fn (LeaveStatus $state) => $state->label())
                                ->color(fn (LeaveStatus $state) => $state->color()),
                        ]),
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('start_date')->date(),
                            Infolists\Components\TextEntry::make('end_date')->date(),
                            Infolists\Components\TextEntry::make('employee.name')->label('Employee'),
                        ]),
                        Infolists\Components\TextEntry::make('notes')
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->notes)),
                    ]),

                Infolists\Components\Section::make('Decision')
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('decidedBy.name')->label('Decided By'),
                            Infolists\Components\TextEntry::make('decided_at')->dateTime('d M Y H:i'),
                        ]),
                        Infolists\Components\TextEntry::make('decision_comment')->label('Comment'),
                    ])
                    ->visible(fn ($record) => $record->decided_by_id !== null),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'view' => Pages\ViewLeaveRequest::route('/{record}'),
        ];
    }
}
