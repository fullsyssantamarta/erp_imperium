<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Movimientos auxiliares</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .totals {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Reporte de Movimientos auxiliares</h1>
    @if ($dateStart || $dateEnd)
        <p>Rango de fechas: {{ $dateStart }} a {{ $dateEnd }}</p>
    @endif
    <table>
        <tr>
            <th>Código</th>
            <th>Cuenta</th>
            <th>Comprobante</th>
            <th>Fecha</th>
            <th>Número de documento</th>
            <th>Nombre del tercero</th>
            <th>Descripción</th>
            <th>Débito</th>
            <th>Crédito</th>
        </tr>
        <tbody>
            @foreach ($accounts as $group)
                <tr>
                    <td colspan="7">
                        <strong>Cuenta contable:</strong> {{ $group['account_code'] }} {{ $group['account_name'] }}
                    </td>
                    <td>{{ $group['total_debit'] }}</td>
                    <td>{{ $group['total_credit'] }}</td>
                </tr>
                    @foreach ($group['details'] as $row)
                        <tr>
                            <td>{{ $row['account_code'] }}</td>
                            <td>{{ $row['account_name'] }}</td>
                            <td>{{ $row['document_info']['number'] }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['document_info']['third_party_number'] }}</td>
                            <td>{{ $row['document_info']['third_party_name'] }}</td>
                            <td>{{ $row['description'] }}</td>
                            <td class="text-right">{{ $row['debit'] }}</td>
                            <td class="text-right">{{ $row['credit'] }}</td>
                        </tr>
                    @endforeach
            @endforeach
        </tbody>
    </table>
    {{-- {{dd($accounts)}} --}}
</body>
</html>