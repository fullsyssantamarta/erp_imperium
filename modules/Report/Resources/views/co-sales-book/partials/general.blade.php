<table class="combined-table">
    <thead>
        <tr>
            <th colspan="6">Información Básica</th>
            <th colspan="{{ 6 + ($taxes->count() * 2) }}">Detalles Financieros</th>
        </tr>
        <tr>
            <th>FECHA</th>
            <th>TIPO DOC</th>
            <th>PREFIJO</th>
            <th>IDENTIFICACIÓN</th>
            <th>NOMBRE</th>
            <th>DIRECCIÓN</th>
            <th>Total/Excento</th>
            <th>Descuento</th>
            @foreach($taxes as $tax)
                <th>Base {{ str_contains($tax->name, '19') ? '19%' : (str_contains($tax->name, '5') ? '5%' : $tax->name) }}</th>
            @endforeach
            <th>Impuestos</th>
            @foreach($taxes as $tax)
                <th>{{ $tax->name }}</th>
            @endforeach
            <th>IVA Total</th>
            <th>Base + Impuesto</th>
            <th>Total pagar</th>
        </tr>
    </thead>
    <tbody>
        @php
            $total = 0;
            $net_total = 0;
            $total_exempt = 0;
            $total_discount = 0;
            $total_tax_base = 0;
            $total_tax_amount = 0;
            $tax_totals_by_type = [];
            $base_totals_by_type = [];
            foreach($taxes as $tax) {
                $tax_totals_by_type[$tax->id] = 0; 
                $base_totals_by_type[$tax->id] = 0;
            }
        @endphp

        @foreach($records as $value)
            @php
                $row = $value->getDataReportSalesBook();
                $customer = $value->person;
                
                // Identificar notas de crédito y documentos POS anulados
                $is_credit_note = stripos($row['type_document_name'], 'crédit') !== false;
                $is_void_pos = $value instanceof \App\Models\Tenant\DocumentPos && isset($row['state_type_id']) && $row['state_type_id'] === '11';
                
                // Aplicar multiplicador según el caso
                $multiplier = $is_void_pos ? 0 : ($is_credit_note ? -1 : 1);
                
                // Solo sumar al total si no es un POS anulado
                if (!$is_void_pos) {
                    $total += floatval(str_replace(',', '', $row['total'])) * $multiplier;
                    $net_total += floatval(str_replace(',', '', $row['net_total'])) * $multiplier;
                    $total_exempt += floatval(str_replace(',', '', $row['total_exempt'])) * $multiplier;
                    $total_discount += floatval(str_replace(',', '', ($row['total_discount'] ?? 0))) * $multiplier;
                }

                // Obtener nombres de impuestos
                $tax_names = collect($value->items)
                    ->pluck('tax.name')
                    ->unique()
                    ->implode(', ');

                // Calcular totales de impuestos por documento
                $tax_totals = [
                    'base' => 0,
                    'tax' => 0
                ];
                
                if (!$is_void_pos) {
                    foreach($taxes as $tax) {
                        $item_values = $value->getItemValuesByTax($tax->id);
                        $base_totals_by_type[$tax->id] += floatval(str_replace(',', '', $item_values['taxable_amount'])) * $multiplier;
                        $tax_totals['tax'] += floatval(str_replace(',', '', $item_values['tax_amount'])) * $multiplier;
                    }
                    
                    $total_tax_base += $tax_totals['base'];
                    $total_tax_amount += $tax_totals['tax'];
                }
            @endphp
            <tr class="{{ $is_void_pos ? 'anulado' : ($is_credit_note ? 'credit-note' : '') }}">
                <td class="celda">{{ $row['date_of_issue'] }}</td>
                <td class="celda">{{ $row['type_document_name'] }}</td>
                <td class="celda">{{ $row['number_full'] }}</td>
                <td class="celda">{{ $customer ? $customer->number : ($row['customer_number'] ?? '') }}</td>
                <td class="celda">{{ $customer ? $customer->name : ($row['customer_name'] ?? '') }}</td>
                <td class="celda">{{ $customer ? $customer->address : ($row['customer_address'] ?? '') }}</td>
                <td class="celda text-right-td">{{ $is_void_pos ? '-' : number_format(floatval(str_replace(',', '', $row['total_exempt'])) * $multiplier, 2, '.', '') }}</td>
                <td class="celda text-right-td">{{ $is_void_pos ? '-' : number_format(floatval(str_replace(',', '', ($row['total_discount'] ?? 0))) * $multiplier, 2, '.', '') }}</td>
                @foreach($taxes as $tax)
                    @php
                        $item_values = $value->getItemValuesByTax($tax->id);
                        $base_amount = $is_void_pos ? 0 : (floatval(str_replace(',', '', $item_values['taxable_amount'])) * $multiplier);
                    @endphp
                    <td class="celda text-right-td">{{ $is_void_pos ? '-' : number_format($base_amount, 2, '.', '') }}</td>
                @endforeach
                <td class="celda">{{ $tax_names }}</td>
                @foreach($taxes as $tax)
                    @php
                        $item_values = $value->getItemValuesByTax($tax->id);
                        $tax_amount = $is_void_pos ? 0 : (floatval(str_replace(',', '', $item_values['tax_amount'])) * $multiplier);
                    @endphp
                    <td class="celda text-right-td">{{ $is_void_pos ? '-' : number_format($tax_amount, 2, '.', '') }}</td>
                @endforeach
                <td class="celda text-right-td">{{ $is_void_pos ? '-' : number_format($tax_totals['tax'], 2, '.', '') }}</td>
                <td class="celda text-right-td">{{ $is_void_pos ? '-' : number_format(floatval(str_replace(',', '', $row['net_total'])) * $multiplier + $tax_totals['tax'], 2, '.', '') }}</td>
                <td class="celda text-right-td">{{ $is_void_pos ? 'ANULADO' : number_format(floatval(str_replace(',', '', $row['total'])) * $multiplier, 2, '.', '') }}</td>
            </tr>
        @endforeach

        <tr>
            <th colspan="6" class="celda text-right-td">TOTALES</th>
            <th>{{ number_format($total_exempt, 2, '.', '') }}</th>
            <th>{{ number_format($total_discount, 2, '.', '') }}</th>
            @foreach($taxes as $tax)
                <th>{{ number_format($base_totals_by_type[$tax->id], 2, '.', '') }}</th>
            @endforeach
            <th></th>
            @foreach($taxes as $tax)
                <th>{{ number_format($tax_totals_by_type[$tax->id], 2, '.', '') }}</th>
            @endforeach
            <th>{{ number_format($total_tax_amount, 2, '.', '') }}</th>
            <th>{{ number_format($total_tax_base + $total_tax_amount, 2, '.', '') }}</th>
            <th>{{ number_format($total, 2, '.', '') }}</th>
        </tr>
    </tbody>
</table>
