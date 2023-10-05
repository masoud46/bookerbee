<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
			$table->bigInteger('user_id')->unsigned();
            $table->bigInteger('patient_id')->unsigned();
            $table->bigInteger('serial')->unsigned();
            $table->integer('session')->default(1);
            $table->string('name', 100);
            $table->string('acc_number')->nullable();
            $table->date('acc_date')->nullable();
            $table->string('doc_code', 20);
            $table->string('doc_name', 50)->nullable();
            $table->date('doc_date')->useCurrent();
			$table->integer('prepayment')->nullable();
            $table->date('granted_at')->nullable();
			$table->boolean('location_check')->nullable();
			$table->string('location_name', 100)->nullable();
			$table->string('location_address', 100)->nullable();
			$table->string('location_code', 10)->nullable();
			$table->string('location_city', 50)->nullable();
			$table->integer('location_country_id')->unsigned()->nullable();
			$table->boolean('active')->unsigned()->default(true);
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
        Schema::dropIfExists('invoices');
    }
}
