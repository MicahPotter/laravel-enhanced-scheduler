<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScheduledTaskLogsTable extends Migration
{
    const TABLE = 'scheduled_task_logs';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->increments('id');
            $table->string('task')->nullable();
            $table->text('expression');
            $table->string('type', 16);
            $table->text('type_data');
            $table->text('configuration');
            $table->text('hostname');
            $table->string('status', 16);
            $table->longText('output')->nullable();
            $table->longText('error')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(self::TABLE);
    }
}
