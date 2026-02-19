<?php

namespace App\Enums;

enum RequisitionType: string
{
    case CASH = 'cash';
    case PURCHASE = 'purchase';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash Requisition',
            self::PURCHASE => 'Purchase Requisition',
        };
    }
}
