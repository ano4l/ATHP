<?php

namespace App\Models;

use App\Enums\Branch;
use App\Enums\RequisitionFor;
use App\Enums\RequisitionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRequisition extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'branch',
        'requisition_for',
        'client_ref',
        'order_ref',
        'amount',
        'currency',
        'purpose',
        'needed_by',
        'status',
        'submitted_at',
        'decided_at',
        'decided_by_id',
        'decision_comment',
    ];

    protected function casts(): array
    {
        return [
            'branch' => Branch::class,
            'requisition_for' => RequisitionFor::class,
            'status' => RequisitionStatus::class,
            'amount' => 'decimal:2',
            'needed_by' => 'date',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
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
