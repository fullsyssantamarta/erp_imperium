<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailyAccountBalances extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_account_balances', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('account_id');
            $table->date('date');
            $table->decimal('balance', 15, 2);
            $table->timestamps();

            $table->unique(['account_id', 'date']);
            $table->index(['account_id', 'date']);
            $table->foreign('account_id')->references('id')->on('chart_of_accounts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('daily_account_balances');
    }
}
