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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
			$table->bigInteger('user_id')->unsigned();
			$table->bigInteger('patient_id')->unsigned()->nullable();
			$table->tinyInteger('category')->comment('1=RDV, 2=private, 9=Locked');
			$table->string('title', 100)->nullable();
			$table->boolean('allday');
			$table->timestamp('start')->nullable();
			$table->timestamp('end')->nullable();
			$table->time('duration')->nullable();
			$table->string('rrule_dtstart', 25)->nullable();
			$table->date('rrule_until')->nullable();
			$table->string('rrule_freq', 10)->nullable();
			$table->string('rrule_byweekday', 40)->nullable()->comment('Comma separated short day-names: Monday=mo, Tuesday=tu ... Sunday=su');
			$table->tinyInteger('status')->comment('1=OK, 2=Cancelled');
			$table->timestamp('created_at')->useCurrent();
			$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
