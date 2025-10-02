<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Factcolombia1\Models\Tenant\TypeDocument;
use Modules\Factcolombia1\Models\Tenant\NoteConcept;

class TenantSeedHealthTypeDocuments extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $documents = [
            [
                'code' => '91',
                'name' => 'Nota Crédito Sector Salud',
                'prefix' => 'NCH',
                'description' => 'Nota crédito específica para sector salud',
                'cufe_algorithm' => 'CUDS',
                'template' => 'health',
            ],
            [
                'code' => '92',
                'name' => 'Nota Débito Sector Salud',
                'prefix' => 'NDH',
                'description' => 'Nota débito específica para sector salud',
                'cufe_algorithm' => 'CUDS',
                'template' => 'health',
            ],
        ];

        $noteConcepts = [
            '91' => [
                ['code' => '1', 'name' => 'Devolución de servicios de salud no prestados'],
                ['code' => '2', 'name' => 'Anulación de procedimientos médicos'],
                ['code' => '3', 'name' => 'Corrección en información de paciente'],
                ['code' => '4', 'name' => 'Ajuste por glosa de EPS'],
                ['code' => '5', 'name' => 'Descuento por copago o cuota moderadora'],
                ['code' => '6', 'name' => 'Anulación por autorización no válida'],
                ['code' => '7', 'name' => 'Corrección en códigos CUPS o CIE-10'],
                ['code' => '8', 'name' => 'Ajuste por cambio de régimen de afiliación'],
            ],
            '92' => [
                ['code' => '1', 'name' => 'Cobro adicional por servicios complementarios'],
                ['code' => '2', 'name' => 'Ajuste por diferencia en valor de procedimiento'],
                ['code' => '3', 'name' => 'Recargo por atención fuera de red'],
                ['code' => '4', 'name' => 'Cobro por servicios no POS'],
                ['code' => '5', 'name' => 'Ajuste por corrección en copago'],
                ['code' => '6', 'name' => 'Recargo por urgencias no justificadas'],
                ['code' => '7', 'name' => 'Cobro adicional por medicamentos especiales'],
            ],
        ];

        $createdDocuments = [];

        foreach ($documents as $data) {
            $document = TypeDocument::withTrashed()->firstOrNew(['code' => $data['code']]);
            $document->fill($data);

            // Ensure required defaults that may not exist in legacy rows
            if (! $document->template) {
                $document->template = $data['template'] ?? 'face';
            }
            $document->save();

            if ($document->trashed()) {
                $document->restore();
            }

            $createdDocuments[$data['code']] = $document->id;
        }

        foreach ($noteConcepts as $code => $concepts) {
            if (!isset($createdDocuments[$code])) {
                continue;
            }

            $typeDocumentId = $createdDocuments[$code];

            foreach ($concepts as $conceptData) {
                $concept = NoteConcept::withTrashed()->firstOrNew([
                    'type_document_id' => $typeDocumentId,
                    'code' => $conceptData['code'],
                ]);

                $concept->name = $conceptData['name'];
                $concept->save();

                if ($concept->trashed()) {
                    $concept->restore();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $codes = ['91', '92'];

        $documents = TypeDocument::withTrashed()->whereIn('code', $codes)->get();

        if ($documents->isEmpty()) {
            return;
        }

        $documentIds = $documents->pluck('id');

        NoteConcept::withTrashed()->whereIn('type_document_id', $documentIds)->forceDelete();
        TypeDocument::withTrashed()->whereIn('id', $documentIds)->forceDelete();
    }
}
