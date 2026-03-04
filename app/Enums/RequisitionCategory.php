<?php

namespace App\Enums;

enum RequisitionCategory: string
{
    case FUEL = 'fuel';
    case AIRTIME = 'airtime';
    case MATERIALS = 'materials';
    case TRAVEL = 'travel';
    case PROCUREMENT = 'procurement';
    case OPERATIONS = 'operations';
    case OFFICE_SUPPLIES = 'office_supplies';
    case IT_SOFTWARE = 'it_software';
    case MARKETING = 'marketing';
    case TRAINING = 'training';
    case FLEET_TRANSPORT = 'fleet_transport';
    case EMERGENCY = 'emergency';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FUEL => 'Fuel',
            self::AIRTIME => 'Airtime',
            self::MATERIALS => 'Materials',
            self::TRAVEL => 'Travel',
            self::PROCUREMENT => 'Procurement',
            self::OPERATIONS => 'Operations',
            self::OFFICE_SUPPLIES => 'Office Supplies',
            self::IT_SOFTWARE => 'IT & Software',
            self::MARKETING => 'Marketing',
            self::TRAINING => 'Training',
            self::FLEET_TRANSPORT => 'Fleet & Transport',
            self::EMERGENCY => 'Emergency',
            self::OTHER => 'Other',
        };
    }
}
