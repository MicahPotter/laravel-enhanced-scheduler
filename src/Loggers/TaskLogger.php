<?php namespace Myriad\Illuminate\Console\Scheduling\Loggers;

use Myriad\Illuminate\Console\Scheduling\EnhancedEvent;

abstract class TaskLogger {

  private $config;

  public function __construct($config = []) {
    $this->config = $config;
  }

  public abstract function log(EnhancedEvent $event, $output = null);

}