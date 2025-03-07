<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommonPricesTable extends Migration
{
    public function up()
    {
        Schema::create('common_prices', function (Blueprint $table) {
            $table->id();
            $table->integer('state_id')->nullable();
            $table->string('gold_22k')->nullable();
            $table->string('gold_24k')->nullable();
            $table->string('petrol')->nullable();
            $table->string('diesel')->nullable();
            $table->string('cng')->nullable();
            $table->string('lpg')->nullable();
            $table->timestamp('today_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('common_prices');
    }
}
