<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use Modules\Inventory\Exports\KardexExport;
use Illuminate\Http\Request;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Company;
use App\Models\Tenant\Kardex;
use App\Models\Tenant\Item;
use Carbon\Carbon;
use Modules\Inventory\Models\InventoryKardex;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Http\Resources\ReportKardexCollection;
use Modules\Inventory\Http\Resources\ReportKardexLotsCollection;

use Modules\Inventory\Models\ItemWarehouse;
use Modules\Item\Models\ItemLotsGroup;
use Modules\Item\Models\ItemLot;

use Modules\Inventory\Http\Resources\ReportKardexLotsGroupCollection;
use Modules\Inventory\Http\Resources\ReportKardexItemLotCollection;
use Modules\Sale\Models\Remission;


class ReportKardexController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    protected $models = [
        "App\Models\Tenant\Document",
        "App\Models\Tenant\Purchase",
        "App\Models\Tenant\SaleNote",
        "Modules\Inventory\Models\Inventory",
        "Modules\Order\Models\OrderNote",
        "App\Models\Tenant\DocumentPos",
        Remission::class
    ];

    public function index() {


        return view('inventory::reports.kardex.index');
    }


    public function filter(Request $request) {
        $warehouse_id = $request->warehouse_id ?? auth()->user()->warehouse_id; // Usar el almacén del usuario por defecto

        $items = Item::query()
            ->whereHas('warehouses', function ($query) use ($warehouse_id) {
                $query->where('warehouse_id', $warehouse_id); // Filtrar productos por almacén
            })
            ->whereNotIsSet()
            ->where([['item_type_id', '01'], ['unit_type_id', '!=', 'ZZ']])
            ->latest()
            ->limit(20)
            ->get()
            ->transform(function ($row) {
                $full_description = $this->getFullDescription($row);
                return [
                    'id' => $row->id,
                    'full_description' => $full_description,
                    'internal_id' => $row->internal_id,
                    'description' => $row->description,
                ];
            });

        return compact('items');
    }


    public function records(Request $request)
    {

        $records = $this->getRecords($request->all());

        return new ReportKardexCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function records_lots()
    {
        $records = ItemWarehouse::with(['item'])->whereHas('item',function($q){
            $q->where([['item_type_id', '01'], ['unit_type_id', '!=','ZZ'], ['lot_code', '!=', null]]);
            $q->whereNotIsSet();
        });

        return new ReportKardexLotsCollection($records->paginate(config('tenant.items_per_page')));

    }



    public function getRecords($request){

        $item_id = $request['item_id'];
        $date_start = $request['date_start'];
        $date_end = $request['date_end'];
        $movement_type = $request['movement_type'] ?? null; // nuevo filtro
        $warehouse_id = $request['warehouse_id'] ?? null; // nuevo filtro
        $today = $request['today'] ?? false;

        $records = $this->data($item_id, $date_start, $date_end, $movement_type, $warehouse_id, $today);

        return $records;

    }


    private function data($item_id, $date_start, $date_end, $movement_type=null, $warehouse_id=null, $today = false)
    {

        $warehouse_id = $warehouse_id ?? auth()->user()->warehouse_id;
        // $warehouse = Warehouse::where('establishment_id', auth()->user()->establishment_id)->first();

        $data = InventoryKardex::with(['inventory_kardexable', 'item'])
            ->where('warehouse_id', $warehouse_id);

        if ($today == 'true' || $today === true || $today === 1 || $today === '1') {
            $data = $data->whereDate('date_of_issue', Carbon::today());
        } elseif ($date_start && $date_end) {
            $data = $data->whereBetween('date_of_issue', [$date_start, $date_end]);
        }

        if($item_id){
            $data = $data->where('item_id', $item_id);
        }

        if ($movement_type) {
        // Aquí filtras por el tipo de modelo relacionado
            $data = $data->where('inventory_kardexable_type', $movement_type);
        }

        $data = $data->orderBy('item_id')->orderBy('id');


        // if($date_start && $date_end){

        //     $data = InventoryKardex::with(['inventory_kardexable'])
        //                 ->where([['item_id', $item_id],['warehouse_id', $warehouse->id]])
        //                 ->whereBetween('date_of_issue', [$date_start, $date_end])
        //                 ->orderBy('id');

        // }else{

        //     $data = InventoryKardex::with(['inventory_kardexable'])
        //                 ->where([['item_id', $item_id],['warehouse_id', $warehouse->id]])
        //                 ->orderBy('id');
        // }

        return $data;

    }

    public function recordsToday(Request $request)
    {
        $warehouse_id = $request->warehouse_id ?? auth()->user()->warehouse_id; // Usar el almacén del usuario por defecto

        $records = InventoryKardex::with(['inventory_kardexable', 'item'])
            ->where('warehouse_id', $warehouse_id)
            ->whereDate('date_of_issue', Carbon::today()) // Filtrar por la fecha de hoy
            ->orderBy('item_id')
            ->orderBy('id')
            ->paginate(config('tenant.items_per_page'));

        return new ReportKardexCollection($records);
    }



    public function getFullDescription($row){

        $desc = ($row->internal_id)?$row->internal_id.' - '.$row->name : $row->name;
        $category = ($row->category) ? " - {$row->category->name}" : "";
        $brand = ($row->brand) ? " - {$row->brand->name}" : "";

        $desc = "{$desc} {$category} {$brand}";

        return $desc;
    }



    /**
     * PDF
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function pdf(Request $request) {

        // dd($request->all());

        $balance = 0;
        $company = Company::first();
        $establishment = Establishment::first();
        $d = $request->date_start;
        $a = $request->date_end;
        $item_id = $request->item_id;
        $movement_type = $request->movement_type ?? null;
        $warehouse_id = $request->warehouse_id ?? auth()->user()->warehouse_id;
        $today = $request->today == 'true' || $request->today === true; // NUEVO
        $status = $request->status ?? null;

        // Usar los mismos filtros que en getRecords/data
        $query = InventoryKardex::with(['inventory_kardexable', 'item'])
            ->where('warehouse_id', $warehouse_id);

        if ($today) {
            $query = $query->whereDate('date_of_issue', Carbon::today());
        } elseif ($d && $a) {
            $query = $query->whereBetween('date_of_issue', [$d, $a]);
        }

        if ($item_id) {
            $query = $query->where('item_id', $item_id);
        }

        if ($movement_type) {
            $query = $query->where('inventory_kardexable_type', $movement_type);
        }

        $reports = $query->orderBy('item_id')->orderBy('id')->get();

        $models = $this->models;

        $pdf = PDF::loadView('inventory::reports.kardex.report_pdf', compact("reports", "company", "establishment", "balance","models", 'a', 'd',"item_id"));
        $filename = 'Reporte_Kardex'.date('YmdHis');

        return $pdf->download($filename.'.pdf');
    }

    /**
     * Excel
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function excel(Request $request) {

        $balance = 0;
        $company = Company::first();
        $establishment = Establishment::first();
        $d = $request->date_start;
        $a = $request->date_end;
        $item_id = $request->item_id;
        $movement_type = $request->movement_type ?? null;
        $warehouse_id = $request->warehouse_id ?? auth()->user()->warehouse_id;
        $today = $request->today == 'true' || $request->today === true;

        // Usar los mismos filtros que en PDF
        $query = InventoryKardex::with(['inventory_kardexable', 'item'])
            ->where('warehouse_id', $warehouse_id);

        if ($today) {
            $query = $query->whereDate('date_of_issue', Carbon::today());
        } elseif ($d && $a) {
            $query = $query->whereBetween('date_of_issue', [$d, $a]);
        }

        if ($item_id) {
            $query = $query->where('item_id', $item_id);
        }

        if ($movement_type) {
            $query = $query->where('inventory_kardexable_type', $movement_type);
        }

        $records = $query->orderBy('item_id')->orderBy('id')->get();

        $models = $this->models;

        return (new KardexExport)
            ->balance($balance)
            ->item_id($item_id)
            ->records($records)
            ->models($models)
            ->company($company)
            ->establishment($establishment)
            ->download('ReporteKar'.Carbon::now().'.xlsx');
    }

    public function getRecords2($request){

        $item_id = $request['item_id'];
        $date_start = $request['date_start'];
        $date_end = $request['date_end'];
        $warehouse_id = $request['warehouse_id'] ?? null;

        $records = $this->data2($item_id, $date_start, $date_end, $warehouse_id);

        return $records;

    }


    private function data2($item_id, $date_start, $date_end , $warehouse_id=null)
    {
        // Si hay almacén, obtener los item_id de ese almacén
        $item_ids = null;
        if ($warehouse_id) {
            $item_ids = ItemWarehouse::where('warehouse_id', $warehouse_id)->pluck('item_id');
        }

        $query = ItemLotsGroup::query();

        if ($date_start && $date_end) {
            $query->whereBetween('date_of_due', [$date_start, $date_end]);
        }

        // Si se seleccionó un producto, filtra solo por ese producto
        if ($item_id) {
            $query->where('item_id', $item_id);
        } elseif ($item_ids) {
            // Si no se seleccionó producto, pero sí almacén, filtra por los productos de ese almacén
            $query->whereIn('item_id', $item_ids);
        }

        $query->orderBy('item_id')->orderBy('id');

        return $query;
    }

    public function records_lots_kardex(Request $request)
    {
        $records = $this->getRecords2($request->all());

        return new ReportKardexLotsGroupCollection($records->paginate(config('tenant.items_per_page')));


    }


    public function getRecords3($request){

        $item_id = $request['item_id'];
        $date_start = $request['date_start'];
        $date_end = $request['date_end'];
        $warehouse_id = $request['warehouse_id'] ?? null;
        $status = $request['status'] ?? null;

        $records = $this->data3($item_id, $date_start, $date_end, $warehouse_id, $status);

        return $records;

    }


    private function data3($item_id, $date_start, $date_end, $warehouse_id=null, $status=null)
    {

       // $warehouse = Warehouse::where('establishment_id', auth()->user()->establishment_id)->first();

        $query = ItemLot::query();

        if ($date_start && $date_end) {
            $query->whereBetween('date', [$date_start, $date_end]);
        }

        if ($item_id) {
            $query->where('item_id', $item_id);
        }

        if ($warehouse_id) {
            $query->where('warehouse_id', $warehouse_id);
        }

        if ($status) {
            if ($status == 'disponible') {
                $query->where('has_sale', false)->where('state', 'Activo');
            } elseif ($status == 'vendido') {
                $query->where('has_sale', true)->where('state', 'Activo');
            } elseif ($status == 'no_disponible') {
                $query->where('has_sale', true)->where('state', 'Inactivo');
            }
        }

        

        $query->orderBy('item_id')->orderBy('id');

        return $query;

    }

    public function records_series_kardex(Request $request)
    {

        $records = $this->getRecords3($request->all());

        return new ReportKardexItemLotCollection($records->paginate(config('tenant.items_per_page')));

        /*$records = [];

        if($item)
        {
            $records  =  ItemLot::where('item_id', $item)->get();

        }
        else{
            $records  = ItemLot::all();
        }

       // $records  =  ItemLot::all();
        return new ReportKardexItemLotCollection($records);*/

    }




    // public function search(Request $request) {
    //     //return $request->item_selected;
    //     $balance = 0;
    //     $d = $request->d;
    //     $a = $request->a;
    //     $item_selected = $request->item_selected;

    //     $items = Item::query()->whereNotIsSet()
    //         ->where([['item_type_id', '01'], ['unit_type_id', '!=','ZZ']])
    //         ->latest()
    //         ->get();

    //     $warehouse = Warehouse::where('establishment_id', auth()->user()->establishment_id)->first();

    //     if($d && $a){

    //         $reports = InventoryKardex::with(['inventory_kardexable'])
    //                     ->where([['item_id', $request->item_selected],['warehouse_id', $warehouse->id]])
    //                     ->whereBetween('date_of_issue', [$d, $a])
    //                     ->orderBy('id')
    //                     ->paginate(config('tenant.items_per_page'));

    //     }else{

    //         $reports = InventoryKardex::with(['inventory_kardexable'])
    //                     ->where([['item_id', $request->item_selected],['warehouse_id', $warehouse->id]])
    //                     ->orderBy('id')
    //                     ->paginate(config('tenant.items_per_page'));

    //     }

    //     //return json_encode($reports);

    //     $models = $this->models;

    //     return view('inventory::reports.kardex.index', compact('items', 'reports', 'balance','models', 'a', 'd','item_selected'));
    // }

}
