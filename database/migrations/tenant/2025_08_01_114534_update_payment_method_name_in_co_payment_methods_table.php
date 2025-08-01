<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdatePaymentMethodNameInCoPaymentMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('co_payment_methods')
            ->where('name', 'Consiganción bancaria')
            ->update(['name' => 'Consignación bancaria']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('co_payment_methods')
            ->where('name', 'Consignación bancaria')
            ->update(['name' => 'Consiganción bancaria']);
    }
}
