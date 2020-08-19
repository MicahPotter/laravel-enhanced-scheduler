<?php namespace Myriad\Illuminate\Console\Scheduling;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use Myriad\Illuminate\Console\Scheduling\Locks\DatabaseLock;
use Myriad\Illuminate\Console\Scheduling\Locks\DistributedLock;
use Myriad\Illuminate\Console\Scheduling\Locks\NullLock;
use Myriad\Illuminate\Console\Scheduling\Loggers\DatabaseLogger;
use Myriad\Illuminate\Console\Scheduling\Loggers\NullLogger;
use Myriad\Illuminate\Console\Scheduling\Loggers\TaskLogger;

/**
 * Class EnhancedSchedulerServiceProvider
 *
 * @package Myriad\Illuminate\Console\Scheduling
 */
class EnhancedSchedulerServiceProvider extends ServiceProvider {

  /**
   * Perform post-registration booting of services.
   *
   * @return void
   */
  public function boot() {
    // publish config and migrations
    $this->publishes([__DIR__ . '/../database/migrations/' => database_path('migrations')], 'migrations');
    $this->publishes([__DIR__ . '/../config/scheduling.php' => config_path('scheduling.php')], 'config');
  }

  /**
   * Register the service provider.
   *
   * @return void
   */
  public function register() {
    // merge the config
    $this->mergeConfigFrom(__DIR__ . '/../config/scheduling.php', 'scheduling');

    $this->bindLocker();
    $this->bindLogger();

    // register the schedule
    $this->app->singleton(EnhancedSchedule::class);

    // alias the schedule as \Illuminate\Console\Scheduling\Schedule as expected by laravel
    $this->app->alias(EnhancedSchedule::class, \Illuminate\Console\Scheduling\Schedule::class);
  }

  public function bindLogger() {
    $this->app->bind(TaskLogger::class, function(Application $app) {
      $config = $this->app['config']->get('scheduling.log', []);
      $default = $this->app['config']->get('scheduling.log.default', 'database');
      $storeConfig = $this->app['config']->get("scheduling.log.stores.$default", ['driver' => strtolower($default) == 'null' ? 'null' : 'database']);

      switch (strtolower($storeConfig['driver'])) {
        case 'database':
          return new DatabaseLogger($config, $storeConfig, $this->app->make(DatabaseManager::class));
        case 'null':
          return new NullLogger($config);
      }
      return null;
    });
  }

  public function bindLocker() {
    $this->app->bind(DistributedLock::class, function(Application $app) {
      $config = $this->app['config']->get('scheduling.distributed', []);
      $default = $this->app['config']->get('scheduling.distributed.default', 'database');
      $connectionConfig = $this->app['config']->get("scheduling.distributed.connections.$default", ['driver' => strtolower($default) == 'null' ? 'null' : 'database']);

      switch (strtolower($connectionConfig['driver'])) {
        case 'database':
          return new DatabaseLock($config, $connectionConfig, $this->app->make(DatabaseManager::class));
        case 'null':
          return new NullLock($config);
      }
      return null;
    });
  }

  /**
   * Get the services provided by the provider.
   *
   * @return array
   */
  public function provides() {
    return [
      \Myriad\Illuminate\Console\Scheduling\EnhancedSchedule::class,
    ];
  }

}