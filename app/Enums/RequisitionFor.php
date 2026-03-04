<?php

namespace App\Enums;

enum RequisitionFor: string
{
    case INTERNAL = 'internal';
    case CLIENT = 'client';
    case PROJECT = 'project';

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Internal',
            self::CLIENT => 'Client',
            self::PROJECT => 'Project',
        };
    }
}
