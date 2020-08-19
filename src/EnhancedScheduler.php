<?php namespace Myriad\Illuminate\Console\Scheduling;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;

/**
 * Class EnhancedScheduler
 *
 * @package Myriad\Illuminate\Scheduling
 * @property Application $app
 */
trait EnhancedScheduler {

  /**
   * Define the application's command schedule.
   *
   * @return void
   */
  protected function defineConsoleSchedule() {
    $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

    $this->schedule($schedule);
  }

  /**
   * Define the application's command schedule.
   *
   * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
   * @return void
   */
  protected function schedule(Schedule $schedule)
  {
    $schedule->command('inspire')->everyMinute();
  }

}