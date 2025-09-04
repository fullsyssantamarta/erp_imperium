<?php

namespace App\Console\Commands;

use App\Models\System\Plan;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Factcolombia1\Models\System\Company;

class RenewCompanyPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'companies:renew-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renueva automáticamente los planes de las empresas con auto-renovación activa';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $today = Carbon::today();
        $companies = Company::where('plan_auto_renew', true)
            ->where('plan_expires_at', '<=', $today)
            ->get();

        foreach ($companies as $company) {
            $plan = Plan::find($company->plan_id);
            if ($plan) {
                $company->plan_started_at = $today;
                $company->plan_expires_at = $plan->period === 'year'
                    ? $today->copy()->addYear()
                    : $today->copy()->addMonth();
                $company->plan_active = true;
                $company->save();
                $this->info("Plan renovado para la empresa: {$company->name}");
            }
        }
    }
}
