<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Distributed Locking
  |--------------------------------------------------------------------------
  |
  | Supported: "null", "database"
  |
  */

  'distributed' => [

    'default' => env('SCHEDULING_DISTRIBUTED_DEFAULT', 'database'),

    'connections' => [

      'database' => [
        'driver' => 'database',
        'table' => 'scheduled_task_locks',
        'connection' => null
      ],

      'grace' => 40,

      'release' => 86400,

    ],

  ],

  /*
  |--------------------------------------------------------------------------
  | Log Scheduled Tasks
  |--------------------------------------------------------------------------
  |
  | Supported: "null", "database"
  |
  */

  'log' => [

    'default' => env('SCHEDULING_LOG_DEFAULT', 'database'),

    'stores' => [

      'database' => [
        'driver' => 'database',
        'table' => 'scheduled_task_logs',
        'connection' => null,
      ],

    ],

  ],

];