<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInfectionmonitorDomestics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('infectionmonitor_domestics', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date')->comment('日付');
            $table->unsignedInteger('daily_infections')->comment('国内の感染者数_1日ごとの発表数');
            $table->unsignedInteger('sum_infections')->comment('国内の感染者数_累計');
            $table->unsignedInteger('daily_deaths')->comment('国内の死者数_1日ごとの発表数');
            $table->unsignedInteger('sum_deaths')->comment('国内の死者数_累計');
            $table->integer('created_id')->nullable();
            $table->string('created_name', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->integer('updated_id')->nullable();
            $table->string('updated_name', 255)->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('deleted_id')->nullable();
            $table->string('deleted_name', 255)->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('infectionmonitor_domestics');
    }
}
