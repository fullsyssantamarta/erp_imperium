<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Estado de Resultados</title>
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
    <h1>Reporte de Estado de Resultados</h1>
    @if ($dateStart || $dateEnd)
        <p>Rango de fechas: {{ $dateStart }} a {{ $dateEnd }}</p>
    @endif
    <table>
        <thead>
            <tr>
                <th>Utilidad Bruta</th>
                <th>Utilidad Operativa</th>
                <th>Resultado Neto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{{ $gross_profit }}}</td>
                <td>{{ $operating_profit }}</td>
                <td>{{ $net_profit }}</td>
            </tr>
        </tbody>
    </table>
    <h2>Ingresos</h2>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($revenues as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ number_format($row['saldo'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="2">Total Ingresos</td>
                <td>{{ number_format($totals['revenue'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Gastos</h2>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($expenses as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ number_format($row['saldo'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="2">Total Gastos</td>
                <td>{{ number_format($totals['expense'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Costos</h2>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($costs as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ number_format($row['saldo'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="2">Total costos</td>
                <td>{{ number_format($totals['cost'], 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>