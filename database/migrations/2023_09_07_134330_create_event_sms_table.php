<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_sms', function (Blueprint $table) {
			$table->bigInteger('event_id')->unsigned();
			$table->string('sms_id', 50);
			$table->string('action', config('project.event_action_max_length'))->nullable();
			$table->string('provider', 10);
			$table->string('country', 2);
			$table->string('currency', 3);
			$table->tinyInteger('parts');
			$table->smallInteger('cost');
			$table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_sms');
    }
};
