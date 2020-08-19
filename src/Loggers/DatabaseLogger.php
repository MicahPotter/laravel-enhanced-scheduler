<?php namespace Myriad\Illuminate\Console\Scheduling\Loggers;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Myriad\Illuminate\Console\Scheduling\EnhancedEvent;

class DatabaseLogger extends TaskLogger {

  private $loggedIds = [];
  private $manager;
  private $connection;
  private $table;

  public function __construct($config, $connectionConfig, DatabaseManager $manager) {
    parent::__construct($config);

    $connectionConfig = array_merge([
      'table' => 'scheduled_task_logs',
      'connection' => null,
    ], $connectionConfig);

    $this->manager = $manager;
    $this->connection = $connectionConfig['connection'];
    $this->table = $connectionConfig['table'];
  }

  public function log(EnhancedEvent $event, $output = null) {
    $hash = spl_object_hash($event);
    $id = Arr::get($this->loggedIds, $hash);

    $db = $this->manager->connection($this->connection);
    $query = $db->table($this->table);

    $values = [
      'task' => $event->getSummaryForDisplay(),
      'expression' => $event->expression,
      'type' => $event->getType(),
      'type_data' => json_encode($event->getTypeData()),
      'configuration' => json_encode($event->getConfiguration()),
      'hostname' => gethostname(),
      'status' => $event->getStatus(),
      'output' => $output,
      'error' => $event->getError() ? (string) $event->getError() : null,
      'started_at' => $event->getStartedAt(),
      'finished_at' => $event->getFinishedAt(),
    ];

    if (!$id) {
      $this->loggedIds[$hash] = $query->insertGetId($values);
    } else {
      $query->where('id', $id)->update($values);
    }
  }

}