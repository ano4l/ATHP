<?php

namespace App\Enums;

enum RequisitionStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case DENIED = 'denied';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::APPROVED => 'Approved',
            self::DENIED => 'Denied',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SUBMITTED => 'info',
            self::APPROVED => 'success',
            self::DENIED => 'danger',
        };
    }
}
