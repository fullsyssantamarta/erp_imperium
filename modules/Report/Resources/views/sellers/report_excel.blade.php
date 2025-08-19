<table>
    <tr>
        <td colspan="6" style="font-size:20px; font-weight:bold; color:#007bff; text-align:center;">
            Reporte de Vendedor
        </td>
    </tr>
    @if($seller)
    <tr>
        <td><strong>Código interno:</strong></td>
        <td>{{ $seller->internal_code ?? '-' }}</td>
        <td><strong>N° Documento:</strong></td>
        <td>{{ $seller->document_number ?? '-' }}</td>
        <td><strong>Nombre:</strong></td>
        <td>{{ $seller->full_name }}</td>
    </tr>
    @else
    <tr>
        <td colspan="6" style="text-align:center;"><strong>Todos los vendedores</strong></td>
    </tr>
    @endif
    @php
        $motivator = '';
        if($seller && $seller->monthly_goal > 0) {
            $percent = isset($progress) ? ($progress['goal'] > 0 ? ($progress['total'] / $progress['goal']) * 100 : 0) : 0;
            if ($percent == 0) $motivator = '¡Vamos, empieza a sumar documentos este mes!';
            elseif ($percent < 50) $motivator = '¡Buen inicio, sigue así para alcanzar la meta!';
            elseif ($percent < 80) $motivator = '¡Vas muy bien, la meta está cerca!';
            elseif ($percent < 100) $motivator = '¡Excelente trabajo, casi logras la meta!';
            else $motivator = '¡Felicidades, superaste tu meta mensual!';
        }
    @endphp
    @if($motivator)
    <tr>
        <td colspan="6" style="font-size:16px; color:#28a745; text-align:center;">
            {{ $motivator }}
        </td>
    </tr>
    @endif
    <tr><td colspan="6"></td></tr>
</table>
<table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse; width:100%;">
    <thead>
        <tr style="background:#007bff; color:#fff;">
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
            <td>{{ $row['total'] }}</td>
            <td>{{ $row['commission'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6" style="text-align:center;">No se encontraron registros.</td>
        </tr>
    @endforelse
    </tbody>
</table>