<?php

namespace App\Enums;

enum ActivityStatus: string
{
    case PLANNED = 'planned';
    case ONGOING = 'ongoing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Direncanakan',
            self::ONGOING => 'Berlangsung',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PLANNED => 'blue',
            self::ONGOING => 'fuchsia',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
        };
    }
}
