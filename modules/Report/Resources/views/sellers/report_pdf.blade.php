<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Vendedor</title>
    <style>
        body { font-size: 13px; font-family: Arial, Helvetica, sans-serif; }
        .header-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #007bff;
            letter-spacing: 1px;
        }
        .info {
            margin-bottom: 15px;
            font-size: 14px;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px 4px; text-align: left; }
        th {
            background: #007bff;
            color: #fff;
            font-size: 13px;
            font-weight: bold;
        }
        tr:nth-child(even) { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="header-title">Reporte de Vendedor</div>
    <div class="info">
        @if($seller)
            <strong>Código interno:</strong> {{ $seller->internal_code ?? '-' }}<br>
            <strong>N° Documento:</strong> {{ $seller->document_number ?? '-' }}<br>
            <strong>Nombre:</strong> {{ $seller->full_name }}
        @else
            <strong>Todos los vendedores</strong>
        @endif
    </div>
    @php
$motivator = '';
if($seller && $seller->monthly_goal > 0) {
    $percent = $progress = isset($progress) ? $progress : (['total' => count($records), 'goal' => $seller->monthly_goal]);
    $percent = ($progress['goal'] > 0) ? ($progress['total'] / $progress['goal']) * 100 : 0;
    if ($percent == 0) $motivator = '¡Vamos, empieza a sumar documentos este mes!';
    elseif ($percent < 50) $motivator = '¡Buen inicio, sigue así para alcanzar la meta!';
    elseif ($percent < 80) $motivator = '¡Vas muy bien, la meta está cerca!';
    elseif ($percent < 100) $motivator = '¡Excelente trabajo, casi logras la meta!';
    else $motivator = '¡Felicidades, superaste tu meta mensual!';
}
@endphp

@if($motivator)
    <div style="font-size:16px; color:#28a745; text-align:center; margin-bottom: 10px;">
        {{ $motivator }}
    </div>
@endif
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Número</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Comisión</th>
            </tr>
        </thead>
        <tbody>
        @forelse($records as $row)
            <tr>
                <td>{{ $row['date_of_issue'] }}</td>
                <td>{{ $row['type'] }}</td>
                <td>{{ $row['number_full'] }}</td>
                <td>{{ $row['customer_name'] }}</td>
                <td>{{ number_format($row['total'], 2) }}</td>
                <td>{{ number_format($row['commission'], 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center;">No se encontraron registros.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>