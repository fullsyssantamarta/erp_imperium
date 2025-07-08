<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Accounting\Models\ChartOfAccount;

class AddChartOfAccountsToCoTaxes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('chart_of_accounts')->where('id', '364')->update([
            'name' => 'IVA generado 19%' // 24080501
        ]);

        $iva5s = DB::table('co_taxes')->where('name', 'IVA5')->orderBy('id')->get();
        if ($iva5s->count() > 1) {
            $last = $iva5s->last();
            DB::table('co_taxes')->where('id', $last->id)->update([
                'name' => 'IVA5 Duplicado'
            ]);
        }

        if (!DB::table('co_taxes')->where('name', 'IVA19')->exists()) {
            DB::table('co_taxes')->insert([
                [
                    'name' => 'IVA19',
                    'code' => '71',
                    'rate' => '19.0',
                    'conversion' => '100.0',
                    'type_tax_id' => 1
                ],
            ]);
        }

        $this->importAccountsFromCSV();

        DB::table('co_taxes')->where('name', 'IVA19')->update([
            'chart_account_sale' => '24080501',
            'chart_account_purchase' => '24081501',
            'chart_account_return_sale' => '24081001',
            'chart_account_return_purchase' => '24082001',
        ]);

        DB::table('co_taxes')->where('name', 'LIKE', '%IVA5%')->update([
            'chart_account_sale' => '24080502',
            'chart_account_purchase' => '24081502',
            'chart_account_return_sale' => '24081002',
            'chart_account_return_purchase' => '24082002',
        ]);
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

        // dd($accounts);
        // Insertar cuentas respetando jerarquía
        $inserted = [];

        foreach ($accounts as $code => $data) {
            $parentId = ChartOfAccount::where('code', $data['parent_code'])->value('id');

            $account = ChartOfAccount::where('code', $data['code'])->first();
            if ($account) {
                $account->update([
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'level' => $data['level'],
                    'parent_id' => $parentId,
                    'status' => true,
                ]);
                $inserted[$code] = $account->id;
            } else {
                $newAccount = ChartOfAccount::create([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'level' => $data['level'],
                    'parent_id' => $parentId,
                    'status' => true,
                ]);
                $inserted[$code] = $newAccount->id;
            }
        }
    }
}
