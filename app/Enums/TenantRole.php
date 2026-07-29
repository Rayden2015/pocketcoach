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

    /**
     * Roles that may manage the space team (invite coaches).
     *
     * @return list<string>
     */
    public static function ownerOrAdminValues(): array
    {
        return [
            self::Owner->value,
            self::Admin->value,
        ];
    }

    /**
     * Roles that can be assigned via invite.
     *
     * @return list<string>
     */
    public static function invitableStaffValues(): array
    {
        return [
            self::Admin->value,
            self::Instructor->value,
        ];
    }
}
