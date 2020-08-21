<?php

namespace App\Modules\DocumentManagement\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Ixudra\Curl\Facades\Curl;
use Image;
class DocumentManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
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

    public function getProcessApplicableDocTypes(Request $req)
    {
        $process_id = $req->input('process_id');
        $workflow_stage = $req->input('workflow_stage');
        $section_id = $req->input('section_id');
        $module_id = $req->input('module_id');
        $sub_module_id = $req->input('sub_module_id');

        $document_type_id = $req->input('document_type_id');
        $where = array(
            'section_id' => $section_id,
            'module_id' => $module_id,
            'sub_module_id' => $sub_module_id
        );
        try {
            $process_id = getSingleRecordColValue('wf_tfdaprocesses', $where, 'id');

            $qry = DB::table('tra_proc_applicable_doctypes as t1')
                ->join('par_document_types as t2', 't1.doctype_id', '=', 't2.id')
                ->select('t2.id', 't2.name');
            if (isset($process_id) && $process_id != '') {
                $qry->where('t1.process_id', $process_id);
            }
            if (isset($workflow_stage) && $workflow_stage != '') {
                $qry->where('t1.stage_id', $workflow_stage);
            }
            if(validateIsNumeric($document_type_id)){
                $qry->where('t1.id', $document_type_id);
            }
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
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

    public function getProcessApplicableDocRequirements(Request $req)
    {
        $docType_id = $req->input('docType_id');
        $process_id = $req->input('process_id');
        $section_id = $req->input('section_id');
        $module_id = $req->input('module_id');
        $sub_module_id = $req->input('sub_module_id');
        $where = array(
            'section_id' => $section_id,
            'module_id' => $module_id,
            'sub_module_id' => $sub_module_id
        ); 
        try {
            $qry = DB::table('tra_documentupload_requirements as t1')
                /*->join('wf_tfdaprocesses as t2', function ($join) {
                    $join->on("t1.section_id", "=", "t2.section_id")
                        ->on("t1.module_id", "=", "t2.module_id")
                        ->on("t1.sub_module_id", "=", "t2.sub_module_id");
                })*/
                ->select('t1.id', 't1.name')
                ->where('t1.document_type_id', $docType_id)
                ->where($where);
            //->where('t2.id', $process_id);
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
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

    public function onLoadApplicationDocumentsRequirements(Request $req)
    {
        try {
            $application_code = $req->input('application_code');
            $table_name = $req->input('table_name');
            $process_id = $req->input('process_id');
            $workflow_stage = $req->input('workflow_stage');
            $where = array(
                'process_id' => $process_id,
                'stage_id' => $workflow_stage
            );
            //get applicable document types
            $qry1 = DB::table('tra_proc_applicable_doctypes')
                ->select('doctype_id')
                ->where($where);
            $docTypes = $qry1->get();
            $docTypes = convertStdClassObjToArray($docTypes);
            $docTypes = convertAssArrayToSimpleArray($docTypes, 'doctype_id');
            //get applicable document requirements
            $qry = DB::table('tra_documentupload_requirements as t1')
                ->leftJoin('par_document_types as t2', 't1.document_type_id', '=', 't2.id')
                ->select('t1.id', 't1.name')
                ->join($table_name . ' as t3', function ($join) {
                    $join->on("t1.section_id", "=", "t3.section_id")
                        ->on("t1.sub_module_id", "=", "t3.sub_module_id");
                })
                ->where(array('t3.application_code' => $application_code))//, 't1.document_type_id' => $document_type_id))
                ->whereIn('t1.document_type_id', $docTypes);

            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
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

    
    public function onLoadUnstructureApplicationDocumentsUploads(Request $req)
    {
        try {
            $reference_record_id = $req->input('reference_record_id');
            $table_name = $req->input('table_name');

            $document_type_id = $req->input('document_type_id');
            
            $qry = DB::table('tra_nonstructured_docdefination as t1')
                ->join('par_document_types as t2', 't1.document_type_id', '=', 't2.id')
                ->select(DB::raw("t4.remarks, 
                t4.node_ref, t2.name as document_type, t4.id,t4.initial_file_name,t4.file_name, reference_record_id,
                t4.file_type,t4.uploaded_on,CONCAT_WS(' ',decrypt(t5.first_name),decrypt(t5.last_name)) as uploaded_by,
                t1.document_type_id"))
                ->leftJoin($table_name.' as t4', function ($join) use ($reference_record_id) {
                    $join->on("t1.document_type_id", "=", "t4.document_type_id")
                         ->where("t4.reference_record_id", "=", $reference_record_id);
                })
                ->leftJoin('users as t5', 't4.uploaded_by', '=', 't5.id');
                $qry->where('t1.document_type_id', $document_type_id);

            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
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
    public function onLoadApplicationDocumentsUploads1(Request $req)
    {
        try {
            $application_code = $req->input('application_code');
            $table_name = $req->input('table_name');
            $process_id = $req->input('process_id');
            $workflow_stage = $req->input('workflow_stage');
            $doc_type_id = $req->input('document_type_id');
            $where = array();
            $docTypes = array();
            if(validateIsNumeric($process_id) && validateIsNumeric($workflow_stage)){
                $where = array(
                    'process_id' => $process_id,
                    'stage_id' => $workflow_stage
                );
                  //get applicable document types
                    $qry1 = DB::table('tra_proc_applicable_doctypes')
                    ->select('doctype_id')
                    ->where($where);
                $docTypes = $qry1->get();
                $docTypes = convertStdClassObjToArray($docTypes);
                $docTypes = convertAssArrayToSimpleArray($docTypes, 'doctype_id');
            }
            
            //get applicable document requirements
            $qry = DB::table('tra_documentupload_requirements as t1')
                ->join('par_document_types as t2', 't1.document_type_id', '=', 't2.id')
                ->select(DB::raw("t4.remarks, t1.module_id,t1.sub_module_id,t1.section_id,
                t3.application_code, t4.node_ref, t2.name as document_type, t4.id,t4.initial_file_name,t4.file_name, 
                t4.file_type,t4.uploaded_on,CONCAT_WS(' ',decrypt(t5.first_name),decrypt(t5.last_name)) as uploaded_by,t1.is_mandatory,
                t1.id as document_requirement_id, t1.document_type_id,t2.name as document_type, t1.name as document_requirement"))
                ->join($table_name . ' as t3', function ($join) {
                    $join->on("t1.section_id", "=", "t3.section_id")
                        ->on("t1.sub_module_id", "=", "t3.sub_module_id");
                })
                ->leftJoin('tra_application_uploadeddocuments as t4', function ($join) {
                    $join->on("t3.application_code", "=", "t4.application_code")
                        ->on("t1.id", "=", "t4.document_requirement_id");
                })
                ->leftJoin('users as t5', 't4.uploaded_by', '=', 't5.id')
                ->where(array('t3.application_code' => $application_code));
                
            if (validateIsNumeric($doc_type_id)) {
                $qry->where('t1.document_type_id', $doc_type_id);
            } else if(count($docTypes) > 0){
                $qry->whereIn('t1.document_type_id', $docTypes);;
            }

            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
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

    public function onLoadApplicationDocumentsUploads(Request $req)
    {
        $application_code = $req->input('application_code');
        $table_name = $req->input('table_name');
        $process_id = $req->input('process_id');
        $workflow_stage = $req->input('workflow_stage');
        $doc_type_id = $req->input('document_type_id');
        $portal_uploads = $req->input('portal_uploads');
        $section_id = $req->input('section_id');
        $module_id = $req->input('module_id');
        $sub_module_id = $req->input('sub_module_id');
        try {
            $where = array(
                'module_id' => $module_id,
                'sub_module_id' => $sub_module_id,
                'section_id' => $section_id
            );
            $process_id = getSingleRecordColValue('wf_tfdaprocesses', $where, 'id');
            //get applicable document types
            $qry1 = DB::table('tra_proc_applicable_doctypes')
                ->select('doctype_id');
            if (isset($process_id) && $process_id != '') {
                $qry1->where('process_id', $process_id);
            }
            if (isset($workflow_stage) && $workflow_stage != '') {
                $qry1->where('stage_id', $workflow_stage);
            }
            if (validateIsNumeric($doc_type_id)) {
                $qry1->where('doctype_id', $doc_type_id);
            }
            $docTypes = $qry1->get();
            $docTypes = convertStdClassObjToArray($docTypes);
            $docTypes = convertAssArrayToSimpleArray($docTypes, 'doctype_id');
            
            //get applicable document requirements
            $qry = DB::table('tra_documentupload_requirements as t1')
                ->join('par_document_types as t2', 't1.document_type_id', '=', 't2.id')
                ->select(DB::raw("t4.remarks, t1.id as document_requirement_id, t4.application_code,
                t4.node_ref, t2.name as document_type, t4.id,t4.initial_file_name,t4.file_name, t1.module_id,t1.sub_module_id,t1.section_id,
                t4.file_type,t4.uploaded_on,CONCAT_WS(' ',decrypt(t5.first_name),decrypt(t5.last_name)) as uploaded_by,t1.is_mandatory,
                t1.id as document_requirement_id, t1.document_type_id,t2.name as document_type, t1.name as document_requirement"))
                ->leftJoin('tra_application_uploadeddocuments as t4', function ($join) use ($application_code) {
                    $join->on("t1.id", "=", "t4.document_requirement_id")
                         ->where("t4.application_code", "=", $application_code);
                })
                ->leftJoin('users as t5', 't4.uploaded_by', '=', 't5.id')
                ->where($where);
                if (isset($doc_type_id) && $doc_type_id != '') {
                    $qry->where('t1.document_type_id', $doc_type_id);
                } else {
                    $qry->whereIn('t1.document_type_id', $docTypes);;
                }
                if (isset($portal_uploads) && $portal_uploads == 1) {
                    $qry->where('t1.portal_uploadable', 1);
                }
            $results = $qry->get();
            $res = array(
                'success' => true,
                'results' => $results,
                'message' => 'All is well'
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
    
    public function onLoadProductImagesUploads(Request $req)
    {
        $product_id = $req->input('product_id');
        $document_type_id = $req->input('document_type_id');
        
        try {
            $data = array();
            $upload_url = env('UPLOAD_URL');
            //get applicable document requirements
            
            $qry = DB::table('par_document_types as t1')
                ->select(DB::raw("t4.remarks, t1.id as document_type_id, t4.product_id,
                 t1.name as document_type, t4.id,t4.initial_file_name,t4.file_name,t4.document_folder,thumbnail_folder,
                t4.filetype,t4.uploaded_on,CONCAT_WS(' ',decrypt(t5.first_name),decrypt(t5.last_name)) as uploaded_by"))
                ->leftJoin('tra_uploadedproduct_images as t4', function ($join) use ($product_id) {
                    $join->on("t1.id", "=", "t4.document_type_id")
                         ->where("t4.product_id", "=", $product_id);
                })
                ->leftJoin('users as t5', 't4.uploaded_by', '=', 't5.id')
                ->where(array('t1.id'=>$document_type_id));
                
            $results = $qry->get();
            foreach($results  as $res){
                    $data[] = array('remarks'=>$res->remarks,
                    'document_type_id'=>$res->document_type_id,
                    'product_id'=>$res->product_id,
                    'document_type'=>$res->document_type,
                    'id'=>$res->id,
                    'initial_file_name'=>$res->initial_file_name,
                    'file_name'=>$res->file_name,
                    'filetype'=>$res->filetype,
                    'uploaded_on'=>$res->uploaded_on,
                    'uploaded_image'=>$upload_url.'/'.$res->document_folder.'/'.$res->thumbnail_folder.'/'.$res->file_name,
                    'originaluploaded_image'=>$upload_url.'/'.$res->document_folder.'/'.$res->file_name,
                    'uploaded_by'=>$res->uploaded_by
                );

            }
            $res = array(
                'success' => true,
                'results' => $data,
                'message' => 'All is well'
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
    public function onLoadOnlineProductImagesUploads(Request $req){
        $product_id = $req->input('product_id');
        $document_type_id = $req->input('document_type_id');
        try {
            $data = array();
            $upload_url = env('UPLOAD_URL');
            $qry = DB::table('par_document_types as t1')
                ->select(DB::raw("t4.remarks, t1.id as document_type_id, t4.product_id,
                 t1.name as document_type, t4.id,t4.initial_file_name,t4.file_name,t4.document_folder,thumbnail_folder,
                t4.filetype,t4.uploaded_on,CONCAT_WS(' ',decrypt(t5.first_name),decrypt(t5.last_name)) as uploaded_by"))
                ->leftJoin('tra_uploadedproduct_images as t4', function ($join) use ($product_id) {
                    $join->on("t1.id", "=", "t4.document_type_id")
                         ->where("t4.portal_product_id", "=", $product_id);
                })
                ->leftJoin('users as t5', 't4.uploaded_by', '=', 't5.id')
                ->where(array('t1.id'=>$document_type_id));
                
            $results = $qry->get();
            foreach($results  as $res){
                    $data[] = array('remarks'=>$res->remarks,
                    'document_type_id'=>$res->document_type_id,
                    'product_id'=>$res->product_id,
                    'document_type'=>$res->document_type,
                    'id'=>$res->id,
                    'initial_file_name'=>$res->initial_file_name,
                    'file_name'=>$res->file_name,
                    'filetype'=>$res->filetype,
                    'uploaded_on'=>$res->uploaded_on,
                    'uploaded_image'=>$upload_url.'/'.$res->document_folder.'/'.$res->thumbnail_folder.'/'.$res->file_name,
                    'originaluploaded_image'=>$upload_url.'/'.$res->document_folder.'/'.$res->file_name,
                    'uploaded_by'=>$res->uploaded_by
                );
            }
            $res = array(
                'success' => true,
                'results' => $data,
                'message' => 'All is well'
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
    public function uploadApplicationDocumentFile(Request $req)
    {
        try {
            $user_id = $this->user_id;
            //get the documetn definations 
            $application_code = $req->application_code;
            $module_id = $req->module_id;
            $record_id = $req->id;
            $node_ref = $req->node_ref;
            $sub_module_id = $req->sub_module_id;
            $document_type_id = $req->document_type_id;
            $document_requirement_id = $req->document_requirement_id;
            $file = $req->file('uploaded_doc');
                
            $app_rootnode = getApplicationRootNode($application_code);
          
            $app_rootnode = getDocumentTypeRootNode($app_rootnode->dms_node_id, $application_code, $document_type_id, $user_id);

            $table_name = 'tra_application_uploadeddocuments';

            if ($app_rootnode) {
                if ($req->hasFile('uploaded_doc')) {
                    $origFileName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $fileSize = $file->getClientSize();
                    $document_rootupload = env('DOC_ROOTUPLOAD', 'tfda');

                    $destination = getcwd() . $document_rootupload;
                    $savedName = str_random(3) . time() . '.' . $extension;

                    $file->move($destination, $savedName);
                    $document_path = $destination . $savedName;
                    //check if tje dpcument type has been mapped and not autoCreate the folder
                    $document_requirement = getParameterItem('tra_documentupload_requirements', $document_requirement_id);

                    //get the application root folder

                    $uploadfile_name = $document_requirement . str_random(5) . '.' . $extension;
                    $destination_node = $app_rootnode->node_ref;

                    if (validateIsNumeric($record_id)) {

                        $response = dmsUploadNodeDocument($destination_node, $document_path, $uploadfile_name, $node_ref);
                        $node_ref = $response['nodeRef'];
                        $document_data = array('application_code' => $application_code,
                            'document_requirement_id' => $document_requirement_id,
                            'uploaded_on' => Carbon::now(),
                            'uploaded_by' => $user_id,
                            'file_name' => $uploadfile_name,
                            'initial_file_name' => $origFileName,
                            'file_type' => $extension,
                            'node_ref' => $node_ref,
                            'dola' => Carbon::now(),
                            'altered_by' => $user_id,
                        );

                        $where = array('id' => $record_id);

                        if (recordExists($table_name, $where)) {

                            $previous_data = getPreviousRecords('tra_application_uploadeddocuments', $where);
                            $previous_data = $previous_data['results'];
                            $res = updateRecord('tra_application_uploadeddocuments', $previous_data, $where, $document_data, $user_id);

                            $previous_data = $previous_data[0];
                            $document_upload_id = $previous_data['id'];
                            unset($previous_data['id']);
                            $previous_data['document_upload_id'] = $document_upload_id;
                            insertRecord('tra_documents_prevversions', $previous_data, $user_id);

                        }
                    } else {
                        $response = dmsUploadNodeDocument($destination_node, $document_path, $uploadfile_name, '');

                        $node_ref = $response['nodeRef'];
                        $document_data = array('application_code' => $application_code,
                            'document_requirement_id' => $document_requirement_id,
                            'document_type_id' => $document_type_id,
                            'uploaded_on' => Carbon::now(),
                            'uploaded_by' => $user_id,
                            'file_name' => $uploadfile_name,
                            'initial_file_name' => $origFileName,
                            'file_type' => $extension,
                            'node_ref' => $node_ref,
                            'created_on' => Carbon::now(),
                            'created_by' => $user_id,
                        );
                        $res = insertRecord('tra_application_uploadeddocuments', $document_data, $user_id);
                        if ($res['success']) {

                            $res['message'] = 'Document Uploaded Successfully';
                        } else {
                            $res['message'] = 'Document Upload failed, try again or contact the system admin';

                        }


                    }

                } else {
                    $res = array(
                        'success' => false,
                        'message' => 'No document attachement for upload'
                    );
                }

            } else {
                $res = array(
                    'success' => false,
                    'message' => 'DMS Document Type Node not configured, contact the system Admin'
                );

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


    
    public function uploadunstructureddocumentuploads(Request $req)
    {
        try {

            $user_id = $this->user_id;
            //get the documetn definations 
            $reference_record_id = $req->reference_record_id;
            $document_type_id = $req->document_type_id;
            $record_id = $req->id;
            $node_ref = $req->node_ref;

            $table_name = $req->table_name;
            $reference_table_name = $req->reference_table_name;

            $file = $req->file('uploaded_doc');
          //tra_nonstructured_docdefination
            $rootnode_ref = getSingleRecordColValue('tra_nonstructured_docdefination', array('document_type_id'=>$document_type_id), 'node_ref');
           
            $app_rootnode = getNonStructuredDocApplicationRootNode($rootnode_ref,$reference_record_id,$reference_table_name,$document_type_id,$user_id);

            if ($app_rootnode) {
                if ($req->hasFile('uploaded_doc')) {
                    $origFileName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $fileSize = $file->getClientSize();
                    $document_rootupload = env('DOC_ROOTUPLOAD', 'tfda');

                    $destination = getcwd() . $document_rootupload;
                    $savedName = str_random(3) . time() . '.' . $extension;

                    $file->move($destination, $savedName);
                    $document_path = $destination . $savedName;
                    //check if tje dpcument type has been mapped and not autoCreate the folder
                    
                     $document_type = getParameterItem('par_document_types', $document_type_id, 'mysql');
                    $uploadfile_name = $document_type . str_random(5) . '.' . $extension;
                    $destination_node = $app_rootnode->node_ref;

                    if (validateIsNumeric($record_id)) {

                        $response = dmsUploadNodeDocument($destination_node, $document_path, $uploadfile_name, $node_ref);
                        $node_ref = $response['nodeRef'];

                        $document_data = array('reference_record_id' => $reference_record_id,
                            'document_type_id' => $document_type_id,
                            'uploaded_on' => Carbon::now(),
                            'uploaded_by' => $user_id,
                            'file_name' => $uploadfile_name,
                            'initial_file_name' => $origFileName,
                            'file_type' => $extension,
                            'node_ref' => $node_ref,
                            'dola' => Carbon::now(),
                            'altered_by' => $user_id,
                        );

                        $where = array('id' => $record_id);

                        if (recordExists($table_name, $where)) {

                            $previous_data = getPreviousRecords( $table_name, $where);
                            $previous_data = $previous_data['results'];
                            $res = updateRecord( $table_name, $previous_data, $where, $document_data, $user_id);

                            $previous_data = $previous_data[0];
                            $document_upload_id = $previous_data['id'];
                           
                        }

                    } else {

                        $response = dmsUploadNodeDocument($destination_node, $document_path, $uploadfile_name, '');

                        $node_ref = $response['nodeRef'];
                         $document_data = array('reference_record_id' => $reference_record_id,
                            'document_type_id' => $document_type_id,
                            'uploaded_on' => Carbon::now(),
                            'uploaded_by' => $user_id,
                            'file_name' => $uploadfile_name,
                            'initial_file_name' => $origFileName,
                            'file_type' => $extension,
                            'node_ref' => $node_ref,
                            'dola' => Carbon::now(),
                            'altered_by' => $user_id,
                        );

                        $res = insertRecord($table_name, $document_data, $user_id);
                        if ($res['success']) {

                            $res['message'] = 'Document Uploaded Successfully';
                        } else {
                            $res['message'] = 'Document Upload failed, try again or contact the system admin';

                        }


                    }

                } else {
                    $res = array(
                        'success' => false,
                        'message' => 'No document attachement for upload'
                    );
                }

            } else {
                $res = array(
                    'success' => false,
                    'message' => 'DMS Document Type Node not configured, contact the system Admin'
                );

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
    public function uploadProductImages(Request $req){
        try {
            $user_id = $this->user_id;
            //get the documetn definations 
            $application_code = $req->application_code;
            $module_id = $req->module_id;
            $record_id = $req->id;
            $node_ref = $req->node_ref;
            $sub_module_id = $req->sub_module_id;
            $document_type_id = $req->document_type_id;
            $product_id = $req->product_id;
            $file = $req->file('uploaded_doc');
            
            $table_name = 'tra_uploadedproduct_images';

                if ($req->hasFile('uploaded_doc')) {
                    $origFileName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $fileSize = $file->getClientSize();
                    $file = $req->file('uploaded_doc');

                    $origFileName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $fileSize = $file->getClientSize();
                    //$folder = '\resources\uploads';
                    $upload_directory = env('UPLOAD_DIRECTORY');

                    $folder = 'product_images';
                    $thumbnail_folder = 'thumbnails';

                    $destination = $upload_directory.'/'. $folder;
                
                    $savedName = str_random(5) . time() . '.' . $extension;
                    
                    if($file->move($destination, $savedName)){
                       
                            //more the thumb nail file 
                            $thumb_dest = $upload_directory.'/'. $folder.'/'.$thumbnail_folder.'/';

                            $img = Image::make($destination.'/'.$savedName);
                            
                            // resize image to fixed size
                            $img->resize(150, 150);
                            $img->save($thumb_dest.$savedName);
                            
                            $params['initial_file_name'] = $origFileName;
                            $params['file_name'] = $savedName;
                            $params['file_size'] = formatBytes($fileSize);
                            $params['filetype'] = $extension;
                            $params['thumbnail_folder'] = $thumbnail_folder;
                            $params['document_folder'] = $folder;
                            $params['product_id'] = $product_id;
                            $params['created_on'] = Carbon::now();
                            $params['created_by'] = $user_id;

                            $params['uploaded_on'] = Carbon::now();
                            $params['uploaded_by'] = $user_id;

                            $params['document_type_id'] = $document_type_id;
                            $res = insertRecord($table_name, $params, $user_id); 

                    }
                       
                    else{
                        $res = array(
                            'success' => false,
                            'message' => 'Product Image Upload Failed'
                        );
                    }
                } else {
                    $res = array(
                        'success' => false,
                        'message' => 'No document attachement for upload'
                    );
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
    public function getApplicationDocumentDownloadurl(Request $req)
    {
        try {

            $node_ref = $req->node_ref;
            $document_versionid = $req->document_versionid;
            $url = downloadDocumentUrl($node_ref, $document_versionid);
            $res = array(
                'success' => true,
                'document_url' => $url
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

    public function getApplicationDocumentPreviousVersions(Request $req)
    {
        try {
            $table_name = $req->table_name;

            $document_upload_id = $req->document_upload_id;

            $doc_data = array();

            $i = 1;
            $doc_data = DB::table('tra_documentupload_requirements as t1')
                ->leftJoin('par_document_types as t2', 't1.document_type_id', '=', 't2.id')
                ->select(DB::raw("t4.remarks,  t4.node_ref, t2.name as document_type, t4.id,t4.initial_file_name,t4.file_name, t4.file_type,t4.uploaded_on,IF(t4.uploaded_by > 0, t5.username, t4.uploaded_by) as uploaded_by ,t1.is_mandatory ,t1.id as document_requirement_id, t1.document_type_id,t2.name as document_type, t1.name as document_requirement"))
                ->join('tra_documents_prevversions as t4', function ($join) {
                    $join->on("t1.id", "=", "t4.document_requirement_id");
                })
                ->leftJoin('users as t5', 't4.uploaded_by', '=', 't5.id')
                ->where(array('document_upload_id' => $document_upload_id))
                ->get();//, 

            $res = array('success' => true, 'results' => $doc_data);

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

    public function onApplicationDocumentDelete(Request $req)
    {
        try {
            $application_code = $req->application_code;
            $node_ref = $req->node_ref;
            $record_id = $req->record_id;
            $user_id = $this->user_id;
            $table_name = 'tra_application_uploadeddocuments';
            $data = array();
            //get the records
            $resp = false;
            $where_state = array('application_code' => $application_code, 'id' => $record_id);
            $records = DB::table($table_name)
                ->where($where_state)
                ->get();
            if (count($records) > 0) {

                $response = dmsDeleteAppRootNodesChildren($node_ref);
                if ($response['success']) {
                    $previous_data = getPreviousRecords($table_name, $where_state);
                    $previous_data = $previous_data['results'];
                    $resp = deleteRecordNoTransaction($table_name, $previous_data, $where_state, $user_id);

                }
            }
            if ($resp) {
                $res = array('success' => true, 'message' => 'Document deleted successfully');

            } else {
                $res = array('success' => false, 'message' => 'Document delete failed, contact the system admin if this persists');
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
    
    public function onDeleteNonStructureApplicationDocument(Request $req)
    {
        try {
            $application_code = $req->application_code;
            $reference_record_id = $req->reference_record_id;
            $table_name = $req->table_name;
            $node_ref = $req->node_ref;
            $record_id = $req->record_id;
            $user_id = $this->user_id;
            $table_name = 'tra_application_uploadeddocuments';
            $data = array();
            //get the records
            $resp = false;
            $where_state = array('reference_record_id' => $reference_record_id, 'id' => $record_id);
            $records = DB::table($table_name)
                ->where($where_state)
                ->get();
            if (count($records) > 0) {

                $response = dmsDeleteAppRootNodesChildren($node_ref);
                if ($response['success']) {
                    $previous_data = getPreviousRecords($table_name, $where_state);
                    $previous_data = $previous_data['results'];
                    $resp = deleteRecordNoTransaction($table_name, $previous_data, $where_state, $user_id);

                }
            }
            if ($resp) {
                $res = array('success' => true, 'message' => 'Document deleted successfully');

            } else {
                $res = array('success' => false, 'message' => 'Document delete failed, contact the system admin if this persists');
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
}
