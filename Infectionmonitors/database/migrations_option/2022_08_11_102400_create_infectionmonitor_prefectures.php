<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInfectionmonitorPrefectures extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('infectionmonitor_prefectures', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date')->comment('日付');
            $table->unsignedTinyInteger('prefecture_code')->comment('都道府県コード');
            $table->string('prefecture_name', 255)->comment('都道府県名');
            $table->unsignedInteger('prefecture_daily_infections')->comment('各地の1日ごとの発表数');
            $table->unsignedInteger('prefecture_sum_infections')->comment('各地の死者数_累計');
            $table->unsignedInteger('prefecture_daily_deaths')->comment('各地の死者数_1日ごとの発表数');
            $table->unsignedInteger('prefecture_sum_deaths')->comment('各地の死者数_累計');
            $table->unsignedInteger('infected_per100000')->comment('各地の直近1週間の人口10万人あたりの感染者数');
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
        Schema::dropIfExists('infectionmonitor_prefectures');
    }
}
