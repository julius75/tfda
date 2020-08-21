<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 9/25/2018
 * Time: 4:18 PM
 */

namespace App\Modules\PremiseRegistration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PDF;
use \TCPDF;

class ReportsController extends Controller
{

    protected $user_id;

    public function __construct(Request $req)
    {
        $is_mobile = $req->input('is_mobile');
        if (is_numeric($is_mobile) && $is_mobile > 0) {
            $this->user_id = $req->input('user_id');
        } else {
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
    }

    public function previewDoc(Request $request)
    {
        $folder = $request->input('folder');
        $initialName = $request->input('initialname');
        $savedName = $request->input('savedname');
        $download = $request->input('download');
        $cwd = getcwd();
        $file_path = $cwd . '\\' . $folder . '\\' . $savedName;
        if (isset($download) && $download == 1) {
            return response()->download($file_path, $initialName);
        } else {
            return response()->file($file_path);
        }
    }

    public function printPremiseRegistrationCertificate(Request $request)
    {

        $application_id = $request->input('application_id');
        $sub_module_id = $request->input('sub_module_id');
        $table_name = $request->input('table_name');
        $user_id = $this->user_id;
        $qry = DB::table($table_name . ' as t1')
            ->join('tra_approval_recommendations as t2', function ($join) {
                $join->on('t1.id', '=', 't2.application_id')
                    ->on('t1.application_code', '=', 't2.application_code');
            })
        ->join('tra_premises as t3', 't1.premise_id', '=', 't3.id');
        /*if ($sub_module_id == 2) {//Renewal
            $qry->join('tra_premises as tp', 't1.premise_id', '=', 'tp.id')
                ->join('tra_premises as t3', 'tp.target_id', '=', 't3.id');
        } else {
            $qry->join('tra_premises as t3', 't1.premise_id', '=', 't3.id');
        }*/
        $qry->join('wb_trader_account as t4', 't1.applicant_id', '=', 't4.id')
            ->select(DB::raw('t1.*,t2.decision_id,t3.name as premise_name,t4.name as applicant_name,t3.premise_reg_no'))
            ->where('t1.id', $application_id);
        $app_details = $qry->first();
        if (is_null($app_details)) {
            echo '<p style="color: red">Problem encountered getting application details!!</p>';
            exit();
        }
        $approval_decision = $app_details->decision_id;
        if ($approval_decision != 1) {
            echo '<p style="color: red">The application was not granted. Certificate cannot be generated!!</p>';
            exit();
        }
        $application_code = $app_details->application_code;
        $section_id = $app_details->section_id;
        $process_id = $app_details->process_id;
        $certificate_no = $app_details->premise_reg_no;
        $physical_address = '';
        $postal_address = '';
        $region_name = '';
        $country_name = '';
        $applicant_name = $app_details->applicant_name;
        $premise_name = $app_details->premise_name;
        $district_name = '';
        $location_desc = '';
        $business_type = '';
        $business_type_details = '';
        $business_txt = "</b> region, have been registered to be used as <b>" . ucwords(strtolower($business_type)) . "</b> for <b>" . ucwords(strtolower($business_type_details));
        $business_txt .= "</b> in premises with registration number <b>$certificate_no</b>.";

        $log_data = array(
            'user_id' => $user_id,
            'application_id' => $application_id,
            'application_code' => $application_code,
            'process_id' => $process_id,
            'table_name' => $table_name,
            'section_id' => $section_id,
            'printed_on' => Carbon::now(),
            'printed_by' => $user_id,
            'ip_address' => getIPAddress()
        );
        DB::table('registration_certificate_logs')
            ->insert($log_data);
        $logo = getcwd() . '\resources\images\logo.jpg';
        PDF::setPrintHeader(false);
        PDF::setPrintFooter(false);
        PDF::AddPage();

        PDF::SetMargins(16, 10, 16, true);

        PDF::SetFont('', 'B', 13);

        PDF::SetLineWidth(0.9);
        PDF::Rect(11, 11, 186, 270);

        PDF::SetLineWidth(0.4);
        PDF::Rect(12, 12, 184, 268);

        PDF::SetFont('', 'B', 14);
        PDF::Cell(0, 11, '', 0, 1);
        PDF::Cell(0, 15, 'TANZANIA FOOD AND DRUGS AUTHORITY', 0, 1, 'C');
        PDF::Image($logo, 85, 37, 48, 20);
        PDF::Cell(0, 28, '', 0, 1);
        PDF::SetFont('', 'B', 13);
        PDF::Cell(0, 7, 'REGISTRATION CERTIFICATE OF PREMISES', 0, 1, 'C');
        PDF::SetFont('', 'BI', 11);
        PDF::Cell(0, 7, 'Made under Section 21(3) of the Tanzania Food, Drugs and Cosmetics Act, Cap 219', 0, 1, 'C');
        PDF::Cell(0, 7, '', 0, 1);
        PDF::setCellHeightRatio(1.5);
        PDF::SetFont('', '', 12);

        PDF::SetFont('', '', 12);

        PDF::WriteHTML("This is to certify that the premises owned by M/S <b>" . strtoupper($premise_name) . "</b> of P.O. Box <b>" . ucwords(strtolower($postal_address)) . "</b>, <b>" . ucwords(strtolower($region_name)) . "</b> located at <b>" . strtoupper($physical_address) . "</b> in <b>" . ucwords(strtolower($district_name)) . "</b> " . $location_desc . " in <b>" . ucwords(strtolower($region_name)) . $business_txt, true, 0, true, true, '');
        PDF::Cell(0, 8, '', 0, 1);
        PDF::Cell(0, 7, 'Subject to the following conditions:-', 0, 1);
        PDF::Cell(0, 2, '', 0, 1);

        PDF::Cell(3);
        PDF::Cell(5, 5, '1. ', 0, 0);
        PDF::MultiCell(0, 6, 'The premises and the manner in which the business is to be conducted must conform to requirements of the Tanzania Food, Drugs and Cosmetics Act, Cap 219 or any other written law related to the premises registration at all times failing of which this certificate shall be suspended or revoked.', 0, '', 0, 1);
        PDF::Cell(0, 1, '', 0, 1);

        PDF::Cell(3);
        PDF::Cell(5, 5, '2. ', 0, 0);
        PDF::MultiCell(0, 6, 'Any change in the ownership, business name and location of the registered premises shall be approved by the Authority.', 0, '', 0, 1);
        PDF::Cell(0, 1, '', 0, 1);

        PDF::Cell(3);
        PDF::Cell(5, 5, '3. ', 0, 0);
        PDF::MultiCell(0, 6, 'This certificate is not transferable to other premises or to any other person.', 0, '', 0, 1);
        PDF::Cell(0, 1, '', 0, 1);

        PDF::Cell(3);
        PDF::Cell(5, 5, '4. ', 0, 0);
        PDF::MultiCell(0, 6, 'This certificate shall be displayed conspicuously in the registered premises.', 0, '', 0, 0);
        PDF::Cell(0, 18, '', 0, 1);

        PDF::SetFont('', 'B', 11);
        PDF::Cell(60, 10, '', 'B', 0, 'L');
        PDF::Cell(50);
        PDF::Cell(60, 10, '', 'B', 0, 'L');

        PDF::Ln();
        PDF::SetFont('', 'B', 11);
        PDF::Cell(60, 10, 'Date', 0, 0, 'C');
        PDF::Cell(50);

        PDF::Output($applicant_name . date('Y') . date('m') . date('d') . date('i') . date('s') . '.pdf', 'I');
    }

    public function printPremiseBusinessPermit(Request $request)
    {
        $application_id = $request->input('application_id');
        $sub_module_id = $request->input('sub_module_id');
        $table_name = $request->input('table_name');
        $user_id = $this->user_id;
        $type = 'premise';

        $qry = DB::table($table_name . ' as t1')
           /* ->join('tra_approval_recommendations as t2', function ($join) {
                $join->on('t1.id', '=', 't2.application_id')
                    ->on('t1.application_code', '=', 't2.application_code');
            });*/
        ->join('tra_premises as t3', 't1.premise_id', '=', 't3.id');
       /* if ($sub_module_id == 2) {//Renewal
            $qry->join('tra_premises as tp', 't1.premise_id', '=', 'tp.id')
                ->join('tra_premises as t3', 'tp.target_id', '=', 't3.id');
        } else {
            $qry->join('tra_premises as t3', 't1.premise_id', '=', 't3.id');
        }*/
         $qry->join('tra_approval_recommendations as t2', function ($join) {
             $join->on('t3.permit_id', '=', 't2.id');
         });
        $qry->join('wb_trader_account as t4', 't1.applicant_id', '=', 't4.id')
            // ->join('tra_approval_recommendations as t5', 't1.application_code', '=', 't5.application_code')
            ->select(DB::raw('t1.*,t2.*,t3.name as premise_name,t4.name as applicant_name,t3.premise_reg_no,
            t3.physical_address as premise_physical_address,t3.postal_address as premise_postal_address'))
            ->where('t1.id', $application_id);
        $app_details = $qry->first();

        if (is_null($app_details)) {
            echo '<p style="color: red">Problem encountered getting application details!!</p>';
            exit();
        }
        $approval_decision = $app_details->decision_id;
        if ($approval_decision != 1) {
            echo '<p style="color: red">The application was not granted. Certificate cannot be generated!!</p>';
            exit();
        }

        $application_code = $app_details->application_code;
        $section_id = $app_details->section_id;
        $process_id = $app_details->process_id;
        $certificate_no = $app_details->premise_reg_no;
        $physical_address = '';
        $postal_address = '';
        $region_name = '';
        $country_name = '';
        $applicant_name = $app_details->applicant_name;
        $premise_name = $app_details->premise_name;
        $district_name = '';
        $location_desc = '';
        $business_type = '';
        $business_type_details = '';
        $business_txt = "</b> region, have been registered to be used as <b>" . ucwords(strtolower($business_type)) . "</b> for <b>" . ucwords(strtolower($business_type_details));
        $business_txt .= "</b> in premises with registration number <b>$certificate_no</b>.";
        $ref = $app_details->reference_no;

        $log_data = array(
            'user_id' => $user_id,
            'application_id' => $application_id,
            'application_code' => $application_code,
            'process_id' => $process_id,
            'table_name' => $table_name,
            'section_id' => $section_id,
            'printed_on' => Carbon::now(),
            'printed_by' => $user_id,
            'ip_address' => getIPAddress()
        );
        DB::table('registration_certificate_logs')
            ->insert($log_data);

        $logo = getcwd() . '\resources\images\logo.jpg';
        $pdf = new TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('', '', 13);
        $pdf->Cell(78);
        $pdf->Cell(0, 15, '', 0, 1);
        $pdf->SetMargins(20, 10, 20, true);
        $pdf->SetFont('', 'B', 13);

        $pdf->Cell(0, 5, 'TANZANIA FOOD AND DRUGS AUTHORITY', 0, 2, 'C');
        $pdf->Image($logo, 86, 32, 39, 16);
        $pdf->SetLineWidth(0.9);
        $pdf->Rect(11, 11, 186, 270);

        $pdf->SetLineWidth(0.4);
        $pdf->Rect(12, 12, 184, 268);

        $pdf->SetFont('', 'B', 12);

        $row = $app_details;

        $expiry_date = $row->expiry_date;
        $applicantName = $row->applicant_name;

        //premise details
        $premise_name = $row->premise_name;
        $premise_poastal_address = $row->premise_postal_address;
        $premise_physical_address = $row->premise_physical_address;
        $premise_region_name = '';
        $premise_district_name = '';

        $company_type = '';
        $permit_issue_date = $row->approval_date;

        $permit_no = $row->permit_no;
        $premise_reg_no = $row->premise_reg_no;

        $business_category_name = '';
        $locationDesc = '';

        $business_type = '';
        $business_type_details = '';

        if ($locationDesc == '' || $locationDesc == null) {
            $locationDesc = 'Municipality';
        }

        //if ($section_id == 1 || $section_id == 2) {
        $pdf->Cell(0, 20, '', 0, 1);
        $pdf->Cell(0, 5, 'BUSINESS PERMIT', 0, 1, 'C');
        $pdf->Cell(77);
        $pdf->SetFont('', 'I', 9);
        $pdf->Cell(0, 4, '', 0, 1);
        if ($business_category_name != '') {
            $text = 'to ' . strtolower($business_category_name);
        } else {
            $text = 'to manufacture/process/operate';
        }
        $pdf->setCellHeightRatio(1.5);
        $pdf->Cell(0, 4, 'Made under Section 21(3) of the Tanzania Food, Drugs and Cosmetics Act, Cap 219', 0, 1, 'C');
        $pdf->Cell(0, 6, '', 0, 1);
        $pdf->SetFont('', 'BI', 11);
        $pdf->Cell(0, 10, 'Permit No.' . $permit_no, 0, 1, 'C');
        $pdf->SetFont('', '', 11);
        $pdf->Cell(0, 5, '', 0, 1);
        if ($company_type == '') {
            $company_type = '';
        }
        if (strpos($premise_poastal_address, 'Box') !== false) {
            $premise_poastal_address = "</b><b> " . $premise_poastal_address . " ";
        } else {
            $premise_poastal_address = "</b> of P.O. Box <b>" . $premise_poastal_address . " ";
        }
        if (strpos($business_type, 'Warehouse') !== false) {
            $business_type_details = $business_type;

        }
        if (strpos(strtolower($business_type), 'manufacture') !== false || strpos($business_category_name, 'Process') !== false || strpos($business_category_name, 'Re-pack') !== false) {
            $text_product = 'Registered';

        } else {
            $text_product = '';
        }
        $pdf->setCellHeightRatio(1.5);
        //$pdf->MultiCell(0,8,"Permit is hereby granted to M/S <b>".$applicantName."of P.O. Box <b>".$postal_address." to operate ".$company_type." at the premises situated/lying between ".$physical_address." in ".$districtName." Municipality in <b>".$regionName."</b> region having Registration ".$permit_no,0,'',0,1);
        $pdf->WriteHTML("Permit is hereby granted to M/S <b>" . strtoupper($premise_name) . $premise_poastal_address . ucwords(strtolower($premise_region_name)) . "</b> " . $text . " <b>" . $text_product . ' ' . ucwords(strtolower($business_type_details)) . "</b> at the premises situated at <b>" . strtoupper($premise_physical_address) . "</b> in <b>" . ucwords(strtolower($premise_district_name)) . "</b> " . $locationDesc . " in <b>" . ucwords(strtolower($premise_region_name)) . "</b> region having Registration No. <b>" . $premise_reg_no . "</b>", true, 0, true, true, 'J');

        $pdf->Cell(0, 5, '', 0, 1);

        //update the premise table temporary

        $expiry_date = date('F d\\, Y', strtotime($expiry_date));

        //$pdf->MultiCell(0,8,"This Permit shall have and continue to effect from and including the day when it is issued until it ceases to have effect on ".$expiry_date,0,'',0,1);
        $pdf->setCellHeightRatio(1.5);
        $pdf->WriteHTML("This Permit shall have and continue to effect from and including the day when it is issued until it ceases to have effect on <b>" . $expiry_date . "</b>", true, 0, true, true, 'J');
        $pdf->Cell(20, 5, '', 0, 1);
        $pdf->Cell(30, 5, 'Issued on', 0, 0);

        $permit_issue_date = date('F d\\, Y', strtotime($permit_issue_date));
        $pdf->SetFont('', 'B', 11);
        $pdf->Cell(20, 5, $permit_issue_date, 0, 1);

        $pdf->SetFont('', '', 11);
        //payments made
        $pdf->Cell(20, 5, '', 0, 1);


        $pdf->Cell(0, 15, '', 0, 1);

        $pdf->Cell(20, 5, '..............................................', 0, 0, 'L');

        $pdf->Cell(0, 5, '...................................................', 0, 1, 'R');

        $pdf->SetFont('', 'B', 10);

        //check for signitory
        $permit_signitory = '';
        $title = '';

        $pdf->Cell(50, 5, 'Date', 0, 0, 'C');

        $pdf->Cell(50);
        $pdf->Cell(0, 5, ucwords(strtolower($permit_signitory)), 0, 1, 'C');
        $pdf->Cell(110);
        $pdf->Cell(0, 5, $title . ' DIRECTOR GENERAL', 0, 1, 'C');

        $pdf->Cell(0, 15, '', 0, 1);
        $pdf->SetFont('', 'B', 12);
        $pdf->Cell(0, 5, 'CONDITIONS', 0, 1, 'C');
        $pdf->SetFont('', '', 11);
        $pdf->Cell(0, 5, '', 0, 1);
        $pdf->Cell(5, 0, '1. ', 0, 0);
        $pdf->setCellHeightRatio(1.5);
        $pdf->WriteHTML("This Permit does not authorize the holder to operate business in unregistered premises or during the period of suspension, revocation or cancellation of registration of the premises in respect of which it was issued", true, true, true, true, 'J');
        $pdf->Cell(0, 5, '', 0, 1);
        $pdf->Cell(5, 0, '2. ', 0, 0);
        $pdf->setCellHeightRatio(1.5);
        $pdf->WriteHTML("This Permit is not transferable without approval of the Authority", true, true, true, true, 'J');
        // }

        $pdf->Output($applicantName . date('Y') . date('m') . date('d') . date('i') . date('s') . '.pdf', 'I');

    }

    private function generatePremiseCertificate($section, $ref, $type)
    {
        $this->load->library('tcpdf');
        $logo = getcwd() . '/assets/images/logo.jpg';
        //$logo=base_url() . 'assets/images/logo.jpg';
        $pdf = new TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $this->load->model('registration_model');

        $section_id = $this->registration_model->getSectionIDFromName($section);

        $recommendation_qry = $this->db->query("select recommendation from granting_recommendations where granting_recommendations.reference_no='" . $ref . "' union all select recommendation from manager_grant_reviews where manager_grant_reviews.reference_no='" . $ref . "'");

        if ($recommendation_qry->num_rows() != 0) {
            //Recommendation present

            $r = $recommendation_qry->row();

            if ($r->recommendation == 'Not Grant') {
                if ($r->recommendation == 'Not Grant') {
                    //Application was rejected. Print Rejection Letter.
                    echo "Application was rejected Print Rejection Letter as per the TFDA rejection Letter format!!";
                    exit();
                }

            } else {
                //Application was granted. Print Certificate
                if ($type == 'premise_renewal') {

                    $sql_statement = "select ''  as reference_no,t1.premise_id,t2.name as locationDesc,t1.business_type_id,t1.business_type_detail_id,premises.id as premiseID,process_id,premises.premise_reg_no,premises.name as premiseName,premises.physical_address as premise_phy_addr,premises.postal_address as premise_postal_addr,t1.date_registered,t1.permit_no,vw_applicants.name as applicantName,vw_applicants.* from premises inner join (renewal_businesspermits t1 inner join vw_applicants on t1.applicant_id=vw_applicants.id ) on premises.id=t1.premise_id left join districtmunic_location t2 on premises.distMun_location= t2.id where t1.reference_no='" . $ref . "'";
                } else if ($type == 'premise_alteration') {

                    $sql_statement = "select t5.reference_no as reference_no,t1.premise_id,t2.name as locationDesc,t1.business_type_id,t1.business_type_detail_id,premises.id as premiseID,process_id,premises.premise_reg_no,premises.name as premiseName,premises.physical_address as premise_phy_addr,premises.postal_address as premise_postal_addr,t1.date_registered,t1.permit_no,vw_applicants.name as applicantName,vw_applicants.* from premises inner join (premise_alteration_applications t1 inner join vw_applicants on t1.applicant_id=vw_applicants.id ) on premises.id=t1.premise_id left join districtmunic_location t2 on premises.distMun_location= t2.id inner join active_premisesapplication t5 on t1.premise_id = t5.premise_id where t1.reference_no='" . $ref . "'";
                } else {
                    $sql_statement = "select ''  as reference_no,registered_premises.premise_id,t2.name as locationDesc,registered_premises.business_type_id,registered_premises.business_type_detail_id,premises.id as premiseID,process_id,premises.premise_reg_no,premises.name as premiseName,premises.physical_address as premise_phy_addr,premises.postal_address as premise_postal_addr,registered_premises.date_registered,registered_premises.permit_no,vw_applicants.name as applicantName,vw_applicants.* from premises inner join (registered_premises inner join vw_applicants on registered_premises.applicant_id=vw_applicants.id ) on premises.id=registered_premises.premise_id left join districtmunic_location t2 on premises.distMun_location= t2.id where registered_premises.reference_no='" . $ref . "'";
                }

                $query = $this->db->query($sql_statement);

                if ($query->num_rows() != 0) {
                    $row = $query->row();
                    $applicantName = $row->applicantName;
                    $premise_name = $row->premiseName;
                    $permit_no = $row->permit_no;

                    $date_added = $row->date_registered;
                    $postal_address = $row->postal_address;
                    $physical_address = $row->physical_address;
                    $countryName = $row->countryName;
                    $regionName = $row->regionName;
                    $districtName = $row->districtName;
                    $premiseID = $row->premiseID;
                    $premise_reg_no = $row->premise_reg_no;
                    $business_type_detail_id = $row->business_type_detail_id;
                    $business_type_id = $row->business_type_id;
                    $locationDesc = $row->locationDesc;
                    $premises_id = $row->premise_id;

                    if ($locationDesc == '' || $locationDesc == null) {
                        $locationDesc = "Municipality";

                    }
                    $sql_query = $this->db->query("select business_category.name as business_category from premises left join business_category on premises.business_category_id = business_category.id where premises.id = '" . $premiseID . "'");
                    if ($sql_query->num_rows() > 0) {

                        $row = $sql_query->row();
                        $business_category = $row->business_category;

                    } else {
                        $business_category = '';

                    }
                    $business_type = '';
                    $business_type_details = '';
                    //alow for multiple business types and business type details
                    $i = 1;
                    $sql_query = $this->db->query("select T1.id,T1.reference_no,T2.name as business_type,T2.id as business_type_id,T3.name as business_type_detail,T3.id as business_type_detail_id from premise_other_details T1 left join company_types T2 on T1.business_type_id = T2.id left join  company_types_details T3 on T1.business_type_detail_id = T3.id  where T1.premise_id = '" . $premiseID . "'");
                    if ($sql_query->num_rows() > 0) {
                        $l = $sql_query->num_rows();
                        foreach ($sql_query->result() as $rows) {
                            if ($i == $l) {
                                $business_type = $rows->business_type;
                                $business_type_details .= $rows->business_type_detail;
                            } else {
                                $business_type = $rows->business_type;
                                $business_type_details .= $rows->business_type_detail . ' / ';

                            }

                            $i++;
                        }

                    }
                    //Get Premise Location
                    $sql_statement = "select premises.physical_address,premises.postal_address,premises.premise_reg_no,districts.name as districtName,countries.name as countryName,regions.name as regionName from premises inner join countries on premises.country_id=countries.id left join regions on regions.id= premises.region_id left join districts on premises.district_id = districts.id where  premises.id='" . $premiseID . "' group by premises.id";

                    $query = $this->db->query($sql_statement);
                    if ($query->num_rows() != 0) {
                        $row = $query->row();
                        $premise_phy_addr = $row->physical_address;
                        $premise_postal_addr = $row->postal_address;
                        $premDistrictName = $row->districtName;
                        $premCountryName = $row->countryName;
                        $premRegionName = $row->regionName;
                        $premise_reg_no = $row->premise_reg_no;

                    } else {
                        $premise_phy_addr = '';
                        $premise_postal_addr = '';
                        $premDistrictName = '';
                        $premCountryName = '';
                        $premRegionName = '';
                        $premise_reg_no = '';
                    }

                    //Is the premise registered?
                    $pdf->AddPage();
                    //if($premise_reg_no == ''){
                    $pdf->SetMargins(16, 10, 16, true);
                    //registration certifcate
                    $pdf->SetFont(' ', 'B', 13);
                    //$pdf->Cell(0,10,'iirams',0,1);

                    $pdf->SetLineWidth(0.9);
                    $pdf->Rect(11, 11, 186, 270);

                    $pdf->SetLineWidth(0.4);
                    $pdf->Rect(12, 12, 184, 268);


                    $pdf->SetFont(' ', 'B', 14);
                    $pdf->Cell(0, 11, '', 0, 1);
                    $pdf->Cell(0, 15, 'TANZANIA FOOD AND DRUGS AUTHORITY', 0, 1, 'C');
                    $pdf->Image($logo, 85, 37, 48, 20);
                    $pdf->Cell(0, 28, '', 0, 1);
                    $pdf->SetFont(' ', 'B', 13);
                    $pdf->Cell(0, 7, 'REGISTRATION CERTIFICATE OF PREMISES', 0, 1, 'C');
                    $pdf->SetFont(' ', 'BI', 11);
                    $pdf->Cell(0, 7, 'Made under Section 21(3) of the Tanzania Food, Drugs and Cosmetics Act, Cap 219', 0, 1, 'C');
                    $pdf->Cell(0, 7, '', 0, 1);
                    $pdf->setCellHeightRatio(1.5);
                    $pdf->SetFont(' ', '', 12);
                    if (strpos($premise_postal_addr, 'Box') !== false) {
                        $text = '';
                    } else {

                        $text = 'P.O. Box';
                    }

                    if ($section_id == 1) {
                        $business_type_detailsMain = $business_type_details;
                        if (strpos($business_category, 'Process') !== false) {
                            $business_type = 'Food Processing Premises';
                            $business_type_details = "processing of " . $business_type_details;
                        }
                        if (strpos($business_category, 'Manufacture') !== false) {
                            $business_type = 'Food Manufacturing Premises';
                            $business_type_details = "manufacturing of " . $business_type_details;
                        }
                        if (strpos($business_category, 'Pack') !== false) {
                            $business_type = 'Food Manufacturing Premises';
                            $business_type_details = "re-packing of " . $business_type_details;
                        }

                        if (strpos(strtolower($business_type), 'warehouse') !== false) {
                            $business_type = $business_type;
                            $business_type_details = "storage of Food Stuffs";
                        }
                        if (strpos($business_category, 'Operate') !== false && strpos(strtolower($business_type_details), 'warehouse') !== false) {

                            $business_type = $business_type_details;

                            $business_type_details = "storage of Food Stuffs";

                        }
                        if (strpos(strtolower($business_type), 'super') !== false || strpos(strtolower($business_type), 'mini') !== false || strpos(strtolower($business_type), 'market') !== false || strpos(strtolower($business_type_details), 'super') !== false || strpos(strtolower($business_type_details), 'hotel') !== false || strpos(strtolower($business_type), 'super') !== false) {
                            $business_type = $business_type_details;

                            $business_type_details = "selling of Food Stuffs";
                        }

                        $textBusiness = "</b> region, have been registered to be used as <b>" . ucwords(strtolower($business_type)) . "</b> for <b>" . ucwords(strtolower($business_type_details));
                        $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";
                    } else if ($section_id == 2) {


                        if (strpos(strtolower($business_type), 'wholesale') !== false && strpos(strtolower($business_type), 'importation') !== false) {
                            //$business_type = "a ".ucwords(strtolower($business_type))." for Importation and";
                            $business_type = "a " . ucwords(strtolower("wholesale")) . " for Importation and";
                            $business_type_details = "Selling of " . ucwords(strtolower($business_type_details));
                            $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . "</b> <b>" . $business_type_details;
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";
                        } else if (strpos(strtolower($business_type), 'wholesale') !== false && strpos(strtolower($business_type), 'importation') == false) {
                            $business_type = "a " . ucwords(strtolower($business_type));
                            $business_type_details = "of " . ucwords(strtolower($business_type_details));
                            $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . "</b> <b>" . $business_type_details;
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";
                        } //comment
                        else if (strpos($business_type, 'Warehouse') !== false) {
                            $business_type = "a " . ucwords(strtolower($business_type));
                            if (strpos($business_type_details, 'Raw') !== false) {

                                $business_type_details = "Storing " . ucwords(strtolower($business_type_details));

                            } else {
                                $business_type_details = "Storing Registered " . ucwords(strtolower($business_type_details));
                            }

                            $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . "</b> for <b>" . $business_type_details;
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";
                        } else if (strpos(strtolower($business_type), 'manufactur') !== false) {

                            $business_type = "to manufacture for sell <b>Registered " . ucwords(strtolower($business_type_details)) . "</b>";
                            $textBusiness = "</b> region, have been registered " . $business_type;
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";

                        } else if (strpos(strtolower($business_type), 'retail') !== false) {

                            $business_type_details = "</b> to operate <b>a business of retail Pharmacy for sale of " . ucwords(strtolower($business_type_details));
                            $textBusiness = "</b> region, have been registered <b>" . $business_type_details;
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";

                        } else if (strpos(strtolower($business_type), 'dldm') !== false) {
                            $business_type = "a " . ucwords(strtolower($business_type)) . " for Importation and";
                            $business_type_details = "an Accredited Drug Dispensing Outlet for retail sale of " . ucwords(strtolower($business_type_details));
                            $textBusiness = "</b> region, have been registered <b>" . $business_type_details;
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";

                        } else if (strpos(strtolower($business_type), 'distributor') !== false OR strpos(strtolower($business_type_details), 'carrier') !== false) {
                            $premise_name = $applicantName;


                            if (strpos(strtolower($business_type_details), 'carrier') !== false) {

                                $business_type = $business_type_details;
                                $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . " ";
                                $textBusiness .= "</b> with registration number <b>$premise_reg_no</b>.";
                            } else {
                                $business_type = "a " . $business_type . " for Importation and";
                                $business_type .= "Selling " . $business_type_details;
                                $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . " ";
                                $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";
                            }

                        } else {

                            $business_type = "a " . ucwords(strtolower($business_type)) . " for Importation and";
                            $business_type_details = "Selling " . ucwords(strtolower($business_type_details));
                            $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . " " . $business_type_details;
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";

                        }

                    } else if ($section_id == 3) {
                        if (strpos(strtolower($business_type), 'warehouse') !== false) {

                            //$business_type = "a manufacturer ".ucwords(strtolower($business_type));
                            $business_type = "a " . ucwords(strtolower($business_type));
                            $business_type_details = "Storing " . ucwords(strtolower($business_type_details));
                            $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . "</b> for <b>" . $business_type_details;
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";
                        } else if (strpos(strtolower($business_type), 'manufacture') !== false) {

                            $business_type = "to manufacture for sell <b>Registered " . ucwords($business_type_details) . "</b>";
                            $textBusiness = "</b> region, have been registered " . $business_type;
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";

                        } else if (strpos(strtolower($business_type), 'wholesale') !== false) {
                            $business_type = "a " . ucwords(strtolower($business_type)) . " Premises ";
                            $business_type_details = "Selling Registered " . ucwords(strtolower($business_type_details));
                            $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . "</b> for <b>" . $business_type_details . "</b>";
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";
                        } else if (strpos(strtolower($business_type), 'retail') !== false) {
                            //$business_type = "a ".ucwords(strtolower($business_type))." for Importation of ".ucwords(strtolower($business_type_details))." for";
                            $business_type_details = "Selling Registered " . ucwords(strtolower($business_type_details));
                            //$textBusiness = "</b> region have been registered to be used as <b>".ucwords(strtolower($business_type))."</b> for <b>".$business_type_details."</b>";
                            $textBusiness = "</b> region, have been registered to be used as <b>" . ucwords(strtolower($business_type)) . "</b> premises <b>" . $business_type_details . "</b>";
                            $textBusiness .= "</b> with registration number <b>$premise_reg_no</b>.";
                        } else if (strpos(strtolower($business_type), 'distributor') !== false OR strpos(strtolower($business_type_details), 'carrier') !== false) {
                            $premise_name = $applicantName;


                            if (strpos(strtolower($business_type_details), 'carrier') !== false) {

                                $business_type = $business_type_details;
                                $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . " ";
                                $textBusiness .= "</b> with registration number <b>$premise_reg_no</b>.";
                            } else {
                                $business_type = "a " . $business_type . " for Importation and";
                                $business_type .= "Selling " . $business_type_details;
                                $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . " ";
                                $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";
                            }

                        } else {
                            $business_type_details = "Selling Registered " . ucwords(strtolower($business_type_details));
                            //$textBusiness = "</b> region have been registered to be used as <b>".ucwords(strtolower($business_type))."</b> for <b>".$business_type_details."</b>";
                            $textBusiness = "</b> region, have been registered to be used as <b>" . ucwords(strtolower($business_type)) . "</b> premises <b>" . $business_type_details . "</b>";
                            $textBusiness .= "</b> with registration number <b>$premise_reg_no</b>.";
                        }

                    } else if ($section_id == 4) {
                        if (strpos(strtolower($business_type), 'warehouse') !== false) {

                            $business_type = "a " . ucwords(strtolower($business_type));
                            $business_type_details = "Storing Registered " . ucwords(strtolower($business_type_details));
                            $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . "</b> for <b>" . $business_type_details;
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";
                        } else if (strpos(strtolower($business_type), 'manufactur') !== false) {

                            $business_type = "to manufacture for sell <b>Registered " . ucwords(strtolower($business_type_details)) . "</b>";
                            $textBusiness = "</b> region, have been registered " . $business_type;
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";

                        } else if (strpos(strtolower($business_type), 'wholesale') !== false && strpos(strtolower($business_type), 'importation') !== false) {
                            //$business_type = "a ".ucwords(strtolower($business_type))." for Importation and";
                            $business_type = "a " . ucwords(strtolower("wholesale")) . " for Importation and";
                            $business_type_details = "Selling of " . ucwords(strtolower($business_type_details));
                            $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . "</b> <b>" . $business_type_details;
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";
                        } else if (strpos(strtolower($business_type), 'wholesale') !== false && strpos(strtolower($business_type), 'importation') == false) {
                            $business_type = "a " . ucwords(strtolower($business_type));
                            $business_type_details = "of " . ucwords(strtolower($business_type_details));
                            $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . "</b> <b>" . $business_type_details;
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";
                        } else if (strpos(strtolower($business_type), 'distributor') !== false OR strpos(strtolower($business_type_details), 'carrier') !== false) {
                            $premise_name = $applicantName;


                            if (strpos(strtolower($business_type_details), 'carrier') !== false) {

                                $business_type = $business_type_details;
                                $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . " ";
                                $textBusiness .= "</b> with registration number <b>$premise_reg_no</b>.";
                            } else {
                                $business_type = "a " . $business_type . " for Importation and";
                                $business_type .= "Selling " . $business_type_details;
                                $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . " ";
                                $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";
                            }

                        } else if (strpos(strtolower($business_type), 'retail') !== false) {
                            $textBusiness = "</b> to operate <b>a premises for dealing in " . ucwords(strtolower($business_type)) . " Selling of Registered " . ucwords(strtolower($business_type_details)) . "";
                        } else {

                            $business_type = "a " . ucwords(strtolower($business_type)) . " for Importation and";
                            $business_type_details = "Selling Registered " . ucwords(strtolower($business_type_details));


                            $textBusiness = "</b> region, have been registered to be used as <b>" . $business_type . "</b> <b>" . ucwords(strtolower($business_type_details));
                            $textBusiness .= "</b> in premises with registration number <b>$premise_reg_no</b>.";
                        }

                    }

                    $pdf->WriteHTML("This is to certify that the premises owned by M/S <b>" . strtoupper($premise_name) . "</b> of P.O. Box <b>" . ucwords(strtolower($premise_postal_addr)) . "</b>, <b>" . ucwords(strtolower($premRegionName)) . "</b> located at <b>" . strtoupper($premise_phy_addr) . "</b> in <b>" . ucwords(strtolower($premDistrictName)) . "</b> " . $locationDesc . " in <b>" . ucwords(strtolower($premRegionName)) . $textBusiness, true, 0, true, true, '');
                    $pdf->Cell(0, 8, '', 0, 1);
                    $pdf->Cell(0, 7, 'Subject to the following conditions:-', 0, 1);
                    $pdf->Cell(0, 2, '', 0, 1);

                    $pdf->Cell(3);
                    $pdf->Cell(5, 5, '1. ', 0, 0);
                    $pdf->MultiCell(0, 6, 'The premises and the manner in which the business is to be conducted must conform to requirements of the Tanzania Food, Drugs and Cosmetics Act, Cap 219 or any other written law related to the premises registration at all times failing of which this certificate shall be suspended or revoked.', 0, '', 0, 1);
                    $pdf->Cell(0, 1, '', 0, 1);

                    $pdf->Cell(3);
                    $pdf->Cell(5, 5, '2. ', 0, 0);
                    $pdf->MultiCell(0, 6, 'Any change in the ownership, business name and location of the registered premises shall be approved by the Authority.', 0, '', 0, 1);
                    $pdf->Cell(0, 1, '', 0, 1);

                    $pdf->Cell(3);
                    $pdf->Cell(5, 5, '3. ', 0, 0);
                    $pdf->MultiCell(0, 6, 'This certificate is not transferable to other premises or to any other person.', 0, '', 0, 1);
                    $pdf->Cell(0, 1, '', 0, 1);

                    $pdf->Cell(3);
                    $pdf->Cell(5, 5, '4. ', 0, 0);
                    $pdf->MultiCell(0, 6, 'This certificate shall be displayed conspicuously in the registered premises.', 0, '', 0, 0);
                    $pdf->Cell(0, 18, '', 0, 1);

                    $pdf->SetFont(' ', 'B', 11);
                    $pdf->Cell(60, 10, '', 'B', 0, 'L');
                    $pdf->Cell(50);
                    $pdf->Cell(60, 10, '', 'B', 0, 'L');

                    $pdf->Ln();
                    $pdf->SetFont(' ', 'B', 11);
                    $pdf->Cell(60, 10, 'Date', 0, 0, 'C');
                    $pdf->Cell(50);
                    $signatory = '';//$this->getProcessSignatory($row->process_id);
                    $designation = '';
                    //get the directoros signitory
                    $permit_signitory = '';
                    $title = '';
                    $signatory_details = getApprovalsignatorydetails($ref);
                    if (is_array($signatory_details)) {
                        $permit_signitory = $signatory_details['permit_signitory'];
                        $title = $signatory_details['title'];
                    }

                    $pdf->Cell(0, 5, ucwords(strtolower($permit_signitory)), 0, 1, 'C');
                    $pdf->Cell(110);
                    $pdf->Cell(0, 5, $title . 'DIRECTOR GENERAL', 0, 1, 'C');

                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                    //	$pdf->Output(date('Y').date('m').date('d').date('i').date('s').'.doc','I');
                    $pdf->Output(date('Y') . date('m') . date('d') . date('i') . date('s') . '.pdf', 'I');


                } else {
                    $pdf->SetFont(' ', '', 10);
                    $pdf->MultiCell(0, 15, 'Premise Data for application with reference no:  {' . $ref . '} was not found. Please contact your System administrator if the problem persists.', 0, 'L');
                    $pdf->Output(date('Y') . date('m') . date('d') . date('i') . date('s') . '.pdf', 'I');
                }
            }
        } else {
            //Grant Recommendation was not found
            $pdf->SetFont(' ', '', 10);
            $pdf->Cell(78);
            $pdf->Cell(20, 0, 'The Application with reference no "' . $ref . '" has not been granted/not granted by the DG', 0, 2, 'C');
            $pdf->Output(date('Y') . date('m') . date('d') . date('i') . date('s') . '.pdf', 'I');
        }
    }

    public function getManagersReports(Request $request)
    {
        $type = $request->input('report_type');
        $pdf = new TCPDF();
        $pdf->AddPage();
        if ($type == 'manager_evaluation') {
            $txt = 'Manager Evaluation Report';
        } else if ($type == 'manager_auditing') {
            $txt = 'Manager Auditing Report';
        } else {
            $txt = $type;
        }
        echo '<p style="color: red">' . $txt . '</p>';
        exit();
        $pdf->Output(date('Y') . date('m') . date('d') . date('i') . date('s') . '.pdf', 'I');
    }

}