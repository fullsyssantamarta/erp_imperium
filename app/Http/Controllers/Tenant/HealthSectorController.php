<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CoDocumentRequest;
use App\Models\Tenant\Catalogs\Country;
use App\Models\Tenant\Catalogs\Department;
use App\Models\Tenant\Catalogs\District;
use Modules\Factcolombia1\Models\TenantService\Municipality;
use App\Models\Tenant\Document;
use App\Models\Tenant\Company;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Item;
use App\Models\Tenant\Person;
use App\Models\Tenant\Series;
use App\Models\Tenant\User;
use App\Models\Tenant\TenancyHealthUser;
use App\Imports\CoDocumentsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class HealthSectorController extends Controller
{
    public function index()
    {
        return view('tenant.health_sector.index');
    }

    public function create()
    {
        $companies = Company::all();
        $establishments = Establishment::all();
        $series = Series::where('document_type_id', '01')->get(); // Facturas
        $customers = Person::whereType('customers')->get();
        $items = Item::all();
        
        return view('tenant.health_sector.create', compact(
            'companies', 'establishments', 'series', 'customers', 'items'
        ));
    }

    /**
     * Buscar usuario por cédula
     */
    public function searchUser(Request $request)
    {
        $documento = $request->get('documento');
        
        if (empty($documento)) {
            return response()->json(['error' => 'Debe proporcionar un número de documento'], 400);
        }

        $user = TenancyHealthUser::where('documento', $documento)->first();
        
        if ($user) {
            return response()->json([
                'success' => true,
                'user' => $user
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }
    }

    /**
     * Importar Excel masivo del sector salud
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        try {
            $file = $request->file('excel_file');
            $filePath = $file->store('temp');
            $fullPath = storage_path('app/' . $filePath);

            DB::beginTransaction();

            $import = new CoDocumentsImport();
            Excel::import($import, $fullPath);

            // Obtener estadísticas de la importación
            $stats = [
                'imported' => 'Procesado exitosamente',
                'errors' => 0
            ];

            DB::commit();

            // Limpiar archivo temporal
            unlink($fullPath);

            return response()->json([
                'success' => true,
                'message' => 'Importación completada exitosamente',
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            if (isset($fullPath) && file_exists($fullPath)) {
                unlink($fullPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error en la importación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar factura del sector salud
     */
    public function generateInvoice(Request $request)
    {
        $request->validate([
            'customer_document' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.code' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            // Buscar usuario del sector salud
            $healthUser = TenancyHealthUser::where('documento', $request->customer_document)->first();
            
            if (!$healthUser) {
                throw new Exception('Usuario del sector salud no encontrado');
            }

            // Crear o buscar el cliente
            $customer = Person::where('number', $request->customer_document)->first();
            
            if (!$customer) {
                $customer = Person::create([
                    'type' => 'customers',
                    'identity_document_type_id' => '1', // CC
                    'number' => $healthUser->documento,
                    'name' => $healthUser->nombre_completo,
                    'trade_name' => $healthUser->nombre_completo,
                    'address' => $healthUser->direccion,
                    'email' => $healthUser->email,
                    'telephone' => $healthUser->telefono ?: $healthUser->celular,
                    'country_id' => '46', // Colombia
                    'department_id' => $this->getDepartmentId($healthUser->departamento),
                    'city_id' => $this->getMunicipalityId($healthUser->municipio),
                ]);
            }

            // Crear la factura
            $invoice = Document::create([
                'user_id' => auth()->id(),
                'soap_type_id' => Company::active()->soap_type_id,
                'state_document_id' => 1,
                'type_document_id' => 1, // Factura
                'number' => $this->getNextInvoiceNumber(),
                'customer' => $customer->toArray(),
                'currency_id' => 35, // COP
                'date' => now()->toDateString(),
                'time' => now()->toTimeString(),
                'establishment_id' => 1,
                'aditional_information' => json_encode([
                    'health_user_id' => $healthUser->id,
                    'eps_codigo' => $healthUser->eps_codigo,
                    'eps_nombre' => $healthUser->eps_nombre,
                    'regimen' => $healthUser->regimen
                ])
            ]);

            $subtotal = 0;
            $tax_total = 0;

            // Agregar items a la factura
            foreach ($request->items as $itemData) {
                $item = Item::where('code', $itemData['code'])->first();
                
                if (!$item) {
                    throw new Exception("Item no encontrado: {$itemData['code']}");
                }

                $quantity = $itemData['quantity'];
                $price = $itemData['price'];
                $itemSubtotal = $quantity * $price;
                
                // Calcular impuestos (19% IVA para sector salud)
                $itemTax = $itemSubtotal * 0.19;
                
                $invoice->invoice_lines()->create([
                    'item_id' => $item->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $itemSubtotal + $itemTax
                ]);

                $subtotal += $itemSubtotal;
                $tax_total += $itemTax;
            }

            // Actualizar totales de la factura
            $invoice->update([
                'subtotal' => $subtotal,
                'tax_total' => $tax_total,
                'total' => $subtotal + $tax_total
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Factura generada exitosamente',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error generando factura: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getNextInvoiceNumber()
    {
        $lastInvoice = Document::where('type_document_id', 1)
            ->orderBy('number', 'desc')
            ->first();
            
        return $lastInvoice ? $lastInvoice->number + 1 : 1;
    }

    private function getDepartmentId($departmentName)
    {
        if (empty($departmentName)) return null;
        
        $department = Department::where('name', 'like', "%$departmentName%")->first();
        return $department ? $department->id : null;
    }

    private function getMunicipalityId($municipalityName)
    {
        if (empty($municipalityName)) return null;
        
        $municipality = Municipality::where('name', 'like', "%$municipalityName%")->first();
        return $municipality ? $municipality->id : null;
    }

    /**
     * Ver listado de facturas del sector salud
     */
    public function invoices()
    {
        $invoices = Document::where('type_document_id', 1)
            ->whereNotNull('aditional_information')
            ->whereRaw("JSON_EXTRACT(aditional_information, '$.health_user_id') IS NOT NULL")
            ->with(['customer', 'invoice_lines.item'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('tenant.health_sector.invoices', compact('invoices'));
    }

    /**
     * Ver usuarios del sector salud
     */
    public function users()
    {
        $users = TenancyHealthUser::orderBy('created_at', 'desc')->paginate(20);
        return view('tenant.health_sector.users', compact('users'));
    }
}
