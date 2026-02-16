<?php

namespace App\Filament\Resources;

use App\Enums\Branch;
use App\Enums\RequisitionFor;
use App\Enums\RequisitionStatus;
use App\Enums\UserRole;
use App\Filament\Resources\CashRequisitionResource\Pages;
use App\Models\AuditEvent;
use App\Models\CashRequisition;
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

class CashRequisitionResource extends Resource
{
    protected static ?string $model = CashRequisition::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Requests';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()?->isAdmin()) {
            $count = CashRequisition::where('status', RequisitionStatus::SUBMITTED)->count();
            return $count > 0 ? (string) $count : null;
        }
        return null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (!auth()->user()?->isAdmin()) {
            $query->where('requester_id', auth()->id());
        }
        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Requisition Details')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('branch')
                                ->options(collect(Branch::cases())->mapWithKeys(fn ($b) => [$b->value => $b->label()]))
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('currency', $state ? Branch::from($state)->currency() : '')),

                            Forms\Components\Select::make('requisition_for')
                                ->options(collect(RequisitionFor::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()]))
                                ->required()
                                ->reactive(),
                        ]),

                        Forms\Components\TextInput::make('client_ref')
                            ->label('Client Reference')
                            ->visible(fn (Forms\Get $get) => $get('requisition_for') === 'client')
                            ->requiredIf('requisition_for', 'client'),

                        Forms\Components\TextInput::make('order_ref')
                            ->label('Order Reference')
                            ->visible(fn (Forms\Get $get) => $get('requisition_for') === 'order')
                            ->requiredIf('requisition_for', 'order'),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('amount')
                                ->numeric()
                                ->required()
                                ->prefix(fn (Forms\Get $get) => $get('currency') ?: '$')
                                ->minValue(0.01),

                            Forms\Components\TextInput::make('currency')
                                ->required()
                                ->maxLength(10)
                                ->default('USD'),

                            Forms\Components\DatePicker::make('needed_by')
                                ->label('Needed By')
                                ->required()
                                ->minDate(now()),
                        ]),

                        Forms\Components\Textarea::make('purpose')
                            ->required()
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

                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Requester')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('branch')
                    ->badge()
                    ->formatStateUsing(fn (Branch $state) => $state->label()),

                Tables\Columns\TextColumn::make('requisition_for')
                    ->label('For')
                    ->formatStateUsing(fn (RequisitionFor $state) => $state->label()),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($record) => $record->formattedAmount())
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (RequisitionStatus $state) => $state->label())
                    ->color(fn (RequisitionStatus $state) => $state->color()),

                Tables\Columns\TextColumn::make('needed_by')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(RequisitionStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('branch')
                    ->options(collect(Branch::cases())->mapWithKeys(fn ($b) => [$b->value => $b->label()])),
                Tables\Filters\SelectFilter::make('requisition_for')
                    ->label('Type')
                    ->options(collect(RequisitionFor::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (CashRequisition $record) => $record->status === RequisitionStatus::DRAFT && $record->requester_id === auth()->id()),

                Tables\Actions\Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Submit for Approval')
                    ->modalDescription('Are you sure you want to submit this requisition for admin approval?')
                    ->visible(fn (CashRequisition $record) => $record->status === RequisitionStatus::DRAFT && $record->requester_id === auth()->id())
                    ->action(function (CashRequisition $record) {
                        $record->update([
                            'status' => RequisitionStatus::SUBMITTED,
                            'submitted_at' => now(),
                        ]);
                        AuditEvent::log('CashRequisition', $record->id, 'SUBMITTED', auth()->id());
                        $admins = User::where('role', UserRole::ADMIN)->get();
                        foreach ($admins as $admin) {
                            AppNotification::notify($admin, 'requisition_submitted', 'New Cash Requisition',
                                $record->requester->name . ' submitted a requisition for ' . $record->formattedAmount(),
                                'CashRequisition', $record->id);
                        }
                        Notification::make()->title('Requisition submitted for approval')->success()->send();
                    }),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('comment')->label('Comment (optional)')->rows(2),
                    ])
                    ->visible(fn (CashRequisition $record) => auth()->user()?->isAdmin() && $record->status === RequisitionStatus::SUBMITTED)
                    ->action(function (CashRequisition $record, array $data) {
                        $record->update([
                            'status' => RequisitionStatus::APPROVED,
                            'decided_by_id' => auth()->id(),
                            'decided_at' => now(),
                            'decision_comment' => $data['comment'] ?? null,
                        ]);
                        AuditEvent::log('CashRequisition', $record->id, 'APPROVED', auth()->id());
                        AppNotification::notify($record->requester, 'requisition_approved', 'Requisition Approved',
                            'Your requisition for ' . $record->formattedAmount() . ' has been approved.',
                            'CashRequisition', $record->id);
                        Notification::make()->title('Requisition approved')->success()->send();
                    }),

                Tables\Actions\Action::make('deny')
                    ->label('Deny')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('comment')->label('Reason for denial')->required()->rows(2),
                    ])
                    ->visible(fn (CashRequisition $record) => auth()->user()?->isAdmin() && $record->status === RequisitionStatus::SUBMITTED)
                    ->action(function (CashRequisition $record, array $data) {
                        $record->update([
                            'status' => RequisitionStatus::DENIED,
                            'decided_by_id' => auth()->id(),
                            'decided_at' => now(),
                            'decision_comment' => $data['comment'],
                        ]);
                        AuditEvent::log('CashRequisition', $record->id, 'DENIED', auth()->id());
                        AppNotification::notify($record->requester, 'requisition_denied', 'Requisition Denied',
                            'Your requisition for ' . $record->formattedAmount() . ' has been denied. Reason: ' . $data['comment'],
                            'CashRequisition', $record->id);
                        Notification::make()->title('Requisition denied')->danger()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Requisition Details')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('branch')
                                ->formatStateUsing(fn (Branch $state) => $state->label()),
                            Infolists\Components\TextEntry::make('requisition_for')
                                ->label('For')
                                ->formatStateUsing(fn (RequisitionFor $state) => $state->label()),
                            Infolists\Components\TextEntry::make('status')
                                ->badge()
                                ->formatStateUsing(fn (RequisitionStatus $state) => $state->label())
                                ->color(fn (RequisitionStatus $state) => $state->color()),
                        ]),
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('amount')
                                ->formatStateUsing(fn ($record) => $record->formattedAmount()),
                            Infolists\Components\TextEntry::make('needed_by')->date(),
                            Infolists\Components\TextEntry::make('requester.name')->label('Requested By'),
                        ]),
                        Infolists\Components\TextEntry::make('client_ref')
                            ->label('Client Reference')
                            ->visible(fn ($record) => $record->client_ref !== null),
                        Infolists\Components\TextEntry::make('order_ref')
                            ->label('Order Reference')
                            ->visible(fn ($record) => $record->order_ref !== null),
                        Infolists\Components\TextEntry::make('purpose')
                            ->columnSpanFull(),
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

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('created_at')->dateTime('d M Y H:i'),
                            Infolists\Components\TextEntry::make('submitted_at')->dateTime('d M Y H:i'),
                            Infolists\Components\TextEntry::make('updated_at')->dateTime('d M Y H:i'),
                        ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashRequisitions::route('/'),
            'create' => Pages\CreateCashRequisition::route('/create'),
            'view' => Pages\ViewCashRequisition::route('/{record}'),
            'edit' => Pages\EditCashRequisition::route('/{record}/edit'),
        ];
    }
}
