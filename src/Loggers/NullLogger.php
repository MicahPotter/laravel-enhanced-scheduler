<?php namespace Myriad\Illuminate\Console\Scheduling\Loggers;

use Myriad\Illuminate\Console\Scheduling\EnhancedEvent;

class NullLogger extends TaskLogger {

  public function log(EnhancedEvent $event, $output = null) {
    // do nothing
  }

}