<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\BookingDeclinedNotification;
use App\Notifications\BookingRequestReceivedNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

final class BookingBookerNotifier
{
    public function requestReceived(Booking $booking): void
    {
        $this->notifyBooker($booking, new BookingRequestReceivedNotification($booking));
    }

    public function confirmed(Booking $booking): void
    {
        $this->notifyBooker($booking, new BookingConfirmedNotification($booking));
    }

    public function declined(Booking $booking): void
    {
        $this->notifyBooker($booking, new BookingDeclinedNotification($booking));
    }

    private function notifyBooker(Booking $booking, Notification $notification): void
    {
        $booking->loadMissing(['booker', 'tenant', 'coach']);

        if ($booking->booker_user_id !== null && $booking->booker instanceof User) {
            $booking->booker->notify($notification);

            return;
        }

        $email = $booking->bookerContactEmail();
        if ($email !== null && $email !== '') {
            NotificationFacade::route('mail', $email)->notify($notification);
        }
    }
}
