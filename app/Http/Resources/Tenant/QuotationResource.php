<?php

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Quotation;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $quotation = Quotation::find($this->id);
        $quotation->payments = self::getTransformPayments($quotation->payments);
        $has_document = (count($this->documents) > 0 || count($this->sale_notes) > 0)?true:false;

        $quotation->items = $quotation->items->map(function($item) {
            $itemArray = $item->toArray();
            if (isset($item->item) && is_object($item->item) && property_exists($item->item, 'is_set')) {
                $itemArray['is_set'] = (bool) $item->item->is_set;
            } else {
                $itemArray['is_set'] = isset($item->is_set) ? (bool) $item->is_set : false;
            }
            return $itemArray;
        });

        return [
            'id' => $this->id,
            'external_id' => $this->external_id,  
            'identifier' => $this->identifier,
            'date_of_issue' => $this->date_of_issue->format('Y-m-d'), 
            'has_document' => $has_document,
            'quotation' => $quotation
        ];
    }

    
    public static function getTransformPayments($payments){
        
        return $payments->transform(function($row, $key){ 
            return [
                'id' => $row->id, 
                'quotation_id' => $row->quotation_id, 
                'date_of_payment' => $row->date_of_payment->format('Y-m-d'), 
                'payment_method_type_id' => $row->payment_method_type_id, 
                'has_card' => $row->has_card, 
                'card_brand_id' => $row->card_brand_id, 
                'reference' => $row->reference, 
                'payment' => $row->payment, 
                'payment_method_type' => $row->payment_method_type, 
                'payment_destination_id' => ($row->global_payment) ? ($row->global_payment->type_record == 'cash' ? 'cash':$row->global_payment->destination_id):null, 
            ];
        }); 

    }

}
