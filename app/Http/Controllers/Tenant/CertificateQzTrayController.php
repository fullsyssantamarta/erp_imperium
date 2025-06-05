<?php

namespace App\Http\Controllers\Tenant;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\Tenant\DocumentPos;
use Modules\Factcolombia1\Models\Tenant\Document;
use Modules\Factcolombia1\Models\Tenant\Company;
use Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration;
use Modules\Factcolombia1\Models\TenantService\Company as ServiceTenantCompany;
use Modules\Store\Helpers\StorageHelper;

class CertificateQzTrayController extends Controller
{
    protected $company;
    protected $config;

    public function __construct()
    {
        $this->company = Company::first();
        $this->config = AdvancedConfiguration::first();
    }

    public function record()
    {
        $certificates_qztray = AdvancedConfiguration::selectCertificateQzTray()->first();

        return $certificates_qztray;
    }

    public function uploadFileQzTray(Request $request)
    {

        try {
            $company = $this->company;
            $config = $this->config;
            if ($request->hasFile('digital_qztray')) {
                $file_digital = $request->file('digital_qztray');
                if (!file_exists(storage_path('app' . DIRECTORY_SEPARATOR . 'certificates'. DIRECTORY_SEPARATOR . 'qztray'))) {
                    Storage::disk('local')->makeDirectory('certificates'. DIRECTORY_SEPARATOR. 'qztray');
                }
                $name_digital = "digital_certificate_" . $company->identification_number .".". $file_digital->getClientOriginalExtension();
                file_put_contents(storage_path('app' . DIRECTORY_SEPARATOR . 'certificates' . DIRECTORY_SEPARATOR . 'qztray'. DIRECTORY_SEPARATOR . $name_digital), file_get_contents($file_digital->getPathname()) );

                $config->digital_certificate_qztray = $name_digital;
                $config->save();

                return [
                    'success' => true,
                    'type' => 'digital_qztray',
                    'name' => $name_digital,
                    'message' =>  __('app.actions.upload.success'),
                ];
            } else if ($request->hasFile('private_qztray')) {
                $file_private = $request->file('private_qztray');
                $name_private = "private_certificate_" . $company->identification_number. "." . $file_private->getClientOriginalExtension();
                if (!file_exists(storage_path('app' . DIRECTORY_SEPARATOR . 'certificates'. DIRECTORY_SEPARATOR . 'qztray'))) {
                    Storage::disk('local')->makeDirectory('certificates'. DIRECTORY_SEPARATOR. 'qztray');
                }
                file_put_contents(storage_path('app' . DIRECTORY_SEPARATOR . 'certificates' . DIRECTORY_SEPARATOR . 'qztray'. DIRECTORY_SEPARATOR . $name_private), file_get_contents($file_private->getPathname()));

                $config->private_certificate_qztray = $name_private;
                $config->save();
                return [
                    'success' => true,
                    'type' =>'private_qztray',
                    'name'=> $name_private,
                    'message' =>  __('app.actions.upload.success'),
                ];
            }
        } catch (\Throwable $error) {
            \Log::error($error);
            return response([
                'success' => false,
                'message' =>  $error->getMessage(),
            ], 400);
        }
    }

    public function destroy()
    {
        $config = $this->config;
        if (Storage::disk('local')->exists('certificates\\qztray\\'.$config->private_certificate_qztray)) {
            Storage::disk('local')->delete('certificates\\qztray\\'.$config->private_certificate_qztray);
            $config->private_certificate_qztray = null;
        }
        if (Storage::disk('local')->exists('certificates\\qztray\\'.$config->digital_certificate_qztray)) {
            Storage::disk('local')->delete('certificates\\qztray\\'.$config->digital_certificate_qztray);
            $config->digital_certificate_qztray = null;
        }

        $config->save();

        return [
            'success' => true,
            'message' => 'Certificados de Qz Tray eliminado con éxito'
        ];
    }

    public function private()
    {
        return $this->contentCertificaQzPrivate();
    }

    public function digital()
    {
        return $this->contentCertificaQzDigital();
    }

    private function contentCertificaQzPrivate()
    {
        $config = $this->config;
        $content_private_certificate = null;
        if (Storage::disk('local')->exists('certificates\\qztray\\'.$config->private_certificate_qztray)) {
            $content_private_certificate = Storage::disk('local')->get('certificates\\qztray\\'.$config->private_certificate_qztray);
        }
        return $content_private_certificate ? $content_private_certificate : '';
    }

    private function contentCertificaQzDigital()
    {
        $config = $this->config;
        $content_digital_certificate=null;
        if (Storage::disk('local')->exists('certificates\\qztray\\'.$config->digital_certificate_qztray)) {
            $content_digital_certificate = Storage::disk('local')->get('certificates\\qztray\\'.$config->digital_certificate_qztray);
        }
        
        return $content_digital_certificate ? $content_digital_certificate : '';
    }

    public function changeStatus(Request $request)
    {
        $config = $this->config;
        $config->enable_qz_tray = $request->enable_qz_tray;
        $config->save();

        return [
            'enable_qz_tray' => $config->enable_qz_tray
        ];
    }

    /***
     * Obsoleto : el documento ticket de pos se genera en DocumentPosController::toPrint()
     */
    // public function getHtmlDocument($id)
    // {
    //     $company = $this->company;
    //     $document = DocumentPos::findOrFail($id);
    //     $filename = 'POSS-' . $document->prefix . $document->number . '.pdf';
    //     $service_company = ServiceTenantCompany::firstOrFail();
    //     $base_url = config('tenant.service_fact');
    //     $api_url = "{$base_url}qztray/{$company->identification_number}/{$filename}";
    //     $ch = curl_init($api_url);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    //         "Authorization: Bearer {$service_company->api_token}"
    //     ));
    //     $response = curl_exec($ch);
    //     curl_close($ch);

    //     return $response;
    // }
}

