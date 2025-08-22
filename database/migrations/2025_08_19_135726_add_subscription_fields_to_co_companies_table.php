<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubscriptionFieldsToCoCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('co_companies', function (Blueprint $table) {
            $table->date('plan_started_at')->nullable()->after('plan_id');
            $table->date('plan_expires_at')->nullable()->after('plan_started_at');
            $table->boolean('plan_auto_renew')->default(false)->after('plan_expires_at');
            $table->boolean('plan_active')->default(true)->after('plan_auto_renew');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('co_companies', function (Blueprint $table) {
            $table->dropColumn(['plan_started_at', 'plan_expires_at', 'plan_auto_renew', 'plan_active']);
        });
    }
}
