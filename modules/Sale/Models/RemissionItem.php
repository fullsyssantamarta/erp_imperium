<?php

namespace Modules\Sale\Models;

use App\Models\Tenant\Item;
use Illuminate\Support\Facades\DB;
use Modules\Factcolombia1\Models\Tenant\TypeUnit;
use App\Models\Tenant\ModelTenant;

class RemissionItem extends ModelTenant
{

    protected $table = 'co_remission_items';
    
    public $timestamps = false;

    protected $fillable = [

        'remission_id',
        'item_id',
        'item',
        'unit_type_id',
        'tax_id',
        'tax',
        'total_tax',
        'subtotal',
        'discount',
        'quantity', 
        'unit_price',
        'total',

    ];


    protected $casts = [
        'tax' => 'object'
    ];

    
    public function unit_type()
    {
        return $this->belongsTo(TypeUnit::class, 'unit_type_id');
    }

    public function getItemAttribute($value)
    {
        return (is_null($value))?null:(object) json_decode($value);
    }

    public function setItemAttribute($value)
    {
        $this->attributes['item'] = (is_null($value))?null:json_encode($value);
    }

    public function remission()
    {
        return $this->belongsTo(Remission::class, 'remission_id');
    }
    
    public function relation_item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function scopeFilterReportSoldItems($query, $request)
    {
        $brand_id = $request->brand_id ?? null;
        $item_id = $request->item_id ?? null;

        return $query->with([
                    'remission' => function($query){
                        return $query->select([
                            'id',
                            'prefix',
                            'number',
                            'customer_id',
                            'user_id',
                            'date_of_issue',
                            'establishment_id'
                        ]);
                    },
                    'relation_item' => function($query){
                        return $query->select([
                            'id',
                            'purchase_unit_price'
                        ]);
                    },
                ])
                ->filterSoldItemsRemission($request)
                ->filterByItem($item_id)
                ->filterByBrand($brand_id)
                ->latest('id');
    }

    /**
     * Filtros específicos para remisiones
     */
    public function scopeFilterSoldItemsRemission($query, $request)
    {
        $customer_id = $request->customer_id ?? null;
        $user_id = $request->user_id ?? null;
        $start_date = $request->start_date ?? null;
        $end_date = $request->end_date ?? null;
        $start_time = $request->start_time ?? null;
        $end_time = $request->end_time ?? null;
        $establishment_id = $request->establishment_id ?? null;

        return $query->whereHas('remission', function($remission) use ($customer_id, $user_id, $start_date, $end_date, $start_time, $end_time, $establishment_id){
            $remission->where('state_type_id', '01'); // Solo remisiones activas
            if($customer_id) $remission->where('customer_id', $customer_id);
            if($user_id) $remission->where('user_id', $user_id);
            if($start_date) $remission->whereDate('date_of_issue', '>=', $start_date);
            if($end_date) $remission->whereDate('date_of_issue', '<=', $end_date);
            if($start_time) $remission->whereTime('created_at', '>=', $start_time);
            if($end_time) $remission->whereTime('created_at', '<=', $end_time);
            if($establishment_id) $remission->where('establishment_id', $establishment_id);
        });
    }

    /**
     * Filtrar por producto
     */
    public function scopeFilterByItem($query, $item_id)
    {
        if($item_id) $query->where('item_id', $item_id);
        return $query;
    }

    /**
     * Filtrar por marca
     */
    public function scopeFilterByBrand($query, $brand_id)
    {
        if($brand_id)
        {
            $query->whereHas('relation_item', function($query) use($brand_id){
                return $query->where('brand_id', $brand_id);
            });
        }
        return $query;
    }

    public function getDataReportSoldItems()
    {
        $cost = $this->relation_item && isset($this->relation_item->purchase_unit_price)
            ? $this->quantity * $this->relation_item->purchase_unit_price
            : 0;

        $net_value = $this->quantity * $this->unit_price;

        return [
            'type_name'     => 'Remisión',
            'internal_id'   => $this->item->internal_id ?? null,
            'name'          => $this->item->name ?? null,
            'quantity'      => (float) $this->quantity,
            'cost'          => (float) $cost,
            'net_value'     => (float) $net_value,
            'discount'      => $this->discount,
            'utility'       => (float) ($net_value - $cost),
            'total_tax'     => $this->total_tax,
            'total'         => $this->total,
        ];
    }
}