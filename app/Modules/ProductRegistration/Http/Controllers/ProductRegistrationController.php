<?php

namespace App\Modules\ProductRegistration\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ProductRegistrationController extends Controller
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

    public function getProductApplications(Request $request)
    {
        $module_id = $request->input('module_id');
        $section_id = $request->input('section_id');
        $sub_module_id = $request->input('sub_module_id');
        $workflow_stage_id = $request->input('workflow_stage_id');
        $user_id = $this->user_id;
        $assigned_groups = getUserGroups($user_id);
        $is_super = belongsToSuperGroup($assigned_groups);
        try {
            $assigned_stages = getAssignedProcessStages($user_id, $module_id);

            $qry = DB::table('tra_product_applications as t1')
                ->join('tra_submissions as t7', 't1.application_code', '=', 't7.application_code')
                ->join('tra_product_information as t2', 't1.product_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('wf_tfdaprocesses as t4', 't7.process_id', '=', 't4.id')
                ->join('wf_workflow_stages as t5', 't7.current_stage', '=', 't5.id')
                ->join('par_system_statuses as t6', 't1.application_status_id', '=', 't6.id')
                ->join('users as t8', 't7.usr_from', '=', 't8.id')
                ->join('users as t9', 't7.usr_to', '=', 't9.id')
                ->select(DB::raw("t7.date_received, CONCAT_WS(' ',decrypt(t8.first_name),decrypt(t8.last_name)) as from_user,CONCAT_WS(' ',decrypt(t9.first_name),decrypt(t9.last_name)) as to_user,  t1.id as active_application_id, t1.application_code, t4.module_id, t4.sub_module_id, t4.section_id, t2.brand_name as product_name,
                    t6.name as application_status, t3.name as applicant_name, t4.name as process_name, t5.name as workflow_stage, t5.is_general, t3.contact_person,
                    t3.tin_no, t3.country_id as app_country_id, t3.region_id as app_region_id, t3.district_id as app_district_id, t3.physical_address as app_physical_address,
                    t3.postal_address as app_postal_address, t3.telephone_no as app_telephone, t3.fax as app_fax, t3.email as app_email, t3.website as app_website,
                    t2.*, t1.*"));

            $is_super ? $qry->whereRaw('1=1') : $qry->whereIn('t1.workflow_stage_id', $assigned_stages);


            if (validateIsNumeric($section_id)) {
                $qry->where('t1.section_id', $section_id);
            }
            if (validateIsNumeric($sub_module_id)) {
                $qry->where('t1.sub_module_id', $sub_module_id);
            }
            if (validateIsNumeric($workflow_stage_id)) {
                $qry->where('t1.workflow_stage_id', $workflow_stage_id);
            }
            $qry->where('t7.isDone', 0);
            $results = $qry->get();

            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);

    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function saveRenAltProductReceivingBaseDetails(Request $request)
    {
        try {
            $reg_product_id = $request->input('reg_product_id');
            $tra_product_id = $request->input('tra_product_id');
            $active_application_id = $request->input('active_application_id');
            $applicant_id = $request->input('applicant_id');
            $process_id = $request->input('process_id');
            $workflow_stage_id = $request->input('workflow_stage_id');
            $zone_id = $request->input('zone_id');
            $section_id = $request->input('section_id');
            $module_id = $request->input('module_id');
            $sub_module_id = $request->input('sub_module_id');
            $local_agent_id = $request->input('local_applicant_id');
            $user_id = $this->user_id;
            $product_id = $request->input('product_id');
            $classification_id = $request->input('classification_id');
            $assessment_procedure_id = $request->input('assessment_procedure_id');

            $prod_data = array("dosage_form_id" => $request->input('dosage_form_id'),
                "classification_id" => $request->input('classification_id'),
                "brand_name" => $request->input('brand_name'),
                "common_name_id" => $request->input('common_name_id'),
                "product_strength" => $request->input('product_strength'),
                "physical_description" => $request->input('physical_description'),
                "si_unit_id" => $request->input('si_unit_id'),
                "atc_code_id" => $request->input('atc_code_id'),
                "product_form_id" => $request->input('product_form_id'),
                "storage_condition_id" => $request->input('storage_condition_id'),
                "product_type_id" => $request->input('product_type_id'),
                "product_category_id" => $request->input('product_category_id'),
                "distribution_category_id" => $request->input('distribution_category_id'),
                "special_category_id" => $request->input('special_category_id'),
                "product_subcategory_id" => $request->input('product_subcategory_id'),
                "intended_enduser_id" => $request->input('intended_enduser_id'),
                "intended_use_id" => $request->input('intended_use_id'),
                "route_of_administration_id" => $request->input('route_of_administration_id'),
                "method_ofuse_id" => $request->input('method_ofuse_id'),
                "instructions_of_use" => $request->input('instructions_of_use'),
                "warnings" => $request->input('warnings'),
                "gmdn_code" => $request->input('gmdn_code'),
                "gmdn_term" => $request->input('gmdn_term'),
                "gmdn_category" => $request->input('gmdn_category'),
                "manufacturing_date" => $request->input('manufacturing_date'),
                "expiry_date" => $request->input('expiry_date'),
                "device_type_id" => $request->input('device_type_id'),

                "shelf_lifeafter_opening" => $request->input('shelf_lifeafter_opening'),
                "shelf_life" => $request->input('shelf_life'),

                "section_id" => $request->input('section_id'));
            $applications_table = 'tra_product_applications';

            $products_table = 'tra_product_information';
            if (validateIsNumeric($active_application_id)) {
                //update

                //Application_edit
                $application_params = array(
                    'applicant_id' => $applicant_id,
                    'local_agent_id' => $local_agent_id,
                    'zone_id' => $zone_id
                );
                $where_product = array(
                    'id' => $product_id
                );
                $where_app = array(
                    'id' => $active_application_id
                );
                $app_details = array();
                if (recordExists($applications_table, $where_app)) {
                    //$app_details = getTableData($applications_table, $where_app);
                    $app_details = getPreviousRecords($applications_table, $where_app);
                    if ($app_details['success'] == false) {
                        return $app_details;
                    }
                    $app_details = $app_details['results'];
                    updateRecord($applications_table, $app_details, $where_app, $application_params, $user_id);
                }

                $application_code = $app_details[0]['application_code'];//$app_details->application_code;
                $ref_number = $app_details[0]['reference_no'];//$app_details->reference_no;

                $where_product = array(
                    'id' => $product_id
                );
                //Premise_edit
                $prod_data['dola'] = Carbon::now();
                $prod_data['altered_by'] = $user_id;
                $previous_data = getPreviousRecords($products_table, $where_product);
                if ($previous_data['success'] == false) {
                    return $previous_data;
                }
                $previous_data = $previous_data['results'];
                $res = updateRecord($products_table, $previous_data, $where_product, $prod_data, $user_id);
                $res['active_application_id'] = $active_application_id;
                $res['application_code'] = $application_code;
                $res['product_id'] = $product_id;
                $res['ref_no'] = $ref_number;

            } else {
                //check for previous applicaitons 
                //expiry dates check span
                $anyOngoingApps = checkForOngoingApplications($reg_product_id, $applications_table, 'reg_product_id', $process_id);
                if ($anyOngoingApps['exists'] == true) {
                    $res = array(
                        'success' => false,
                        'message' => 'There is an ongoing application pending approval with reference number ' . $anyOngoingApps['ref_no']
                    );
                    return \response()->json($res);
                }

                $dms_node_details = getApplicationSubModuleNodeDetails($section_id, $module_id, $sub_module_id, $user_id);

                if ($dms_node_details != '') {
                    $prod_data['created_by'] = \Auth::user()->id;
                    $prod_data['created_on'] = Carbon::now();

                    $res = insertRecord('tra_product_information', $prod_data, $user_id);

                    $record_id = $res['record_id'];
                    $product_id = $res['record_id'];
                    $applications_table = 'tra_product_applications';
                    //get the primary reference no
                    $application_code = generateApplicationCode($sub_module_id, $applications_table);
                    $application_status = getApplicationInitialStatus($module_id, $sub_module_id);

                    $ref_id = getSingleRecordColValue('refnumbers_formats', array('sub_module_id' => $sub_module_id, 'module_id' => $module_id, 'refnumbers_type_id' => 1), 'id');
                    $where_statement = array('sub_module_id' => 7, 't1.reg_product_id' => $reg_product_id);
                    $primary_reference_no = getProductPrimaryReferenceNo($where_statement, 'tra_product_applications');
                    $codes_array = array(
                        'ref_no' => $primary_reference_no
                    );
                    $ref_number = generateProductsSubRefNumber($reg_product_id, $applications_table, $ref_id, $codes_array, $sub_module_id, $user_id);

                    //save other products details 
                    //ingredients
                   


                    $where_statement = array('tra_product_id' => $product_id);
                    //save other applications details 

                    $app_data = array(
                        "process_id" => $request->input('process_id'),
                        "workflow_stage_id" => $request->input('workflow_stage_id'),
                        "application_status_id" => $application_status->status_id,
                        "application_code" => $application_code,
                        "reference_no" => $ref_number,
                        "applicant_id" => $request->input('applicant_id'),
                        "sub_module_id" => $request->input('sub_module_id'),
                        "module_id" => $request->input('module_id'),
                        "section_id" => $request->input('section_id'),
                        "product_id" => $product_id,
                        "local_agent_id" => $request->input('local_applicant_id'),
                        "assessment_procedure_id" => $request->input('assessment_procedure_id'),
                        "date_added" => Carbon::now(),
                        'reg_product_id' => $reg_product_id,
                        'reg_product_id' => $reg_product_id,
                        "created_by" => \Auth::user()->id,
                        "created_on" => Carbon::now());

                    $res = insertRecord('tra_product_applications', $app_data, $user_id);
                    $active_application_id = $res['record_id'];

                    //add to submissions table
                    $submission_params = array(
                        'application_id' => $active_application_id,
                        'process_id' => $process_id,
                        'application_code' => $application_code,
                        'reference_no' => $ref_number,
                        'usr_from' => $user_id,
                        'usr_to' => $user_id,
                        'previous_stage' => $workflow_stage_id,
                        'current_stage' => $workflow_stage_id,
                        'module_id' => $module_id,
                        'sub_module_id' => $sub_module_id,
                        'section_id' => $section_id,
                        'application_status_id' => $application_status->status_id,
                        'urgency' => 1,
                        'applicant_id' => $applicant_id,
                        'remarks' => 'Initial save of the application',
                        'date_received' => Carbon::now(),
                        'created_on' => Carbon::now(),
                        'created_by' => $user_id
                    );

                    insertRecord('tra_submissions', $submission_params, $user_id);
                    $res['active_application_id'] = $active_application_id;
                    $res['application_code'] = $application_code;
                    $res['product_id'] = $product_id;
                    $res['ref_no'] = $ref_number;
                    //dms function

                    //dms function
                    initializeApplicationDMS($section_id, $module_id, $sub_module_id, $application_code, $ref_number, $user_id);

                } else {
                    $res = array(
                        'success' => false,
                        'message' => 'DMS Repository for the selected Application hasn\'t been configured, contact the system administration.');
                }


            }

        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);


    }

    public function saveNewProductReceivingBaseDetails(Request $request)
    {
        try {
            $active_application_id = $request->input('active_application_id');
            $applicant_id = $request->input('applicant_id');
            $process_id = $request->input('process_id');
            $workflow_stage_id = $request->input('workflow_stage_id');
            $zone_id = $request->input('zone_id');
            $section_id = $request->input('section_id');
            $module_id = $request->input('module_id');
            $sub_module_id = $request->input('sub_module_id');
            $local_agent_id = $request->input('local_applicant_id');
            $user_id = $this->user_id;
            $product_id = $request->input('product_id');
            $classification_id = $request->input('classification_id');
            $assessment_procedure_id = $request->input('assessment_procedure_id');
                
            $prod_data = array("dosage_form_id" => $request->input('dosage_form_id'),
                "classification_id" => $request->input('classification_id'),
                "brand_name" => $request->input('brand_name'),
                "common_name_id" => $request->input('common_name_id'),
                "product_strength" => $request->input('product_strength'),
                "physical_description" => $request->input('physical_description'),
                "si_unit_id" => $request->input('si_unit_id'),
                "atc_code_id" => $request->input('atc_code_id'),
                "product_form_id" => $request->input('product_form_id'),
                "storage_condition_id" => $request->input('storage_condition_id'),
                "product_type_id" => $request->input('product_type_id'),
                "product_category_id" => $request->input('product_category_id'),
                "distribution_category_id" => $request->input('distribution_category_id'),
                "special_category_id" => $request->input('special_category_id'),
                "product_subcategory_id" => $request->input('product_subcategory_id'),
                "intended_enduser_id" => $request->input('intended_enduser_id'),
                "intended_use_id" => $request->input('intended_use_id'),
                "route_of_administration_id" => $request->input('route_of_administration_id'),
                "method_ofuse_id" => $request->input('method_ofuse_id'),
                "instructions_of_use" => $request->input('instructions_of_use'),

                "warnings" => $request->input('warnings'),
                "gmdn_code" => $request->input('gmdn_code'),
                "gmdn_term" => $request->input('gmdn_term'),
                "gmdn_category" => $request->input('gmdn_category'),
                "manufacturing_date" => $request->input('manufacturing_date'),
                "expiry_date" => $request->input('expiry_date'),
                "device_type_id" => $request->input('device_type_id'),
                "assessment_procedure_id" => $request->input('assessment_procedure_id'),


                "shelf_lifeafter_opening" => $request->input('shelf_lifeafter_opening'),
                "shelf_life" => $request->input('shelf_life'),

                "section_id" => $request->input('section_id'));

            if (validateIsNumeric($active_application_id)) {
                //update
                $applications_table = 'tra_product_applications';

                $products_table = 'tra_product_information';
                //Application_edit
                $application_params = array(
                    'applicant_id' => $applicant_id,
                    'local_agent_id' => $local_agent_id,
                    'zone_id' => $zone_id
                );
                $where_product = array(
                    'id' => $product_id
                );
                $where_app = array(
                    'id' => $active_application_id
                );
                $app_details = array();
                if (recordExists($applications_table, $where_app)) {
                    //$app_details = getTableData($applications_table, $where_app);
                    $app_details = getPreviousRecords($applications_table, $where_app);
                    if ($app_details['success'] == false) {
                        return $app_details;
                    }
                    $app_details = $app_details['results'];
                    updateRecord($applications_table, $app_details, $where_app, $application_params, $user_id);
                }

                $application_code = $app_details[0]['application_code'];//$app_details->application_code;
                $ref_number = $app_details[0]['reference_no'];//$app_details->reference_no;

                $where_product = array(
                    'id' => $product_id
                );
                //Premise_edit
                $prod_data['dola'] = Carbon::now();
                $prod_data['altered_by'] = $user_id;
                $previous_data = getPreviousRecords($products_table, $where_product);
                if ($previous_data['success'] == false) {
                    return $previous_data;
                }
                $previous_data = $previous_data['results'];
                $res = updateRecord($products_table, $previous_data, $where_product, $prod_data, $user_id);
                $res['active_application_id'] = $active_application_id;
                $res['application_code'] = $application_code;
                $res['product_id'] = $product_id;
                $res['ref_no'] = $ref_number;

            } else {

                $dms_node_details = getApplicationSubModuleNodeDetails($section_id, $module_id, $sub_module_id, $user_id);

                if ($dms_node_details != '') {
                    $prod_data['created_by'] = \Auth::user()->id;
                    $prod_data['created_on'] = Carbon::now();

                    $res = insertRecord('tra_product_information', $prod_data, $user_id);

                    $record_id = $res['record_id'];
                    $product_id = $res['record_id'];
                    $applications_table = 'tra_product_applications';

                    $zone_code = getSingleRecordColValue('par_zones', array('id' => $zone_id), 'zone_code');
                    $section_code = getSingleRecordColValue('par_sections', array('id' => $section_id), 'code');
                    $class_code = getSingleRecordColValue('par_classifications', array('id' => $classification_id), 'code');

                    $assessment_code = getSingleRecordColValue('par_assessment_procedures', array('id' => $assessment_procedure_id), 'code');

                    $codes_array = array(
                        'section_code' => $section_code,
                        'zone_code' => $zone_code,
                        'class_code' => $class_code,
                        'assessment_code' => $assessment_code
                    );

                    $application_code = generateApplicationCode($sub_module_id, $applications_table);
                    $application_status = getApplicationInitialStatus($module_id, $sub_module_id);

                    $ref_number = generateProductsRefNumber(12, $codes_array, date('Y'), $process_id, $zone_id, $user_id);

                    $registration_data = array('tra_product_id' => $product_id,
                        'status_id' => $application_status,
                        'validity_status_id' => 1,
                        'registration_status_id' => 1
                    );
                    $where_statement = array('tra_product_id' => $product_id);
                    saveApplicationRegistrationDetails('tra_registered_products', $registration_data, $where_statement, $user_id);

                    $app_data = array(
                        "process_id" => $request->input('process_id'),
                        "workflow_stage_id" => $request->input('workflow_stage_id'),
                        "application_status_id" => $application_status->status_id,
                        "application_code" => $application_code,
                        "reference_no" => $ref_number,
                        "applicant_id" => $request->input('applicant_id'),
                        "sub_module_id" => $request->input('sub_module_id'),
                        "module_id" => $request->input('module_id'),
                        "section_id" => $request->input('section_id'),
                        "product_id" => $product_id,
                        "local_agent_id" => $request->input('local_agent_id'),
                        "assessment_procedure_id" => $request->input('assessment_procedure_id'),
                        "date_added" => Carbon::now(),
                        "created_by" => \Auth::user()->id,
                        "created_on" => Carbon::now());

                    $res = insertRecord('tra_product_applications', $app_data, $user_id);
                    $active_application_id = $res['record_id'];

                    //add to submissions table
                    $submission_params = array(
                        'application_id' => $active_application_id,
                        'process_id' => $process_id,
                        'application_code' => $application_code,
                        'reference_no' => $ref_number,
                        'usr_from' => $user_id,
                        'usr_to' => $user_id,
                        'previous_stage' => $workflow_stage_id,
                        'current_stage' => $workflow_stage_id,
                        'module_id' => $module_id,
                        'sub_module_id' => $sub_module_id,
                        'section_id' => $section_id,
                        'application_status_id' => $application_status->status_id,
                        'urgency' => 1,
                        'applicant_id' => $applicant_id,
                        'remarks' => 'Initial save of the application',
                        'date_received' => Carbon::now(),
                        'created_on' => Carbon::now(),
                        'created_by' => $user_id
                    );

                    insertRecord('tra_submissions', $submission_params, $user_id);
                    $res['active_application_id'] = $active_application_id;
                    $res['application_code'] = $application_code;
                    $res['product_id'] = $product_id;
                    $res['ref_no'] = $ref_number;
                    //dms function 
                    $nodetracking = str_replace("/", "-", $ref_number);
                    $parentnode_ref = $dms_node_details->node_ref;

                    $node_details = array(
                        'name' => $nodetracking,
                        'nodeType' => 'cm:folder');

                    $response = dmsCreateAppRootNodesChildren($parentnode_ref, $node_details);

                    if ($response['success']) {
                        $dms_node_id = $response['node_details']->id;
                        saveApplicationDocumentNodedetails($module_id, $sub_module_id, $application_code, '', $ref_number, $dms_node_id, $user_id);

                    } else {
                        $res = $response;
                    }

                } else {
                    $res = array(
                        'success' => false,
                        'message' => 'DMS Repository for the selected Application asnt been configured, contact the system administration.');
                }


            }

        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function uploadApplicationFile(Request $req)
    {
        $application_id = $req->input('application_id');
        $description = $req->input('description');
        $user_id = $this->user_id;
        $res = array();
        try {
            $record = DB::table('tra_product_applications')
                ->where('id', $application_id)
                ->first();
            $application_code = $record->application_code;
            $workflow_stage_id = $record->workflow_stage_id;

            if ($req->hasFile('uploaded_doc')) {
                $file = $req->file('uploaded_doc');
                $origFileName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileSize = $file->getClientSize();
                $folder = '\resources\uploads';
                $destination = getcwd() . $folder;
                $savedName = str_random(5) . time() . '.' . $extension;
                $file->move($destination, $savedName);
                $params = array(
                    'application_id' => $application_id,
                    'application_code' => $application_code,
                    'workflow_stage_id' => $workflow_stage_id,
                    'initial_filename' => $origFileName,
                    'savedname' => $savedName,
                    'filesize' => formatBytes($fileSize),
                    'filetype' => $extension,
                    'server_filepath' => $destination,
                    'server_folder' => $folder,
                    'description' => $description,
                    'created_on' => Carbon::now(),
                    'created_by' => \Auth::user()->id
                );

                $res = insertRecord('tra_product_application_uploads', $params, $user_id);
                if ($res['success'] == true) {
                    $res = array(
                        'success' => true,
                        'message' => 'File uploaded successfully!!'
                    );
                }
            }
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);
    }

    public function getElementCosts(Request $request)
    {
        $feeType = $request->input('fee_type');
        $costSubCat = $request->input('cost_subcategory');
        $where = array(
            't1.feetype_id' => $feeType,
            't1.sub_cat_id' => $costSubCat
        );
        try {
            $qry = DB::table('element_costs as t1')
                ->join('cost_elements as t2', 't1.element_id', 't2.id')
                ->join('cost_sub_elements as t3', 't1.sub_element_id', 't3.id')
                ->join('par_currencies as t4', 't1.currency_id', 't4.id')
                ->join('par_cost_sub_categories as t5', 't1.sub_cat_id', 't5.id')
                ->join('par_cost_categories as t6', 't5.cost_category_id', 't6.id')
                ->join('par_exchange_rates as t7', 't4.id', 't7.currency_id')
                ->select('t1.*', 't1.id as element_costs_id', 't4.id as currency_id', 't2.name as element', 't3.name as sub_element',
                    't4.name as currency', 't5.name as sub_category', 't6.name as category', 't7.exchange_rate')
                ->where($where);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function saveApplicationInvoicingDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $invoice_id = $request->input('invoice_id');
        $details = $request->input();
        $user_id = $this->user_id;
        unset($details['_token']);
        unset($details['application_id']);
        unset($details['application_code']);
        unset($details['invoice_id']);
        try {
            if (isset($invoice_id) && $invoice_id != '') {
                $invoice_no = '';
            } else {
                $invoice_no = generateInvoiceNo($user_id);
                $invoice_params = array(
                    'invoice_no' => $invoice_no,
                    'application_id' => $application_id,
                    'application_code' => $application_code
                );
                $res = insertRecord('tra_application_invoices', $invoice_params, $user_id);
                if ($res['success'] == false) {
                    return \response()->json($res);
                }
                $invoice_id = $res['record_id'];
            }
            $params = array();
            foreach ($details as $detail) {
                //check
                $element_costs_id = $detail['element_costs_id'];
                $where_check = array(
                    'invoice_id' => $invoice_id,
                    'element_costs_id' => $element_costs_id
                );
                if (DB::table('tra_invoice_details')
                        ->where($where_check)
                        ->count() < 1) {
                    $params[] = array(
                        'invoice_id' => $invoice_id,
                        'element_costs_id' => $element_costs_id,
                        'element_amount' => $detail['cost'],
                        'currency_id' => $detail['currency_id'],
                        'exchange_rate' => $detail['exchange_rate']
                    );
                }
            }
            DB::table('tra_invoice_details')->insert($params);
            $res = array(
                'success' => true,
                'invoice_id' => $invoice_id,
                'invoice_no' => $invoice_no,
                'message' => 'Invoice details saved successfully!!'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getManagerEvaluationApplications(Request $request)
    {

        $table_name = $request->input('table_name');
        $workflow_stage = $request->input('workflow_stage_id');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('tra_product_information as t2', 't1.product_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', function ($join) {
                    $join->on('t1.application_status_id', '=', 't4.id');
                })
                ->leftJoin('tra_approval_recommendations as t5', function ($join) {
                    $join->on('t1.id', '=', 't5.application_id')
                        ->on('t1.application_code', '=', 't5.application_code');
                })
                ->leftJoin('par_classifications as t7', 't2.classification_id', '=', 't7.id')
                ->leftJoin('par_common_names as t8', 't2.common_name_id', '=', 't8.id')
                ->leftJoin('par_approval_decisions as t6', 't5.decision_id', '=', 't6.id')
                ->leftJoin('tra_submissions as t9', function ($join) {
                    $join->on('t1.workflow_stage_id', '=', 't9.current_stage')
                        ->on('t1.application_code', '=', 't9.application_code');
                })
                ->join('users as t10', 't9.usr_from', '=', 't10.id')
                ->select('t1.*', 't7.name as classification_name', 't10.username as submitted_by', 't9.date_received as submitted_on', 't8.name as common_name', 't2.brand_name as product_name', 't3.name as applicant_name', 't4.name as application_status',
                    't6.name as approval_status', 't5.decision_id', 't1.id as active_application_id')
                ->where('t1.workflow_stage_id', $workflow_stage);

            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);


    }

    public function getTcMeetingParticipants(Request $request)
    {
        $meeting_id = $request->input('meeting_id');
        try {
            $qry = DB::table('tc_meeting_participants as t1')
                ->select('t1.*')
                ->where('t1.meeting_id', $meeting_id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getManagerAuditingApplications(Request $request)
    {
        $table_name = $request->input('table_name');
        $workflow_stage = $request->input('workflow_stage_id');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('tra_product_information as t2', 't1.product_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', function ($join) {
                    $join->on('t1.application_status_id', '=', 't4.id');
                })
                ->leftJoin('tra_approval_recommendations as t5', function ($join) {
                    $join->on('t1.id', '=', 't5.application_id')
                        ->on('t1.application_code', '=', 't5.application_code');
                })
                ->leftJoin('par_classifications as t7', 't2.classification_id', '=', 't7.id')
                ->leftJoin('par_common_names as t8', 't2.common_name_id', '=', 't8.id')
                ->leftJoin('tra_submissions as t9', function ($join) {
                    $join->on('t1.workflow_stage_id', '=', 't9.current_stage')
                        ->on('t1.application_code', '=', 't9.application_code');
                })
                ->join('users as t10', 't9.usr_from', '=', 't10.id')
                ->leftJoin('tra_evaluation_recommendations as t11', function ($join) {
                    $join->on('t1.id', '=', 't11.application_id')
                        ->on('t1.application_code', '=', 't11.application_code');
                })
                ->leftJoin('wf_workflow_actions as t12', 't11.recommendation_id', '=', 't12.id')
                ->leftJoin('tra_auditing_recommendations as t13', function ($join) {
                    $join->on('t1.id', '=', 't13.application_id')
                        ->on('t1.application_code', '=', 't13.application_code');
                })
                ->leftJoin('wf_workflow_actions as t14', 't13.recommendation_id', '=', 't14.id')
                ->select('t1.*', 't12.name as evaluator_recommendation', 't14.name as auditor_recommendation', 't2.brand_name as product_name', 't7.name as classification_name', 't10.username as submitted_by', 't9.date_received as submitted_on', 't8.name as common_name', 't3.name as applicant_name', 't4.name as application_status',
                    't5.decision_id', 't1.id as active_application_id')
                ->where('t1.workflow_stage_id', $workflow_stage);

            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getGrantingDecisionApplications(Request $request)
    {
        $table_name = $request->input('table_name');
//        $workflow_stage = $request->input('workflow_stage_id');
        $wf = DB::table("wf_workflow_stages")->where('name', '=', 'Granting Decision')->first();
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('tra_product_information as t2', 't1.product_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_application_statuses as t4', function ($join) {
                    $join->on('t1.application_status_id', '=', 't4.id')
                        ->on('t1.process_id', '=', 't4.process_id');
                })
                ->select('t1.*', 't2.brand_name', 't3.name as applicant_name', 't4.name as application_status')
                ->where('t1.workflow_stage_id', $wf->id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function saveMeeting(Request $request)
    {
        try {
            $meetingId = DB::transaction(function () use ($request) {
                $meetingId = insertRecordNoTransaction('tra_product_application_meetings',
                    [
                        "title" => $request->input('title'),
                        "description" => $request->input('dezcription'),
                        "date_requested" => Carbon::parse($request->input('date_requested')),
                        "physical_address" => $request->input('physical_address')
                    ], \Auth::user()->id);
                $members = $request->input('members');
                foreach ($members as $member) {
                    insertRecordNoTransaction('tra_product_application_meeting_members',
                        [
                            "product_application_meeting_id" => $meetingId,
                            "member_name" => $member
                        ]
                        , \Auth::user()->id);
                }

                return $meetingId;

            });

            $res = array(
                'success' => true,
                'meeting_id' => $meetingId,
                'message' => 'Meeting Saved!'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }

        return \response()->json($res);
    }

    public function getApplicationUploadedDocs(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $workflow_stage_id = $request->input('workflow_stage_id');
        try {
            $qry = DB::table('tra_product_application_uploads as t1')
                ->leftJoin('wf_workflow_stages as t2', 't1.workflow_stage_id', '=', 't2.id')
                ->select('t1.*', 't2.name as stage_name')
                ->where('t1.application_id', $application_id);
            if (isset($workflow_stage_id) && $workflow_stage_id != '') {
                $qry->where('t1.workflow_stage_id', $workflow_stage_id);
            }
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);
    }

    public function saveSample(Request $request)
    {
        try {
            $sampleRecord = array(
                'application_id' => $request->input('application_id'),
                'brand_name' => $request->input('brand_name'),
                'batch_number' => $request->input('batch_no'),
                'expiry_date' => Carbon::parse($request->input('expiry_date')),
                'submission_date' => carbon::parse($request->input('submission_date')),
                'storage_condition_id' => $request->input('storage_condition_id'),
                'shelf_life_months' => $request->input('shelf_life'),
                'shelf_life_after_opening' => $request->input('shelf_life_after_opening')
            );
            $res = insertRecord('tra_product_samples', $sampleRecord, \Auth::user()->id);
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function onRegisteredProductsSearchdetails(Request $req)
    {

        $reg_product_id = $req->input('reg_product_id');
        $tra_product_id = $req->input('tra_product_id');
        $table_name = $req->input('table_name');

        try {
            $main_qry = DB::table('tra_product_applications as t1')
                ->join('tra_product_information as t2', 't1.product_id', '=', 't2.id')
                ->where('t2.id', $tra_product_id);

            $qry1 = clone $main_qry;
            $qry1->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->select('t1.*', 't2.brand_name as brand_name', 't1.product_id as tra_product_id',
                    't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website',
                    't2.*');

            $results = $qry1->first();
            $results->product_id = '';
            $qry2 = clone $main_qry;
            $qry2->join('wb_trader_account as t3', 't1.local_agent_id', '=', 't3.id')
                ->select('t3.id as applicant_id', 't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website');
            $ltrDetails = $qry2->first();

            $res = array(
                'success' => true,
                'results' => $results,
                'ltrDetails' => $ltrDetails,
                'message' => 'All is well'
            );

        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);

    }

    public function prepareNewProductReceivingStage(Request $req)
    {

        $application_id = $req->input('application_id');
        $application_code = $req->input('application_code');
        $table_name = $req->input('table_name');
        try {
            $main_qry = DB::table('tra_product_applications as t1')
                ->join('tra_product_information as t2', 't1.product_id', '=', 't2.id')
                ->where('t1.id', $application_id);

            $qry1 = clone $main_qry;
            $qry1->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->leftJoin('tra_application_invoices as t4', function ($join) use ($application_code) {
                    $join->on('t1.id', '=', 't4.application_id')
                        ->on('t4.application_code', '=', 't4.application_code');
                })
                ->select('t1.*', 't1.id as active_application_id', 't2.brand_name as brand_name',
                    't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website',
                    't2.*', 't4.id as invoice_id', 't4.invoice_no');

            $results = $qry1->first();

            $qry2 = clone $main_qry;
            $qry2->join('wb_trader_account as t3', 't1.local_agent_id', '=', 't3.id')
                ->select('t3.id as applicant_id', 't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website');
            $ltrDetails = $qry2->first();

            $res = array(
                'success' => true,
                'results' => $results,
                'ltrDetails' => $ltrDetails,
                'message' => 'All is well'
            );

        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }
    
    public function prepareOnlineProductReceivingStage(Request $req)
    {

        $application_id = $req->input('application_id');
        $application_code = $req->input('application_code');
        $table_name = $req->input('table_name');
        try {
            $main_qry = DB::connection('portal_db')->table('wb_product_applications as t1')
                ->join('wb_product_information as t2', 't1.product_id', '=', 't2.id')
                ->leftJoin('wb_statuses as q', 't1.application_status_id','=','q.id')
                ->where('t1.id', $application_id);

            $qry1 = clone $main_qry;
            $qry1->join('wb_trader_account as t3', 't1.trader_id', '=', 't3.id')
                ->select('t1.*','q.name as application_status', 't1.id as active_application_id', 't2.brand_name as brand_name',
                    't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website',
                    't2.*');

            $results = $qry1->first();

            $qry2 = clone $main_qry;
            $qry2->join('wb_trader_account as t3', 't1.local_agent_id', '=', 't3.id')
                ->select('t3.id as trader_id', 't3.name as applicant_name', 't3.contact_person',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website');
            $ltrDetails = $qry2->first();

            $res = array(
                'success' => true,
                'results' => $results,
                'ltrDetails' => $ltrDetails,
                'message' => 'All is well'
            );

        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }
    public function prepareProductsInvoicingStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t2', 't1.applicant_id', '=', 't2.id')
                ->leftJoin('tra_application_invoices as t3', function ($join) use ($application_code) {
                    $join->on('t1.id', '=', 't3.application_id')
                        ->on('t3.application_code', '=', DB::raw($application_code));
                })
                ->join('tra_product_information as t4', 't1.product_id', '=', 't4.id')
                ->select(DB::raw("t1.applicant_id,t1.product_id,CONCAT_WS(',',t2.name,t2.postal_address) as applicant_details, t3.id as invoice_id, t3.invoice_no,
                t1.section_id,t1.module_id,CONCAT_WS(',',t4.brand_name,t4.physical_description) as product_details"))
                ->where('t1.id', $application_id);

            $results = $qry->first();

            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function prepareNewProductPaymentStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t2', 't1.applicant_id', '=', 't2.id')
                ->leftJoin('tra_application_invoices as t3', function ($join) use ($application_code) {
                    $join->on('t1.id', '=', 't3.application_id')
                        ->on('t3.application_code', '=', DB::raw($application_code));
                })
                ->join('tra_product_information as t4', 't1.product_id', '=', 't4.id')
                ->select(DB::raw("t1.applicant_id,t1.product_id,CONCAT_WS(',',t2.name,t2.postal_address) as applicant_details, t3.id as invoice_id, t3.invoice_no,
                t1.section_id,t1.module_id,CONCAT_WS(',',t4.brand_name,t4.physical_description) as product_details"))
                ->where('t1.id', $application_id);
            $results = $qry->first();
            $payment_details = getApplicationPaymentsRunningBalance($application_id, $application_code, $results->invoice_id);
            $res = array(
                'success' => true,
                'results' => $results,
                'balance' => formatMoney($payment_details['running_balance']),
                'invoice_amount' => formatMoney($payment_details['invoice_amount']),
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function onSaveProductOtherDetails(Request $req)
    {
        try {
            $resp = "";
            $user_id = $this->user_id;
            $data = $req->all();

            $table_name = $req->table_name;
            $record_id = $req->id;
            unset($data['table_name']);
            unset($data['model']);
            unset($data['manufacturer_name']);
            unset($data['id']);
            if (validateIsNumeric($record_id)) {
                $where = array('id' => $record_id);
                if (recordExists($table_name, $where)) {

                    $data['dola'] = Carbon::now();
                    $data['altered_by'] = $user_id;

                    $previous_data = getPreviousRecords($table_name, $where);

                    $resp = updateRecord($table_name, $previous_data['results'], $where, $data, $user_id);

                }
            } else {
                //insert
                $data['created_by'] = $user_id;
                $data['created_on'] = Carbon::now();

                $resp = insertRecord($table_name, $data, $user_id);

            }
            if ($resp['success']) {
                $res = array('success' => true,
                    'message' => 'Saved Successfully');

            } else {
                $res = array('success' => false,
                    'message' => $resp['message']);

            }
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }

        return response()->json($res);

    }

    public function onSaveProductinformation(Request $req)
    {
        try {
            $resp = "";
            $user_id = $this->user_id;
            $data = $req->all();

            $table_name = $req->table_name;
            $record_id = $req->product_id;
            unset($data['table_name']);
            unset($data['model']);
            unset($data['manufacturer_name']);
            unset($data['product_id']);
            unset($data['application_id']);
            unset($data['assessment_procedure_id']);
            unset($data['zone_id']);
            if (validateIsNumeric($record_id)) {
                $where = array('id' => $record_id);
                if (recordExists($table_name, $where)) {

                    $data['dola'] = Carbon::now();
                    $data['altered_by'] = $user_id;

                    $previous_data = getPreviousRecords($table_name, $where);

                    $resp = updateRecord($table_name, $previous_data['results'], $where, $data, $user_id);

                }
            }

            if ($resp['success']) {
                $res = array('success' => true,
                    'message' => 'Saved Successfully');

            } else {
                $res = array('success' => false,
                    'message' => $resp['message']);

            }
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }

        return response()->json($res);

    }
    public function onLoadOnlineproductIngredients(Request $req){
        try{
            $product_id = $req->product_id;
            $data = array();
            //get the records 
            $records = DB::connection('portal_db')->table('wb_product_ingredients as t1')
                    ->select('t1.*')
                    ->where(array('t1.product_id' => $product_id))
                    ->get();
                     //loop
                     $speficification_typeData = getParameterItems('par_specification_types','','');
                     $si_unitData = getParameterItems('par_si_units','','');
                     $ingredientsData = getParameterItems('par_ingredients_details','','');
                     $inclusion_reasonData = getParameterItems('par_inclusions_reasons','','');
                     $ingredientTypeData = getParameterItems('par_ingredients_types','','');
                      
                     foreach ($records as $rec) {
                        //get the array 
                        
                        $data[] = array('product_id'=>$rec->product_id,
                                        'id'=>$rec->id,
                                        'ingredient_type_id'=>$rec->ingredient_type_id,
                                        'ingredient_id'=>$rec->ingredient_id,
                                        'specification_type_id'=>$rec->specification_type_id,
                                        'strength'=>$rec->strength,
                                        'proportion'=>$rec->proportion,
                                        'ingredientssi_unit_id'=>$rec->ingredientssi_unit_id,
                                        'inclusion_reason_id'=>$rec->inclusion_reason_id,
                                        'ingredient_name'=>returnParamFromArray($ingredientsData,$rec->ingredient_id),
                                        'ingredient_type'=>returnParamFromArray($ingredientTypeData,$rec->ingredient_type_id),
                                        'ingredient_specification'=>returnParamFromArray($speficification_typeData,$rec->specification_type_id),
                                        'si_unit'=>returnParamFromArray($si_unitData,$rec->ingredientssi_unit_id),
                                        'reason_for_inclusion'=>returnParamFromArray($inclusion_reasonData,$rec->inclusion_reason_id),
                                    );
                                    
                     }
                     $res =array('success'=>true,'results'=> $data);
        }
        catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);


    }
    public function onLoadOnlineproductPackagingDetails(Request $req){
        try{
            $product_id = $req->product_id;
            $data = array();
            //get the records 
            $records = DB::connection('portal_db')->table('wb_product_packaging as t1')
                    ->select('t1.*')
                    ->where(array('t1.product_id' => $product_id))
                    ->get();
                     //loop container_id
                     $containersData = getParameterItems('par_containers','','');
                     $containersMaterialsData = getParameterItems('par_containers_materials','','');
                     $containersMaterialsData = getParameterItems('par_containers_materials','','');
                     $containersClosuresData = getParameterItems('par_closure_materials','','');
                     $containersSealData = getParameterItems('par_seal_types','','');
                     $containersTypesData = getParameterItems('par_containers_types','','');
                     $packagingUnitsData = getParameterItems('par_packaging_units','','');
                    
                     foreach ($records as $rec) {
                        //get the array 
                       
                        $data[] = array('product_id'=>$rec->product_id,
                                        'id'=>$rec->id,
                                        'container_id'=>$rec->container_id,
                                        'container_material_id'=>$rec->container_material_id,
                                        'container_type_id'=>$rec->container_type_id,
                                        'closure_material_id'=>$rec->closure_material_id,
                                        'seal_type_id'=>$rec->seal_type_id,
                                        'retail_packaging_size'=>$rec->retail_packaging_size,
                                        'packaging_units_id'=>$rec->packaging_units_id,
                                        'unit_pack'=>$rec->unit_pack,
                                        'container_name'=>returnParamFromArray($containersData,$rec->container_id),
                                        'container_material'=>returnParamFromArray($containersMaterialsData,$rec->container_material_id),
                                        'container_type'=>returnParamFromArray($containersTypesData,$rec->container_type_id),
                                        'closure_materials'=>returnParamFromArray($containersClosuresData,$rec->closure_material_id),
                                        'seal_type'=>returnParamFromArray($containersSealData,$rec->seal_type_id),
                                        'packaging_units'=>returnParamFromArray($packagingUnitsData,$rec->packaging_units_id)
                                    );
                                    
                     }
                     $res =array('success'=>true,'results'=> $data);
        }
        catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);



    }
    public function onLoadOnlineproductManufacturer(Request $req){
         
        try{
            $data = array();
            $product_id = $req->product_id;
            $manufacturer_type_id =1;
            $records = DB::connection('portal_db')->table('wb_product_manufacturers as t1')
                       ->where(array('product_id'=>$product_id,'manufacturer_type_id'=>$manufacturer_type_id))   
                         ->get();
                         foreach ($records as $rec) {
                                $manufacturer_id = $rec->manufacturer_id;
                                $manufacturer_role_id = $rec->manufacturer_role_id;
                                $manufacturer_roleData = getParameterItems('par_manufacturing_roles','','');
                                $manufacturing_role = returnParamFromArray($manufacturer_roleData,$manufacturer_role_id);
                                //print_r($rec);
                                $records = DB::table('tra_manufacturers_information as t1')
                                    ->select('t1.*','t1.id as manufacturer_id', 't1.name as manufacturer_name', 't2.name as country', 't3.name as region','t4.name as district')
                                    ->join('par_countries as t2', 't1.country_id', '=','t2.id')
                                    ->join('par_regions as t3', 't1.region_id', '=','t3.id')
                                    ->leftJoin('par_districts as t4', 't1.district_id', '=','t4.id')
                                    ->where(array('t1.id'=>$manufacturer_id))
                                    ->first();

                                $data[] = array('id'=>$rec->id,
                                                 'manufacturer_name'=>$records->manufacturer_name,
                                                 'country'=>$records->country,
                                                 'region'=>$records->region,
                                                 'product_id'=>$rec->product_id,
                                                 'physical_address'=>$records->physical_address,
                                                 'postal_address'=>$records->postal_address,
                                                 'manufacturing_role'=>$manufacturing_role,
                                                 'email_address'=>$records->email_address
                                              );
                        }  
                        $res = array(
                            'success' => true,
                            'results' => $data
                        );
      }
      catch (\Exception $e) {
          $res = array(
              'success' => false,
              'message' => $e->getMessage()
          );
      } catch (\Throwable $throwable) {
          $res = array(
              'success' => false,
              'message' => $throwable->getMessage()
          );
      }
      return response()->json($res);

    }
    public function onLoadOnlineproductApiManufacturer(Request $req){
         
        try{
            $data = array();
            $product_id = $req->product_id;
            $manufacturer_type_id = 2;
            $records = DB::connection('portal_db')->table('wb_product_manufacturers as t1')
                        ->select('t1.*', 't2.ingredient_id')
                        ->join('wb_product_ingredients as t2', 't1.active_ingredient_id','=','t2.id')
                        ->where(array('t1.product_id'=>$product_id,'manufacturer_type_id'=>$manufacturer_type_id))   
                         ->get();
                         foreach ($records as $rec) {
                                $manufacturer_id = $rec->manufacturer_id;
                                $ingredient_id = $rec->ingredient_id;
                              //  print_r($rec);

                                $manufacturer_role_id = $rec->manufacturer_role_id;
                                $manufacturer_roleData = getParameterItems('par_manufacturing_roles','','');
                                $manufacturing_role = returnParamFromArray($manufacturer_roleData,$manufacturer_role_id);
                                
                                $ingredients_Data = getParameterItems('par_ingredients_details','','');
                                $active_ingredient = returnParamFromArray($ingredients_Data,$ingredient_id);
                                
                                $records = DB::connection('')
                                    ->table('tra_manufacturers_information as t1')
                                    ->select('t1.*','t1.id as manufacturer_id', 't1.name as manufacturer_name', 't2.name as country', 't3.name as region','t4.name as district')
                                    ->join('par_countries as t2', 't1.country_id', '=','t2.id')
                                    ->join('par_regions as t3', 't1.region_id', '=','t3.id')
                                    ->leftJoin('par_districts as t4', 't1.district_id', '=','t4.id')
                                    ->where(array('t1.id'=>$manufacturer_id))
                                    ->first();

                                $data[] = array('id'=>$rec->id,
                                                 'manufacturer_name'=>$records->manufacturer_name,
                                                 'country_name'=>$records->country,
                                                 'region_name'=>$records->region,
                                                 'product_id'=>$rec->product_id,
                                                 'physical_address'=>$records->physical_address,
                                                 'postal_address'=>$records->postal_address,
                                                 'manufacturing_role'=>$manufacturing_role,
                                                 'ingredient_name'=>$active_ingredient,
                                                 'email_address'=>$records->email_address
                                              );
                        } 
                        $res = array(
                            'success' => true,
                            'results' => $data
                        );
      }
      catch (\Exception $e) {
          $res = array(
              'success' => false,
              'message' => $e->getMessage()
          );
      } catch (\Throwable $throwable) {
          $res = array(
              'success' => false,
              'message' => $throwable->getMessage()
          );
      }
      return response()->json($res);

    }
    public function onLoadOnlinegmpInspectionApplicationsDetails(Request $req){
         
        try{
            $data = array();
            $product_id = $req->product_id;
            $manufacturer_type_id = 2;
            $records = DB::connection('portal_db')->table('wb_product_gmpinspectiondetails as t1')
                        ->select('t1.*')
                        ->where(array('t1.product_id'=>$product_id))   
                         ->get();
                         foreach ($records as $rec) {
                                $reg_manufacturer_site_id = $rec->reg_manufacturer_site_id;
                               
                                $records =  DB::table('tra_manufacturing_sites as t1')
                                            ->select('t5.id as reg_manufacturer_site_id', 't7.permit_no as gmp_certificate_no', 't6.reference_no as gmp_application_reference', 't8.name as registration_status', 't7.permit_no', 't1.physical_address', 't1.email as email_address', 't1.id as manufacturer_id', 't1.name as manufacturer_name', 't2.name as country_name', 't3.name as region_name', 't4.name as district')
                                            ->join('par_countries as t2', 't1.country_id', '=', 't2.id')
                                            ->join('par_regions as t3', 't1.region_id', '=', 't3.id')
                                            ->leftJoin('par_districts as t4', 't1.district_id', '=', 't4.id')
                                            ->join('registered_manufacturing_sites as t5', 't1.id', '=', 't5.tra_site_id')
                                            ->join('tra_gmp_applications as t6', 't1.id', '=', 't6.manufacturing_site_id')
                                            ->join('tra_approval_recommendations as t7', 't1.permit_id', '=', 't7.id')
                                            ->join('par_system_statuses as t8', 't5.status_id', '=', 't8.id')
                                            ->where(array('t5.id' => $reg_manufacturer_site_id))
                                            ->first();
                             
                                $data[] = array('id'=>$rec->id,
                                                 'reg_manufacturer_site_id'=>$records->reg_manufacturer_site_id,
                                                 'gmp_certificate_no'=>$records->gmp_certificate_no,
                                                 'gmp_application_reference'=>$records->gmp_application_reference,
                                                 'permit_no'=>$records->permit_no,
                                                 'physical_address'=>$records->physical_address,
                                                 'email_address'=>$records->email_address,
                                                 'manufacturer_id'=>$records->manufacturer_id,
                                                 'manufacturer_name'=>$records->manufacturer_name,
                                                 'country_name'=>$records->country_name,
                                                 'region_name'=>$records->region_name,
                                                 'district'=>$records->district,
                                                 'registration_status'=>$records->registration_status
                                              );
                        } 
                        $res = array(
                            'success' => true,
                            'results' => $data
                        );
      }
      catch (\Exception $e) {
          $res = array(
              'success' => false,
              'message' => $e->getMessage()
          );
      } catch (\Throwable $throwable) {
          $res = array(
              'success' => false,
              'message' => $throwable->getMessage()
          );
      }
      return response()->json($res);

    }
   
    public function onLoadproductIngredients(Request $req)
    {

        try {
            $product_id = $req->product_id;
            $data = array();
            //get the records
            $data = DB::table('tra_product_ingredients as t1')
                ->select('t1.*', 't6.name as reason_for_inclusion', 't2.name as ingredient_specification', 't3.name as si_unit', 't4.name as ingredient_name', 't5.name as ingredient_type')
                ->leftJoin('par_specification_types as t2', 't1.specification_type_id', '=', 't2.id')
                ->leftJoin('par_si_units as t3', 't1.ingredientssi_unit_id', '=', 't3.id')
                ->leftJoin('par_ingredients_details as t4', 't1.ingredient_id', '=', 't4.id')
                ->leftJoin('par_ingredients_types as t5', 't1.ingredient_type_id', '=', 't5.id')
                ->leftJoin('par_inclusions_reasons as t6', 't1.inclusion_reason_id', '=', 't6.id')
                ->where(array('t1.product_id' => $product_id))
                ->get();
            $res = array('success' => true, 'results' => $data);
        } catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);


    }

    public function onLoadproductNutrients(Request $req)
    {
        try {
            $product_id = $req->product_id;
            $data = array();
            //get the records
            $data = DB::table('tra_product_nutrients as t1')
                ->select('t1.*', 't2.name as nutrients_category', 't3.name as nutrients', 't4.name as si_unit')
                ->leftJoin('par_nutrients_category as t2', 't1.nutrients_category_id', '=', 't2.id')
                ->leftJoin('par_nutrients as t3', 't1.nutrients_id', '=', 't3.id')
                ->leftJoin('par_si_units as t4', 't1.units_id', '=', 't4.id')
                ->where(array('t1.product_id' => $product_id))
                ->get();
            $res = array('success' => true, 'results' => $data);
        } catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);


    }

    public function getProductActiveIngredients(Request $req)
    {

        try {
            $filters = (array)json_decode($req->filters);
            $data = array();
            //get the records
            $filters['t3.is_active_reason'] = 1;
            $data = DB::table('tra_product_ingredients as t1')
                ->select('t1.id as active_ingredient_id', 't2.name as ingredient_name')
                ->join('par_ingredients_details as t2', 't1.ingredient_id', '=', 't2.id')
                ->join('par_inclusions_reasons as t3', 't1.inclusion_reason_id', '=', 't3.id')
                ->where($filters)
                ->get();
            if (count($data) > 0) {
                $res = array('success' => true, 'results' => $data);
            } else {
                $res = array('success' => false, 'message' => 'Active Pharmaceutical Ingredient not captured.');
            }

        } catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);


    }

    public function onLoadproductPackagingDetails(Request $req)
    {

        try {
            $product_id = $req->product_id;
            $data = array();
            //get the records
            $data = DB::table('tra_product_packaging as t1')
                ->select('t1.*', 't2.name as container_type', 't3.name as container_name', 't4.name as container_material', 't5.name as closure_materials', 't4.name as container_material', 't5.name as closure_material', 't6.name as seal_type', 't7.name as packaging_units')
                ->leftJoin('par_containers_types as t2', 't1.container_type_id', '=', 't2.id')
                ->leftJoin('par_containers as t3', 't1.container_id', '=', 't3.id')
                ->leftJoin('par_containers_materials as t4', 't1.container_material_id', '=', 't4.id')
                ->leftJoin('par_closure_materials as t5', 't1.closure_material_id', '=', 't5.id')
                ->leftJoin('par_seal_types as t6', 't1.seal_type_id', '=', 't6.id')
                ->leftJoin('par_packaging_units as t7', 't1.packaging_units_id', '=', 't7.id')
                ->where(array('t1.product_id' => $product_id))
                ->get();
            $res = array('success' => true, 'results' => $data);
        } catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);

    }

    public function onLoadproductManufacturer(Request $req)
    {

        try {
            $product_id = $req->product_id;
            $records = array();
            //get the records
            $records = DB::table('tra_product_manufacturers as t1')
                ->select('t1.*', 't2.email_address', 't1.id as manufacturer_id', 't6.name as manufacturing_role', 't2.physical_address', 't2.name as manufacturer_name', 't3.name as country_name', 't4.name as region_name', 't5.name as district_name')
                ->join('tra_manufacturers_information as t2', 't1.manufacturer_id', '=', 't2.id')
                ->join('par_countries as t3', 't2.country_id', '=', 't3.id')
                ->join('par_regions as t4', 't2.region_id', '=', 't4.id')
                ->leftJoin('par_districts as t5', 't2.district_id', '=', 't5.id')
                ->leftJoin('par_manufacturing_roles as t6', 't1.manufacturer_role_id', '=', 't6.id')
                ->where(array('t1.product_id' => $product_id, 'manufacturer_type_id' => 1))
                ->get();

            $res = array('success' => true, 'results' => $records);

        } catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);


    }

    public function onLoadproductApiManufacturer(Request $req)
    {

        try {
            $product_id = $req->product_id;
            $records = array();
            //get the records
            $records = DB::table('tra_product_manufacturers as t1')
                ->select('t1.*', 't2.email_address', 't2.id as manufacturer_id', 't7.name as ingredient_name', 't2.physical_address', 't2.name as manufacturer_name', 't3.name as country_name', 't4.name as region_name', 't5.name as district_name')
                ->join('tra_manufacturers_information as t2', 't1.manufacturer_id', '=', 't2.id')
                ->join('par_countries as t3', 't2.country_id', '=', 't3.id')
                ->join('par_regions as t4', 't2.region_id', '=', 't4.id')
                ->leftJoin('par_districts as t5', 't2.district_id', '=', 't5.id')
                ->join('tra_product_ingredients as t6', 't1.active_ingredient_id', '=', 't6.id')
                ->join('par_ingredients_details as t7', 't6.ingredient_id', '=', 't7.id')
                ->where(array('t1.product_id' => $product_id, 'manufacturer_type_id' => 2))
                ->get();

            $res = array('success' => true, 'results' => $records);
        } catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);


    }

    public function getEValuationComments(Request $req)
    {
        $application_code = $req->input('application_code');
        $table_name = 'tra_evaluations_overralcomments';
        $res = $this->getEvalAuditComments($table_name, $application_code);
        return \response()->json($res);


    }

    public function getAuditingComments(Request $req)
    {
        $application_code = $req->input('application_code');
        $table_name = 'tra_auditing_overralcomments';
        $res = $this->getEvalAuditComments($table_name, $application_code);
        return \response()->json($res);

    }

    function getEvalAuditComments($table_name, $application_code)
    {

        try {
            $records = DB::table($table_name . ' as t1')
                ->where('application_code', $application_code)
                ->join('users as t2', 't1.created_by', '=', 't2.id')
                ->select('t1.*', 't2.username as author')
                ->get();

            $res = array(
                'success' => true,
                'results' => $records,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return $res;


    }

    public function getProductApplicationMoreDetails(Request $request)
    {
        $application_id = $request->input('application_id');
        $applicant_id = $request->input('applicant_id');
        $product_id = $request->input('product_id');
        try {
            $zone_id = DB::table('tra_product_applications')
                ->where('id', $application_id)
                ->value('zone_id');
            $qryProducts = DB::table('tra_product_information as t1')
                ->join('tra_product_applications as t2', 't1.id', '=', 't2.product_id')
                ->select('t1.id as product_id', 't2.assessment_procedure_id', 't1.*')
                ->where('t1.id', $product_id);

            $product_details = $qryProducts->first();

            $res = array(
                'success' => true,
                //  'applicant_details' => $applicantDetails,
                'product_details' => $product_details,
                'zone_id' => $zone_id,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }


    //onLoadgmpInspectionApplicationsDetails
    public function onDeleteProductOtherDetails(Request $req)
    {
        try {
            $record_id = $req->input('id');
            $table_name = $req->input('table_name');
            $user_id = \Auth::user()->id;
            $where = array(
                'id' => $record_id
            );
            $previous_data = getPreviousRecords($table_name, $where);
            if ($previous_data['success'] == false) {
                return $previous_data;
            }
            $previous_data = $previous_data['results'];
            $res = deleteRecord($table_name, $previous_data, $where, $user_id);
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);


    }

    public function onLoadManufacturingSitesDetails(Request $req)
    {
        try {
            $records = DB::table('tra_manufacturers_information as t1')
                ->select('t1.physical_address', 't1.email_address', 't1.contact_person', 't1.id as manufacturer_id', 't1.name as manufacturer_name', 't2.name as country_name', 't3.name as region_name', 't4.name as district')
                ->join('par_countries as t2', 't1.country_id', '=', 't2.id')
                ->join('par_regions as t3', 't1.region_id', '=', 't3.id')
                ->leftJoin('par_districts as t4', 't1.district_id', '=', 't4.id')
                ->get();
            $res = array('success' => true,
                'results' => $records
            );
        } catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);


    }

    public function onLoadproductGmpInspectionDetailsStr(Request $req)
    {
        try {
            $product_id = $req->product_id;
            $records = array();
            //get the records
            $records = DB::table('tra_manufacturing_sites as t1')
                ->select('t9.id', 't5.id as reg_manufacturer_site_id', 't7.permit_no as gmp_certificate_no', 't6.reference_no as gmp_application_reference', 't8.name as registration_status', 't7.permit_no', 't1.physical_address', 't1.email as email_address', 't1.id as manufacturer_id', 't1.name as manufacturer_name', 't2.name as country_name', 't3.name as region_name', 't4.name as district')
                ->join('par_countries as t2', 't1.country_id', '=', 't2.id')
                ->join('par_regions as t3', 't1.region_id', '=', 't3.id')
                ->leftJoin('par_districts as t4', 't1.district_id', '=', 't4.id')
                ->join('registered_manufacturing_sites as t5', 't1.id', '=', 't5.tra_site_id')
                ->join('tra_gmp_applications as t6', 't1.id', '=', 't6.manufacturing_site_id')
                ->join('tra_approval_recommendations as t7', 't1.permit_id', '=', 't7.id')
                ->join('par_system_statuses as t8', 't5.status_id', '=', 't8.id')
                ->join('tra_product_gmpinspectiondetails as t9', 't5.id', '=', 't9.reg_manufacturer_site_id')
                ->where(array('t9.product_id' => $product_id))
                ->get();

            $res = array('success' => true, 'results' => $records);
        } catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);


    }

    public function onLoadgmpInspectionApplicationsDetails()
    {
        try {
            $records = DB::table('tra_manufacturing_sites as t1')
                ->select('t5.id as reg_manufacturer_site_id', 't7.permit_no as gmp_certificate_no', 't6.reference_no as gmp_application_reference', 't8.name as registration_status', 't7.permit_no', 't1.physical_address', 't1.email as email_address', 't1.id as manufacturer_id', 't1.name as manufacturer_name', 't2.name as country_name', 't3.name as region_name', 't4.name as district')
                ->join('par_countries as t2', 't1.country_id', '=', 't2.id')
                ->join('par_regions as t3', 't1.region_id', '=', 't3.id')
                ->leftJoin('par_districts as t4', 't1.district_id', '=', 't4.id')
                ->join('registered_manufacturing_sites as t5', 't1.id', '=', 't5.tra_site_id')
                ->join('tra_gmp_applications as t6', 't1.id', '=', 't6.manufacturing_site_id')
                ->join('tra_approval_recommendations as t7', 't1.permit_id', '=', 't7.id')
                ->join('par_system_statuses as t8', 't5.status_id', '=', 't8.id')
                ->get();
            $res = array('success' => true,
                'results' => $records
            );
        } catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);
    }

    public function onLoadProductsSampledetails(Request $req)
    {
        try {
            $product_id = $req->product_id;

            $records = DB::table('tra_sample_information as t1')
                ->select('t1.*', 't2.name as quantity_unit', 't3.name as pack_unit', 't4.name as sample_status', 't5.name as sample_storage', 't6.brand_name')
                ->join('par_packaging_units as t2', 't1.quantity_unit_id', '=', 't2.id')
                ->join('par_packaging_units as t3', 't1.pack_unit_id', '=', 't3.id')
                ->leftJoin('par_sample_status as t4', 't1.sample_status_id', '=', 't4.id')
                ->join('par_storage_conditions as t5', 't1.storage_id', '=', 't5.id')
                ->join('tra_product_information as t6', 't1.product_id', '=', 't6.id')
                ->where(array('product_id' => $product_id))
                ->get();

            $res = array('success' => true,
                'results' => $records
            );
        } catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);


    }

    public function saveProductGmpApplicationDetails(Request $req)
    {
        try {
            $resp = "";
            $user_id = $this->user_id;
            $product_id = $req->product_id;
            $reg_manufacturer_site_id = $req->reg_manufacturer_site_id;
            $table_name = 'tra_product_gmpinspectiondetails';
            $tra_site_id = $req->tra_site_id;
            $data = array('product_id' => $product_id,
                'reg_manufacturer_site_id' => $reg_manufacturer_site_id,
                'tra_site_id' => $tra_site_id,
                'status_id' => 1);

            $where = array('reg_manufacturer_site_id' => $reg_manufacturer_site_id,
                'product_id' => $product_id);
            if (!recordExists($table_name, $where)) {
                $data['created_by'] = $user_id;
                $data['created_on'] = Carbon::now();

                $resp = insertRecord($table_name, $data, $user_id);
                $manufacturer_id = $resp['record_id'];

            } else {
                $resp = array('success' => false, 'message' => 'The Product GMP Application inspection exists');
            }

            if ($resp['success']) {

                $res = array('success' => true,
                    'message' => 'The Product GMP Application inspection Saved Successfully');

            } else {
                $res = array('success' => false,
                    'message' => $resp['message']);

            }
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }

        return response()->json($res);


    }

    public function saveManufacturerDetails(Request $req)
    {
        try {
            $resp = "";
            $user_id = $this->user_id;
            $data = $req->all();
            $physical_address = $req->physical_address;
            $manufacturer_name = $req->name;
            $table_name = $req->model;
            $record_id = $req->id;
            unset($data['table_name']);
            unset($data['model']);
            if (validateIsNumeric($record_id)) {
                $where = array('id' => $record_id);
                if (recordExists($table_name, $where)) {
                    $manufacturer_id = $record_id;
                    $data['dola'] = Carbon::now();
                    $data['altered_by'] = $user_id;

                    $previous_data = getPreviousRecords($table_name, $where);

                    $resp = updateRecord($table_name, $previous_data['results'], $where, $data, $user_id);

                }
            } else {
                //insert
                //check duplicate
                $where = array('name' => $manufacturer_name,
                    'physical_address' => $physical_address);
                if (!recordExists($table_name, $where)) {
                    $data['created_by'] = $user_id;
                    $data['created_on'] = Carbon::now();

                    $resp = insertRecord($table_name, $data, $user_id);
                    $manufacturer_id = $resp['record_id'];

                } else {
                    $resp = array('success' => false, 'message' => 'The Manufacturer details exists');
                }

            }
            if ($resp['success']) {

                $res = array('success' => true,
                    'manufacturer_id' => $manufacturer_id,
                    'manufacturer_name' => $manufacturer_name,
                    'physical_address' => $physical_address,
                    'message' => 'Saved Successfully');

            } else {
                $res = array('success' => false,
                    'message' => $resp['message']);

            }
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);
    }

    //approvals
    public function getproductregistrationAppsApproval(Request $req)
    {
        $table_name = $request->input('table_name');
        $workflow_stage = $request->input('workflow_stage_id');
        try {

            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', 't1.application_status_id', '=', 't4.id')
                ->leftJoin('tra_approval_recommendations as t5', function ($join) {
                    $join->on('t1.id', '=', 't5.application_id')
                        ->on('t1.application_code', '=', 't5.application_code');
                })
                ->join('tra_product_information as t7', 't1.product_id', '=', 't7.id')
                ->join('par_common_names as t8', 't7.common_name_id', '=', 't8.id')
                ->leftJoin('par_approval_decisions as t6', 't5.decision_id', '=', 't6.id')
                ->join('wf_tfdaprocesses as t7', 't1.process_id', '=', 't7.id')
                ->join('wf_workflow_stages as t8', 't1.workflow_stage_id', '=', 't8.id')
                ->join('tc_recommendations as t14', 't1.application_code', '=', 't14.application_code')
                ->join('par_tcmeeting_decisions as t15', 't14.decision_id', '=', 't15.id')
                ->select('t1.*', 't1.id as active_application_id', 't3.name as applicant_name', 't4.name as application_status', 't6.name as approval_status',
                    't7.name as process_name', 't8.name as workflow_stage', 't8.is_general', 't5.id as recommendation_id', 't6.name as recommendation',
                    't15.name as tc_recomm', 't14.decision_id', 't14.id as recomm_id', 't14.comments')
                ->where('t1.workflow_stage_id', $workflow_stage);

            $results = $qry->get();

            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);


    }

    //
    public function getProductTcReviewMeetingApplications(Request $req)
    {
        $table_name = $req->input('table_name');
        $workflow_stage = $req->input('workflow_stage_id');
        $meeting_id = $req->input('meeting_id');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', 't1.application_status_id', '=', 't4.id')
                ->join('tra_product_information as t7', 't1.product_id', '=', 't7.id')
                ->join('par_common_names as t8', 't7.common_name_id', '=', 't8.id')
                ->join('tc_meeting_applications as t9', 't1.application_code', '=', 't9.application_code')
                ->leftJoin('tra_evaluation_recommendations as t10', 't1.application_code', '=', 't10.application_code')
                ->leftJoin('tra_auditing_recommendations as t11', 't1.application_code', '=', 't11.application_code')
                ->leftJoin('wf_workflow_actions as t12', 't10.recommendation_id', '=', 't12.id')
                ->leftJoin('wf_workflow_actions as t13', 't11.recommendation_id', '=', 't13.id')
                ->select('t1.*', 't3.name as applicant_name', 't4.name as application_status',
                    't9.meeting_id', 't1.id as active_application_id', 't7.brand_name', 't8.name as common_name',
                    't12.name as evaluator_recommendation', 't13.name as auditor_recommendation', 't15.name as tc_recomm', 't14.decision_id', 't14.id as recomm_id', 't14.comments')
                ->leftJoin('tc_recommendations as t14', 't1.application_code', '=', 't14.application_code')
                ->leftJoin('par_tcmeeting_decisions as t15', 't14.decision_id', '=', 't15.id')
                ->where(array('t1.workflow_stage_id' => $workflow_stage, 't9.meeting_id' => $meeting_id));
            $results = $qry->get();

            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );

        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);

    }

    public function getProductApprovalApplicationsNonTc(Request $req)
    {

        $table_name = $req->input('table_name');
        $workflow_stage = $req->input('workflow_stage_id');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', 't1.application_status_id', '=', 't4.id')
                ->join('tra_product_information as t7', 't1.product_id', '=', 't7.id')
                ->join('par_common_names as t8', 't7.common_name_id', '=', 't8.id')
                ->join('par_classifications as t14', 't7.classification_id', '=', 't14.id')
                ->leftJoin('tra_evaluation_recommendations as t10', 't1.application_code', '=', 't10.application_code')
                ->leftJoin('tra_auditing_recommendations as t11', 't1.application_code', '=', 't11.application_code')
                ->leftJoin('wf_workflow_actions as t12', 't10.recommendation_id', '=', 't12.id')
                ->leftJoin('wf_workflow_actions as t13', 't11.recommendation_id', '=', 't13.id')
                ->select('t1.*', 't3.name as applicant_name', 't4.name as application_status', 't6.name as dg_recommendation', 't5.decision_id as recommendation_id',
                    't1.id as active_application_id', 't7.brand_name', 't8.name as common_name', 't14.name as classification_name',
                    't12.name as evaluator_recommendation', 't13.name as auditor_recommendation')
                ->leftJoin('tra_approval_recommendations as t5', function ($join) {
                    $join->on('t1.id', '=', 't5.application_id')
                        ->on('t1.application_code', '=', 't5.application_code');
                })
                ->leftJoin('par_approval_decisions as t6', 't5.decision_id', '=', 't6.id')
                ->where(array('t1.workflow_stage_id' => $workflow_stage));

            $results = $qry->get();

            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );

        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);


    }

    public function getProductApprovalApplications(Request $req)
    {
        $table_name = $req->input('table_name');
        $workflow_stage = $req->input('workflow_stage_id');
        $meeting_id = $req->input('meeting_id');
        try {

            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', 't1.application_status_id', '=', 't4.id')
                ->join('tra_product_information as t7', 't1.product_id', '=', 't7.id')
                ->join('par_common_names as t8', 't7.common_name_id', '=', 't8.id')
                ->join('tc_meeting_applications as t9', 't1.application_code', '=', 't9.application_code')
                ->leftJoin('tra_evaluation_recommendations as t10', 't1.application_code', '=', 't10.application_code')
                ->leftJoin('tra_auditing_recommendations as t11', 't1.application_code', '=', 't11.application_code')
                ->leftJoin('wf_workflow_actions as t12', 't10.recommendation_id', '=', 't12.id')
                ->leftJoin('wf_workflow_actions as t13', 't11.recommendation_id', '=', 't13.id')
                ->select('t1.*', 't3.name as applicant_name', 't4.name as application_status', 't6.name as dg_recommendation', 't5.decision_id as recommendation_id',
                    't9.meeting_id', 't1.id as active_application_id', 't7.brand_name', 't8.name as common_name',
                    't12.name as evaluator_recommendation', 't13.name as auditor_recommendation', 't15.name as tc_recomm', 't14.decision_id', 't14.id as recomm_id', 't14.comments')
                ->leftJoin('tc_recommendations as t14', 't1.application_code', '=', 't14.application_code')
                ->leftJoin('par_tcmeeting_decisions as t15', 't14.decision_id', '=', 't15.id')
                ->leftJoin('tra_approval_recommendations as t5', function ($join) {
                    $join->on('t1.id', '=', 't5.application_id')
                        ->on('t1.application_code', '=', 't5.application_code');
                })
                ->leftJoin('par_approval_decisions as t6', 't5.decision_id', '=', 't6.id')
                ->where(array('t1.workflow_stage_id' => $workflow_stage, 't9.meeting_id' => $meeting_id));

            $results = $qry->get();

            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );

        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getRegisteredProductsAppsDetails(Request $req)
    {
        $limit = $req->input('limit');
        $start = $req->input('start');
        $section_id = $req->input('section_id');

        $search_value = $req->input('search_value');
        $search_field = $req->input('search_field');
        try {
            $qry = DB::table('tra_product_applications as t1')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('tra_product_information as t7', 't1.product_id', '=', 't7.id')
                ->leftJoin('par_common_names as t8', 't7.common_name_id', '=', 't8.id')
                ->leftJoin('wb_trader_account as t9', 't1.local_agent_id', '=', 't9.id')
                ->leftJoin('par_classifications as t10', 't7.classification_id', '=', 't10.id')
                ->leftJoin('tra_approval_recommendations as t11', 't1.permit_id', '=', 't11.id')
                ->leftJoin('tra_registered_products as t12', 't12.tra_product_id', '=', 't7.id')
                ->join('par_validity_statuses as t4', 't12.validity_status_id', '=', 't4.id')
                ->leftJoin('par_storage_conditions as t13', 't7.storage_condition_id', '=', 't13.id')
                ->leftJoin('tra_product_manufacturers as t14', function ($join) {
                    $join->on('t7.id', '=', 't14.product_id')
                        ->on('t14.manufacturer_role_id', '=', DB::raw(1))
                        ->on('t14.manufacturer_type_id', '=', DB::raw(1));
                })
                ->select('t7.*','t1.*', 't1.id as active_application_id', 't1.reg_product_id', 't3.name as applicant_name', 't9.name as local_agent', 't4.name as application_status',
                    't13.name as storage_condition','t7.brand_name', 't12.tra_product_id', 't8.name as common_name', 't10.name as classification_name', 't11.certificate_no', 't11.expiry_date',
                    't7.brand_name as sample_name','t11.certificate_no','t14.manufacturer_id')
                ->where(array('t12.registration_status_id' => 2));//, 't7.section_id'=>$section_id
            if (isset($section_id) && is_numeric($section_id)) {
                $qry->where('t1.section_id', $section_id);
            }
            if ($search_field != '') {
                $qry = $qry->where($search_field, 'like', '%' . $search_value . '%');
            }
            $records = $qry->get();
            $count = $records->count();
            // $results = $qry->get();

            $results = $records->slice($start)->take($limit);

            $res = array(
                'success' => true,
                'results' => $results,
                'totals' => $count,
                'message' => 'All is well'
            );

        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function getProductRegistrationMeetingApplications(Request $request)
    {
        $table_name = $request->input('table_name');
        $workflow_stage = $request->input('workflow_stage_id');
        $meeting_id = $request->input('meeting_id');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('wb_trader_account as t3', 't1.applicant_id', '=', 't3.id')
                ->join('par_system_statuses as t4', 't1.application_status_id', '=', 't4.id')
                ->join('tra_product_information as t7', 't1.product_id', '=', 't7.id')
                ->join('par_common_names as t8', 't7.common_name_id', '=', 't8.id')
                ->leftJoin('tc_meeting_applications as t9', function ($join) use ($meeting_id) {
                    $join->on('t1.application_code', '=', 't9.application_code')
                        ->where('t9.meeting_id', $meeting_id);
                })
                ->leftJoin('tra_evaluation_recommendations as t10', 't1.application_code', '=', 't10.application_code')
                ->leftJoin('tra_auditing_recommendations as t11', 't1.application_code', '=', 't11.application_code')
                ->leftJoin('wf_workflow_actions as t12', 't10.recommendation_id', '=', 't12.id')
                ->leftJoin('wf_workflow_actions as t13', 't11.recommendation_id', '=', 't13.id')
                ->select('t1.*', 't3.name as applicant_name', 't4.name as application_status',
                    't9.meeting_id', 't1.id as active_application_id', 't7.brand_name', 't8.name as common_name',
                    't12.name as evaluator_recommendation', 't13.name as auditor_recommendation')
                ->where('t1.workflow_stage_id', $workflow_stage);
            $results = $qry->get();

            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );

        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function saveTCMeetingDetails(Request $request)
    {
        $id = $request->input('id');
        $application_code = $request->input('application_code');
        $meeting_name = $request->input('meeting_name');
        $meeting_desc = $request->input('meeting_desc');
        $date_requested = $request->input('date_requested');
        $selected = $request->input('selected');
        $selected_codes = json_decode($selected);
        $user_id = $this->user_id;
        try {
            $params = array(
                'meeting_name' => $meeting_name,
                'meeting_desc' => $meeting_desc,
                'date_requested' => $date_requested
            );
            if (isset($id) && $id != '') {
                $params['altered_by'] = $user_id;
                DB::table('tc_meeting_details')
                    ->where('id', $id)
                    ->update($params);
            } else {
                $params['created_by'] = $user_id;
                $insert_res = insertRecord('tc_meeting_details', $params, $user_id);
                $id = $insert_res['record_id'];
                $app_meeting = array(
                    'application_code' => $application_code,
                    'meeting_id' => $id,
                    'created_by' => $user_id
                );
                insertRecord('tc_meeting_applications', $app_meeting, $user_id);
            }
            $params2 = array();
            foreach ($selected_codes as $selected_code) {
                $params2[] = array(
                    'meeting_id' => $id,
                    'application_code' => $selected_code,
                    'created_by' => $this->user_id
                );
            }
            DB::table('tc_meeting_applications')
                ->where('meeting_id', $id)
                ->delete();
            DB::table('tc_meeting_applications')
                ->insert($params2);

            $res = array(
                'success' => true,
                'record_id' => $id,
                'message' => 'Details saved successfully!!'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function syncTcMeetingParticipants(Request $request)
    {
        $selected = $request->input('selected');
        $meeting_id = $request->input('meeting_id');
        $selected_users = json_decode($selected);
        $where = array(
            'meeting_id' => $meeting_id
        );
        try {
            DB::transaction(function () use ($selected_users, $meeting_id, $where) {
                $params = array();
                foreach ($selected_users as $selected_user) {
                    $check = array(
                        'user_id' => $selected_user->user_id,
                        'meeting_id' => $meeting_id
                    );
                    if (DB::table('tc_meeting_participants')
                            ->where($check)->count() == 0) {
                        $params[] = array(
                            'meeting_id' => $meeting_id,
                            'user_id' => $selected_user->user_id,
                            'participant_name' => $selected_user->participant_name,
                            'phone' => $selected_user->phone,
                            'email' => $selected_user->email,
                            'created_by' => $this->user_id
                        );
                    }
                }
                DB::table('tc_meeting_participants')
                    ->insert($params);
            }, 5);
            $res = array(
                'success' => true,
                'message' => 'Participants saved successfully!!'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function prepareProductsRegMeetingStage(Request $request)
    {
        $application_id = $request->input('application_id');
        $application_code = $request->input('application_code');
        $table_name = $request->input('table_name');
        try {
            $qry = DB::table($table_name . ' as t1')
                ->join('tc_meeting_applications as t2', function ($join) use ($application_code) {
                    $join->on('t1.application_code', '=', 't2.application_code');
                })
                ->join('tc_meeting_details as t3', 't2.meeting_id', '=', 't3.id')
                ->select(DB::raw("t3.*"))
                ->where('t1.id', $application_id);
            $results = $qry->first();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }

    public function saveProductRegistrationComments(Request $req)
    {
        try {
            $user_id = \Auth::user()->id;
            $post_data = $req->all();
            $table_name = $post_data['table_name'];
            $id = $post_data['id'];
            //unset unnecessary values
            unset($post_data['_token']);
            unset($post_data['table_name']);
            unset($post_data['model']);
            unset($post_data['id']);
            unset($post_data['unset_data']);
            $unsetData = $req->input('unset_data');
            if (isset($unsetData)) {
                $unsetData = explode(",", $unsetData);
                $post_data = unsetArrayData($post_data, $unsetData);
            }

            $table_data = $post_data;
            //add extra params
            $table_data['created_on'] = Carbon::now();
            $table_data['created_by'] = $user_id;
            $where = array(
                'id' => $id
            );
            $res = array();
            if (isset($id) && $id != "") {
                if (recordExists($table_name, $where)) {
                    unset($table_data['created_on']);
                    unset($table_data['created_by']);
                    $table_data['dola'] = Carbon::now();
                    $table_data['altered_by'] = $user_id;
                    $previous_data = getPreviousRecords($table_name, $where);
                    if ($previous_data['success'] == false) {
                        return $previous_data;
                    }
                    $previous_data = $previous_data['results'];
                    $res = updateRecord($table_name, $previous_data, $where, $table_data, $user_id);
                }
            } else {
                $res = insertRecord($table_name, $table_data, $user_id);
            }
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);
    }

    public function getOnlineApplications(Request $request)
    {
        $section_id = $request->input('section_id');
        $sub_module_id = $request->input('sub_module_id');
        try {
            $data = array();
            $portal_db = DB::connection('portal_db');
            //get process details
            $qry = $portal_db->table('wb_product_applications as t1')
                ->join('wb_product_information as t2', 't1.product_id', '=', 't2.id')
                ->join('wb_trader_account as t3', 't1.trader_id', '=', 't3.id')
                ->join('wb_statuses as t4', 't1.application_status_id', '=', 't4.id')
                ->join('wb_trader_account as t5', 't1.local_agent_id', '=', 't5.id')
                ->select('t1.*', 't1.id as active_application_id', 't1.application_code', 't2.brand_name',
                    't3.name as applicant_name', 't3.contact_person', 't5.name as local_agent',
                    't3.tin_no', 't3.country_id as app_country_id', 't3.region_id as app_region_id', 't3.district_id as app_district_id', 't3.physical_address as app_physical_address',
                    't3.postal_address as app_postal_address', 't3.telephone_no as app_telephone', 't3.fax as app_fax', 't3.email as app_email', 't3.website as app_website',
                    't2.*', 't4.name as application_status', 't4.is_manager_query')
                ->whereIn('application_status_id', array(2, 13, 15, 17));

                $modulesData = getParameterItems('modules','','');
                $subModulesData = getParameterItems('sub_modules','','');
                $zoneData = getParameterItems('par_zones','','');
                if (isset($sub_module_id) && $sub_module_id != '') {
                    $qry->where('t1.sub_module_id', $sub_module_id);
                }
                if (isset($section_id) && $section_id != '') {
                    $qry->where('t1.section_id', $section_id);
                }
                $records = $qry->get();
            foreach($records as $rec){
                $data[] = array('active_application_id'=>$rec->active_application_id,
                'application_code'=>$rec->application_code,
                'brand_name'=>$rec->brand_name,
                'applicant_name'=>$rec->applicant_name,
                'contact_person'=>$rec->contact_person,
                'local_agent'=>$rec->local_agent,
                'app_physical_address'=>$rec->app_physical_address,
                'application_status'=>$rec->application_status,
                'module_id'=>$rec->module_id,
                'sub_module_id'=>$rec->sub_module_id,
                'reg_product_id'=>$rec->reg_product_id,
                'tracking_no'=>$rec->tracking_no,
                'applicant_id'=>$rec->trader_id,
                'local_agent_id'=>$rec->local_agent_id,
                'section_id'=>$rec->section_id,
                'product_id'=>$rec->product_id,
                'zone_id'=>$rec->zone_id,
                'assessment_procedure_id'=>$rec->assessment_procedure_id,
                'date_added'=>$rec->date_added,
                'submission_date'=>$rec->submission_date,

                'module_name'=>returnParamFromArray($modulesData,$rec->module_id),
                'sub_module'=>returnParamFromArray($subModulesData,$rec->sub_module_id),
                'zone_name'=>returnParamFromArray($zoneData,$rec->zone_id));


            }

           
            $res = array(
                'success' => true,
                'results' => $data,
                'message' => 'All is well'
            );
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return \response()->json($res);
    }
    public function deleteUploadedProductImages(Request $req){
        try {
            $record_id = $req->input('id');
            $table_name = $req->input('table_name');
            $user_id = \Auth::user()->id;
            $where = array(
                'id' => $record_id
            );
            $previous_data = getPreviousRecords($table_name, $where);
            if (!$previous_data['success']) {
                return $previous_data;
            }
            $previous_data = $previous_data['results'];
            //get the path to unlink the image s
            $product_img = $previous_data[0];
             $upload_url = env('UPLOAD_DIRECTORY');
             $original_image = $upload_url.'/'.$product_img['document_folder'].'/'.$product_img['file_name'];
             if(file_exists($original_image)){
                $thumbnail_img  = $upload_url.'/'.$product_img['document_folder'].'/'.$product_img['thumbnail_folder'].'/'.$product_img['file_name'];

                unlink($original_image);
                unlink($thumbnail_img);
             }
            
            $res = deleteRecord($table_name, $previous_data, $where, $user_id);
        } catch (\Exception $exception) {
            $res = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);

    }
    public function onLoadOnlineproductNutrients(Request $req){
        try{
            $product_id = $req->product_id;
            $data = array();
            //get the records 
            $records = DB::connection('portal_db')->table('wb_product_nutrients as t1')
                    ->select('t1.*')
                    ->where(array('t1.product_id' => $product_id))
                    ->get();
                     //loop
                     $nutrientsCategory = getParameterItems('par_nutrients_category','','');
                     $si_unitData = getParameterItems('par_si_units','','');
                     $nutrientsData = getParameterItems('par_nutrients','','');
                   
                     foreach ($records as $rec) {
                        //get the array 
                        
                        $data[] = array('product_id'=>$rec->product_id,
                                        'id'=>$rec->id,
                                        'nutrients_category_id'=>$rec->nutrients_category_id,
                                        'nutrients_id'=>$rec->nutrients_id,
                                        'proportion'=>$rec->proportion,
                                        'units_id'=>$rec->units_id,
                                        'nutrients'=>returnParamFromArray($nutrientsData,$rec->nutrients_id),
                                        'nutrients_category'=>returnParamFromArray($nutrientsCategory,$rec->nutrients_category_id),
                                        'si_unit'=>returnParamFromArray($si_unitData,$rec->units_id),
                                );
                                    
                     }
                     $res =array('success'=>true,'results'=> $data);
        }
        catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        } catch (\Throwable $throwable) {
            $res = array(
                'success' => false,
                'message' => $throwable->getMessage()
            );
        }
        return response()->json($res);
    }
}
