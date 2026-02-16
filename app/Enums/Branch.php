<?php

namespace App\Enums;

enum Branch: string
{
    case SOUTH_AFRICA = 'south_africa';
    case ZAMBIA = 'zambia';
    case ESWATINI = 'eswatini';
    case ZIMBABWE = 'zimbabwe';

    public function label(): string
    {
        return match ($this) {
            self::SOUTH_AFRICA => 'South Africa',
            self::ZAMBIA => 'Zambia',
            self::ESWATINI => 'Eswatini',
            self::ZIMBABWE => 'Zimbabwe',
        };
    }

    public function currency(): string
    {
        return match ($this) {
            self::SOUTH_AFRICA => 'ZAR',
            self::ZAMBIA => 'ZMW',
            self::ESWATINI => 'SZL',
            self::ZIMBABWE => 'USD',
        };
    }
}
