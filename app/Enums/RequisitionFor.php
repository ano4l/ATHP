<?php

namespace App\Enums;

enum RequisitionFor: string
{
    case CLIENT = 'client';
    case ORDER = 'order';
    case SELF = 'self';

    public function label(): string
    {
        return match ($this) {
            self::CLIENT => 'Client',
            self::ORDER => 'Order',
            self::SELF => 'Self',
        };
    }
}
