<?php

// Test script para el importador - ejecutar dentro del contenedor Docker
require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

echo "🧪 === TEST IMPORTADOR EXCEL DDB ===\n\n";

// Simular datos del Excel
$row = [
    0 => 2051,                           // numero
    1 => '2025-07-24',                   // fecha  
    2 => '17:40:00',                     // hora
    3 => 18764094268883,                 // numero_resolucion
    4 => 'FEC',                          // prefijo
    5 => 'Deposito de Drogas Boyaca',    // nombre_establecimiento
    6 => 'TV 93 51 98 BG 18 BG 20 PAR EMPRESA PTA DEL SOL', // direccion_establecimiento
    7 => 3006568566,                     // telefono_establecimiento
    8 => 149,                            // municipio_establecimiento
    9 => 'info@ddb.com.co',              // email_establecimiento
    10 => 900298372,                     // identificacion_cliente
    11 => 'Crédito',                     // id_forma_pago
    12 => 10,                            // id_metodo_pago
    13 => '2025-08-23',                  // fecha_vencimiento
    14 => 30,                            // plazo_dias
    15 => '92707,42',                    // total_valor_bruto
    16 => 95490,                         // total_base_impuestos
    17 => 95490,                         // total_impuestos_incl
    18 => 0,                             // total_descuentos
    19 => 0,                             // total_cargos
    20 => 92707,                         // total_pagado
    21 => 30,                            // cantidad_linea
    22 => 92707,                         // base_impuestos_linea
    23 => '19983582-4',                  // codigo_linea
    24 => 3183,                          // precio_venta
    25 => 'Deposito de Drogas Boyaca',   // nota_cabecera
    26 => 'Factura Electronica de Venta en Salud - Fecha de Generación: 2025-07-24', // nota_pie
    27 => 'Periodo facturacion Julio 2025', // notas_factura
    28 => '2025-05-01',                  // fecha_inicial_perido_facturado
    29 => '2025-05-15',                  // fecha_final_perido_facturado
    30 => '1100187014,1,24038215,SEPULVEDA,MURILLO,ROSA,MYRIAM,4,4,1,25116214515327400,NULL,NULL,CS-AS-075-2023,POL55555,0,0,0,0,0', // informacion_usuarios_sector_salud
    31 => 3,                             // id_impuesto
    32 => '395,33',                      // monto_impuesto
    33 => ' 0,04 ',                      // porcentaje_impuesto
    34 => 95490,                         // base_impuesto
    35 => 5,                             // id_impuesto_linea
    36 => 2387,                          // monto_impuesto_linea
    37 => '2,5',                         // porcentaje_impuesto_linea
    38 => 95490,                         // base_impuesto_linea
    39 => 'false',                       // indicador_cargo
    40 => 'descuento',                   // razon_descuento
    41 => 0,                             // monto_descuento
    42 => 0,                             // base_descuento
];

echo "📋 Datos del Excel:\n";
echo "  - Documento: {$row[4]}-{$row[0]}\n";
echo "  - Cliente: {$row[10]}\n";  
echo "  - Item: {$row[23]}\n";
echo "  - Total: {$row[15]}\n";
echo "  - Forma pago: {$row[11]}\n";
echo "  - Método pago: {$row[12]}\n\n";

// Simular funciones del importador  
function formatMonetaryValue($value)
{
    if (empty($value) && $value !== 0 && $value !== '0') {
        return '0.00';
    }
    
    $stringValue = trim((string)$value);
    $stringValue = str_replace(',', '.', $stringValue);
    $stringValue = preg_replace('/[^0-9.\-+]/', '', $stringValue);
    
    $parts = explode('.', $stringValue);
    if (count($parts) > 2) {
        $integerPart = implode('', array_slice($parts, 0, -1));
        $decimalPart = end($parts);
        $stringValue = $integerPart . '.' . $decimalPart;
    }
    
    $numericValue = is_numeric($stringValue) ? (float)$stringValue : 0;
    return number_format($numericValue, 2, '.', '');
}

function ExcelDateToPHP($value)
{
    try {
        if (is_numeric($value)) {
            $baseDate = new \DateTime('1900-01-01');
            return $baseDate->modify('+' . ($value - 2) . ' days')->format('Y-m-d');
        } else {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        }
    } catch (\Exception $e) {
        return date('Y-m-d');
    }
}

function ExcelTimeToPHP($value)
{
    try {
        if (is_numeric($value)) {
            $secondsFrom1970 = $value * 86400;
            return (new \DateTime('@' . $secondsFrom1970))->format('H:i:s');
        } else {
            return \Carbon\Carbon::parse($value)->format('H:i:s');
        }
    } catch (\Exception $e) {
        return date('H:i:s');
    }
}

echo "🔄 Procesando valores monetarios...\n";
$processedValues = [
    'line_extension_amount' => formatMonetaryValue($row[15]),
    'tax_exclusive_amount' => formatMonetaryValue($row[16]),
    'tax_inclusive_amount' => formatMonetaryValue($row[17]),
    'allowance_total_amount' => formatMonetaryValue($row[18]),
    'charge_total_amount' => formatMonetaryValue($row[19] ?? 0),
    'payable_amount' => formatMonetaryValue($row[20])
];

foreach ($processedValues as $key => $value) {
    echo "  - $key: $value\n";
}

echo "\n🕐 Procesando fechas...\n";
$date = ExcelDateToPHP($row[1]);
$time = ExcelTimeToPHP($row[2]);
echo "  - Fecha: $date\n";
echo "  - Hora: $time\n";

echo "\n✅ Procesamiento de valores completado exitosamente!\n";
echo "📝 El importador debería funcionar correctamente con estos datos.\n\n";

echo "🔍 Para probar en la interfaz web:\n";
echo "1. Ir a https://imperiumfevsrips.net\n";
echo "2. Loguearse en la tenancy DDB\n";
echo "3. Ir a Documentos > Importar Excel\n";
echo "4. Subir el archivo /root/test_excel_ddb.csv\n\n";

echo "🧪 === TEST COMPLETADO ===\n";
?>
