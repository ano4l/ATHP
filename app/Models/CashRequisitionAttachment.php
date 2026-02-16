<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRequisitionAttachment extends Model
{
    protected $fillable = [
        'requisition_id',
        'file_name',
        'file_type',
        'file_size',
        'storage_path',
        'uploaded_by_id',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(CashRequisition::class, 'requisition_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
