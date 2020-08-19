<?php namespace Myriad\Illuminate\Console\Scheduling;

use LogicException;
use InvalidArgumentException;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class EnhancedCallbackEvent extends EnhancedEvent {

  /**
   * The type of callback
   * 
   * @var string
   */
  protected $type = 'callback';

  /**
   * The callback to call.
   *
   * @var string
   */
  protected $callback;

  /**
   * The parameters to pass to the method.
   *
   * @var array
   */
  protected $parameters;

  /**
   * Holds the result of the callback
   *
   * @var mixed
   */
  private $result;

  /**
   * Create a new event instance.
   *
   * @param  string $callback
   * @param  array $parameters
   *
   * @throws \InvalidArgumentException
   */
  public function __construct(EnhancedSchedule $schedule, $callback, array $parameters = []) {
    parent::__construct($schedule, null);


    $this->callback = $callback;
    $this->parameters = $parameters;

    if (!is_string($this->callback) && !is_callable($this->callback)) {
      throw new InvalidArgumentException(
        'Invalid scheduled callback event. Must be string or callable.'
      );
    }
  }

  /**
   * Run the given event.
   *
   * @param  \Illuminate\Contracts\Container\Container $container
   * @return mixed
   *
   * @throws \Exception
   */
  public function run(Container $container) {
    try {
      $this->onStarted();
      $this->result = $this->runInternal($container);
      $this->onCompleted();
      return $this->result;
    } catch (\Throwable $t) {
      $this->onFailed($t);
    }
    return null;
  }

  /**
   * The normal laravel implementation
   *
   * @param Container $container
   * @return mixed
   */
  private function runInternal(Container $container) {
    if ($this->description) {
      touch($this->mutexPath());
    }

    try {
      $response = $container->call($this->callback, $this->parameters);
    } finally {
      $this->removeMutex();
    }

    parent::callAfterCallbacks($container);

    return $response;
  }


  /**
   * Override setup output file to avoid creating an output file
   */
  protected function setupOutputFile() {
    // do nothing
  }

  /**
   * Get the output from the callback result
   *
   * @return mixed
   */
  protected function readAndUnlinkOutput() {
    return $this->result;
  }

  /**
   * Remove the mutex file from disk.
   *
   * @return void
   */
  protected function removeMutex() {
    if ($this->description) {
      @unlink($this->mutexPath());
    }
  }

  /**
   * Do not allow the event to overlap each other.
   *
   * @return $this
   *
   * @throws \LogicException
   */
  public function withoutOverlapping() {
    if (!isset($this->description)) {
      throw new LogicException(
        "A scheduled event name is required to prevent overlapping. Use the 'name' method before 'withoutOverlapping'."
      );
    }

    return $this->skip(function() {
      return file_exists($this->mutexPath());
    });
  }

  /**
   * Get the mutex path for the scheduled command.
   *
   * @return string
   */
  protected function mutexPath() {
    return storage_path('framework/schedule-' . sha1($this->description));
  }

  /**
   * Get the summary of the event for display.
   *
   * @return string
   */
  public function getSummaryForDisplay() {
    if (is_string($this->description)) {
      return $this->description;
    }

    return is_string($this->callback) ? $this->callback : 'Closure';
  }

  /**
   * Get the data associated with the type of event
   *
   * @return array
   */
  public function getTypeData() {
    return (new VarCloner())->cloneVar($this->callback)->getRawData();
  }

}