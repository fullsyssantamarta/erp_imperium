<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('type_documents')) {
            $documents = [
                [
                    'id' => 91,
                    'name' => 'Nota Crédito Sector Salud',
                    'code' => '91',
                    'cufe_algorithm' => 'CUDS',
                    'prefix' => 'NCH',
                ],
                [
                    'id' => 92,
                    'name' => 'Nota Débito Sector Salud',
                    'code' => '92',
                    'cufe_algorithm' => 'CUDS',
                    'prefix' => 'NDH',
                ],
            ];

            foreach ($documents as $document) {
                DB::table('type_documents')->updateOrInsert(
                    ['id' => $document['id']],
                    $document
                );
            }
        }

        if (Schema::hasTable('co_note_concepts')) {
            $concepts = [
                ['type_document_id' => 91, 'code' => '1', 'name' => 'Devolución de servicios de salud no prestados'],
                ['type_document_id' => 91, 'code' => '2', 'name' => 'Anulación de procedimientos médicos'],
                ['type_document_id' => 91, 'code' => '3', 'name' => 'Corrección en información de paciente'],
                ['type_document_id' => 91, 'code' => '4', 'name' => 'Ajuste por glosa de EPS'],
                ['type_document_id' => 91, 'code' => '5', 'name' => 'Descuento por copago o cuota moderadora'],
                ['type_document_id' => 91, 'code' => '6', 'name' => 'Anulación por autorización no válida'],
                ['type_document_id' => 91, 'code' => '7', 'name' => 'Corrección en códigos CUPS o CIE-10'],
                ['type_document_id' => 91, 'code' => '8', 'name' => 'Ajuste por cambio de régimen de afiliación'],
                ['type_document_id' => 92, 'code' => '1', 'name' => 'Cobro adicional por servicios complementarios'],
                ['type_document_id' => 92, 'code' => '2', 'name' => 'Ajuste por diferencia en valor de procedimiento'],
                ['type_document_id' => 92, 'code' => '3', 'name' => 'Recargo por atención fuera de red'],
                ['type_document_id' => 92, 'code' => '4', 'name' => 'Cobro por servicios no POS'],
                ['type_document_id' => 92, 'code' => '5', 'name' => 'Ajuste por corrección en copago'],
                ['type_document_id' => 92, 'code' => '6', 'name' => 'Recargo por urgencias no justificadas'],
                ['type_document_id' => 92, 'code' => '7', 'name' => 'Cobro adicional por medicamentos especiales'],
            ];

            foreach ($concepts as $concept) {
                DB::table('co_note_concepts')->updateOrInsert(
                    [
                        'type_document_id' => $concept['type_document_id'],
                        'code' => $concept['code'],
                    ],
                    ['name' => $concept['name']]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('type_documents')) {
            DB::table('type_documents')->whereIn('id', [91, 92])->delete();
        }

        if (Schema::hasTable('co_note_concepts')) {
            DB::table('co_note_concepts')->whereIn('type_document_id', [91, 92])->delete();
        }
    }
};
