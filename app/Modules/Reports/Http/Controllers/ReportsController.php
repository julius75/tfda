<?php

namespace App\Modules\Reports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{

    protected $user_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!\Auth::check()) {
                $res = array(
                    'success' => false,
                    'message' => '<p>NO SESSION, SERVICE NOT ALLOWED!!<br>PLEASE RELOAD THE SYSTEM!!</p>'
                );
                echo json_encode($res);
                exit();
            }
            $this->user_id = \Auth::user()->id;
            return $next($request);
        });
    }

    public function index()
    {
        return view('reports::index');
    }

    public function generateApplicationInvoice(Request $request)
    {
        $invoice_id = $request->input('invoice_id');
        $application_id = $request->input('application_id');
        $module_id = $request->input('module_id');
        $invoice_details = getInvoiceDetails($module_id, $application_id);
        $params = array(
            'invoice_id' => $invoice_id,
            'process_name' => $invoice_details['process_name'],
            'module_name' => $invoice_details['module_name'],
            'module_desc' => $invoice_details['module_desc'],
            'reference_no' => $invoice_details['reference_no']
        );
        $report = generateJasperReport('invoiceReport', 'invoice_'.time(), 'pdf', $params);
        return $report;
    }

    public function generateApplicationReceipt(Request $request)
    {
        $payment_id =$request->input('payment_id');
        $application_id = $request->input('application_id');
        $module_id = $request->input('module_id');
        $module_details = getTableData('modules',array('id'=>$module_id));
        $table_name = $module_details->table_name;
        $reference_no = getSingleRecordColValue($table_name, array('id' => $application_id), 'reference_no');
        $params = array(
            'payment_id' => $payment_id,
            'reference_no' => $reference_no
        );
        $report = generateJasperReport('receiptReport', 'receipt_'.time(), 'pdf', $params);
        return $report;
    }

    public function generatePremiseCertificate(Request $request)
    {
        $premise_id =$request->input('premise_id');
        $params = array(
            'premise_id' => $premise_id
        );
        $report = generateJasperReport('certificateReport', 'certificate_'.time(), 'pdf', $params);
        return $report;
    }

    public function generatePremisePermit(Request $request)
    {
        $premise_id =$request->input('premise_id');
        $params = array(
            'premise_id' => $premise_id
        );
        $report = generateJasperReport('premisePermitReport', 'permit_'.time(), 'pdf', $params);
        return $report;
    }

}
