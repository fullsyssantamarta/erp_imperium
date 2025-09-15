<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>REPORTE PRODUCTOS</title>
    </head>
    <body> 
        @if(!empty($records))
            <div class="">
                <div class=" "> 
                    <table class="">
                        <thead>
                            <tr>
                                <th class="">FECHA DE EMISIÓN</th>
                                <th class="">TIPO DOCUMENTO</th>
                                <th class="">SERIE</th> 
                                <th class="">NÚMERO</th> 
                                <th class="">CLIENTE/PROVEEDOR</th> 
                                <th class="">MONEDA</th> 
                                <th class="">UNIDAD DE MEDIDA</th> 
                                <th class="">CÓDIGO INTERNO</th> 
                                <th class="">NOMBRE</th> 
                                <th class="">CANTIDAD</th> 
                                <th class="">PRECIO UNITARIO</th> 
                                <th class="">TOTAL</th>  
                            </tr>
                        </thead>
                        <tbody>
                            @if($type == 'sale')
                                @foreach($records as $item)
                                    <tr>
                                        <td>{{ optional($item->document)->date_of_issue }}</td>
                                        <td>{{ optional($item->document->type_document)->name }}</td>
                                        <td>{{ optional($item->document)->series }}</td>
                                        <td>{{ optional($item->document)->number }}</td>
                                        <td>{{ optional($item->document->customer)->number }} - {{ optional($item->document->customer)->name }}</td>
                                        <td>{{ optional($item->document)->currency_type_id }}</td>
                                        <td>{{ optional($item->relation_item->unit_type)->name }}</td>
                                        <td>{{ optional($item->relation_item)->internal_id }}</td>
                                        <td>{{ optional($item->relation_item)->name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->unit_price }}</td>
                                        <td>{{ $item->total }}</td>
                                    </tr>
                                @endforeach
                            @elseif($type == 'purchase')
                                @foreach($records as $item)
                                    <tr>
                                        <td>{{ optional($item->purchase)->date_of_issue }}</td>
                                        <td>{{ optional($item->purchase->document_type)->description }}</td>
                                        <td>{{ optional($item->purchase)->series }}</td>
                                        <td>{{ optional($item->purchase)->number }}</td>
                                        <td>{{ optional($item->purchase->supplier)->name }} - {{ optional($item->purchase->supplier)->number }}</td>
                                        <td>{{ optional($item->purchase)->currency_type_id }}</td>
                                        <td>{{ optional($item->relation_item->unit_type)->name }}</td>
                                        <td>{{ optional($item->relation_item)->internal_id }}</td>
                                        <td>{{ optional($item->relation_item)->name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->unit_price }}</td>
                                        <td>{{ $item->total }}</td>
                                    </tr>
                                @endforeach
                            @elseif($type == 'pos')
                                @foreach($records as $item)
                                    <tr>
                                        <td>{{ optional($item->document_pos)->date_of_issue }}</td>
                                        <td>POS</td>
                                        <td>{{ optional($item->document_pos)->series }}</td>
                                        <td>{{ optional($item->document_pos)->number }}</td>
                                        <td>{{ optional($item->document_pos->customer)->number }} - {{ optional($item->document_pos->customer)->name }}</td>
                                        <td>{{ optional($item->document_pos)->currency_type_id }}</td>
                                        <td>{{ optional($item->relation_item->unit_type)->name }}</td>
                                        <td>{{ optional($item->relation_item)->internal_id }}</td>
                                        <td>{{ optional($item->relation_item)->name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->unit_price }}</td>
                                        <td>{{ $item->total }}</td>
                                    </tr>
                                @endforeach
                            @elseif($type == 'remission')
                                @foreach($records as $item)
                                    <tr>
                                        <td>{{ optional($item->remission)->date_of_issue }}</td>
                                        <td>Remisión</td>
                                        <td>{{ optional($item->remission)->series }}</td>
                                        <td>{{ optional($item->remission)->number }}</td>
                                        <td>{{ optional($item->remission->customer)->number }} - {{ optional($item->remission->customer)->name }}</td>
                                        <td>{{ optional($item->remission)->currency_type_id }}</td>
                                        <td>{{ optional($item->relation_item->unit_type)->name }}</td>
                                        <td>{{ optional($item->relation_item)->internal_id }}</td>
                                        <td>{{ optional($item->relation_item)->name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->unit_price }}</td>
                                        <td>{{ $item->total }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div>
                <p>No se encontraron registros.</p>
            </div>
        @endif
    </body>
</html>
