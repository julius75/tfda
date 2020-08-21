<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 7/24/2018
 * Time: 12:38 PM
 */

namespace App\Helpers;

use Jaspersoft\Client\Client;
use App\Modules\ProductRegistration\Traits\ProductsRegistrationTrait;
use App\Modules\PremiseRegistration\Traits\PremiseRegistrationTrait;
use App\Modules\GmpApplications\Traits\GmpApplicationsTrait;
use App\Modules\ClinicalTrial\Traits\ClinicalTrialTrait;

class ReportsHelper
{
    public $client = '';
    public $jasper_server_url = '';
    public $jasper_server_username = '';
    public $jasper_server_password = '';
    use ProductsRegistrationTrait;
    use PremiseRegistrationTrait;
    use GmpApplicationsTrait;
    use ClinicalTrialTrait;

    public function __construct()
    {
        $this->jasper_server_url = env('JASPER_SERVER_URL', 'http://localhost:8080/jasperserver');
        $this->jasper_server_username = env('JASPER_SERVER_USERNAME', 'jasperadmin');
        $this->jasper_server_password = env('JASPER_SERVER_PASSWORD', 'jasperadmin');

        $this->client = new Client(
            $this->jasper_server_url,
            $this->jasper_server_username,
            $this->jasper_server_password
        );
    }

    public function generateJasperReport($input_filename, $output_filename, $mode, $controls)
    {
        $report = $this->client->reportService()->runReport('/reports/Tfda_v2/' . $input_filename, $mode, null, null, $controls);
        return response($report)
            ->header('Cache-Control', 'no-cache private')
            ->header('Content-Description', 'File Transfer')
            ->header('Content-Type', 'application/pdf')
            ->header('Content-length', strlen($report))
            ->header('Content-Disposition', 'inline; filename=' . $output_filename . '.' . $mode)
            ->header('Content-Transfer-Encoding', 'binary');
    }

    public function getInvoiceDetails($module_id, $application_id)
    {
        $res = array(
            'reference_no' => 'N/A',
            'process_name' => 'N/A',
            'module_name' => 'N/A',
            'module_desc' => 'N/A'
        );
        $invoice_details=array();
        if ($module_id == 1) {//Product Registration
            $invoice_details = $this->getProductInvoiceDetails($application_id);
        } else if ($module_id == 2) {//Premise Registration
            $invoice_details = $this->getPremiseInvoiceDetails($application_id);
        } else if ($module_id == 3) {//GMP Applications
            $invoice_details = $this->getGmpInvoiceDetails($application_id);
        } else if ($module_id == 4) {//Import & Export

        } else if ($module_id == 5) {//PMS Module

        } else if ($module_id == 6) {//Product Notification

        } else if ($module_id == 7) {//Clinical Trial
            $invoice_details = $this->getClinicalTrialInvoiceDetails($application_id);
        } else if ($module_id == 8) {//QMS Module

        } else if ($module_id == 9) {//Surveillance Applications

        } else if ($module_id == 10) {//Disposal Module

        } else if ($module_id == 12) {//Narcotic Permit Applications

        } else if ($module_id == 14) {//Promotional & Advertisements

        }
        if (!is_null($invoice_details)) {
            $res = array(
                'reference_no' => $invoice_details->reference_no,
                'process_name' => $invoice_details->process_name,
                'module_name' => $invoice_details->module_name,
                'module_desc' => $invoice_details->module_desc
            );
        }
        return $res;
    }

}