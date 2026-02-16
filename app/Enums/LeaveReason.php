<?php

namespace App\Enums;

enum LeaveReason: string
{
    case ANNUAL = 'annual';
    case SICK = 'sick';
    case FAMILY_RESPONSIBILITY = 'family_responsibility';
    case STUDY = 'study';
    case UNPAID = 'unpaid';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ANNUAL => 'Annual Leave',
            self::SICK => 'Sick Leave',
            self::FAMILY_RESPONSIBILITY => 'Family Responsibility',
            self::STUDY => 'Study Leave',
            self::UNPAID => 'Unpaid Leave',
            self::OTHER => 'Other',
        };
    }
}
