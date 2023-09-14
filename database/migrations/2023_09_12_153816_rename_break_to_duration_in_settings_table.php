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
        Schema::table('settings', function (Blueprint $table) {
			$table->dropColumn('cal_break');
			$table
				->tinyInteger('duration')
				->after('user_id')
				->default(50)
				->comment('Session duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
			$table->dropColumn('duration');
			$table
				->tinyInteger('cal_break')
				->after('cal_slot')
				->default(10)
				->comment('Break time between two appointments');
        });
    }
};
