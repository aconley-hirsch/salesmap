<?php

namespace App\Enums;

enum RoleType: string
{
    case Rsm = 'rsm';
    case Se = 'se';
    case InsideSales = 'inside_sales';
    case Credentials = 'credentials';

    public function label(): string
    {
        return match ($this) {
            self::Rsm => 'Regional Sales Manager',
            self::Se => 'Solutions Engineer',
            self::InsideSales => 'Inside Sales',
            self::Credentials => 'Credentials Sales',
        };
    }
}
