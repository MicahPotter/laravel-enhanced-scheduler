<?php namespace Myriad\Illuminate\Console\Scheduling\Locks;

use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Myriad\Illuminate\Console\Scheduling\EnhancedEvent;

class DatabaseLock extends DistributedLock {

  private $manager;
  private $connection;
  private $table;
  private $locked = [];

  public function __construct($config, $connectionConfig, DatabaseManager $manager) {
    parent::__construct($config);

    $connectionConfig = array_merge([
      'table' => 'scheduled_task_locks',
      'connection' => null,
    ], $connectionConfig);

    $this->manager = $manager;
    $this->connection = $connectionConfig['connection'];
    $this->table = $connectionConfig['table'];
  }

  /**
   * @return \Illuminate\Database\Connection
   */
  public function getDatabase() {
    return $this->manager->connection($this->connection);
  }

  /**
   * @return \Illuminate\Database\Query\Builder
   */
  public function getQuery() {
    return $this->getDatabase()->table($this->table);
  }

  /**
   * Release any expired locks
   */
  public function releaseExpiredLocks() {
    $this->getQuery()
      ->whereNotNull('locked_at')
      ->where('lock_expires_at', '<', Carbon::now())
      ->where('grace_expires_at', '<', Carbon::now())
      ->update(['locked_at' => null, 'locked_by' => null]);
  }

  /**
   * Gain a lock for the event
   *
   * @param string $task
   * @param EnhancedEvent $event
   * @return bool true when a lock was obtained
   */
  public function lock($task, EnhancedEvent $event) {
    // assume we don't have a lock
    $locked = false;

    // release any expired locks
    $this->releaseExpiredLocks();

    // start a transaction, and lock the row for update for the given task
    $this->getDatabase()->beginTransaction();

    $lock = $this->getQuery()
      ->lockForUpdate()
      ->where('task', $task)
      ->first();

    // if a lock row doesn't exist, insert it
    if (!$lock) {
      $locked = $this->getQuery()->insert(array_merge($this->getAttributesArray(), ['task' => $task]));
    } else {
      // otherwise query again to ensure that the event is not locked and the grace period has expired
      $lock = $this->getQuery()
        ->lockForUpdate()
        ->where('task', $task)
        ->whereNull('locked_at')
        ->where('grace_expires_at', '<', Carbon::now())
        ->first();

      // if we got a row back, lock it
      if ($lock) {
        $locked = $this->getQuery()->where('task', '=', $task)->update($this->getAttributesArray());
      }
    }

    // commit the transaction, this will unlock any rows locked in the transaction
    $this->getDatabase()->commit();

    // if we got a lock, make note if it so we can unlock later
    if ($locked) {
      $this->locked[] = spl_object_hash($event);
    }

    return $locked;
  }

  /**
   * Unlock a previously locked event
   *
   * @param $task
   * @param EnhancedEvent $event
   * @return bool true when record was unlocked
   */
  public function unlock($task, EnhancedEvent $event) {
    $hash = spl_object_hash($event);
    if (in_array($hash, $this->locked)) {
      $this->getQuery()
        ->lockForUpdate()
        ->where('task', '=', $task)
        ->update(['locked_at' => null, 'locked_by' => null]);

      unset($this->locked[array_search($hash, $this->locked)]);
      return true;
    }
    return false;
  }

  /**
   * Get a list of attributes to update lock records with
   *
   * @return array
   */
  public function getAttributesArray() {
    return [
      'locked_by' => gethostname(),
      'locked_at' => Carbon::now(),
      'grace_expires_at' => Carbon::now()->addSeconds($this->grace),
      'lock_expires_at' => Carbon::now()->addSeconds($this->release),
    ];
  }

}