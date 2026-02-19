<?php

namespace App\Enums;

enum RequisitionStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case STAGE1_APPROVED = 'stage1_approved';
    case MODIFICATION_REQUESTED = 'modification_requested';
    case APPROVED = 'approved';
    case DENIED = 'denied';
    case PROCESSING = 'processing';
    case PAID = 'paid';
    case OUTSTANDING = 'outstanding';
    case FULFILLED = 'fulfilled';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::STAGE1_APPROVED => 'Stage 1 Approved',
            self::MODIFICATION_REQUESTED => 'Modification Requested',
            self::APPROVED => 'Approved',
            self::DENIED => 'Denied',
            self::PROCESSING => 'Processing',
            self::PAID => 'Paid / Disbursed',
            self::OUTSTANDING => 'Outstanding',
            self::FULFILLED => 'Fulfilled',
            self::CLOSED => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SUBMITTED => 'info',
            self::STAGE1_APPROVED => 'warning',
            self::MODIFICATION_REQUESTED => 'warning',
            self::APPROVED => 'success',
            self::DENIED => 'danger',
            self::PROCESSING => 'primary',
            self::PAID => 'success',
            self::OUTSTANDING => 'warning',
            self::FULFILLED => 'success',
            self::CLOSED => 'gray',
        };
    }
}
