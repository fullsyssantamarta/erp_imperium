<?php

namespace App\Http\Controllers\System;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use App\Models\System\Plan;
use App\Models\System\PlanDocument;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\System\PlanCollection;
use App\Http\Resources\System\PlanResource;
use App\Http\Requests\System\PlanRequest;
use Carbon\Carbon;
use Modules\Factcolombia1\Models\System\Company;

class PlanController extends Controller
{
    public function index()
    {
        return view('system.plans.index');
    }

    
    public function records()
    {
        $records = Plan::all();

        return new PlanCollection($records);
    }

    public function record($id)
    {
        $record = new PlanResource(Plan::findOrFail($id));

        return $record;
    }

    public function tables()
    {
        $plans = Plan::all();
        return compact('plans');
    }


    public function store(PlanRequest $request)
    {
        $id = $request->input('id');
        $plan = Plan::firstOrNew(['id' => $id]);
        $old_period = $plan->period;
        $plan->fill($request->all());
        $plan->save();

        if ($id && $old_period !== $plan->period) {
            $companies = Company::where('plan_id', $plan->id)->get();
            foreach ($companies as $company) {
                $now = Carbon::now();
                $company->plan_started_at = $now;
                $company->plan_expires_at = $plan->period === 'year'
                    ? $now->copy()->addYear()
                    : $now->copy()->addMonth();
                $company->save();
            }
        }

        return [
            'success' => true,
            'message' => ($id)?'Plan editado con éxito':'Plan registrado con éxito'
        ];
    }

    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->delete();

        return [
            'success' => true,
            'message' => 'Plan eliminado con éxito'
        ];
    }

}
