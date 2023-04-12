<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
			$table->tinyInteger('category')->comment('1=National healthcare, 2=other');
			$table->string('code', 20)->unique();
            $table->string('firstname', 50);
            $table->string('lastname', 50);
            $table->string('email')->nullable()->unique();
			$table->integer('phone_country_id')->nullable();
			$table->string('phone_number', 20)->nullable();
			$table->string('address_line1', 100);
			$table->string('address_line2', 100)->nullable();
			$table->string('address_line3', 100)->nullable();
			$table->string('address_code', 20);
			$table->string('address_city', 50);
			$table->integer('address_country_id');
			$table->timestamp('created_at')->useCurrent();
			$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patients');
    }
}
