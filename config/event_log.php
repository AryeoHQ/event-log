<?php

declare(strict_types=1);

return [
    'enabled' => env('EVENT_LOG_ENABLED', true),

    'context' => [
        /*
         * Top-level Context keys to persist into the `event_logs.context` column.
         * Anything not listed here is dropped before the row is written.
         */
        'whitelist' => array_filter(
            array_map(
                'trim',
                explode(',', (string) env('EVENT_LOG_CONTEXT_WHITELIST', ''))
            )
        ),
    ],
];
