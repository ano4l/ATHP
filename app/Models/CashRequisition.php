<?php

namespace App\Models;

use App\Enums\Branch;
use App\Enums\DeliveryStatus;
use App\Enums\PaymentMethod;
use App\Enums\RequisitionCategory;
use App\Enums\RequisitionFor;
use App\Enums\RequisitionStatus;
use App\Enums\RequisitionType;
use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRequisition extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'requester_id',
        'branch',
        'requisition_type',
        'project_name',
        'project_code',
        'category',
        'cost_center',
        'budget_code',
        'requires_additional_approval',
        'requisition_for',
        'client_ref',
        'order_ref',
        'amount',
        'currency',
        'purpose',
        'needed_by',
        'status',
        'submitted_at',
        'stage1_approved_at',
        'stage1_approved_by_id',
        'stage1_comment',
        'decided_at',
        'decided_by_id',
        'decision_comment',
        'processed_by_id',
        'processed_at',
        'payment_method',
        'payment_reference',
        'payment_date',
        'finance_comment',
        'purchase_status',
        'delivery_status',
        'fulfilled_at',
        'fulfilled_by_id',
        'fulfilment_notes',
        'actual_amount',
        'variance_reason',
        'requester_confirmed_at',
        'closed_at',
        'closed_by_id',
        'closure_comment',
        'approval_turnaround_hours',
    ];

    protected function casts(): array
    {
        return [
            'branch' => Branch::class,
            'requisition_type' => RequisitionType::class,
            'category' => RequisitionCategory::class,
            'requisition_for' => RequisitionFor::class,
            'status' => RequisitionStatus::class,
            'payment_method' => PaymentMethod::class,
            'purchase_status' => PurchaseStatus::class,
            'delivery_status' => DeliveryStatus::class,
            'requires_additional_approval' => 'boolean',
            'amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
            'needed_by' => 'date',
            'submitted_at' => 'datetime',
            'stage1_approved_at' => 'datetime',
            'decided_at' => 'datetime',
            'processed_at' => 'datetime',
            'payment_date' => 'date',
            'fulfilled_at' => 'datetime',
            'requester_confirmed_at' => 'datetime',
            'closed_at' => 'datetime',
            'approval_turnaround_hours' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $requisition): void {
            $requisition->requisition_type ??= RequisitionType::CASH;
            $requisition->category ??= RequisitionCategory::OPERATIONS;
        });

        static::saving(function (self $requisition): void {
            if ($requisition->amount !== null) {
                $threshold = (float) config('requisition.stage2_threshold', 10000);
                $requisition->requires_additional_approval = (float) $requisition->amount >= $threshold;
            }
        });

        static::created(function (self $requisition): void {
            if (blank($requisition->reference_no)) {
                $datePart = ($requisition->created_at ?? now())->format('Ymd');
                $requisition->reference_no = sprintf('REQ-%s-%06d', $datePart, $requisition->id);
                $requisition->saveQuietly();
            }
        });
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }

    public function stage1ApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stage1_approved_by_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_id');
    }

    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CashRequisitionAttachment::class, 'requisition_id');
    }

    public function formattedAmount(): string
    {
        return $this->currency . ' ' . number_format((float) $this->amount, 2);
    }
}
