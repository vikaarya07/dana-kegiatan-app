<?php

namespace App\Enums;

enum IncomeType: string
{
    case REGULAR_MEETING = 'regular_meeting';
    case ADDITIONAL_FUND = 'additional_fund';

    public function label(): string
    {
        return match ($this) {
            self::REGULAR_MEETING => 'Rapat Rutin',
            self::ADDITIONAL_FUND => 'Tambahan Dana / Bantuan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::REGULAR_MEETING => 'blue',
            self::ADDITIONAL_FUND => 'amber',
        };
    }
}
