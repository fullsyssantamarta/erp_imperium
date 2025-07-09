<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Accounting\Models\ChartOfAccount;

class AddRestOfChartOfAccountsToCoTaxes2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->importAccountsFromCSV();

        DB::table('co_taxes')->where('name', 'IMPUESTO AL CONSUMO')->update([
            'chart_account_purchase' => '51159501',
            'chart_account_sale' => '24950101',
            'chart_account_return_purchase' => '51159502',
            'chart_account_return_sale' => '24950102',
        ]);

        DB::table('co_taxes')->where('name', 'IMPUESTO NACIONAL AL CONSUMO')->update([
            'chart_account_purchase' => '51159503',
            'chart_account_sale' => '24950105',
            'chart_account_return_purchase' => '51159504',
            'chart_account_return_sale' => '24950106',
        ]);

        // borrar  13551530 13551535 13551540
        ChartOfAccount::where('code', '13551530')->delete();
        ChartOfAccount::where('code', '13551535')->delete();
        ChartOfAccount::where('code', '13551540')->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){}

    private function importAccountsFromCSV()
    {
        $file = public_path('csv/cuentas_contables_update.csv');
        if (!file_exists($file)) {
            throw new Exception("El archivo cuentas.csv no fue encontrado en la carpeta public.");
        }

        $handle = fopen($file, 'r');
        $header = fgetcsv($handle, 1000, ','); // Leer cabecera

        // Eliminar cualquier BOM (Byte Order Mark) de la cabecera
        $header = array_map('trim', $header);

        // Verificar que la cabecera esté correctamente leída
        if (!$header || count($header) < 5) {
            throw new Exception("El archivo CSV no tiene el formato esperado.");
        }

        $accounts = [];
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            // Eliminar cualquier BOM de los datos de la fila
            $row = array_map('trim', $row);

            // Verificar que la fila tenga el mismo número de columnas que la cabecera
            if (count($row) !== count($header)) {
                continue;
            }

            $data = [
                'code' => $row[0],
                'name' => $row[1],
                'type' => $row[2],
                'level' => $row[3],
                'parent_code'=> $row[4]
            ];

            // Verificar que la clave 'code' esté presente y no vacía
            if (!isset($data['code']) || empty($data['code'])) {
                continue;
            }

            // Asignar la cuenta por su código
            $accounts[$data['code']] = $data;
        }

        fclose($handle);

        foreach ($accounts as $code => $data) {
            $parentId = ChartOfAccount::where('code', $data['parent_code'])->value('id');

            // Buscar si ya existe la cuenta
            $account = ChartOfAccount::where('code', $data['code'])->first();
            if ($account) {
                // Actualizar si existe
                $account->update([
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'level' => $data['level'],
                    'parent_id' => $parentId,
                    'status' => true,
                ]);
            } else {
                // Crear si no existe
                ChartOfAccount::create([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'level' => $data['level'],
                    'parent_id' => $parentId,
                    'status' => true,
                ]);
            }
        }
    }
}
