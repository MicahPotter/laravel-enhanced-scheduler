# Laravel Enhanced Scheduler

## Install

### Add Myriad Repository

The package is distributed internally with Satis. First add the repository to `composer.json`:

```json
  "repositories": [
    {
      "type": "composer",
      "url": "https://satis.myriadmobile.com"
    }
  ]
```

### Require the Dependency

Require the package with composer using the following command:

```bash
composer require myriad/laravel-enhanced-scheduler
```

### Install Service Provider

After updating composer, add the service provider to the `providers` array in `config/app.php`:

```php
Myriad\Illuminate\Console\Scheduling\EnhancedSchedulerServiceProvider::class,
```

### Publish Migrations

Rather than creating your own migrations, you can publish the ones from the package:

```bash
php artisan vendor:publish --provider=myriad/laravel-enhanced-scheduler --tag=migrations
```

### Use The Trait

Finally, to make the scheduler actually use the enhanced scheduler, 
simply use the trait in `app/Console/Kernel.php`

```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Myriad\Illuminate\Console\Scheduling\EnhancedScheduler;

class Kernel extends ConsoleKernel {

  use EnhancedScheduler;
  
  ...
  
}
```

### Configuration

The enhanced scheduler is designed to work out of the box without additional configuration. 
Should you need to edit the configuration, publish the config file to the `config` directory:

```bash
php artisan vendor:publish --provider=myriad/laravel-enhanced-scheduler --tag=config
```

## Usage

Use the enhanced scheduler exactly as you would the Laravel scheduler.
Simply register your schedules in the `schedules` method!

New methods:

__`->withoutDistributedOverlapping($task)`__

Uses a distributed locking mechanism to ensure that the event is only run on one node in a cluster.
The `$task` should be a unique name to identify the entry across the cluster. e.g. `my-nightly-task`.

__`->withoutLog()`__

Use when you don't want an event logged.

__`->withoutLogOutput()`__

Use when you want to log an event, but not the output. This is useful for events
with very long output or when you just don't care.

__`->failOnNonZeroExit($task)`__

Use to ensure that a command exited cleanly. Only for use with commands.