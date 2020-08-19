<?php

use Mockery as m;

class EnhancedSchedulerTestCase extends PHPUnit_Framework_TestCase {

  public function tearDown() {
    m::close();
  }

}