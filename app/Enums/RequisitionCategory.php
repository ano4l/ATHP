<?php

namespace App\Enums;

enum RequisitionCategory: string
{
    case OPERATIONS = 'operations';
    case PROJECT = 'project';
    case EMERGENCY = 'emergency';
    case CLIENT = 'client';
    case PROCUREMENT = 'procurement';
    case TRAVEL = 'travel';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::OPERATIONS => 'Operations',
            self::PROJECT => 'Project',
            self::EMERGENCY => 'Emergency',
            self::CLIENT => 'Client Related',
            self::PROCUREMENT => 'Procurement',
            self::TRAVEL => 'Travel',
            self::OTHER => 'Other',
        };
    }
}
