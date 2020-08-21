<?php
/**
 * Created by PhpStorm.
 * User: softclans
 * Date: 9/26/18
 * Time: 12:35 PM
 */

namespace Modules\Parameters\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BaseController extends Controller
{
    protected $invoker = [];

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

    protected function validateParameterRequest($request, $foreignKey = null) {
        $validator = null;

        if($request->isMethod("PUT")) {
            if(!$foreignKey) {
                $validator = Validator::make($request -> all(), [
                    "id" => "required|Integer",
                    "name" => "required|max:255",
                    "description" => "sometimes|max:255"
                ]);
            } else {
                $validator = Validator::make($request -> all(), [
                    "id" => "required|Integer",
                    "name" => "required|max:255",
                    "description" => "sometimes|max:255",
                    $foreignKey => "required|Integer"
                ]);
            }
        } else {
            if(!$foreignKey) {
                $validator = Validator::make($request->all(), [
                    "name" => "required|max:255",
                    "description" => "sometimes|max:255"
                ]);
            } else {
                $validator = Validator::make($request->all(), [
                    "name" => "required|max:255",
                    "description" => "sometimes|max:255",
                    $foreignKey => "required|Integer"
                ]);
            }
        }

        return $validator;
    }

    protected function tryAndHandleErrorException(\ErrorException $e, $action, $entity) {
        if(strtolower(trim($e->getMessage())) === trim("undefined index: $action-$entity")) {
            return response() -> json([
                "success" =>  false,
                "message" => "The entity specified does not exist"
            ]);
        }
        throw $e;
    }

    /**
     * Create a parameter
     * required parameters are :
     *      - id of the parameter(required on update)
     *      - name of the parameter
     *      - a description
     * The parameter are accessed
     * via the request object injected automatically
     * by laravel
     *
     * @param Request $request
     * @param Request
     * @return Response (in json)
     * @throws \ErrorException
     */
    public function saveParameter(Request $request, $entity)
    {
        $validator = null;
        $response = null;
        $handler = null;

        try {
            $handler = $this->invoker["save-" . $entity];
            $response = $handler($request);
        } catch (\ErrorException $e) {
            return $this->tryAndHandleErrorException($e, "save", $entity);
        }

        return $response;
    }

    /**
     * Retrieve a list of locations optionally with
     * start and limit arguments
     *
     * @param Request $request
     * @param $entity
     * @return \Illuminate\Http\JsonResponse
     * @throws \ErrorException
     */
    public function getParameters(Request $request, $entity)
    {
      
        $validator = Validator::make($request -> all(), [
            "start" => "sometimes|Integer",
            "limit" => "sometimes|Integer"
        ]);

        if($validator->fails()) {
            return response() -> json([
                "success" =>  false,
                "message" => "Request has errors",
                "errors" => $validator -> errors()
            ]);
        }

        $start = $request -> input('start');
        $limit = $request -> input('limit');
        $filter = $request -> input('filter');

        $doRetrieveAll = false;
        $all = $request->input('all');
        if($all && $all == 1) {
            $doRetrieveAll = true;
        }
        $entity = strtolower($entity);
        $response = null;
        try {
            $handler = $this->invoker['get-'.$entity];
            if($filter) {
                $response = $handler($start, $limit, $doRetrieveAll, $filter);
            } else {
                $response = $handler($start, $limit, $doRetrieveAll);
            }

        } catch (\ErrorException $e) {
            
            return $this->tryAndHandleErrorException($e, "get", $entity);
        }

        return response() -> json([
            "success" => true,
            "message" => count($response["records"]) . " $entity(s) have been retrieved",
            "total" => $response["total"],
            "data" => $response["records"],
            "results" => $response["records"]
        ]);
    }

    public function merge(Request $request, $entity) {
        $validator = Validator::make($request -> all(), [
            "mergeToId" => "required|Integer",
            "ids" => "required|Array",
            "ids.*" => 'integer'
        ]);

        if($validator->fails()) {
            return response() -> json([
                "success" =>  false,
                "message" => "Request has errors",
                "errors" => $validator -> errors()
            ]);
        }

        $entity = strtolower($entity);

        try {
            $handler = $this->invoker['merge-'.$entity];
            return response() -> json($handler($request));
        } catch (\ErrorException $e) {
            return $this->tryAndHandleErrorException($e, "merge", $entity);
        }
    }

    public function deleteParameter($entity, $id, $action)
    {
        $entity = strtolower($entity);

        try {
            $handler = $this->invoker['delete-'.$entity];
            return response() -> json($handler($id, $action));
        } catch (\ErrorException $e) {
            return $this->tryAndHandleErrorException($e, "delete", $entity);
        }
    }

    public function parseFilter($filter){
       return json_decode($filter,true);
    }

    public function parseFilter1($filterStr) {
        $filterStr = rtrim($filterStr, ';').';';
        $regex = '/^([a-zA-Z_]+:([a-zA-Z0-9]+(,[a-zA-Z0-9]+)*);)+$/';
        $match = preg_match_all($regex, $filterStr);
        if(!$match) {
            throw new \Exception("The filter is in the wrong format");
        }
        $filterStr = substr($filterStr, 0, -1);
        $filter = [];
        $filterParams = explode(";", $filterStr);
        foreach($filterParams as $filterParam) {
            $paramNameAndValues = explode(",", $filterParam);
            $paramNameAndFirstValue = explode(":", $paramNameAndValues[0]);
            $values = [
                $paramNameAndFirstValue[1]
            ];

            for($i = 1; $i < count($paramNameAndValues); $i++) {
                array_push($values, $paramNameAndValues[$i]);
            }
            $filter[$paramNameAndFirstValue[0]] = $values;
        }

        return $filter;
    }
}