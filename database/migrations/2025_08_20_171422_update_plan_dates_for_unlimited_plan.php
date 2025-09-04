<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdatePlanDatesForUnlimitedPlan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $now = Carbon::now()->toDateString();
        $farFuture = Carbon::now()->addYears(100)->toDateString();

        DB::table('co_companies')
            ->where('plan_id', 1)
            ->update([
                'plan_started_at' => $now,
                'plan_expires_at' => $farFuture,
                'plan_active' => true
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('co_companies')
            ->where('plan_id', 1)
            ->update([
                'plan_started_at' => null,
                'plan_expires_at' => null,
                'plan_active' => true
            ]);
    }
}
