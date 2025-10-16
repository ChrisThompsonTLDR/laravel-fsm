<?php

declare(strict_types=1);

namespace Fsm;

/**
 * Typed constants for FSM operations with enhanced type safety.
 *
 * Using PHP 8.3+ typed class constants for better static analysis
 * and improved IDE support with type inference.
 */
class Constants
{
    /**
     * Commands that should be skipped during FSM discovery to avoid database access
     * during package discovery and bootstrap operations.
     */
    public const array SKIP_DISCOVERY_COMMANDS = [
        'package:discover',
        'config:cache',
        'config:clear',
        'cache:clear',
        'optimize',
        'optimize:clear',
        'dump-autoload',
    ];

    /**
     * Represents a wildcard event when defining transitions.
     * If a transition is defined without a specific event, this wildcard
     * is used as the event key.
     */
    public const string EVENT_WILDCARD = '*';

    /**
     * Represents a wildcard state when defining transitions.
     * If a transition is defined to allow any "from" state, this wildcard
     * can be used.
     */
    public const string STATE_WILDCARD = '__STATE_WILDCARD__';

    /**
     * Transition result constants with proper typing for enhanced type safety.
     */
    public const string TRANSITION_SUCCESS = 'success';

    public const string TRANSITION_BLOCKED = 'blocked';

    public const string TRANSITION_FAILED = 'failed';

    /**
     * Event sourcing integration constants for Verbs compatibility.
     */
    public const string VERBS_AGGREGATE_TYPE = 'fsm_aggregate';

    public const string VERBS_EVENT_TYPE = 'fsm_transitioned';

    public const int VERBS_DEFAULT_REPLAY_CHUNK_SIZE = 100;

    /**
     * FSM operation types for logging and event sourcing.
     */
    public const string OPERATION_TRANSITION = 'transition';

    public const string OPERATION_GUARD_CHECK = 'guard_check';

    public const string OPERATION_ACTION_EXECUTE = 'action_execute';

    public const string OPERATION_CALLBACK_EXECUTE = 'callback_execute';

    /**
     * Priority levels for transition processing with proper typing.
     */
    public const int PRIORITY_HIGH = 100;

    public const int PRIORITY_NORMAL = 50;

    public const int PRIORITY_LOW = 10;

    /**
     * Configuration keys with strong typing for better config access.
     */
    public const string CONFIG_LOGGING_ENABLED = 'fsm.logging.enabled';

    public const string CONFIG_VERBS_DISPATCH = 'fsm.verbs.dispatch_transitioned_verb';

    public const string CONFIG_USE_TRANSACTIONS = 'fsm.use_transactions';

    public const string CONFIG_DISCOVERY_PATHS = 'fsm.discovery_paths';

    /**
     * State metadata keys for enhanced state definitions.
     */
    public const string META_DISPLAY_NAME = 'display_name';

    public const string META_DESCRIPTION = 'description';

    public const string META_ICON = 'icon';

    public const string META_COLOR = 'color';

    public const array META_ALLOWED_KEYS = [
        self::META_DISPLAY_NAME,
        self::META_DESCRIPTION,
        self::META_ICON,
        self::META_COLOR,
    ];

    /**
     * Validation error types with proper typing.
     */
    public const string ERROR_INVALID_STATE = 'invalid_state';

    public const string ERROR_INVALID_TRANSITION = 'invalid_transition';

    public const string ERROR_GUARD_FAILED = 'guard_failed';

    public const string ERROR_ACTION_FAILED = 'action_failed';

    public const string ERROR_CALLBACK_FAILED = 'callback_failed';
}
