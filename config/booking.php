<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Signed “respond from email” links
    |--------------------------------------------------------------------------
    |
    | Coaches receive links to confirm or decline a pending booking without
    | signing in first. URLs are signed with APP_KEY; shorten TTL in production
    | if your security policy requires it.
    |
    */
    'mail_response_link_ttl_days' => (int) env('BOOKING_MAIL_LINK_TTL_DAYS', 7),

];
