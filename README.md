# Laravel FSM

[![Latest Version on Packagist](https://img.shields.io/packagist/v/christhompsontldr/laravel-fsm.svg?style=flat-square)](https://packagist.org/packages/christhompsontldr/laravel-fsm)
[![License](https://img.shields.io/packagist/l/christhompsontldr/laravel-fsm.svg?style=flat-square)](https://packagist.org/packages/christhompsontldr/laravel-fsm)
[![PHP Version](https://img.shields.io/packagist/php-v/christhompsontldr/laravel-fsm.svg?style=flat-square)](https://packagist.org/packages/christhompsontldr/laravel-fsm)

[![Coverage Status](https://coveralls.io/repos/github/ChrisThompsonTLDR/laravel-fsm/badge.svg?branch=main)](https://coveralls.io/github/ChrisThompsonTLDR/laravel-fsm?branch=main)

[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red.svg?style=flat-square)](https://laravel.com)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-brightgreen.svg?style=flat-square)](https://phpstan.org)
[![Pint](https://img.shields.io/badge/Code%20Style-Laravel%20Pint-ff69b4.svg?style=flat-square)](https://laravel.com/docs/pint)

A robust, plug-and-play Finite State Machine (FSM) package for Laravel applications with advanced features like guards, actions, callbacks, state machines, and comprehensive logging.

## Features

- 🚀 **Zero-config setup** - Works out of the box with sensible defaults
- 🔒 **Guards** - Control transitions with custom validation logic
- ⚡ **Actions** - Execute code when transitioning between states
- 📝 **Callbacks** - Hook into state entry/exit events
- 🔄 **Event-driven** - Integrates with Laravel's event system
- 📊 **Logging** - Comprehensive transition logging for audit trails
- 🎯 **State validation** - Prevent invalid state transitions
- 🔧 **Extensible** - Easy to customize and extend
- 🎨 **Fluent API** - Clean, expressive syntax
- 📚 **Multiple FSM support** - Define different state machines per model column
- ⚡ **Performance** - Caching and optimization built-in

## What It Does

This package allows you to manage state transitions in your Laravel applications with ease. Define states, transitions, and business logic that governs how your models can change state.

Perfect for:
- Order workflows (pending → paid → shipped → delivered)
- User verification flows (unverified → pending → verified)
- Content moderation (draft → review → published)
- Issue tracking (open → in-progress → resolved)
- And any other stateful business process!

## Installation

You can install the package via composer:

```bash
composer require christhompsontldr/laravel-fsm
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Fsm\FsmServiceProvider" --tag="fsm-config"
```

This will create a `config/fsm.php` file where you can customize:

```php
<?php

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
    | Event Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure FSM event logging for auditability and state replay.
    |
    */
    'event_logging' => [
        'enabled' => true,
        'queue' => false,
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
        'enabled' => true,
        'log_failures' => false,
    ],
];
```

## Usage

### 1. Describe your states

```php
<?php

namespace App\Fsm\Enums;

use Fsm\Contracts\FsmStateEnum;

enum OrderStatus: string implements FsmStateEnum
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
```

### 2. Register a definition

Place definition classes under `app/Fsm` (the service provider discovers them automatically) and implement `FsmDefinition` using the fluent `FsmBuilder` API.

```php
<?php

namespace App\Fsm\Definitions;

use App\Fsm\Enums\OrderStatus;
use App\Models\Order;
use Fsm\Contracts\FsmDefinition;
use Fsm\FsmBuilder;

class OrderStatusFsm implements FsmDefinition
{
    public function define(): void
    {
        FsmBuilder::for(Order::class, 'status')
            ->initialState(OrderStatus::Pending)
            ->state(OrderStatus::Pending)
            ->state(OrderStatus::Paid)
            ->state(OrderStatus::Shipped)
            ->state(OrderStatus::Delivered, fn ($state) => $state->isTerminal(true))
            ->state(OrderStatus::Cancelled)
            ->from(OrderStatus::Pending)->to(OrderStatus::Paid)->event('pay')
            ->from(OrderStatus::Paid)->to(OrderStatus::Shipped)->event('ship')
            ->from(OrderStatus::Shipped)->to(OrderStatus::Delivered)->event('deliver')
            ->from([OrderStatus::Pending, OrderStatus::Paid])->to(OrderStatus::Cancelled)->event('cancel')
            ->build();
    }
}
```

### 3. Add the trait to your model

```php
<?php

namespace App\Models;

use Fsm\Traits\HasFsm;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFsm;

    protected $fillable = ['status', 'amount'];
}
```

### 4. Drive the workflow

```php
$order = Order::create(['status' => OrderStatus::Pending->value]);

// Trigger transitions by event name (the FSM resolves the target state for you)
$order->fsm()->trigger('pay');
$order->fsm()->trigger('ship');

// Check or dry-run transitions without mutating state
if ($order->fsm()->can('deliver')) {
    $order->fsm()->trigger('deliver');
}

$preview = $order->fsm()->dryRun('cancel');
// ['can_transition' => true, 'from_state' => 'delivered', 'to_state' => 'cancelled', ...]

// Work directly with states
$order->getFsmState();                  // -> App\Fsm\Enums\OrderStatus
$order->transitionFsm('status', OrderStatus::Cancelled); // bypass event shortcuts
```

### Guards, actions, callbacks & queues

The fluent API exposes rich hooks for guards, synchronous/queued actions, and state entry/exit callbacks:

```php
FsmBuilder::for(Order::class, 'status')
    ->initialState(OrderStatus::Pending)
    ->state(OrderStatus::Paid, fn ($state) => $state
        ->onEntry([SendReceipt::class, 'handle'])
        ->metadata(['color' => 'blue'])
    )
    ->from(OrderStatus::Pending)->to(OrderStatus::Paid)
        ->event('pay')
        ->guard([EnsurePaymentAuthorized::class, '__invoke'])
        ->action([RecordPaymentMetrics::class, '__invoke'])
    ->transition()
        ->from(\Fsm\Constants::STATE_WILDCARD)
        ->to(OrderStatus::Cancelled)
        ->event('force_cancel')
        ->queuedAction(\App\Jobs\NotifyOpsJob::class)
        ->add()
    ->build();
```

### Multiple FSMs on the same model

Call `FsmBuilder::for()` with different column names to maintain independent workflows:

```php
FsmBuilder::for(Document::class, 'approval_status')
    ->initialState('draft')
    ->from('draft')->to('review')->event('submit')
    ->from('review')->to('approved')->event('approve')
    ->from('review')->to('rejected')->event('reject')
    ->build();

FsmBuilder::for(Document::class, 'publication_status')
    ->initialState('unpublished')
    ->from('unpublished')->to('published')->event('publish')
    ->from('published')->to('archived')->event('archive')
    ->build();
```

Use `HasFsm` helpers to address the appropriate column:

```php
$document->fsm('approval_status')->trigger('approve');
$document->fsm('publication_status')->trigger('publish');
```

### Observe transition events

```php
use Fsm\Events\StateTransitioned;

Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    Log::info(sprintf(
        '%s %s transitioned from %s to %s on %s',
        $event->getModel()::class,
        $event->getModel()->getKey(),
        $event->getFromState(),
        $event->getToState(),
        $event->getColumn()
    ));
});
```

### Customizing the FsmLog Model

By default, the `FsmLog` model uses UUIDs for its primary key via Laravel's `HasUuids` trait. If you need to customize the primary key type (e.g., to use auto-incrementing integers or ULIDs instead), you have two options:

### Option 1: Copy the Model (Recommended for changing traits)

Since PHP doesn't allow removing parent traits in child classes, and adding a different key-generation trait (like `HasUlids`) would conflict with the parent's `HasUuids`, the cleanest approach is to copy the entire model into your application:

```php
<?php

namespace App\Models;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Concerns\HasUlids; // or remove for auto-increment IDs
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FsmLog extends Model
{
    use HasUlids; // Change to HasUlids, or remove entirely for auto-increment IDs

    // Copy all properties and methods from Fsm\Models\FsmLog
    // See: https://github.com/ChrisThompsonTLDR/laravel-fsm/blob/main/src/Fsm/Models/FsmLog.php
    
    public $timestamps = false;
    protected $table = 'fsm_logs';
    protected $fillable = ['id', 'subject_id', 'subject_type', 'model_id', 'model_type', 'fsm_column', 'from_state', 'to_state', 'transition_event', 'context_snapshot', 'exception_details', 'duration_ms', 'happened_at'];
    protected $casts = ['happened_at' => 'immutable_datetime', 'context_snapshot' => 'array', 'exception_details' => 'string', 'duration_ms' => 'integer'];

    public function subject(): MorphTo { return $this->morphTo(); }
    public function model(): MorphTo { return $this->morphTo(); }
    
    // Include the booted() method and other methods from the original model
}
```

### Option 2: Extend and Override Key Methods

If you prefer to extend the package model and only need to override key generation behavior without changing traits:

```php
<?php

namespace App\Models;

use Fsm\Models\FsmLog as BaseFsmLog;

class FsmLog extends BaseFsmLog
{
    /**
     * Indicates if the model uses UUID/ULID keys.
     */
    public function getIncrementing(): bool
    {
        return true; // Set to true for auto-increment IDs
    }

    /**
     * Get the auto-increment key type.
     */
    public function getKeyType(): string
    {
        return 'int'; // Change to 'int' for auto-increment IDs
    }
    
    // Add any additional customizations here
}
```

### Configure the Package

Whichever option you choose, configure the package to use your custom model in `config/fsm.php`:

```php
return [
    // ... other configuration ...

    'models' => [
        'fsm_log' => \App\Models\FsmLog::class,
    ],
];
```

**Note:** If you change the primary key type, you'll need to create a migration to modify the `fsm_logs` table structure accordingly. The default migration uses `uuid('id')` for the primary key.


## Commands

### Generate FSM Diagram

Generate PlantUML (default) or DOT diagrams for every registered FSM:

```bash
# Write PlantUML files into storage/app/fsm-diagrams
php artisan fsm:diagram

# Export DOT files to a custom directory
php artisan fsm:diagram storage/app/fsm-diagrams --format=dot
```

Each definition produces a `<Model>_<column>.puml` (or `.dot`) file that you can render with PlantUML/Graphviz.

### Clear FSM Cache

Clear the FSM definition cache:

```bash
php artisan fsm:cache:clear
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
