<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';

    case Confirmed = 'confirmed';

    case Declined = 'declined';

    case CancelledByBooker = 'cancelled_by_booker';

    case CancelledByCoach = 'cancelled_by_coach';

    /**
     * Blocks the time range from being offered to other bookers.
     */
    public function reservesSlot(): bool
    {
        return $this === self::Pending || $this === self::Confirmed;
    }
}
