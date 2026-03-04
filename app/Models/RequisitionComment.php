<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionComment extends Model
{
    protected $fillable = [
        'requisition_id',
        'user_id',
        'body',
        'context',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(CashRequisition::class, 'requisition_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
