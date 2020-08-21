<?php

namespace App\Modules\Administration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Modules\Administration\Entities\Menu;
use App\Modules\Administration\Entities\Permission;
use App\Modules\Administration\Entities\ProcessesPermission;
use League\OAuth2\Server\Exception\OAuthServerException;

class AdministrationController extends Controller
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

    public function index()
    {
        return view('administration::index');
    }

    public function getSystemNavigationMenuItems(Request $request)
    {
        $row = $this->getSystemMenuItem(0, 0);
        $menus = '[';
        if (count($row)) {
            $menu_count = count($row);
            $menu_counter = 0;

            foreach ($row as $item) {
                $menu_counter++;
                $id = $item['id'];
                $name = $item['name'];
                $tab_title = $item['tab_title'];
                $text = $name;
                $level = $item['level'];
                $parent_id = $item['parent_id'];
                $child_id = $item['parent_id'];
                $viewType = $item['viewType'];
                $iconCls = $item['iconCls'];
                $routeId = $item['routeId'];
                $order_no = $item['order_no'];
                $is_menu = $item['is_menu'];
                $is_disabled = $item['is_disabled'];
                $workflow_id = $item['workflow_id'];
                $access_level = $this->getMenuAccessLevel($id);

                $menus .= '{';
                $menus .= '"text": "' . $text . '",';
                $menus .= '"name": "' . $name . '",';
                $menus .= '"tab_title": "' . $tab_title . '",';
                $menus .= '"iconCls": "' . $iconCls . '",';
                $menus .= '"menu_id": "' . $id . '",';
                $menus .= '"id": "' . $id . '",';
                $menus .= '"access_level": "' . $access_level . '",';
                $menus .= '"viewType": "' . $viewType . '",';
                $menus .= '"routeId": "' . $routeId . '",';
                $menus .= '"level": "' . $level . '",';
                $menus .= '"order_no": "' . $order_no . '",';
                $menus .= '"is_menu": "' . $is_menu . '",';
                $menus .= '"workflow_id": "' . $workflow_id . '",';
                $menus .= '"is_disabled": "' . $is_disabled . '",';
                $children = $this->getSystemMenuItem(1, $id);
                if (count($children) > 0) {
                    $menus .= '"selectable": false,';
                    $children_count = count($children);
                    $children_counter = 0;
                    $menus .= '"children": [';
                    foreach ($children as $child) {
                        $children_counter++;
                        $child_id = $child['id'];
                        $child_name = $child['name'];
                        $child_title = $child['tab_title'];
                        $child_text = $child_name;
                        $child_level = $child['level'];
                        $child_viewType = $child['viewType'];
                        $child_iconCls = 'x-fa fa-angle-double-right';//$child['iconCls'];
                        $child_route = $child['routeId'];
                        $child_order_no = $child['order_no'];
                        $child_is_menu = $child['is_menu'];
                        $child_is_disabled = $child['is_disabled'];
                        $child_parent_id = $child['parent_id'];
                        $child_workflow_id = $child['workflow_id'];
                        $child_access_level = $this->getMenuAccessLevel($child_id);

                        if ($id == 254) {//online applications
                            $child_title = $child_name . ' Online Applications-' . $child_name;
                        }

                        $menus .= '{';
                        $menus .= '"text": "' . $child_text . '",';
                        $menus .= '"name": "' . $child_name . '",';
                        $menus .= '"tab_title": "' . $child_title . '",';
                        $menus .= '"iconCls": "' . $child_iconCls . '",';
                        $menus .= '"menu_id": "' . $child_id . '",';
                        $menus .= '"id": "' . $child_id . '",';
                        $menus .= '"access_level": "' . $child_access_level . '",';
                        $menus .= '"viewType": "' . $child_viewType . '",';
                        $menus .= '"routeId": "' . $child_route . '",';
                        $menus .= '"level": "' . $child_level . '",';
                        $menus .= '"order_no": "' . $child_order_no . '",';
                        $menus .= '"is_menu": "' . $child_is_menu . '",';
                        $menus .= '"is_disabled": "' . $child_is_disabled . '",';
                        $menus .= '"workflow_id": "' . $child_workflow_id . '",';
                        $menus .= '"parent_id": ' . $child_parent_id . ',';
                        //level 2 menu items
                        $grandchildren = $this->getSystemMenuItem(2, $child_id);
                        if (count($grandchildren) > 0) {
                            $menus .= '"selectable": false,';
                            $grandchildren_count = count($grandchildren);
                            $grandchildren_counter = 0;
                            $menus .= '"children": [';
                            foreach ($grandchildren as $grandchild) {
                                $grandchildren_counter++;
                                $grandchild_id = $grandchild['id'];
                                $grandchild_name = $grandchild['name'];
                                $grandchild_tab_title = $grandchild['tab_title'];
                                $grandchild_text = $grandchild_name;
                                $grandchild_level = $grandchild['level'];
                                $grandchild_viewType = $grandchild['viewType'];
                                $grandchild_iconCls = 'x-fa fa-arrow-circle-right';//$grandchild['iconCls'];
                                $grandchild_route = $grandchild['routeId'];
                                $grandchild_order_no = $grandchild['order_no'];
                                $grandchild_is_menu = $grandchild['is_menu'];
                                $grandchild_is_disabled = $grandchild['is_disabled'];
                                $grandchild_parent_id = $child['parent_id'];
                                $grandchild_child_id = $grandchild['parent_id'];
                                $grandchild_workflow_id = $grandchild['workflow_id'];
                                $grandchild_access_level = $this->getMenuAccessLevel($grandchild_id);

                                if ($id == 254) {//online applications
                                    $grandchild_tab_title = $child_name . ' Online Applications-' . $grandchild_name;
                                }
                                if ($id == 182) {//Registration module
                                    $grandchild_tab_title = $child_name . ' Registration-' . $grandchild_name;
                                }
                                if ($child_id == 277) {//GMP module
                                    $grandchild_tab_title = 'GMP-' . $grandchild_name;
                                }
                                if ($child_id == 327) {//PMS module
                                    $grandchild_tab_title = 'PMS-' . $grandchild_name;
                                }

                                $menus .= '{';
                                $menus .= '"text": "' . $grandchild_text . '",';
                                $menus .= '"name": "' . $grandchild_name . '",';
                                $menus .= '"tab_title": "' . $grandchild_tab_title . '",';
                                $menus .= '"iconCls": "' . $grandchild_iconCls . '",';
                                $menus .= '"menu_id": "' . $grandchild_id . '",';
                                $menus .= '"id": "' . $grandchild_id . '",';
                                $menus .= '"access_level": "' . $grandchild_access_level . '",';
                                $menus .= '"viewType": "' . $grandchild_viewType . '",';
                                $menus .= '"routeId": "' . $grandchild_route . '",';
                                $menus .= '"level": "' . $grandchild_level . '",';
                                $menus .= '"order_no": "' . $grandchild_order_no . '",';
                                $menus .= '"is_menu": "' . $grandchild_is_menu . '",';
                                $menus .= '"is_disabled": "' . $grandchild_is_disabled . '",';
                                $menus .= '"parent_id": ' . $grandchild_parent_id . ',';
                                $menus .= '"child_id": ' . $grandchild_child_id . ',';
                                $menus .= '"workflow_id": "' . $grandchild_workflow_id . '",';
                                $menus .= '"leaf": true';

                                if ($grandchildren_counter == $grandchildren_count) {
                                    //Last Child in this level. Level=2
                                    $menus .= '}';
                                } else {
                                    $menus .= '},';
                                }
                            }
                            $menus .= ']';
                        } else {
                            $menus .= '"leaf": true';
                        }
                        if ($children_counter == $children_count) {
                            //Last Child in this level. Level=1
                            $menus .= '}';
                        } else {
                            $menus .= '},';
                        }
                    }
                    $menus .= ']';
                } else {
                    $menus .= '"leaf": true';
                }
                if ($menu_counter == $menu_count) {
                    $menus .= '}';
                } else {
                    $menus .= '},';
                }
            }
        }
        $menus .= ']';
        echo $menus;
    }

    function getSystemMenuItem($level = 0, $parent_id = 0)
    {
        $where = array(
            'par_menus.level' => $level,
            'par_menus.is_menu' => 1
        );
        $user_id = \Auth::user()->id;
        $groups = getUserGroups($user_id);
        $belongsToSuperGroup = belongsToSuperGroup($groups);
        $qry = DB::table('par_menus')
            ->distinct()
            ->select('par_menus.*')
            ->where($where);

        $qry = $parent_id == 0 ? $qry->orderBy('par_menus.order_no') : $qry->where('par_menus.parent_id', $parent_id)->orderBy('par_menus.order_no');
        $qry = $belongsToSuperGroup == true ? $qry->whereRaw("1=1") : $qry->join('tra_permissions', 'par_menus.id', '=', 'tra_permissions.menu_id')
            ->where('tra_permissions.status', 1)
            ->where('tra_permissions.accesslevel_id', '<>', 1)
            ->where('par_menus.is_disabled', 0)
            ->whereIn('tra_permissions.group_id', $groups);
        $menus = $qry->get();
        $menus = convertStdClassObjToArray($menus);
        return $menus;
    }

    public function getMenuAccessLevel($menu_id)
    {
        //first get his/her groups
        $user_id = \Auth::user()->id;
        $groups = getUserGroups($user_id);
        //check if this user belongs to the super user group...if so then should have system full access
        $belongsToSuperGroup = belongsToSuperGroup($groups);
        if ($belongsToSuperGroup == true) {
            $access_level = 4;
        } else {
            $results = DB::table('tra_permissions')
                ->select(DB::raw('max(accesslevel_id) as highestAccessLevel'))
                ->where('menu_id', $menu_id)
                ->whereIn('tra_permissions.group_id', $groups)
                ->value('highestAccessLevel');
            if (is_null($results)) {
                $access_level = 1;
            } else {
                $access_level = $results;
            }
        }
        return $access_level;
    }

    public function getParentMenus()
    {
        try {
            $parents = Menu::where('level', 0)->get()->toArray();
            $res = array(
                'success' => true,
                'results' => $parents,
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

    public function getChildMenus(Request $request)
    {
        $parent_id = $request->input('parent_id');
        try {
            $where = array(
                'level' => 1,
                'parent_id' => $parent_id
            );
            $parents = Menu::where($where)->get()->toArray();
            $res = array(
                'success' => true,
                'results' => $parents,
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

    public function saveMenuItem(Request $request)
    {
        $res = array();
        try {
            $user_id = \Auth::user()->id;
            $post_data = $request->all();
            $table_name = $post_data['table_name'];
            $id = $post_data['id'];
            $level = $post_data['level'];
            $parent_id = $post_data['parent_id'];
            $child_id = $post_data['child_id'];

            if ($level > 1) {
                $parent_id = $child_id;
            }
            //unset unnecessary values
            unset($post_data['_token']);
            unset($post_data['table_name']);
            unset($post_data['model']);
            unset($post_data['id']);
            unset($post_data['skip']);
            unset($post_data['child_id']);
            unset($post_data['parent_id']);
            $table_data = $post_data;
            //add extra params
            $table_data['tab_title'] = $post_data['name'];
            $table_data['created_on'] = Carbon::now();
            $table_data['created_by'] = $user_id;
            $table_data['parent_id'] = $parent_id;
            $where = array(
                'id' => $id
            );
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

    public function deleteAdminRecord(Request $req)
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

    public function softDeleteAdminRecord(Request $req)
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
            $res = softDeleteRecord($table_name, $previous_data, $where, $user_id);
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

    public function undoAdminSoftDeletes(Request $req)
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
            $res = undoSoftDeletes($table_name, $previous_data, $where, $user_id);
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

    public function getAdminParamFromModel(Request $request)
    {
        $model_name = $request->input('model_name');
        $strict_mode = $request->input('strict_mode');
        try {
            $model = 'App\\Modules\\Administration\\Entities\\' . $model_name;
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

    public function getSystemUserGroups(Request $request)
    {
        $directorate_id = $request->input('directorate_id');
        $department_id = $request->input('department_id');
        $zone_id = $request->input('zone_id');
        try {
            $qry = DB::table('par_groups as t1')
                ->join('par_directorates as t2', 't1.directorate_id', '=', 't2.id')
                ->join('par_departments as t3', 't1.department_id', '=', 't3.id')
                ->join('par_zones as t4', 't1.zone_id', '=', 't4.id')
                ->select('t1.*', 't2.name as directorate', 't3.name as department', 't4.name as zone');
            if (isset($directorate_id) && $directorate_id != '') {
                $qry->where('t1.directorate_id', $directorate_id);
            }
            if (isset($department_id) && $department_id != '') {
                $qry->where('t1.department_id', $department_id);
            }
            if (isset($zone_id) && $zone_id != '') {
                $qry->where('t1.zone_id', $zone_id);
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

    public function saveAdminCommonData(Request $req)
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
            $table_data = $post_data;
            //add extra params
            $table_data['created_on'] = Carbon::now();
            $table_data['created_by'] = $user_id;
            $where = array(
                'id' => $id
            );
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

    public function getSystemRoles(Request $request)
    {
        $user_group = $request->input('user_group');
        $row = $this->getSystemRole(0, 0, $user_group);
        $roles = '{"roles": "."';
        $roles .= ',';
        $roles .= '"children": [';
        if (count($row)) {
            $menu_count = count($row);
            $menu_counter = 0;

            foreach ($row as $item) {
                $menu_counter++;
                $id = $item['menu_id'];
                $permission_id = $item['permission_id'];
                $name = aes_decrypt($item['menu_name']);
                $level = aes_decrypt($item['level_name']);
                $level_id = $item['level_id'];
                $iconCls = $item['iconCls'];

                $roles .= '{';
                $roles .= '"menu_id": ' . $id . ',';
                $roles .= '"permission_id": "' . $permission_id . '",';
                $roles .= '"menu_name": "' . $name . '",';
                $roles .= '"iconCls": "' . $iconCls . '",';
                $roles .= '"level_name": "' . $level . '",';
                $roles .= '"level_id": "' . $level_id . '",';

                $children = $this->getSystemRole(1, $id, $user_group);
                if (count($children) > 0) {
                    $children_count = count($children);
                    $children_counter = 0;
                    $roles .= '"expanded": false,';
                    //$roles.='"iconCls": "tree-parent",';
                    $roles .= '"children": [';
                    foreach ($children as $child) {
                        $children_counter++;
                        $child_id = $child['menu_id'];
                        $child_permission_id = $child['permission_id'];
                        $child_name = aes_decrypt($child['menu_name']);
                        $child_level = aes_decrypt($child['level_name']);
                        $child_level_id = $child['level_id'];
                        $child_iconCls = $item['iconCls'];

                        $roles .= '{';
                        $roles .= '"menu_id": ' . $child_id . ',';
                        $roles .= '"permission_id": "' . $child_permission_id . '",';
                        $roles .= '"menu_name": "' . $child_name . '",';
                        $roles .= '"level_name": "' . $child_level . '",';
                        $roles .= '"level_id": "' . $child_level_id . '",';
                        $roles .= '"iconCls": "' . $child_iconCls . '",';
                        //$menus.="leaf: true";
                        //level 2 menu items
                        $grandchildren = $this->getSystemRole(2, $child_id, $user_group);
                        if (count($grandchildren) > 0) {
                            $grandchildren_count = count($grandchildren);
                            $grandchildren_counter = 0;
                            $roles .= '"expanded": false,';
                            $roles .= '"iconCls": "tree-parent",';
                            $roles .= '"children": [';
                            foreach ($grandchildren as $grandchild) {
                                $grandchildren_counter++;
                                $grandchild_id = $grandchild['menu_id'];
                                $grand_permission_id = $grandchild['permission_id'];
                                $grandchild_name = aes_decrypt($grandchild['menu_name']);
                                $grandchild_level = aes_decrypt($grandchild['level_name']);
                                $grandchild_level_id = $grandchild['level_id'];
                                $grandchild_iconCls = $item['iconCls'];

                                $roles .= '{';
                                $roles .= '"menu_id": ' . $grandchild_id . ',';
                                $roles .= '"permission_id": "' . $grand_permission_id . '",';
                                $roles .= '"menu_name": "' . $grandchild_name . '",';
                                $roles .= '"level_name": "' . $grandchild_level . '",';
                                $roles .= '"level_id": "' . $grandchild_level_id . '",';
                                $roles .= '"iconCls": "' . $grandchild_iconCls . '",';
                                $roles .= '"leaf": true';

                                if ($grandchildren_counter == $grandchildren_count) {
                                    //Last Child in this level. Level=2
                                    $roles .= '}';
                                } else {
                                    $roles .= '},';
                                }
                            }
                            $roles .= '],';
                        } else {
                            $roles .= '"leaf": true';
                        }
                        if ($children_counter == $children_count) {
                            //Last Child in this level. Level=1
                            $roles .= '}';
                        } else {
                            $roles .= '},';
                        }
                    }
                    $roles .= '],';

                } else {
                    //$menus.="viewType: '".$viewType."',";
                    $roles .= '"leaf": true';
                }

                if ($menu_counter == $menu_count) {
                    $roles .= '}';
                } else {
                    $roles .= '},';
                }
            }
        }
        $roles .= ']}';
        return $roles;
    }

    function getSystemRole($level = 0, $parent_id = 0, $user_group)
    {
        $where = array(
            'par_menus.is_menu' => 1,
            'par_menus.is_disabled' => 0,
            'par_menus.level' => $level
        );
        $qry = DB::table('par_menus')
            ->select('par_menus.id as menu_id', 'par_menus.name as menu_name', 'par_accesslevels.name as level_name', 'par_accesslevels.id as level_id', 'tra_permissions.id as permission_id', 'par_menus.iconCls')
            ->leftJoin('tra_permissions', function ($join) use ($user_group) {
                $join->on('par_menus.id', '=', 'tra_permissions.menu_id')
                    ->on('tra_permissions.group_id', '=', DB::raw($user_group));
            })
            ->leftJoin('par_accesslevels', 'tra_permissions.accesslevel_id', '=', 'par_accesslevels.id')
            ->where($where);
        $qry = $parent_id == 0 ? $qry->orderBy('par_menus.order_no') : $qry->where('par_menus.parent_id', $parent_id)->orderBy('par_menus.order_no');
        $menus = $qry->get();
        $menus = json_decode(json_encode($menus), true);
        return $menus;
    }

    public function updateSystemNavigationAccessRoles(Request $req)
    {
        $group_id = $req->input('group_id');
        $menuPermission_id = $req->input('menuPermission_id');
        $menuLevel_id = $req->input('menuLevel_id');
        $menu_id = $req->input('menu_id');

        $res = array();
        $menuPermissions = array_filter(explode(',', $menuPermission_id));
        $menuLevels = array_filter(explode(',', $menuLevel_id));
        $menus = array_filter(explode(',', $menu_id));

        $count = count($menus);

        if ($count < 1) {
            $res = array(
                'success' => false,
                'message' => "Operation failed-->No record was changed for saving!!"
            );
        } else {
            DB::transaction(function () use ($count, $menuPermissions, $menuLevels, $menus, $group_id, &$res) {
                try {
                    //for menus
                    if ($count > 0) {
                        for ($i = 0; $i < $count; $i++) {
                            $params = array(
                                'group_id' => $group_id,
                                'menu_id' => $menus[$i],
                                'accesslevel_id' => $menuLevels[$i],
                                'status' => 1,
                                'created_by' => \Auth::user()->id,
                                'altered_by' => \Auth::user()->id
                            );
                            if (isset($menuPermissions[$i])) {
                                $menuPermission_id = $menuPermissions[$i];
                                $menuPermission = Permission::find($menuPermission_id);
                                if ($menuPermission) {
                                    $menuPermission->group_id = $group_id;
                                    $menuPermission->menu_id = $menus[$i];
                                    $menuPermission->accesslevel_id = $menuLevels[$i];
                                    $menuPermission->status = 1;
                                    $menuPermission->created_by = \Auth::user()->id;
                                    $menuPermission->save();
                                }
                            } else {
                                Permission::create($params);
                            }
                        }
                    }
                    $res = array(
                        'success' => true,
                        'message' => "Access Roles updated successfully!"
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
            }, 5);
        }
        return response()->json($res);
    }

    public function updateSystemPermissionAccessRoles(Request $req)
    {
        $group_id = $req->input('group_id');
        $processPermission_id = $req->input('processPermission_id');
        $processLevel_id = $req->input('processLevel_id');
        $process_id = $req->input('process_id');

        $res = array();
        $processPermissions = array_filter(explode(',', $processPermission_id));
        $processLevels = array_filter(explode(',', $processLevel_id));
        $processes = array_filter(explode(',', $process_id));

        $count2 = count($processes);

        if ($count2 < 1) {
            $res = array(
                'success' => false,
                'message' => "Operation failed-->No record was changed for saving!!"
            );
        } else {
            DB::transaction(function () use ($count2, $processPermissions, $processLevels, $processes, $group_id, &$res) {
                try {
                    //for menus processes
                    if ($count2 > 0) {
                        for ($j = 0; $j < $count2; $j++) {
                            $params = array(
                                'group_id' => $group_id,
                                'process_id' => $processes[$j],
                                'accesslevel_id' => $processLevels[$j],
                                'status' => 1,
                                'created_by' => \Auth::user()->id,
                                'altered_by' => \Auth::user()->id
                            );
                            if (isset($processPermissions[$j])) {
                                $processPermission_id = $processPermissions[$j];
                                $processPermission = ProcessesPermission::find($processPermission_id);
                                if ($processPermission) {
                                    $processPermission->group_id = $group_id;
                                    $processPermission->process_id = $processes[$j];
                                    $processPermission->accesslevel_id = $processLevels[$j];
                                    $processPermission->status = 1;
                                    $processPermission->created_by = \Auth::user()->id;
                                    $processPermission->save();
                                }
                            } else {
                                ProcessesPermission::create($params);
                            }
                        }
                    }
                    $res = array(
                        'success' => true,
                        'message' => "Access Roles updated successfully!"
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
            }, 5);
        }
        return response()->json($res);
    }

    function getMenuProcessesRoles(Request $request)
    {
        $user_group = $request->input('user_group');
        $qry = DB::table('par_menuitems_processes as t1')
            ->leftJoin('tra_processes_permissions as t2', function ($join) use ($user_group) {
                $join->on('t1.id', '=', 't2.process_id')
                    ->on('t2.group_id', '=', DB::raw($user_group));
            })
            ->leftJoin('par_processes_accesslevels as t3', 't2.accesslevel_id', '=', 't3.id')
            ->select('t1.id', 't1.name', 't1.description', 't1.id as process_id', 't1.identifier', 't3.name as level_name', 't3.id as level_id', 't2.id as permission_id')
            ->orderBy('t1.id');
        $menus = $qry->get();
        $menus = json_decode(json_encode($menus), true);
        return $menus;
    }

    public function removeSelectedUsersFromGroup(Request $request)
    {
        $selected = $request->input('selected');
        $group_id = $request->input('group_id');
        $selected_ids = json_decode($selected);
        $user_id = $this->user_id;
        try {
            $params = DB::table('tra_user_group as t1')
                ->select(DB::raw("t1.*,$user_id as deletion_by"))
                ->where('group_id', $group_id)
                ->whereIn('user_id', $selected_ids)
                ->get();
            $params = convertStdClassObjToArray($params);
            DB::table('tra_user_group_log')
                ->insert($params);
            DB::table('tra_user_group')
                ->where('group_id', $group_id)
                ->whereIn('user_id', $selected_ids)
                ->delete();
            $res = array(
                'success' => true,
                'message' => 'Users removed successfully!!'
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

    public function getFormFields(Request $request)
    {
        $form_id = $request->input('form_id');
        try {
            $qry = DB::table('par_key_form_fields as t1')
                ->join('par_form_field_types as t2', 't1.field_type_id', '=', 't2.id')
                ->where('t1.form_id', $form_id)
                ->select('t1.*', 't2.name as field_type');
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

}
