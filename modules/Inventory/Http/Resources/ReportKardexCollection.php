<?php

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Inventory\Models\InventoryTransaction;
use Modules\Inventory\Models\InventoryKardex;
use Modules\Inventory\Models\Warehouse;
use Modules\Sale\Models\Remission;

class ReportKardexCollection extends ResourceCollection
{

    protected static $balance = 0;
    protected static $restante = 0;
    protected static $re;

    public function toArray($request)
    {
      self::$re = $request;
      $this->calcularRestante(self::$re);

      return $this->collection->transform(function($row, $key) {
        return self::determinateRow($row);
      });
    }

    public static function determinateRow($row){

        $models = [
            "App\Models\Tenant\Document",
            "App\Models\Tenant\Purchase",
            "App\Models\Tenant\SaleNote",
            "Modules\Inventory\Models\Inventory",
            "Modules\Order\Models\OrderNote",
            "App\Models\Tenant\DocumentPos",
            Remission::class
        ];

        switch ($row->inventory_kardexable_type) {

            case $models[0]: //venta
                $isCreditNote = isset($row->inventory_kardexable->document_type_id) && $row->inventory_kardexable->document_type_id == 3;
                $isCreditNoteConcept = isset($row->inventory_kardexable->note_concept_id) && $row->inventory_kardexable->note_concept_id == 5;
                $isVoided = $isCreditNote && !$isCreditNoteConcept && $row->quantity > 0;
                $item_name = optional($row->item)->description ?? optional($row->item)->name;

                if (!$item_name && $row->inventory_kardexable) {
                    // Si el modelo relacionado tiene relación item
                    if (method_exists($row->inventory_kardexable, 'item')) {
                        $item_name = optional($row->inventory_kardexable->item)->description
                            ?? optional($row->inventory_kardexable->item)->name;
                    }
                    // Si el modelo relacionado tiene directamente el campo name o description
                    if (!$item_name) {
                        $item_name = $row->inventory_kardexable->description
                            ?? $row->inventory_kardexable->name
                            ?? null;
                    }
                }

                $item_name = $item_name ?? '-';
                $type_transaction = "Venta";
                if ($isCreditNote) {
                    if ($isCreditNoteConcept) {
                        $type_transaction = "Anulación Venta";
                    } else {
                        $type_transaction = "Devolución Venta";
                    }
                } elseif ($row->quantity > 0) {
                    $type_transaction = "Anulación Venta";
                }
                return [
                    'id' => $row->id,
                    'item_name' => $item_name,
                    'date_time' => $row->created_at->format('Y-m-d H:i:s'),
                    'date_of_issue' => isset($row->inventory_kardexable->date_of_issue) ? $row->inventory_kardexable->date_of_issue->format('Y-m-d') : '',
                    'type_transaction' => $type_transaction,
                    'number' => optional($row->inventory_kardexable)->series.'-'.optional($row->inventory_kardexable)->number,
                    'input' => ($row->quantity > 0) ?  $row->quantity:"-",
                    'output' => ($row->quantity < 0) ?  $row->quantity:"-",
                    'balance' => self::$balance+= $row->quantity,
                    'sale_note_asoc' => isset($row->inventory_kardexable->sale_note_id)  ? optional($row->inventory_kardexable)->sale_note->prefix.'-'.optional($row->inventory_kardexable)->sale_note->id:"-",
                    'warehouse_name' => $row->warehouse->description,
                ];

            case $models[1]:
                $item_name = optional($row->item)->description ?? optional($row->item)->name;

                if (!$item_name && $row->inventory_kardexable) {
                    // Si el modelo relacionado tiene relación item
                    if (method_exists($row->inventory_kardexable, 'item')) {
                        $item_name = optional($row->inventory_kardexable->item)->description
                            ?? optional($row->inventory_kardexable->item)->name;
                    }
                    // Si el modelo relacionado tiene directamente el campo name o description
                    if (!$item_name) {
                        $item_name = $row->inventory_kardexable->description
                            ?? $row->inventory_kardexable->name
                            ?? null;
                    }
                }

                $item_name = $item_name ?? '-';
                return [
                    'id' => $row->id,
                    'item_name' => $item_name,
                    'date_time' => $row->created_at->format('Y-m-d H:i:s'),
                    'date_of_issue' => isset($row->inventory_kardexable->date_of_issue) ? $row->inventory_kardexable->date_of_issue->format('Y-m-d') : '',
                    'type_transaction' => ($row->quantity < 0) ? "Anulación Compra":"Compra",
                    'number' => optional($row->inventory_kardexable)->series.'-'.optional($row->inventory_kardexable)->number,
                    'input' => ($row->quantity > 0) ?  $row->quantity:"-",
                    'output' => ($row->quantity < 0) ?  $row->quantity:"-",
                    'balance' => self::$balance+= $row->quantity,
                    'sale_note_asoc' => '-',
                    'warehouse_name' => $row->warehouse->description,
                ];

            case $models[2]: // Nota de venta
                $item_name = optional($row->item)->description ?? optional($row->item)->name;

                if (!$item_name && $row->inventory_kardexable) {
                    // Si el modelo relacionado tiene relación item
                    if (method_exists($row->inventory_kardexable, 'item')) {
                        $item_name = optional($row->inventory_kardexable->item)->description
                            ?? optional($row->inventory_kardexable->item)->name;
                    }
                    // Si el modelo relacionado tiene directamente el campo name o description
                    if (!$item_name) {
                        $item_name = $row->inventory_kardexable->description
                            ?? $row->inventory_kardexable->name
                            ?? null;
                    }
                }

                $item_name = $item_name ?? '-';
                return [
                    'id' => $row->id,
                    'item_name' => $item_name,
                    'date_time' => $row->created_at->format('Y-m-d H:i:s'),
                    'type_transaction' => "Nota de venta",
                    'date_of_issue' => isset($row->inventory_kardexable->date_of_issue) ? $row->inventory_kardexable->date_of_issue->format('Y-m-d') : '',
                    'number' => optional($row->inventory_kardexable)->series.'-'.optional($row->inventory_kardexable)->number,
                    'input' => ($row->quantity > 0) ?  $row->quantity:"-",
                    'output' => ($row->quantity < 0) ?  $row->quantity:"-",
                    'balance' => self::$balance+= $row->quantity,
                    'sale_note_asoc' => '-',
                    'warehouse_name' => $row->warehouse->description,
                ];

            case $models[3]:{
                $item_name = optional($row->item)->description ?? optional($row->item)->name;

                if (!$item_name && $row->inventory_kardexable) {
                    // Si el modelo relacionado tiene relación item
                    if (method_exists($row->inventory_kardexable, 'item')) {
                        $item_name = optional($row->inventory_kardexable->item)->description
                            ?? optional($row->inventory_kardexable->item)->name;
                    }
                    // Si el modelo relacionado tiene directamente el campo name o description
                    if (!$item_name) {
                        $item_name = $row->inventory_kardexable->description
                            ?? $row->inventory_kardexable->name
                            ?? null;
                    }
                }

                $item_name = $item_name ?? '-';

                $transaction = '';
                $input = '';
                $output = '';

                if(!$row->inventory_kardexable->type){
                    $transaction = InventoryTransaction::findOrFail($row->inventory_kardexable->inventory_transaction_id);
                }

                if($row->inventory_kardexable->type != null){
                    $input = ($row->inventory_kardexable->type == 1) ? $row->quantity : "-";
                }
                else{
                    $input = ($transaction->type == 'input') ? $row->quantity : "-" ;
                }

                if($row->inventory_kardexable->type != null){
                    $output = ($row->inventory_kardexable->type == 2 || $row->inventory_kardexable->type == 3) ? $row->quantity : "-";
                }
                else{
                    $output = ($transaction->type == 'output') ? $row->quantity : "-";
                }

                return [
                    'id' => $row->id,
                    'item_name' => $item_name,
                    'date_time' => $row->created_at->format('Y-m-d H:i:s'),
                    'date_of_issue' => '-',
                    'type_transaction' => $row->inventory_kardexable->description,
                    'number' => "-",
                    'input' => $input,
                    'output' => $output,
                    'balance' => self::$balance+= $row->quantity,
                    'sale_note_asoc' => '-',
                    'warehouse_name' => $row->warehouse->description,
                ];
            }

            case $models[4]:
                $item_name = optional($row->item)->description ?? optional($row->item)->name;

                if (!$item_name && $row->inventory_kardexable) {
                    // Si el modelo relacionado tiene relación item
                    if (method_exists($row->inventory_kardexable, 'item')) {
                        $item_name = optional($row->inventory_kardexable->item)->description
                            ?? optional($row->inventory_kardexable->item)->name;
                    }
                    // Si el modelo relacionado tiene directamente el campo name o description
                    if (!$item_name) {
                        $item_name = $row->inventory_kardexable->description
                            ?? $row->inventory_kardexable->name
                            ?? null;
                    }
                }

                $item_name = $item_name ?? '-';
                return [
                    'id' => $row->id,
                    'item_name' => $item_name,
                    'date_time' => $row->created_at->format('Y-m-d H:i:s'),
                    'date_of_issue' => isset($row->inventory_kardexable->date_of_issue) ? $row->inventory_kardexable->date_of_issue->format('Y-m-d') : '',
                    'type_transaction' => ($row->quantity < 0) ? "Pedido":"Anulación Pedido",
                    'number' => optional($row->inventory_kardexable)->prefix.'-'.optional($row->inventory_kardexable)->id,
                    'input' => ($row->quantity > 0) ?  $row->quantity:"-",
                    'output' => ($row->quantity < 0) ?  $row->quantity:"-",
                    'balance' => self::$balance+= $row->quantity,
                    'sale_note_asoc' => '-',
                    'warehouse_name' => $row->warehouse->description,
                ];

            case $models[5]:
                $item_name = optional($row->item)->description ?? optional($row->item)->name;

                if (!$item_name && $row->inventory_kardexable) {
                    // Si el modelo relacionado tiene relación item
                    if (method_exists($row->inventory_kardexable, 'item')) {
                        $item_name = optional($row->inventory_kardexable->item)->description
                            ?? optional($row->inventory_kardexable->item)->name;
                    }
                    // Si el modelo relacionado tiene directamente el campo name o description
                    if (!$item_name) {
                        $item_name = $row->inventory_kardexable->description
                            ?? $row->inventory_kardexable->name
                            ?? null;
                    }
                }

                $item_name = $item_name ?? '-';
                $isVoided = false;
                if (
                    isset($row->inventory_kardexable->state_type_id) &&
                    $row->inventory_kardexable->state_type_id == '11' &&
                    $row->quantity > 0
                ) {
                    $isVoided = true;
                }
                return [
                    'id' => $row->id,
                    'item_name' => $item_name,
                    'date_time' => $row->created_at->format('Y-m-d H:i:s'),
                    'date_of_issue' => isset($row->inventory_kardexable->date_of_issue) ? $row->inventory_kardexable->date_of_issue->format('Y-m-d') : '',
                    'type_transaction' => $isVoided ? "Anulación Venta POS" : "Venta POS",
                    'number' => optional($row->inventory_kardexable)->prefix.'-'.optional($row->inventory_kardexable)->number,
                    'input' => ($row->quantity > 0) ?  $row->quantity:"-",
                    'output' => ($row->quantity < 0) ?  $row->quantity:"-",
                    'balance' => self::$balance+= $row->quantity,
                    'sale_note_asoc' => '-',
                    'warehouse_name' => $row->warehouse->description,
                ];

            case $models[6]:
                $item_name = optional($row->item)->description ?? optional($row->item)->name;

                if (!$item_name && $row->inventory_kardexable) {
                    // Si el modelo relacionado tiene relación item
                    if (method_exists($row->inventory_kardexable, 'item')) {
                        $item_name = optional($row->inventory_kardexable->item)->description
                            ?? optional($row->inventory_kardexable->item)->name;
                    }
                    // Si el modelo relacionado tiene directamente el campo name o description
                    if (!$item_name) {
                        $item_name = $row->inventory_kardexable->description
                            ?? $row->inventory_kardexable->name
                            ?? null;
                    }
                }

                $item_name = $item_name ?? '-';
                $isVoided = false;
                if (
                    isset($row->inventory_kardexable->state_type_id) &&
                    $row->inventory_kardexable->state_type_id == '11' &&
                    $row->quantity > 0
                ) {
                    $isVoided = true;
                }
                return [
                    'id' => $row->id,
                    'item_name' => $item_name,
                    'date_time' => $row->created_at->format('Y-m-d H:i:s'),
                    'type_transaction' => $isVoided ? "Anulación de remisión" : "Remisión",
                    'date_of_issue' => isset($row->inventory_kardexable->date_of_issue) ? $row->inventory_kardexable->date_of_issue->format('Y-m-d') : '',
                    'number' => optional($row->inventory_kardexable)->number_full,
                    'input' => ($row->quantity > 0) ?  $row->quantity:"-",
                    'output' => ($row->quantity < 0) ?  $row->quantity:"-",
                    'balance' => self::$balance+= $row->quantity,
                    'sale_note_asoc' => '-',
                    'warehouse_name' => $row->warehouse->description,
                ];

        }

    }
    

    public function calcularRestante($request)
    {

      if($request->page >= 2) {

        $warehouse = Warehouse::where('establishment_id', auth()->user()->establishment_id)->first();

        if($request->date_start && $request->date_end) {
          $data = InventoryKardex::where([['warehouse_id', $warehouse->id],['item_id',$request->item_id]])
          ->whereBetween('date_of_issue', [$request->date_start, $request->date_end])
          ->limit(($request->page*20)-20)->get();
        } else {
          $data = InventoryKardex::where([['warehouse_id', $warehouse->id],['item_id',$request->item_id]])
          ->limit(($request->page*20)-20)->get();
        }

        for($i=0;$i<=count($data)-1;$i++) {
          self::$restante+=$data[$i]->quantity;
        }

        return self::$balance = self::$restante;

      }

    }
}
