<?php namespace Myriad\Illuminate\Console\Scheduling\Locks;

use Myriad\Illuminate\Console\Scheduling\EnhancedEvent;

abstract class DistributedLock {

  protected $config;
  protected $grace;
  protected $release;

  public function __construct($config) {
    $config = array_merge([
      'grace' => 40,
      'release' => 86400
    ], $config);
    $this->config = $config;
    $this->grace = $config['grace'];
    $this->release = $config['release'];
  }

  abstract function lock($task, EnhancedEvent $event);

  abstract function unlock($task, EnhancedEvent $event);

}