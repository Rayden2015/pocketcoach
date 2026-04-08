<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver: "trello" | "null"
    |--------------------------------------------------------------------------
    | Use null locally when you do not want outbound API calls.
    */
    'driver' => env('TASK_BOARD_DRIVER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Incoming automation (optional)
    |--------------------------------------------------------------------------
    | When set, POST /api/v1/integrations/qa-tasks is enabled and requires this
    | secret as Bearer token or X-Task-Board-Secret header.
    */
    'incoming_secret' => env('TASK_BOARD_INCOMING_SECRET'),

    'trello' => [
        'api_key' => env('TRELLO_API_KEY'),
        'token' => env('TRELLO_TOKEN'),
        /*
         * Optional board short link (URL segment after /b/). When set and default_list_id
         * is empty, the first list on the board (by Trello pos / column order) is used.
         */
        'board_id' => env('TRELLO_BOARD_ID'),
        'default_list_id' => env('TRELLO_DEFAULT_LIST_ID'),
        'base_url' => env('TRELLO_BASE_URL', 'https://api.trello.com/1'),
        /*
         * Log outbound Trello HTTP metadata + response body (credentials redacted).
         * Set TRELLO_LOG_HTTP=true while debugging; turn off in production.
         */
        'log_http' => filter_var(env('TRELLO_LOG_HTTP', false), FILTER_VALIDATE_BOOLEAN),
        'log_http_body_max' => (int) env('TRELLO_LOG_HTTP_BODY_MAX', 12_000),
    ],

];
