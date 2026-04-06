<?php

namespace App\Enums;

enum TenantRole: string
{
    case Owner = 'owner';

    case Admin = 'admin';

    case Instructor = 'instructor';

    case Learner = 'learner';

    /**
     * @return list<string>
     */
    public static function staffValues(): array
    {
        return [
            self::Owner->value,
            self::Admin->value,
            self::Instructor->value,
        ];
    }
}
