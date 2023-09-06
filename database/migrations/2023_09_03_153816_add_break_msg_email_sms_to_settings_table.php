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
			$table
				->tinyInteger('cal_break')
				->after('cal_slot')
				->default(10)
				->comment('Break time between two appointments');
			$table
				->text('msg_email')
				->nullable()
				->after('cal_break');
			$table
				->string('msg_sms')
				->nullable()
				->after('msg_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
			$table->dropColumn('cal_break');
			$table->dropColumn('msg_email');
			$table->dropColumn('msg_sms');
        });
    }
};
