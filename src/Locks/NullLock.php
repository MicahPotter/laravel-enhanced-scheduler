<?php namespace Myriad\Illuminate\Console\Scheduling\Locks;

use Myriad\Illuminate\Console\Scheduling\EnhancedEvent;

class NullLock extends DistributedLock {

  function lock($task, EnhancedEvent $event) {
    return true;
  }

  function unlock($task, EnhancedEvent $event) {
    return true;
  }

}