<?php

// config/fsm.php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default FSM State Column
    |--------------------------------------------------------------------------
    |
    | This is the default database column name used to store the state of an FSM
    | when a specific column is not provided in the FSM definition.
    |
    */
    'default_column_name' => 'status',

    /*
    |--------------------------------------------------------------------------
    | Use Database Transactions
    |--------------------------------------------------------------------------
    |
    | Specify whether FSM transitions should be wrapped in a database transaction.
    | This ensures that state changes and any associated database operations
    | (e.g., in callbacks or actions) are atomic.
    |
    */
    'use_transactions' => true,

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, additional debug logging will be emitted to help troubleshoot
    | FSM behaviour.
    |
    */
    'debug' => false,

    /*
    |--------------------------------------------------------------------------
    | FSM Definition Caching
    |--------------------------------------------------------------------------
    |
    | When enabled, compiled FSM definitions will be cached to disk. This can
    | significantly speed up application boot by avoiding runtime discovery.
    |
    */
    'cache' => [
        'enabled' => false,
        'path' => storage_path('framework/cache/fsm.php'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure FSM event logging for auditability and state replay.
    |
    */
    'event_logging' => [
        // Enable or disable event logging to the 'fsm_event_logs' table.
        'enabled' => true,

        // Queue event logging for better performance (requires queue workers).
        'queue' => false,

        // Enable automatic registration of event listeners.
        'auto_register_listeners' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure aspects of FSM transition logging.
    |
    */
    'logging' => [
        // Enable or disable logging of FSM transitions to the 'fsm_logs' table.
        'enabled' => true,

        // Enable or disable logging of failed FSM transitions.
        'log_failures' => false,

        // Optional Laravel log channel to mirror FSM logs to.
        'channel' => null,

        // When true, pass the full log data array to the logger. If false, context is stringified.
        'structured' => false,

        // Define which context properties should be excluded from the context_snapshot
        // in fsm_logs. This can be useful to avoid logging sensitive data or
        // very large objects. Provide an array of property names (dot notation supported for nested DTOs).
        // e.g., ['user.password', 'large_object']
        'excluded_context_properties' => [],

        // Maximum character limit for exception details in failure logs
        'exception_character_limit' => 65535,
    ],

    /*
    |--------------------------------------------------------------------------
    | Thunk Verbs Integration
    |--------------------------------------------------------------------------
    |
    | Configure how this package integrates with thunk.dev/verbs.
    |
    */
    'verbs' => [
        // Automatically dispatch the FsmTransitioned verb when a transition succeeds.
        'dispatch_transitioned_verb' => true,
        // When enabled, the logger will attempt to record the user context
        // from Verbs by populating `subject_id` and `subject_type` in fsm_logs.
        'log_user_subject' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | FSM Definition Discovery
    |--------------------------------------------------------------------------
    |
    | Paths where the FSM service provider will look for classes implementing
    | the Fsm\Contracts\FsmDefinition interface. This is used to auto-register FSMs.
    | By default, it looks in the app/Fsm directory.
    |
    | Note: Using a callback here prevents issues during package discovery in CI
    | environments where the application may not be fully bootstrapped.
    |
    */
    'discovery_paths' => function () {
        try {
            // Only resolve app_path if the functions exist and are working
            if (function_exists('app_path') && function_exists('app')) {
                return [app_path('Fsm')];
            }
        } catch (\Throwable $e) {
            // If anything fails, we're not in a bootstrapped environment
        }

        // Fallback for package discovery or when app isn't fully bootstrapped
        return [];
    },

    /*
    |--------------------------------------------------------------------------
    | Modular FSM Definitions
    |--------------------------------------------------------------------------
    |
    | Configuration for modular FSM extensions, state overrides, and transition
    | overrides. This allows extending or modifying existing FSM definitions
    | without changing the original definition classes.
    |
    */
    'modular' => [
        /*
        | FSM Extensions
        |
        | Array of extension class names that implement FsmExtension interface.
        | Extensions are applied in priority order to modify FSM definitions.
        */
        'extensions' => [
            // Example: App\Fsm\Extensions\OrderStatusExtension::class,
        ],

        /*
        | State Overrides
        |
        | Override or extend state definitions for specific FSMs.
        | Structure: [ModelClass => [columnName => [stateName => config]]]
        */
        'state_overrides' => [
            // Example:
            // App\Models\Order::class => [
            //     'status' => [
            //         'pending' => [
            //             'override' => true,
            //             'priority' => 100,
            //             'definition' => [
            //                 'description' => 'Enhanced pending state',
            //                 'metadata' => ['enhanced' => true],
            //             ],
            //         ],
            //     ],
            // ],
        ],

        /*
        | Transition Overrides
        |
        | Override or extend transition definitions for specific FSMs.
        | Structure: [ModelClass => [columnName => [array of transition configs]]]
        */
        'transition_overrides' => [
            // Example:
            // App\Models\Order::class => [
            //     'status' => [
            //         [
            //             'from' => 'pending',
            //             'to' => 'confirmed',
            //             'event' => 'confirm',
            //             'override' => true,
            //             'priority' => 100,
            //             'definition' => [
            //                 'description' => 'Enhanced confirmation transition',
            //                 'guards' => ['enhanced_payment_validation'],
            //             ],
            //         ],
            //     ],
            // ],
        ],

        /*
        | Runtime Extensions
        |
        | Enable or disable runtime extension capabilities.
        */
        'runtime_extensions' => [
            'enabled' => true,
            'cache_extensions' => false,
        ],
    ],
];
