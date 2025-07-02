@php
    use Modules\Factcolombia1\Helpers\DocumentHelper;
@endphp
<table class="">
    <thead>
        <tr>
            <th colspan="5"></th>
            
            @foreach($taxes as $tax)
                <th colspan="2">
                    IMPUESTO #{{ $loop->iteration }}
                    <br>
                    {{ $tax->name }} - ({{ $tax->rate }}%)
                </th>
            @endforeach
        </tr>
        <tr>
            <th>Fact. Inicial</th>
            <th>Fact. Final</th>

            <th>Total/Neto</th>
            <th>Total <br>+<br> Impuesto</th>
            <th>Total/Excento</th>
            
            @foreach($taxes as $tax)
                <th>Base</th>
                <th>Impuesto</th>
            @endforeach
        </tr>
    </thead>
    <tbody>

        @php
            $global_total_exempt = 0;
        @endphp

        @foreach($summary_records as $record)
            @php
                $net_total = 0;
                $total = 0;
                $total_exempt = 0;
                $first_document = $record['first_document'];
                $ordered_documents = $record['ordered_documents'];
               
                foreach ($ordered_documents as $document)
                {
                    $row = $document->getDataReportSalesBook();
                    $is_credit_note = stripos($row['type_document_name'], 'crédit') !== false || 
                                    ($document instanceof \App\Models\Tenant\DocumentPos && isset($row['state_type_id']) && $row['state_type_id'] === '11');
                    
                    $multiplier = $is_credit_note ? -1 : 1;
                    
                    $net_total += floatval(str_replace(',', '', $row['net_total'])) * $multiplier;
                    $total += floatval(str_replace(',', '', $row['total'])) * $multiplier;
                    $total_exempt += floatval(str_replace(',', '', $row['total_exempt'])) * $multiplier;
                }

                $global_total_exempt += $total_exempt;
                $first_row = $first_document->getDataReportSalesBook();
            @endphp

            <tr>
                <td class="celda">{{ $first_row['type_document_name'] }} <br/> {{ $record['prefix'] }}-{{$record['first_document']->number}}</td>
                <td class="celda">{{ $first_row['type_document_name'] }} <br/> {{ $record['prefix'] }}-{{$record['last_document']->number}}</td>

                {{-- TOTALES --}}
                <td class="celda text-right-td">{{ number_format($net_total, 2, '.', '') }}</td>
                <td class="celda text-right-td">{{ number_format($total, 2, '.', '') }}</td>
                <td class="celda text-right-td">{{ number_format($total_exempt, 2, '.', '') }}</td>
                
                {{-- IMPUESTOS --}}
                @foreach($taxes as &$tax)
                    @php
                        $sum_taxable_amount = 0;
                        $sum_tax_amount = 0;

                        foreach ($ordered_documents as $document)
                        {
                            $row = $document->getDataReportSalesBook();
                            $item_values = $document->getItemValuesByTax($tax->id);
                            $is_credit_note = stripos($row['type_document_name'], 'crédit') !== false || 
                                            ($document instanceof \App\Models\Tenant\DocumentPos && isset($row['state_type_id']) && $row['state_type_id'] === '11');
                            
                            $multiplier = $is_credit_note ? -1 : 1;
                            
                            $sum_taxable_amount += floatval(str_replace(',', '', $item_values['taxable_amount'])) * $multiplier;
                            $sum_tax_amount += floatval(str_replace(',', '', $item_values['tax_amount'])) * $multiplier;
                        }

                        $tax->global_taxable_amount += $sum_taxable_amount;
                        $tax->global_tax_amount += $sum_tax_amount;
                    @endphp
                    
                    <td class="celda text-right-td">{{ number_format($sum_taxable_amount, 2, '.', '') }}</td>
                    <td class="celda text-right-td">{{ number_format($sum_tax_amount, 2, '.', '') }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

<br><br>

<table style="width: 60% !important">
    <thead>
        <tr>
            <th></th>
            <th>BASE</th>
            <th>IMPUESTO</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="celda">TOTAL VENTAS EXENTAS</td>
            <td class="celda text-right-td">{{ DocumentHelper::applyNumberFormat($global_total_exempt) }}</td>
            <td class="celda text-right-td">0.00</td>
        </tr>

        @foreach($taxes as $tax)
            <tr>
                <td class="celda">
                    TOTAL VENTAS IMPUESTO #{{ $loop->iteration }}
                    {{-- <br> --}}
                    - {{ $tax->name }} ({{ $tax->rate }}%)
                </td>
                <td class="celda text-right-td">{{ DocumentHelper::applyNumberFormat($tax->global_taxable_amount) }}</td>
                <td class="celda text-right-td">{{ DocumentHelper::applyNumberFormat($tax->global_tax_amount) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>