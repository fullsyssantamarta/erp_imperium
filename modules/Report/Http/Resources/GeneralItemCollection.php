<?php

namespace Modules\Report\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class GeneralItemCollection extends ResourceCollection
{
     

    public function toArray($request) {
        

        return $this->collection->transform(function($row, $key){
            if ($row instanceof \App\Models\Tenant\DocumentPosItem) {
                return [
                    'id' => $row->id,
                    'unit_type_id' => $row->item->unit_type_id,
                    'internal_id' => $row->relation_item->internal_id,
                    'description' => $row->relation_item->name,
                    'date_of_issue' => $row->document_pos->date_of_issue->format('Y-m-d'),
                    'customer_name' => $row->document_pos->customer->name,
                    'customer_number' => $row->document_pos->customer->number,
                    'series' => $row->document_pos->series,
                    'alone_number' => $row->document_pos->number,
                    'quantity' => number_format($row->quantity,2),
                    'total' => number_format($row->total,2),
                    'document_type_description' => 'POS',
                    'document_type_id' => $row->document_pos->prefix,
                ];
            }
            if ($row instanceof \Modules\Sale\Models\RemissionItem) {
                return [
                    'id' => $row->id,
                    'unit_type_id' => $row->item->unit_type_id,
                    'internal_id' => $row->relation_item->internal_id,
                    'description' => $row->relation_item->name,
                    'date_of_issue' => $row->remission->date_of_issue->format('Y-m-d'),
                    'customer_name' => $row->remission->customer->name ?? '',
                    'customer_number' => $row->remission->customer->number ?? '',
                    'series' => $row->remission->series ?? '',
                    'alone_number' => $row->remission->number ?? '',
                    'quantity' => number_format($row->quantity,2),
                    'total' => number_format($row->total,2),
                    'document_type_description' => 'Remisión',
                    'document_type_id' => $row->remission->prefix,
                ];
            }                
            return [
                'id' => $row->id,
                'unit_type_id' => $row->item->unit_type_id,
                'internal_id' => $row->relation_item->internal_id,
                'description' => $row->relation_item->name,
                'date_of_issue' => $row->document ? $row->document->date_of_issue->format('Y-m-d'):$row->purchase->date_of_issue->format('Y-m-d'),
                'customer_name' => $row->document ? $row->document->customer->name:$row->purchase->supplier->name,
                'customer_number' => $row->document ? $row->document->customer->number:$row->purchase->supplier->number,
                'series' => $row->document ? $row->document->series: $row->purchase->series,
                'alone_number' => $row->document ? $row->document->number:$row->purchase->number,
                'quantity' => number_format($row->quantity,2),
                'total' => number_format($row->total,2),
                'document_type_description' => $row->document ? $row->document->type_document->name :$row->purchase->document_type->description,
                'document_type_id' => $row->document ? $row->document->document_type->id:$row->purchase->document_type->id,   
            ];
        });
    }
}
