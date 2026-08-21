<?php

declare(strict_types=1);

use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Logs\Log;
use Support\Events\Log\Relays\Relay;

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

    'queues' => [
        /*
         * The queue each pipeline layer's processing job is dispatched onto,
         * keyed by model. null uses the default queue. A transport may override
         * the relay (collecting) and delivery (sending) layers via the #[Queues]
         * attribute.
         */
        Log::class => env('EVENT_LOG_QUEUE_LOG'),
        Relay::class => env('EVENT_LOG_QUEUE_RELAY'),
        Delivery::class => env('EVENT_LOG_QUEUE_DELIVERY'),
    ],

    'watchdog' => [
        /*
         * How many minutes a record may sit in an in-flight state (Pending or
         * Locked) before the watchdog considers it stuck and fails it. Set this
         * comfortably longer than the slowest legitimate single step, including
         * the longest backoff wait between delivery attempts.
         */
        'grace' => (int) env('EVENT_LOG_WATCHDOG_GRACE', 15),
    ],

    'locking' => [
        /*
         * How many seconds a record's transition lock is held before it expires.
         * Set this longer than the slowest Process handle() so a live worker
         * never loses its lock mid-flight.
         */
        'ttl' => (int) env('EVENT_LOG_LOCKING_TTL', 300),
    ],
];
