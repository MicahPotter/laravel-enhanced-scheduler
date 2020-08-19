<?php namespace Myriad\Illuminate\Console\Scheduling;

use Illuminate\Console\Scheduling\Schedule;
use Myriad\Illuminate\Console\Scheduling\Locks\DistributedLock;
use Myriad\Illuminate\Console\Scheduling\Loggers\TaskLogger;

class EnhancedSchedule extends Schedule {

  protected $logger;
  protected $locker;

  public function __construct(TaskLogger $logger, DistributedLock $locker) {
    $this->logger = $logger;
    $this->locker = $locker;
  }

  /**
   * Add a new callback event to the schedule.
   *
   * @param  string $callback
   * @param  array $parameters
   * @return \Myriad\Illuminate\Console\Scheduling\EnhancedEvent
   */
  public function call($callback, array $parameters = []) {
    $this->events[] = $event = new EnhancedCallbackEvent($this, $callback, $parameters);

    return $event;
  }

  /**
   * Add a new command event to the schedule.
   *
   * @param  string $command
   * @param  array $parameters
   * @return \Myriad\Illuminate\Console\Scheduling\EnhancedEvent
   */
  public function exec($command, array $parameters = []) {
    if (count($parameters)) {
      $command .= ' ' . $this->compileParameters($parameters);
    }

    $this->events[] = $event = new EnhancedEvent($this, $command);

    return $event;
  }

  public function log(EnhancedEvent $event, $output = null) {
    $this->logger->log($event, $output);
  }

  public function lock($task, EnhancedEvent $event) {
    return $this->locker->lock($task, $event);
  }

  public function unlock($task, EnhancedEvent $event) {
    return $this->locker->unlock($task, $event);
  }

}