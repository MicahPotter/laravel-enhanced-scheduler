<?php namespace Myriad\Illuminate\Console\Scheduling;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\Process\Process;

class EnhancedEvent extends Event {

  /**
   * The type of callback
   *
   * @var string
   */
  protected $type = 'command';

  protected $schedule;

  protected $withoutDistributedOverlapping = null;
  protected $withoutLog;
  protected $withoutLogOutput;
  protected $failOnNonZeroExit;

  protected $status;
  protected $error;
  protected $startedAt;
  protected $finishedAt;
  protected $exitCode;

  /**
   * Create a new event instance.
   *
   * @param EnhancedSchedule $schedule
   * @param  string $command
   */
  public function __construct(EnhancedSchedule $schedule, $command) {
    parent::__construct($command);
    $this->schedule = $schedule;
  }

  /**
   * Do not allow the event to overlap in a distributed
   * cluster. This will also prevent local event overlaps.
   *
   * @param String $task The unique name of the task
   *
   * @return $this
   */
  public function withoutDistributedOverlapping($task) {
    $this->withoutDistributedOverlapping = $task;
    return $this->skip(function() use($task) {
      return !$this->schedule->lock($task, $this);
    });
  }

  /**
   * Do not log the event.
   */
  public function withoutLog() {
    $this->withoutLog = true;
    return $this;
  }

  /**
   * Log the event, but do not include the output.
   */
  public function withoutLogOutput() {
    $this->withoutLogOutput = true;
    return $this;
  }

  /**
   * Cause the event to "fail" when the exit code is non-zero
   *
   * @return $this
   */
  public function failOnNonZeroExit() {
    $this->failOnNonZeroExit = true;
    return $this;
  }

  /**
   * Run the given event.
   *
   * @param  \Illuminate\Contracts\Container\Container $container
   * @return void
   */
  public function run(Container $container) {
    try {
      $this->setupOutputFile();
      $this->onStarted();
      $this->runCommandInForeground($container);
      $this->onCompleted();
    } catch (\Throwable $t) {
      $this->onFailed($t);
    }
  }

  /**
   * Run the command in the foreground.
   *
   * @param  \Illuminate\Contracts\Container\Container $container
   * @return void
   */
  protected function runCommandInForeground(Container $container) {
    $this->callBeforeCallbacks($container);

    $this->exitCode = (new Process(
      trim($this->buildCommand(), '& '), base_path(), null, null, null
    ))->run();

    if ($this->failOnNonZeroExit && $this->exitCode !== 0) {
      throw new \InvalidArgumentException("Failed with unexpected exit code: {$this->exitCode}");
    }

    $this->callAfterCallbacks($container);
  }

  /**
   * Called when the event is started
   */
  protected function onStarted() {
    $this->status = 'started';
    $this->startedAt = \Carbon\Carbon::now();
    if (!$this->withoutLog) {
      $this->schedule->log($this);
    }
  }

  /**
   * Called when the event has failed
   **
   *
   * @param \Throwable $t
   */
  protected function onFailed(\Throwable $t) {
    $this->status = 'failed';
    $this->error = $t;
    $this->onFinished();
  }

  /**
   * Called when the event has completed
   */
  protected function onCompleted() {
    $this->status = 'completed';
    $this->onFinished();
  }

  /**
   * Called when the event has finished (completed or failed)
   */
  protected function onFinished() {
    if ($this->withoutDistributedOverlapping) {
      $this->schedule->unlock($this->withoutDistributedOverlapping, $this);
    }

    $this->finishedAt = \Carbon\Carbon::now();
    if (!$this->withoutLog) {
      $this->schedule->log($this, !$this->withoutLogOutput ? $this->readAndUnlinkOutput() : null);
    }
  }

  /**
   * Create the temp output file if needed
   */
  protected function setupOutputFile() {
    if (!$this->withoutLog && !$this->withoutLogOutput && ($this->output == null || $this->output == $this->getDefaultOutput())) {
      $this->output = tempnam(sys_get_temp_dir(), 'scheduler-output');
    }
  }

  /**
   * Read the the output from the temp file and unlink it
   *
   * @return string
   */
  protected function readAndUnlinkOutput() {
    try {
      return file_get_contents($this->output);
    } catch (\Throwable $e) {
      return $e->getMessage();
    } finally {
      @unlink($this->output);
    }
  }

  /**
   * Get the type of event
   *
   * @return string
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Get the data associated with the type of event
   *
   * @return array
   */
  public function getTypeData() {
    return [
      'command' => $this->command,
    ];
  }

  /**
   * Get the configuration data
   *
   * @return array
   */
  public function getConfiguration() {
    return [
      'environments' => $this->environments,
      'evenInMaintenanceMode' => $this->evenInMaintenanceMode,
      'failOnNonZeroExit' => $this->failOnNonZeroExit,
      'timezone' => (string)$this->timezone,
      'user' => $this->user,
      'withoutDistributedOverlapping' => $this->withoutOverlapping,
      'withoutLog' => $this->withoutLog,
      'withoutLogOutput' => $this->withoutLogOutput,
      'withoutOverlapping' => $this->withoutOverlapping,
    ];
  }

  /**
   * Get the status of the event
   *
   * @return string one of: started, completed, failed
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Get the time the event was started
   *
   * @return Carbon
   */
  public function getStartedAt() {
    return $this->startedAt;
  }

  /**
   * Get the time the task was finished
   *
   * @return Carbon
   */
  public function getFinishedAt() {
    return $this->finishedAt;
  }

  /**
   * Get the error throwable
   *
   * @return null|\Throwable
   */
  public function getError() {
    return $this->error;
  }

}