<?php

namespace Modules\Parameters\Http\Controllers;


use App\Modules\Parameters\Entities\PremiseRegistration\BusinessScale;
use Illuminate\Support\Facades\Validator;
use App\Modules\Parameters\Entities\PremiseRegistration\BusinessType;
use App\Modules\Parameters\Entities\PremiseRegistration\BusinessTypeDetail;
use App\Modules\Parameters\Entities\PremiseRegistration\PersonnelQualification;
use App\Modules\Parameters\Entities\PremiseRegistration\Section;
use App\Modules\Parameters\Entities\PremiseRegistration\BusinessCategory;
use App\Modules\Parameters\Entities\PremiseRegistration\StudyField;

class PremiseRegistration extends BaseController
{

    public function __construct() {
        $this->invoker =  [
            "save-businessscale" => function($request) {
                $validator = $this->validateParameterRequest($request);

                if($validator -> fails()){
                    return response() -> json([
                        "success" =>  false,
                        "message" => "Form has errors",
                        "errors" => $validator -> errors()
                    ]);
                }

                return BusinessScale::saveData($request,
                    "par_business_scales",
                    $request->input('id'));
            },
            "save-section" => function($request) {
                $validator = null;
                if($request->isMethod("PUT")) {
                    $validator = Validator::make($request -> all(), [
                        "id" => "required|Integer",
                        "name" => "required|max:255",
                        "code" => "sometimes|max:20",
                        "description" => "sometimes|max:255"
                    ]);
                } else {
                    $validator = Validator::make($request->all(), [
                        "name" => "required|max:255",
                        "code" => "sometimes|max:20",
                        "description" => "sometimes|max:255"
                    ]);
                }

                if($validator -> fails()){
                    return response() -> json([
                        "success" =>  false,
                        "message" => "Form has errors",
                        "errors" => $validator -> errors()
                    ]);
                }

                return Section::saveData($request,
                    "par_sections",
                    $request->input('id'));
            },
            "save-businesstype" => function($request) {
                $validator = null;
                if($request->isMethod("PUT")) {
                    $validator = Validator::make($request -> all(), [
                        "id" => "required|Integer",
                        "name" => "required|max:255",
                        "section_id" => "required|Integer",
                        "description" => "sometimes|max:255"
                    ]);
                } else {
                    $validator = Validator::make($request->all(), [
                        "name" => "required|max:255",
                        "section_id" => "required|Integer",
                        "description" => "sometimes|max:255"
                    ]);
                }

                if($validator -> fails()){
                    return response() -> json([
                        "success" =>  false,
                        "message" => "Form has errors",
                        "errors" => $validator -> errors()
                    ]);
                }

                return Section::saveData($request,
                    "par_business_types",
                    $request->input('id'),
                    "section_id");
            },
            "save-businesstypedetail" => function($request) {
                $validator = null;
                if($request->isMethod("PUT")) {
                    $validator = Validator::make($request -> all(), [
                        "id" => "required|Integer",
                        "name" => "required|max:255",
                        "business_type_id" => "required|Integer",
                        "description" => "sometimes|max:255"
                    ]);
                } else {
                    $validator = Validator::make($request->all(), [
                        "name" => "required|max:255",
                        "business_type_id" => "required|Integer",
                        "description" => "sometimes|max:255"
                    ]);
                }

                if($validator -> fails()){
                    return response() -> json([
                        "success" =>  false,
                        "message" => "Form has errors",
                        "errors" => $validator -> errors()
                    ]);
                }

                return Section::saveData($request,
                    "par_business_type_details",
                    $request->input('id'),
                    "business_type_id");
            },
            "save-businesscategory" => function($request) {
                $validator = null;
                if($request->isMethod("PUT")) {
                    $validator = Validator::make($request -> all(), [
                        "id" => "required|Integer",
                        "name" => "required|max:255",
                        "description" => "sometimes|max:255"
                    ]);
                } else {
                    $validator = Validator::make($request->all(), [
                        "name" => "required|max:255",
                        "description" => "sometimes|max:255"
                    ]);
                }

                if($validator -> fails()){
                    return response() -> json([
                        "success" =>  false,
                        "message" => "Form has errors",
                        "errors" => $validator -> errors()
                    ]);
                }

                return Section::saveData($request,
                    "par_business_categories",
                    $request->input('id'));
            },
            "save-studyfield" => function($request) {
                $validator = $this->validateParameterRequest($request);

                if($validator -> fails()){
                    return response() -> json([
                        "success" =>  false,
                        "message" => "Form has errors",
                        "errors" => $validator -> errors()
                    ]);
                }

                return StudyField::saveData($request,
                    "par_study_fields",
                    $request->input('id'));
            },
            "save-personnelqualification" => function($request) {
                $validator = $this->validateParameterRequest($request);

                if($validator -> fails()){
                    return response() -> json([
                        "success" =>  false,
                        "message" => "Form has errors",
                        "errors" => $validator -> errors()
                    ]);
                }

                return StudyField::saveData($request,
                    "par_personnel_qualifications",
                    $request->input('id'),
                    "study_field_id");
            },
            "get-businessscale" => function($start, $limit, $doRetrieveAll, $filter = null) {
                if($filter) {
                    $filter = $this->parseFilter($filter);
                }
                return BusinessScale::getData($start, $limit, $doRetrieveAll, $filter);
            },
            "get-section" => function($start, $limit, $doRetrieveAll, $filter = null) {
                if($filter) {
                    $filter = $this->parseFilter($filter);
                }
                return Section::getData($start, $limit, $doRetrieveAll, $filter);
            },
            "get-businesstype" => function($start, $limit, $doRetrieveAll, $filter = null) {
                $businessTypes = null;
                if($filter) {
                    $filter = $this->parseFilter($filter);
                }
                $businessTypes = BusinessType::getData($start, $limit, $doRetrieveAll, $filter);
                $records = [];
                foreach ($businessTypes["records"] as $businessType) {
                    $section = Section::find($businessType->section_id);
                    if($section != null) {
                        $businessType->section_name = $section->name;
                        array_push($records, $businessType);
                    }
                }

                $businessTypes["records"] = $records;
                return $businessTypes;
            },
            "get-businesstypedetail" => function($start, $limit, $doRetrieveAll, $filter = null) {
                $businessTypeDetails = null;
                $records = [];
                if($filter) {
                    $filter = $this->parseFilter($filter);
                }
                $businessTypeDetails = BusinessTypeDetail::getData($start, $limit, $doRetrieveAll, $filter);
                foreach ($businessTypeDetails["records"] as $businessTypeDetail) {
                    $businessType = BusinessType::find($businessTypeDetail->business_type_id);
                    if($businessType != null) {
                        $businessTypeDetail->business_type_name = $businessTypeDetail->name;
                        array_push($records, $businessTypeDetail);
                    }
                }

                $businessTypeDetails["records"] = $records;
                return $businessTypeDetails;
            },
            "get-businesscategory" => function($start, $limit, $doRetrieveAll, $filter = null) {
                if($filter) {
                    $filter = $this->parseFilter($filter);
                }
                return BusinessCategory::getData($start, $limit, $doRetrieveAll, $filter);

            },
            "get-studyfield" => function($start, $limit, $doRetrieveAll, $filter = null) {
                if($filter) {
                    $filter = $this->parseFilter($filter);
                }
                return Section::getData($start, $limit, $doRetrieveAll, $filter);
            },
            "get-personnelqualification" => function($start, $limit, $doRetrieveAll, $filter = null) {
                $personnelQualifications = null;
                $records = [];
                if($filter) {
                    $filter = $this->parseFilter($filter);
                }
                $personnelQualifications = PersonnelQualification::getData($start, $limit, $doRetrieveAll, $filter);
                foreach ($personnelQualifications["records"] as $personnelQualification) {
                    $studyField = StudyField::find($personnelQualification->study_field_id);
                    if($studyField) {
                        $personnelQualification->study_field_name =$studyField->name;
                        array_push($records, $personnelQualification);
                    }
                }

                $personnelQualifications["records"] = $records;
                return $personnelQualifications;
            },
            "merge-businessscale" => function($request) {
                return BusinessScale::merge(
                    $request->input('mergeToId'),
                    "business_scale_id",
                    "par_business_scales",
                    $request->input('ids'));
            },
            "merge-section" => function($request) {
                return Section::merge(
                    $request->input('mergeToId'),
                    "section_id",
                    "par_sections",
                    $request->input('ids'));
            },
            "merge-businesstype" => function($request) {
                return Section::merge(
                    $request->input('mergeToId'),
                    "business_type_id",
                    "par_business_types",
                    $request->input('ids'));
            },
            "merge-businesstypedetail" => function($request) {
                return Section::merge(
                    $request->input('mergeToId'),
                    "business_type_detail_id",
                    "par_business_type_details",
                    $request->input('ids'));
            },
            "merge-businesscategory" => function($request) {
                return Section::merge(
                    $request->input('mergeToId'),
                    "business_category_id",
                    "par_business_categories",
                    $request->input('ids'));
            },
            "merge-studyfield" => function($request) {
                return Section::merge(
                    $request->input('mergeToId'),
                    "study_field_id",
                    "par_study_fields",
                    $request->input('ids'));
            },
            "merge-personnelqualification" => function($request) {
                return Section::merge(
                    $request->input('mergeToId'),
                    "personnel_qualification_id",
                    "par_personnel_qualifications",
                    $request->input('ids'));
            },
            "delete-businessscale" => function($id, $action) {
                return BusinessScale::deleteData('par_business_scales', $id, $action);
            },
            "delete-section" => function($id, $action) {
                return Section::deleteData('par_sections', $id, $action);
            },
            "delete-businesstype" => function($id, $action) {
                return Section::deleteData('par_business_types', $id, $action);
            },
            "delete-businesstypedetail" => function($id, $action) {
                return Section::deleteData('par_business_type_details', $id, $action);
            },
            "delete-businesscategory" => function($id, $action) {
                return Section::deleteData('par_business_categories', $id, $action);
            },
            "delete-studyfield" => function($id, $action) {
                return Section::deleteData('par_study_fields', $id, $action);
            },
            "delete-personnelqualification" => function($id, $action) {
                return Section::deleteData('par_personnel_qualifications', $id, $action);
            }
        ];
    }
}
