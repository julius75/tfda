<?php

namespace Modules\Parameters\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Modules\Parameters\Entities\Finance\CostCenter;
use App\Modules\Parameters\Entities\Finance\CostCategory;
use App\Modules\Parameters\Entities\Finance\CostSubCategory;
use App\Modules\Parameters\Entities\Finance\Currency;
use App\Modules\Parameters\Entities\Finance\ExchangeRate;
use App\Modules\Parameters\Entities\Finance\FeeType;
use App\Modules\Parameters\Entities\Finance\PaymentInterval;
use App\Modules\Parameters\Entities\Finance\TransactionType;
use App\Modules\Parameters\Entities\Locations\Country;
use App\Modules\Parameters\Entities\Locations\Region;
use App\Modules\Parameters\Entities\Locations\District;
use App\Modules\Parameters\Entities\Locations\City;
use App\Modules\Parameters\Entities\PortalParameter;
use Illuminate\Support\Facades\DB;

class CommonParameter extends BaseController
{
    public function __construct()
    {

        $this->invoker = [
//            "save-portalparameter" => function($request) {
//                $validator = $this->validateParameterRequest($request);
//
//                if($validator -> fails()){
//                    return response() -> json([
//                        "success" =>  false,
//                        "message" => "Form has errors",
//                        "errors" => $validator -> errors()
//                    ]);
//                }
//
//                return PortalParameter::saveData($request,
//                    "par_portal_parameters",
//                    $request->input('id'));
//            },
            "save-country" => function ($request) {
                $validator = $this->validateParameterRequest($request);

                if ($validator->fails()) {
                    return response()->json([
                        "success" => false,
                        "message" => "Form has errors",
                        "errors" => $validator->errors()
                    ]);
                }

                return Country::saveData($request,
                    "par_countries",
                    $request->input('id'));
            },
            "save-region" => function ($request) {
                $validator = $this->validateParameterRequest($request,
                    "country_id");

                if ($validator->fails()) {
                    return response()->json([
                        "success" => false,
                        "message" => "Form has errors",
                        "errors" => $validator->errors()
                    ]);
                }
                return Region::saveData($request, 'par_regions',
                    $request->input('id'),
                    'country_id');
            },
            "save-district" => function ($request) {
                $validator = $this->validateParameterRequest($request, "region_id");

                if ($validator->fails()) {
                    return response()->json([
                        "success" => false,
                        "message" => "Form has errors",
                        "errors" => $validator->errors()
                    ]);
                }
                return District::saveData($request, 'par_districts',
                    $request->input('id'),
                    'region_id');
            },
            "save-city" => function ($request) {
                $validator = $this->validateParameterRequest($request,
                    "district_id");

                if ($validator->fails()) {
                    return response()->json([
                        "success" => false,
                        "message" => "Form has errors",
                        "errors" => $validator->errors()
                    ]);
                }
                return city::saveData($request, 'par_cities',
                    $request->input('id'),
                    'district_id');
            },
            "save-costcenter" => function ($request) {
                $validator = $this->validateParameterRequest($request);

                if ($validator->fails()) {
                    return response()->json([
                        "success" => false,
                        "message" => "Form has errors",
                        "errors" => $validator->errors()
                    ]);
                }
                return CostCenter::saveData($request, "par_cost_centers", $request->input('id'));
            },
            "save-costcategory" => function ($request) {
                $validator = $this->validateParameterRequest($request);

                if ($validator->fails()) {
                    return response()->json([
                        "success" => false,
                        "message" => "Form has errors",
                        "errors" => $validator->errors()
                    ]);
                }
                return CostCategory::saveData($request, 'par_cost_categories',
                    $request->input('id'),
                    'cost_center_id');
            },
            "save-costsubcategory" => function ($request) {
                $validator = $this->validateParameterRequest($request);
                if ($validator->fails()) {
                    return response()->json([
                        "success" => false,
                        "message" => "Form has errors",
                        "errors" => $validator->errors()
                    ]);
                }
                return CostSubCategory::saveData($request, 'par_cost_sub_categories',
                    $request->input('id'),
                    'cost_category_id');
            },
            "save-currency" => function ($request) {
                $validator = $this->validateParameterRequest($request);

                if ($validator->fails()) {
                    return response()->json([
                        "success" => false,
                        "message" => "Form has errors",
                        "errors" => $validator->errors()
                    ]);
                }
                return Currency::saveData($request, "par_currencies", $request->input('id'));
            },
            "save-exchangerate" => function ($request) {
                $validator = null;
                if ($request->isMethod("PUT")) {
                    $validator = Validator::make($request->all(), [
                        "id" => "required|Integer",
                        "rate" => "required|Numeric",
                        "currency_id" => "required|Integer",
                        "description" => "sometimes|max:255"
                    ]);
                } else {
                    $validator = Validator::make($request->all(), [
                        "rate" => "required|Numeric",
                        "currency_id" => "required|Integer",
                        "description" => "sometimes|max:255"
                    ]);
                }

                if ($validator->fails()) {
                    return response()->json([
                        "success" => false,
                        "message" => "Form has errors",
                        "errors" => $validator->errors()
                    ]);
                }
                return ExchangeRate::saveExchangeRate($request, $request->input('id'));
            },
            "save-feetype" => function ($request) {
                $validator = null;
                if ($request->isMethod("PUT")) {
                    $validator = Validator::make($request->all(), [
                        "id" => "required|Integer",
                        "name" => "required",
                        "gl_code" => "required"
                    ]);
                } else {
                    $validator = Validator::make($request->all(), [
                        "name" => "required",
                        "gl_code" => "required"
                    ]);
                }

                if ($validator->fails()) {
                    return response()->json([
                        "success" => false,
                        "message" => "Form has errors",
                        "errors" => $validator->errors()
                    ]);
                }
                return FeeType::saveFeeType($request, $request->input('id'));
            },
            "save-transactiontype" => function ($request) {
                $validator = null;
                if ($request->isMethod("PUT")) {
                    $validator = Validator::make($request->all(), [
                        "id" => "required|Integer",
                        "t_code" => "required",
                        "description" => "sometimes|max:255",
                        "t_type" => [
                            "required",
                            Rule::in(["Debit", "Credit"])
                        ],
                        "output" => [
                            "required",
                            Rule::in(["None", "Receipt", "Debit Note", "Credit Note"])
                        ],
                        "system_invoice" => "sometimes|boolean",
                        "system_receipt" => "sometimes|boolean"
                    ]);
                } else {
                    $validator = Validator::make($request->all(), [
                        "t_code" => "required",
                        "description" => "sometimes|max:255",
                        "t_type" => [
                            "required",
                            Rule::in(["Debit", "Credit"])
                        ],
                        "output" => [
                            "required",
                            Rule::in(["None", "Receipt", "Debit None", "Credit None"])
                        ],
                        "system_invoice" => "sometimes|boolean",
                        "system_receipt" => "sometimes|boolean"
                    ]);
                }

                if ($validator->fails()) {
                    return response()->json([
                        "success" => false,
                        "message" => "Form has errors",
                        "errors" => $validator->errors()
                    ]);
                }
                return TransactionType::saveTransactionType($request, $request->input('id'));
            },
            "save-paymentinterval" => function ($request) {
                $validator = null;
                if ($request->isMethod("PUT")) {
                    $validator = Validator::make($request->all(), [
                        "id" => "sometimes|Integer",
                        "name" => "sometimes",
                        "duration" => "sometimes|Integer",
                        "unit" => "sometimes|Integer",
                        "fixed" => "required|boolean",
                        "fixed_entry_point" => "sometimes",
                        "notification_time_interval" => "sometimes|Integer",
                        "notification_time_interval_unit" => "sometimes|Integer"
                    ]);
                } else {
                    $validator = Validator::make($request->all(), [
                        "name" => "sometimes",
                        "duration" => "sometimes|Integer",
                        "unit" => "sometimes|Integer",
                        "fixed" => "required|boolean",
                        "fixed_entry_point" => "sometimes",
                        "notification_time_interval" => "sometimes|Integer",
                        "notification_time_interval_unit" => "sometimes|Integer"
                    ]);
                }

                if ($validator->fails()) {
                    return response()->json([
                        "success" => false,
                        "message" => "Form has errors",
                        "errors" => $validator->errors()
                    ]);
                }
                return PaymentInterval::savePaymentInterval($request, $request->input('id'));
            },
            "get-portalparameters" => function ($start, $limit, $doRetrieveAll, $filter = null) {
                return PortalParameter::getData($start,
                    $limit,
                    $doRetrieveAll,
                    $filter != null ? $this->parseFilter($filter) : null);
            },
            "get-country" => function ($start, $limit, $doRetrieveAll, $filter = null) {
                return Country::getData($start,
                    $limit,
                    $doRetrieveAll,
                    $filter != null ? $this->parseFilter($filter) : null);
            },
            "get-region" => function ($start, $limit, $doRetrieveAll, $filter = null) {
                return Region::getData($start,
                    $limit,
                    $doRetrieveAll,
                    $filter != null ? $this->parseFilter($filter) : null);
            },
            "get-district" => function ($start, $limit, $doRetrieveAll, $filter = null) {
                return District::getData($start,
                    $limit,
                    $doRetrieveAll,
                    $filter != null ? $this->parseFilter($filter) : null);
            },
            "get-city" => function ($start, $limit, $doRetrieveAll, $filter = null) {
                return City::getData($start,
                    $limit,
                    $doRetrieveAll,
                    $filter != null ? $this->parseFilter($filter) : null);
            },
            "get-costcenter" => function ($start, $limit, $doRetrieveAll, $filter = null) {
                return CostCenter::getData($start,
                    $limit,
                    $doRetrieveAll,
                    $filter != null ? $this->parseFilter($filter) : null);

            },
            "get-costcategory" => function ($start, $limit, $doRetrieveAll, $filter = null) {
                return CostCategory::getData($start,
                    $limit,
                    $doRetrieveAll,
                    $filter != null ? $this->parseFilter($filter) : null);
            },
            "get-costsubcategory" => function ($start, $limit, $doRetrieveAll, $filter = null) {
                 
                return CostSubCategory::getData($start,
                    $limit,
                    $doRetrieveAll,
                    $filter != null ? $this->parseFilter($filter) : null);

            },
            "get-currency" => function ($start, $limit, $doRetrieveAll, $filter = null) {
                return Currency::getData($start,
                    $limit,
                    $doRetrieveAll,
                    $filter != null ? $this->parseFilter($filter) : null);

            },
            "get-exchangerate" => function ($start, $limit, $doRetrieveAll, $filter = null) {
                return ExchangeRate::getData($start,
                    $limit,
                    $doRetrieveAll,
                    $filter != null ? $this->parseFilter($filter) : null);
            },
            "get-feetype" => function ($start, $limit, $doRetrieveAll, $filter = null) {
                return FeeType::getData($start,
                    $limit,
                    $doRetrieveAll,
                    $filter != null ? $this->parseFilter($filter) : null);

            },
            "get-transactiontype" => function ($start, $limit, $doRetrieveAll, $filter = null) {
                return TransactionType::getData($start,
                    $limit,
                    $doRetrieveAll,
                    $filter != null ? $this->parseFilter($filter) : null);

            },
            "get-paymentinterval" => function ($start, $limit, $doRetrieveAll, $filter = null) {
                return PaymentInterval::getData($start,
                    $limit,
                    $doRetrieveAll,
                    $filter != null ? $this->parseFilter($filter) : null);
            },
            "merge-country" => function ($request) {
                return Country::merge(
                    $request->input('mergeToId'),
                    "country_id",
                    "par_countries",
                    $request->input('ids'));
            },
            "merge-region" => function ($request) {
                return Region::merge(
                    $request->input('mergeToId'),
                    "region_id",
                    "par_regions",
                    $request->input('ids'));
            },
            "merge-district" => function ($request) {
                return District::merge(
                    $request->input('mergeToId'),
                    "district_id",
                    "par_districts",
                    $request->input('ids'));
            },
            "merge-city" => function ($request) {
                return City::merge(
                    $request->input('mergeToId'),
                    "city_id",
                    "par_cities",
                    $request->input('ids'));
            },
            "merge-costcenter" => function ($request) {
                return CostCenter::merge(
                    $request->input('mergeToId'),
                    "cost_center_id",
                    "par_cost_centers",
                    $request->input('ids'));
            },
            "merge-costcategory" => function ($request) {
                return CostCategory::merge(
                    $request->input('mergeToId'),
                    "cost_category_id",
                    "par_cost_categories",
                    $request->input('ids'));
            },
            "merge-costsubcategory" => function ($request) {
                return CostCategory::merge(
                    $request->input('mergeToId'),
                    "cost_sub_category_id",
                    "par_cost_sub_categories",
                    $request->input('ids'));
            },
            "merge-currency" => function ($request) {
                return Currency::merge(
                    $request->input('mergeToId'),
                    "currency_id",
                    "par_currencies",
                    $request->input('ids'));
            },
            "merge-exchangerate" => function ($request) {
                return Currency::merge(
                    $request->input('mergeToId'),
                    "exchange_rate_id",
                    "par_exchange_rates",
                    $request->input('ids'));
            },
            "merge-feetype" => function ($request) {
                return Currency::merge(
                    $request->input('mergeToId'),
                    "fee_type_id",
                    "par_fee_types",
                    $request->input('ids'));
            },
            "merge-transactiontype" => function ($request) {
                return Currency::merge(
                    $request->input('mergeToId'),
                    "transaction_type_id",
                    "par_transaction_types",
                    $request->input('ids'));
            },
            "merge-paymentinterval" => function ($request) {
                return Currency::merge(
                    $request->input('mergeToId'),
                    "payment_interval_id",
                    "par_payment_intervals",
                    $request->input('ids'));
            },
            "delete-country" => function ($id, $action) {
                return Country::deleteData('par_countries', $id, $action);
            },
            "delete-region" => function ($id, $action) {
                return Region::deleteData('par_regions', $id, $action);
            },
            "delete-district" => function ($id, $action) {
                return District::deleteData('par_districts', $id, $action);
            },
            "delete-city" => function ($id, $action) {
                return District::deleteData('par_cities', $id, $action);
            },
            "delete-costcenter" => function ($id, $action) {
                return CostCenter::deleteData('par_cost_centers', $id, $action);
            },
            "delete-costcategory" => function ($id, $action) {
                return CostCategory::deleteData('par_cost_categories', $id, $action);
            },
            "delete-costsubcategory" => function ($id, $action) {
                return CostCategory::deleteData('par_cost_sub_categories', $id, $action);
            },
            "delete-currency" => function ($id, $action) {
                return Currency::deleteData('par_currencies', $id, $action);
            },
            "delete-exchangerate" => function ($id, $action) {
                return Currency::deleteData('par_exchange_rates', $id, $action);
            },
            "delete-feetype" => function ($id, $action) {
                return Currency::deleteData('par_fee_types', $id, $action);
            },
            "delete-transactiontype" => function ($id, $action) {
                return Currency::deleteData('par_transaction_types', $id, $action);
            },
            "delete-paymentinterval" => function ($id, $action) {
                return Currency::deleteData('par_payment_intervals', $id, $action);
            }
        ];
    }

    //Added by KIP
    public function getCommonParamFromModel(Request $request)
    {
        $model_name = $request->input('model_name');
        $strict_mode = $request->input('strict_mode');
        try {
            $model = 'App\\Modules\\Parameters\\Entities\\' . $model_name;
            if (isset($strict_mode) && $strict_mode == 1) {
                $results = $model::where('is_enabled', 1)
                    ->get()
                    ->toArray();
            } else {
                $results = $model::all()
                    ->toArray();
            }
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

    public function getCommonParamFromTable(Request $request)
    {
        $table_name = $request->input('table_name');
        $strict_mode = $request->input('strict_mode');
        $filters = $request->input('filters');
        
        $filters=(array)json_decode($filters);
       
        try {
            $qry=DB::table($table_name);
            if(count((array)$filters)>0){
                $qry->where($filters);
            }
            $results=$qry->get();
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

}
