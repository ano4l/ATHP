<?php

namespace App\Filament\Resources;

use App\Enums\Branch;
use App\Enums\DeliveryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseStatus;
use App\Enums\RequisitionCategory;
use App\Enums\RequisitionFor;
use App\Enums\RequisitionStatus;
use App\Enums\RequisitionType;
use App\Enums\UserRole;
use App\Filament\Resources\CashRequisitionResource\Pages;
use App\Models\AuditEvent;
use App\Models\CashRequisition;
use App\Models\CashRequisitionAttachment;
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
use Illuminate\Support\Carbon;

class CashRequisitionResource extends Resource
{
    protected static ?string $model = CashRequisition::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Requests';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()?->isAdmin()) {
            $count = CashRequisition::query()
                ->whereIn('status', [
                    RequisitionStatus::SUBMITTED->value,
                    RequisitionStatus::STAGE1_APPROVED->value,
                ])
                ->count();
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

    public static function canEdit(Model $record): bool
    {
        if (!$record instanceof CashRequisition) {
            return false;
        }

        $user = auth()->user();

        if (!$user || $record->requester_id !== $user->id) {
            return false;
        }

        return in_array($record->status, [RequisitionStatus::DRAFT, RequisitionStatus::MODIFICATION_REQUESTED], true);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof CashRequisition
            && $record->requester_id === auth()->id()
            && $record->status === RequisitionStatus::DRAFT;
    }

    public static function form(Form $form): Form
    {
        $threshold = (float) config('requisition.stage2_threshold', 10000);
        $requiredCategories = config('requisition.attachment_required_categories', []);

        return $form
            ->schema([
                Forms\Components\Section::make('Requisition Details')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('reference_no')
                                ->label('Reference No.')
                                ->dehydrated(false)
                                ->disabled()
                                ->placeholder('Auto-generated on create'),

                            Forms\Components\Select::make('requisition_type')
                                ->options(collect(RequisitionType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                                ->required()
                                ->default(RequisitionType::CASH->value),

                            Forms\Components\Select::make('branch')
                                ->options(collect(Branch::cases())->mapWithKeys(fn ($b) => [$b->value => $b->label()]))
                                ->required()
                                ->default(fn () => auth()->user()?->branch?->value)
                                ->reactive()
                                ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('currency', $state ? Branch::from($state)->currency() : '')),

                            Forms\Components\TextInput::make('project_name')
                                ->required()
                                ->maxLength(120),

                            Forms\Components\TextInput::make('project_code')
                                ->maxLength(100),

                            Forms\Components\Select::make('category')
                                ->options(collect(RequisitionCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                                ->required(),

                            Forms\Components\TextInput::make('cost_center')
                                ->required()
                                ->maxLength(100),

                            Forms\Components\TextInput::make('budget_code')
                                ->required()
                                ->maxLength(100),

                            Forms\Components\Select::make('requisition_for')
                                ->options(collect(RequisitionFor::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()]))
                                ->required()
                                ->reactive(),

                            Forms\Components\DatePicker::make('needed_by')
                                ->label('Required Date')
                                ->required()
                                ->minDate(now()),
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
                                ->default(fn () => auth()->user()?->branch?->currency() ?? 'USD'),

                            Forms\Components\Placeholder::make('approval_route')
                                ->label('Approval Route')
                                ->content(function (Forms\Get $get) use ($threshold): string {
                                    $amount = (float) ($get('amount') ?? 0);

                                    return $amount >= $threshold
                                        ? 'High-value request: Stage 1 + Stage 2 approvals required.'
                                        : 'Standard request: single approval stage.';
                                }),
                        ]),

                        Forms\Components\Textarea::make('purpose')
                            ->label('Business Justification')
                            ->required()
                            ->minLength(10)
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('new_attachments')
                            ->label('Supporting Documents')
                            ->multiple()
                            ->disk('local')
                            ->directory('requisitions')
                            ->visibility('private')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->maxSize(((int) config('requisition.attachment_max_mb', 10)) * 1024)
                            ->dehydrated(false)
                            ->helperText('Mandatory on submit for categories: ' . implode(', ', $requiredCategories))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_no')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('requisition_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '-'),

                Tables\Columns\TextColumn::make('project_name')
                    ->label('Project')
                    ->searchable()
                    ->limit(35),

                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Requester')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '-'),

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
                Tables\Filters\SelectFilter::make('requisition_type')
                    ->label('Requisition Type')
                    ->options(collect(RequisitionType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])),
                Tables\Filters\SelectFilter::make('category')
                    ->options(collect(RequisitionCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
                Tables\Filters\SelectFilter::make('branch')
                    ->options(collect(Branch::cases())->mapWithKeys(fn ($b) => [$b->value => $b->label()])),
                Tables\Filters\SelectFilter::make('requisition_for')
                    ->label('Type')
                    ->options(collect(RequisitionFor::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (CashRequisition $record) =>
                        in_array($record->status, [RequisitionStatus::DRAFT, RequisitionStatus::MODIFICATION_REQUESTED], true)
                        && $record->requester_id === auth()->id()
                    ),

                Tables\Actions\Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Submit for Approval')
                    ->modalDescription('Are you sure you want to submit this requisition for admin approval?')
                    ->visible(fn (CashRequisition $record) =>
                        in_array($record->status, [RequisitionStatus::DRAFT, RequisitionStatus::MODIFICATION_REQUESTED], true)
                        && $record->requester_id === auth()->id()
                    )
                    ->action(function (CashRequisition $record) {
                        $requiredCategories = config('requisition.attachment_required_categories', []);
                        $lookbackDays = (int) config('requisition.duplicate_lookback_days', 30);

                        $categoryValue = $record->category?->value ?? (string) $record->category;
                        $requiresAttachments = in_array($categoryValue, $requiredCategories, true);

                        if ($requiresAttachments && !$record->attachments()->exists()) {
                            Notification::make()
                                ->title('Attachments required')
                                ->body('This category requires supporting documents before submission.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $isDuplicate = CashRequisition::query()
                            ->where('id', '!=', $record->id)
                            ->where('requester_id', $record->requester_id)
                            ->where('amount', $record->amount)
                            ->where('purpose', $record->purpose)
                            ->where('created_at', '>=', now()->subDays($lookbackDays))
                            ->whereNotIn('status', [RequisitionStatus::DENIED->value, RequisitionStatus::CLOSED->value])
                            ->exists();

                        if ($isDuplicate) {
                            Notification::make()
                                ->title('Potential duplicate detected')
                                ->body('A similar requisition already exists in the last ' . $lookbackDays . ' days.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $record->update([
                            'status' => RequisitionStatus::SUBMITTED,
                            'submitted_at' => now(),
                            'stage1_approved_at' => null,
                            'stage1_approved_by_id' => null,
                            'stage1_comment' => null,
                            'decided_at' => null,
                            'decided_by_id' => null,
                            'decision_comment' => null,
                        ]);
                        AuditEvent::log('CashRequisition', $record->id, 'SUBMITTED', auth()->id());
                        $admins = User::where('role', UserRole::ADMIN->value)->get();
                        foreach ($admins as $admin) {
                            AppNotification::notify($admin, 'requisition_submitted', 'New Cash Requisition',
                                $record->requester->name . ' submitted a requisition for ' . $record->formattedAmount(),
                                'CashRequisition', $record->id);
                        }
                        Notification::make()->title('Requisition submitted for approval')->success()->send();
                    }),

                Tables\Actions\Action::make('approve_stage1')
                    ->label('Approve Stage 1')
                    ->icon('heroicon-o-shield-check')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('comment')->label('Stage 1 Comment')->rows(2),
                    ])
                    ->visible(fn (CashRequisition $record) =>
                        auth()->user()?->isAdmin()
                        && $record->status === RequisitionStatus::SUBMITTED
                        && $record->requester_id !== auth()->id()
                    )
                    ->action(function (CashRequisition $record, array $data) {
                        $now = now();
                        $turnaround = $record->submitted_at
                            ? Carbon::parse($record->submitted_at)->diffInHours($now)
                            : null;

                        if ($record->requires_additional_approval) {
                            $nextApprovers = User::query()
                                ->where('role', UserRole::ADMIN->value)
                                ->where('id', '!=', auth()->id())
                                ->where('id', '!=', $record->requester_id)
                                ->get();

                            $canEscalateToStage2 = $nextApprovers->isNotEmpty();

                            $record->update([
                                'status' => $canEscalateToStage2
                                    ? RequisitionStatus::STAGE1_APPROVED
                                    : RequisitionStatus::APPROVED,
                                'stage1_approved_at' => $now,
                                'stage1_approved_by_id' => auth()->id(),
                                'stage1_comment' => $data['comment'] ?? null,
                                'decided_by_id' => $canEscalateToStage2 ? null : auth()->id(),
                                'decided_at' => $canEscalateToStage2 ? null : $now,
                                'decision_comment' => $canEscalateToStage2 ? null : ($data['comment'] ?? null),
                                'approval_turnaround_hours' => $canEscalateToStage2 ? null : $turnaround,
                            ]);

                            if ($canEscalateToStage2) {
                                foreach ($nextApprovers as $approver) {
                                    AppNotification::notify(
                                        $approver,
                                        'requisition_stage2_pending',
                                        'Final Approval Required',
                                        'Requisition ' . $record->reference_no . ' is awaiting final approval.',
                                        'CashRequisition',
                                        $record->id
                                    );
                                }

                                AppNotification::notify(
                                    $record->requester,
                                    'requisition_stage1_approved',
                                    'Requisition Stage 1 Approved',
                                    'Your requisition ' . $record->reference_no . ' passed stage 1 and awaits final approval.',
                                    'CashRequisition',
                                    $record->id
                                );
                            } else {
                                AppNotification::notify(
                                    $record->requester,
                                    'requisition_approved',
                                    'Requisition Approved',
                                    'Your requisition ' . $record->reference_no . ' has been approved (single approver fallback).',
                                    'CashRequisition',
                                    $record->id
                                );
                            }
                        } else {
                            $record->update([
                                'status' => RequisitionStatus::APPROVED,
                                'stage1_approved_at' => $now,
                                'stage1_approved_by_id' => auth()->id(),
                                'stage1_comment' => $data['comment'] ?? null,
                                'decided_by_id' => auth()->id(),
                                'decided_at' => $now,
                                'decision_comment' => $data['comment'] ?? null,
                                'approval_turnaround_hours' => $turnaround,
                            ]);

                            AppNotification::notify(
                                $record->requester,
                                'requisition_approved',
                                'Requisition Approved',
                                'Your requisition ' . $record->reference_no . ' has been approved.',
                                'CashRequisition',
                                $record->id
                            );
                        }

                        AuditEvent::log('CashRequisition', $record->id, 'STAGE1_APPROVED', auth()->id(), [
                            'requires_additional_approval' => $record->requires_additional_approval,
                        ]);

                        Notification::make()->title('Stage 1 approval completed')->success()->send();
                    }),

                Tables\Actions\Action::make('approve_final')
                    ->label('Final Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('comment')->label('Final Approval Comment')->rows(2),
                    ])
                    ->visible(fn (CashRequisition $record) =>
                        auth()->user()?->isAdmin()
                        && $record->status === RequisitionStatus::STAGE1_APPROVED
                        && $record->requester_id !== auth()->id()
                        && $record->stage1_approved_by_id !== auth()->id()
                    )
                    ->action(function (CashRequisition $record, array $data) {
                        $turnaround = $record->submitted_at
                            ? Carbon::parse($record->submitted_at)->diffInHours(now())
                            : null;

                        $record->update([
                            'status' => RequisitionStatus::APPROVED,
                            'decided_by_id' => auth()->id(),
                            'decided_at' => now(),
                            'decision_comment' => $data['comment'] ?? null,
                            'approval_turnaround_hours' => $turnaround,
                        ]);

                        AuditEvent::log('CashRequisition', $record->id, 'FINAL_APPROVED', auth()->id());
                        AppNotification::notify(
                            $record->requester,
                            'requisition_approved',
                            'Requisition Approved',
                            'Your requisition ' . $record->reference_no . ' has received final approval.',
                            'CashRequisition',
                            $record->id
                        );
                        Notification::make()->title('Requisition final-approved')->success()->send();
                    }),

                Tables\Actions\Action::make('request_modification')
                    ->label('Request Modification')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('comment')->label('Required Changes')->required()->rows(3),
                    ])
                    ->visible(fn (CashRequisition $record) =>
                        auth()->user()?->isAdmin()
                        && in_array($record->status, [RequisitionStatus::SUBMITTED, RequisitionStatus::STAGE1_APPROVED], true)
                        && $record->requester_id !== auth()->id()
                    )
                    ->action(function (CashRequisition $record, array $data) {
                        $record->update([
                            'status' => RequisitionStatus::MODIFICATION_REQUESTED,
                            'decision_comment' => $data['comment'],
                        ]);

                        AuditEvent::log('CashRequisition', $record->id, 'MODIFICATION_REQUESTED', auth()->id(), [
                            'comment' => $data['comment'],
                        ]);

                        AppNotification::notify(
                            $record->requester,
                            'requisition_modification_requested',
                            'Modification Requested',
                            'Changes were requested on requisition ' . $record->reference_no . '. ' . $data['comment'],
                            'CashRequisition',
                            $record->id
                        );

                        Notification::make()->title('Modification requested')->warning()->send();
                    }),

                Tables\Actions\Action::make('deny')
                    ->label('Deny')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('comment')->label('Reason for denial')->required()->rows(2),
                    ])
                    ->visible(fn (CashRequisition $record) =>
                        auth()->user()?->isAdmin()
                        && in_array($record->status, [RequisitionStatus::SUBMITTED, RequisitionStatus::STAGE1_APPROVED, RequisitionStatus::MODIFICATION_REQUESTED], true)
                        && $record->requester_id !== auth()->id()
                    )
                    ->action(function (CashRequisition $record, array $data) {
                        $turnaround = $record->submitted_at
                            ? Carbon::parse($record->submitted_at)->diffInHours(now())
                            : null;

                        $record->update([
                            'status' => RequisitionStatus::DENIED,
                            'decided_by_id' => auth()->id(),
                            'decided_at' => now(),
                            'decision_comment' => $data['comment'],
                            'approval_turnaround_hours' => $turnaround,
                        ]);
                        AuditEvent::log('CashRequisition', $record->id, 'DENIED', auth()->id());
                        AppNotification::notify($record->requester, 'requisition_denied', 'Requisition Denied',
                            'Your requisition for ' . $record->formattedAmount() . ' has been denied. Reason: ' . $data['comment'],
                            'CashRequisition', $record->id);
                        Notification::make()->title('Requisition denied')->danger()->send();
                    }),

                Tables\Actions\Action::make('start_processing')
                    ->label('Start Processing')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('comment')->label('Finance/Admin Comment')->rows(2),
                    ])
                    ->visible(fn (CashRequisition $record) => auth()->user()?->isAdmin() && $record->status === RequisitionStatus::APPROVED)
                    ->action(function (CashRequisition $record, array $data) {
                        $record->update([
                            'status' => RequisitionStatus::PROCESSING,
                            'processed_by_id' => auth()->id(),
                            'processed_at' => now(),
                            'finance_comment' => $data['comment'] ?? null,
                        ]);
                        AuditEvent::log('CashRequisition', $record->id, 'PROCESSING_STARTED', auth()->id());
                        AppNotification::notify($record->requester, 'requisition_processing', 'Requisition Processing',
                            'Requisition ' . $record->reference_no . ' is now being processed by Finance/Admin.',
                            'CashRequisition', $record->id);
                        Notification::make()->title('Requisition moved to processing')->success()->send();
                    }),

                Tables\Actions\Action::make('mark_outstanding')
                    ->label('Mark Outstanding')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('comment')->label('Reason')->required()->rows(2),
                    ])
                    ->visible(fn (CashRequisition $record) => auth()->user()?->isAdmin() && $record->status === RequisitionStatus::PROCESSING)
                    ->action(function (CashRequisition $record, array $data) {
                        $record->update([
                            'status' => RequisitionStatus::OUTSTANDING,
                            'finance_comment' => $data['comment'],
                        ]);

                        AuditEvent::log('CashRequisition', $record->id, 'MARKED_OUTSTANDING', auth()->id(), [
                            'comment' => $data['comment'],
                        ]);

                        AppNotification::notify(
                            $record->requester,
                            'requisition_outstanding',
                            'Requisition Outstanding',
                            'Requisition ' . $record->reference_no . ' is currently outstanding. ' . $data['comment'],
                            'CashRequisition',
                            $record->id
                        );

                        Notification::make()->title('Marked as outstanding')->warning()->send();
                    }),

                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark Paid / Disbursed')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('payment_method')
                            ->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]))
                            ->required(),
                        Forms\Components\TextInput::make('payment_reference')->required()->maxLength(150),
                        Forms\Components\DatePicker::make('payment_date')->required(),
                        Forms\Components\Textarea::make('finance_comment')->label('Finance Comment')->rows(2),
                    ])
                    ->visible(fn (CashRequisition $record) =>
                        auth()->user()?->isAdmin()
                        && in_array($record->status, [RequisitionStatus::PROCESSING, RequisitionStatus::OUTSTANDING], true)
                    )
                    ->action(function (CashRequisition $record, array $data) {
                        $record->update([
                            'status' => RequisitionStatus::PAID,
                            'processed_by_id' => $record->processed_by_id ?: auth()->id(),
                            'processed_at' => $record->processed_at ?: now(),
                            'payment_method' => $data['payment_method'],
                            'payment_reference' => $data['payment_reference'],
                            'payment_date' => $data['payment_date'],
                            'finance_comment' => $data['finance_comment'] ?? null,
                        ]);

                        AuditEvent::log('CashRequisition', $record->id, 'PAID_OR_DISBURSED', auth()->id());
                        AppNotification::notify(
                            $record->requester,
                            'requisition_paid',
                            'Requisition Paid / Disbursed',
                            'Requisition ' . $record->reference_no . ' has been paid/disbursed.',
                            'CashRequisition',
                            $record->id
                        );

                        Notification::make()->title('Payment captured')->success()->send();
                    }),

                Tables\Actions\Action::make('mark_fulfilled')
                    ->label('Mark Fulfilled')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('purchase_status')
                            ->options(collect(PurchaseStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                            ->required(),
                        Forms\Components\Select::make('delivery_status')
                            ->options(collect(DeliveryStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                            ->required(),
                        Forms\Components\TextInput::make('actual_amount')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        Forms\Components\Textarea::make('variance_reason')->rows(2),
                        Forms\Components\Textarea::make('fulfilment_notes')->required()->rows(2),
                    ])
                    ->visible(fn (CashRequisition $record) =>
                        auth()->user()?->isAdmin()
                        && in_array($record->status, [RequisitionStatus::PAID, RequisitionStatus::PROCESSING], true)
                    )
                    ->action(function (CashRequisition $record, array $data) {
                        $actualAmount = (float) $data['actual_amount'];
                        $requestedAmount = (float) $record->amount;

                        if ($actualAmount !== $requestedAmount && blank($data['variance_reason'] ?? null)) {
                            Notification::make()
                                ->title('Variance reason required')
                                ->body('Please provide a reason when actual spend differs from requested amount.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update([
                            'status' => RequisitionStatus::FULFILLED,
                            'purchase_status' => $data['purchase_status'],
                            'delivery_status' => $data['delivery_status'],
                            'actual_amount' => $actualAmount,
                            'variance_reason' => $data['variance_reason'] ?? null,
                            'fulfilment_notes' => $data['fulfilment_notes'],
                            'fulfilled_at' => now(),
                            'fulfilled_by_id' => auth()->id(),
                        ]);

                        AuditEvent::log('CashRequisition', $record->id, 'FULFILLED', auth()->id());
                        AppNotification::notify(
                            $record->requester,
                            'requisition_fulfilled',
                            'Requisition Fulfilled',
                            'Requisition ' . $record->reference_no . ' has been fulfilled. Please confirm closure readiness.',
                            'CashRequisition',
                            $record->id
                        );

                        Notification::make()->title('Requisition marked fulfilled')->success()->send();
                    }),

                Tables\Actions\Action::make('confirm_fulfilment')
                    ->label('Confirm Fulfilment')
                    ->icon('heroicon-o-hand-thumb-up')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (CashRequisition $record) =>
                        $record->requester_id === auth()->id()
                        && $record->status === RequisitionStatus::FULFILLED
                        && $record->requester_confirmed_at === null
                    )
                    ->action(function (CashRequisition $record) {
                        $record->update([
                            'requester_confirmed_at' => now(),
                        ]);

                        AuditEvent::log('CashRequisition', $record->id, 'REQUESTER_CONFIRMED', auth()->id());
                        Notification::make()->title('Fulfilment confirmed')->success()->send();
                    }),

                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('closure_comment')->label('Closure Comment')->required()->rows(2),
                    ])
                    ->visible(fn (CashRequisition $record) =>
                        auth()->user()?->isAdmin()
                        && $record->status === RequisitionStatus::FULFILLED
                        && $record->requester_confirmed_at !== null
                    )
                    ->action(function (CashRequisition $record, array $data) {
                        $record->update([
                            'status' => RequisitionStatus::CLOSED,
                            'closed_at' => now(),
                            'closed_by_id' => auth()->id(),
                            'closure_comment' => $data['closure_comment'],
                        ]);

                        AuditEvent::log('CashRequisition', $record->id, 'CLOSED', auth()->id(), [
                            'closure_comment' => $data['closure_comment'],
                        ]);

                        AppNotification::notify(
                            $record->requester,
                            'requisition_closed',
                            'Requisition Closed',
                            'Requisition ' . $record->reference_no . ' has been closed.',
                            'CashRequisition',
                            $record->id
                        );

                        Notification::make()->title('Requisition closed')->success()->send();
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
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('reference_no')
                                ->label('Reference No.'),
                            Infolists\Components\TextEntry::make('requisition_type')
                                ->formatStateUsing(fn ($state) => $state?->label() ?? '-'),
                            Infolists\Components\TextEntry::make('project_name'),
                            Infolists\Components\TextEntry::make('project_code')
                                ->placeholder('-'),
                        ]),

                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('branch')
                                ->formatStateUsing(fn (Branch $state) => $state->label()),
                            Infolists\Components\TextEntry::make('category')
                                ->formatStateUsing(fn ($state) => $state?->label() ?? '-'),
                            Infolists\Components\TextEntry::make('cost_center')
                                ->placeholder('-'),
                            Infolists\Components\TextEntry::make('budget_code')
                                ->placeholder('-'),
                        ]),

                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('requisition_for')
                                ->label('For')
                                ->formatStateUsing(fn (RequisitionFor $state) => $state->label()),
                            Infolists\Components\TextEntry::make('status')
                                ->badge()
                                ->formatStateUsing(fn (RequisitionStatus $state) => $state->label())
                                ->color(fn (RequisitionStatus $state) => $state->color()),
                        ]),
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('amount')
                                ->formatStateUsing(fn ($record) => $record->formattedAmount()),
                            Infolists\Components\TextEntry::make('actual_amount')
                                ->formatStateUsing(fn ($record) => $record->actual_amount ? $record->currency . ' ' . number_format((float) $record->actual_amount, 2) : '-'),
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
                            ->label('Business Justification')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('attachments_list')
                            ->label('Supporting Documents')
                            ->state(function (CashRequisition $record) {
                                $record->loadMissing('attachments');

                                if ($record->attachments->isEmpty()) {
                                    return 'No attachments uploaded.';
                                }

                                return $record->attachments
                                    ->map(fn (CashRequisitionAttachment $attachment) =>
                                        '<a class="text-primary-600 underline" href="' . route('attachments.download', $attachment) . '">' . e($attachment->file_name) . '</a>'
                                    )
                                    ->implode('<br>');
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Decision')
                    ->schema([
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('stage1ApprovedBy.name')->label('Stage 1 Approver'),
                            Infolists\Components\TextEntry::make('stage1_approved_at')->dateTime('d M Y H:i'),
                            Infolists\Components\TextEntry::make('decidedBy.name')->label('Final Approver'),
                            Infolists\Components\TextEntry::make('decided_at')->dateTime('d M Y H:i'),
                        ]),
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('stage1_comment')->label('Stage 1 Comment'),
                            Infolists\Components\TextEntry::make('decision_comment')->label('Final Decision Comment'),
                        ]),
                        Infolists\Components\TextEntry::make('approval_turnaround_hours')
                            ->label('Approval Turnaround')
                            ->formatStateUsing(fn ($state) => $state !== null ? $state . ' hour(s)' : '-'),
                    ])
                    ->visible(fn ($record) => $record->stage1_approved_by_id !== null || $record->decided_by_id !== null),

                Infolists\Components\Section::make('Finance / Admin Processing')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('processedBy.name')->label('Processed By'),
                            Infolists\Components\TextEntry::make('processed_at')->dateTime('d M Y H:i'),
                            Infolists\Components\TextEntry::make('payment_method')
                                ->formatStateUsing(fn ($state) => $state?->label() ?? '-'),
                        ]),
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('payment_reference')->placeholder('-'),
                            Infolists\Components\TextEntry::make('payment_date')->date(),
                            Infolists\Components\TextEntry::make('finance_comment')->placeholder('-'),
                        ]),
                    ])
                    ->visible(fn ($record) => $record->processed_by_id !== null || $record->payment_reference !== null),

                Infolists\Components\Section::make('Fulfilment & Closure')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('purchase_status')
                                ->formatStateUsing(fn ($state) => $state?->label() ?? '-'),
                            Infolists\Components\TextEntry::make('delivery_status')
                                ->formatStateUsing(fn ($state) => $state?->label() ?? '-'),
                            Infolists\Components\TextEntry::make('fulfilledBy.name')->label('Fulfilled By'),
                        ]),
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('fulfilled_at')->dateTime('d M Y H:i'),
                            Infolists\Components\TextEntry::make('requester_confirmed_at')->dateTime('d M Y H:i'),
                            Infolists\Components\TextEntry::make('closed_at')->dateTime('d M Y H:i'),
                        ]),
                        Infolists\Components\TextEntry::make('fulfilment_notes')->placeholder('-'),
                        Infolists\Components\TextEntry::make('variance_reason')->placeholder('-'),
                        Infolists\Components\TextEntry::make('closure_comment')->placeholder('-'),
                    ])
                    ->visible(fn ($record) =>
                        $record->fulfilled_at !== null
                        || $record->requester_confirmed_at !== null
                        || $record->closed_at !== null
                    ),

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
