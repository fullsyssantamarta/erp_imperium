<?php

namespace Modules\Report\Http\Controllers;

use App\Models\Tenant\Catalogs\DocumentType;
use Modules\Factcolombia1\Models\Tenant\TypeDocument;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use Modules\Report\Exports\GeneralItemExport;
use Illuminate\Http\Request;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Document;
use App\Models\Tenant\PurchaseItem;
use App\Models\Tenant\DocumentItem;
use App\Models\Tenant\Company;
use Carbon\Carbon;
use Modules\Report\Http\Resources\GeneralItemCollection;
use Modules\Report\Traits\ReportTrait;
use App\Models\Tenant\ConfigurationPos;
use App\Models\Tenant\DocumentPosItem;
use Modules\Sale\Models\Remission;
use Modules\Sale\Models\RemissionItem;


class ReportGeneralItemController extends Controller
{
    use ReportTrait;

    public function __construct()
    {
    }

    public function filter(Request $request) {
        $type = $request->input('type', 'sale'); // por defecto 'sale'
        $document_types = [];
        $resolutions = [];

        if ($type == 'sale') {
            $document_types = TypeDocument::where('name', '!=', 'Factura Electronica de venta')->get();
        } elseif ($type == 'pos') {
            $document_types = ConfigurationPos::all(); // para POS, las resoluciones son los tipos
        } elseif ($type == 'remission') {
            $document_types = Remission::query()
                ->select('prefix')
                ->distinct()
                ->get()
                ->map(function($row) {
                    return [
                        'prefix' => $row->prefix,
                        'description' => 'Remisión',
                    ];
                });
        }
        // Puedes agregar más tipos aquí (ej: nómina, etc.)

        $establishments = Establishment::all()->transform(function($row) {
            return [
                'id' => $row->id,
                'name' => $row->description
            ];
        });

        return compact('document_types', 'establishments');
    }

    public function index() {

        return view('report::general_items.index');
    }

    public function records(Request $request)
    {
        $records = $this->getRecordsItems($request->all());
        return new GeneralItemCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function getRecordsItems($request){
        $data_of_period = $this->getDataOfPeriod($request);
        $data_type = $this->getDataType($request);
        $document_type_id = $request['document_type_id'];
        $d_start = $data_of_period['d_start'];
        $d_end = $data_of_period['d_end'];
        $establishment_id = $request['establishment_id'];
        $records = $this->dataItems($d_start, $d_end, $document_type_id, $data_type, $request, $establishment_id);
        return $records;
    }

    private function dataItems($date_start, $date_end, $document_type_id, $data_type, $request, $establishment_id)
    {
        if($request['type'] == 'remission'){
            $prefix = $document_type_id;
            $data = $data_type['model']::whereHas($data_type['relation'], function($query) use($date_start, $date_end, $prefix, $establishment_id){
                $query->whereBetween('date_of_issue', [$date_start, $date_end]);
                if ($prefix) $query->where('prefix', $prefix);
                if ($establishment_id) $query->where('establishment_id', $establishment_id);
                $query->latest()->whereTypeUser();
            });
        }else if($request['type'] == 'pos'){
            // Para POS, filtra por prefijo (document_type_id será el prefijo)
            $prefix = $document_type_id;
            $data = $data_type['model']::whereHas($data_type['relation'], function($query) use($date_start, $date_end, $prefix, $establishment_id){
                $query->whereBetween('date_of_issue', [$date_start, $date_end]);
                if ($prefix) $query->where('prefix', $prefix);
                if ($establishment_id) $query->where('establishment_id', $establishment_id);
                $query->latest()->whereTypeUser();
            });
            
        } else{
            if($document_type_id === null)
                $document_type_ids = range(1, 1000);
            else
                $document_type_ids = [$document_type_id];

            if($establishment_id === null)
                $establishment_ids = range(1, 100);
            else
                $establishment_ids = [$establishment_id];

            $model = $data_type['model'];
            $relation = $data_type['relation'];
            if($request['type'] == 'sale'){
                $data = $model::whereHas($relation, function($query) use($date_start, $date_end, $document_type_ids, $establishment_ids){
                                    $query
                                    ->whereBetween('date_of_issue', [$date_start, $date_end])
                                    ->whereIn('type_document_id', $document_type_ids)
                                    ->whereIn('establishment_id', $establishment_ids)
                                    ->latest()
                                    ->whereTypeUser();
                                });
            }
            else{
                $data = $model::whereHas($relation, function($query) use($date_start, $date_end, $document_type_ids, $establishment_ids){
                                    $query
                                    ->whereBetween('date_of_issue', [$date_start, $date_end])
                                    ->whereIn('establishment_id', $establishment_ids)
                                    ->latest()
                                    ->whereTypeUser();
                                });
            }
        }
        return $data;
    }

    private function getDataType($request){
        if($request['type'] == 'sale'){
            return ['model' => DocumentItem::class, 'relation' => 'document'];
        } elseif($request['type'] == 'pos'){
            return ['model' => DocumentPosItem::class, 'relation' => 'document_pos'];
        } elseif($request['type'] == 'remission'){
            return ['model' => RemissionItem::class, 'relation' => 'remission'];
        } else{
            return ['model' => PurchaseItem::class, 'relation' => 'purchase'];
        }
    }

    public function excel(Request $request) {

        $records = $this->getRecordsItems($request->all())->get();
        $type = ($request->type == 'sale') ? 'Ventas_':'Compras_';

        return (new GeneralItemExport)
                ->records($records)
                ->type($request->type)
                ->download('Reporte_General_Productos_'.$type.Carbon::now().'.xlsx');

    }
}
