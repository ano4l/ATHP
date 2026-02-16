<?php

namespace App\Enums;

enum LeaveStatus: string
{
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case DENIED = 'denied';

    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Submitted',
            self::APPROVED => 'Approved',
            self::DENIED => 'Denied',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SUBMITTED => 'info',
            self::APPROVED => 'success',
            self::DENIED => 'danger',
        };
    }
}
