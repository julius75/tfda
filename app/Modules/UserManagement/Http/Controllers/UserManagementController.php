<?php

namespace App\Modules\UserManagement\Http\Controllers;

use App\Mail\AccountActivation;
use Illuminate\Http\Request;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Carbon\Carbon;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserManagementController extends Controller
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
        return view('usermanagement::index');
    }

    public function getUserParamFromModel(Request $request)
    {
        $model_name = $request->input('model_name');
        $strict_mode = $request->input('strict_mode');
        try {
            $model = 'App\\Modules\\UserManagement\\Entities\\' . $model_name;
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

    public function saveUserCommonData(Request $req)
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
            $table_data['created_at'] = Carbon::now();
            $table_data['created_by'] = $user_id;
            $where = array(
                'id' => $id
            );
            if (isset($id) && $id != "") {
                if (recordExists($table_name, $where)) {
                    unset($table_data['created_at']);
                    unset($table_data['created_by']);
                    $table_data['updated_at'] = Carbon::now();
                    $table_data['updated_by'] = $user_id;
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

    public function deleteUserRecord(Request $req)
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

    public function softDeleteUserRecord(Request $req)
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

    public function undoUserSoftDeletes(Request $req)
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

    public function getActiveSystemUsers(Request $request)
    {
        $group_id = $request->input('group_id');
        try {
            $qry = DB::table('users as t1')
                ->leftJoin('par_titles as t2', 't1.title_id', '=', 't2.id')
                ->leftJoin('par_user_images as t3', 't1.id', '=', 't3.user_id')
                ->leftJoin('par_departments as t4', 't1.department_id', '=', 't4.id')
                ->leftJoin('par_zones as t5', 't1.zone_id', '=', 't5.id')
                ->leftJoin('tra_blocked_accounts as t6', 't1.id', '=', 't6.account_id')
                ->select(DB::raw("t1.*,CONCAT_WS(' ',t2.name,decrypt(t1.first_name),decrypt(t1.last_name)) as fullnames,decrypt(t1.email) as email,
                                   t1.last_login_time,t3.saved_name,t4.name as department_name,t5.name as zone_name"))
                ->whereNull('t6.id');
            if (isset($group_id) && $group_id != '') {
                $users = DB::table('tra_user_group')
                    ->select('user_id')
                    ->where('group_id', $group_id)
                    ->get();
                $users = convertStdClassObjToArray($users);
                $users = convertAssArrayToSimpleArray($users, 'user_id');
                $qry->whereIn('t1.id', $users);
            }
            $results = $qry->get();
            $results = convertStdClassObjToArray($results);
            $results = decryptArray($results);
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

    public
    function getBlockedSystemUsers()
    {
        try {
            $qry = DB::table('users as t1')
                ->join('tra_blocked_accounts as t2', 't1.id', '=', 't2.account_id')
                ->leftJoin('users as t3', 't3.id', '=', 't2.action_by')
                ->leftJoin('par_user_images as t4', 't1.id', '=', 't4.user_id')
                ->leftJoin('par_titles as t6', 't1.title_id', '=', 't6.id')
                ->leftJoin('par_departments as t7', 't1.department_id', '=', 't7.id')
                ->leftJoin('par_zones as t8', 't1.zone_id', '=', 't8.id')
                ->select(DB::raw("t1.*,CONCAT_WS(' ',t6.name,decrypt(t1.first_name),decrypt(t1.last_name)) as fullnames,decrypt(t1.email) as email,
                                   t1.last_login_time,t4.saved_name,t7.name as department_name,t8.name as zone_name,t2.date as blocked_on,
                                   t2.reason,t3.first_name as first_name2,t3.last_name as last_name2,t2.account_id"));
            $data = $qry->get();
            $data = convertStdClassObjToArray($data);
            $data = decryptArray($data);
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
        }
        return response()->json($res);
    }

    public
    function getUnblockedSystemUsers()
    {
        try {
            $qry = DB::table('users as t1')
                ->join('tra_unblocked_accounts as t2', 't1.id', '=', 't2.account_id')
                ->leftJoin('users as t3', 't3.id', '=', 't2.action_by')
                ->leftJoin('par_user_images as t4', 't1.id', '=', 't4.user_id')
                ->leftJoin('par_titles as t6', 't1.title_id', '=', 't6.id')
                ->leftJoin('par_departments as t7', 't1.department_id', '=', 't7.id')
                ->leftJoin('par_zones as t8', 't1.zone_id', '=', 't8.id')
                ->select(DB::raw("t1.*,CONCAT_WS(' ',t6.name,decrypt(t1.first_name),decrypt(t1.last_name)) as fullnames,decrypt(t1.email) as email,
                                   t1.last_login_time,t4.saved_name,t7.name as department_name,t8.name as zone_name,t2.date as blocked_on,
                                   t2.unblock_date,t2.reason,t3.first_name as first_name3,t3.last_name as last_name3,t2.account_id,t2.unblock_reason"));
            $data = $qry->get();
            $data = convertStdClassObjToArray($data);
            $data = decryptArray($data);
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
        return response()->json($res);
    }

    public function saveUserInformation(Request $req)
    {
        $res = array();
        DB::transaction(function () use ($req, &$res) {
            $user_id = \Auth::user()->id;

            $id = $req->input('id');
            $email = $req->input('email');
            $profile_url = $req->input('saved_name');
            $first_name = $req->input('first_name');
            $othernames = $req->input('last_name');

            $groups = $req->input('groups');
            $groups = json_decode($groups);
            $assigned_groups = array();

            $table_data = array(
                'first_name' => $first_name,
                'last_name' => $othernames,
                'gender_id' => $req->input('gender_id'),
                'mobile' => $req->input('mobile'),
                'phone' => $req->input('phone'),
                'title_id' => $req->input('title_id')
            );

            $skip = $req->input('skip');
            $skipArray = explode(",", $skip);

            $table_data = encryptArray($table_data, $skipArray);

            $where = array(
                'id' => $id
            );
            $table_name = 'users';
            try {
                if (isset($id) && $id != "") {
                    if (recordExists($table_name, $where)) {
                        $table_data['updated_at'] = Carbon::now();
                        $table_data['updated_by'] = $user_id;
                        $previous_data = getPreviousRecords($table_name, $where);
                        $success = updateRecord($table_name, $previous_data, $where, $table_data, $user_id);
                        //check profile pic
                        if (recordExists('par_user_images', array('user_id' => $id))) {
                            if ($profile_url != '') {
                                DB::table('par_user_images')
                                    ->where(array('user_id' => $id))
                                    ->update(array('saved_name' => $profile_url));
                            }
                        }
                        if ($success === true) {
                            if (count($groups) > 0) {
                                foreach ($groups as $group_id) {
                                    $assigned_groups[] = array(
                                        'user_id' => $id,
                                        'group_id' => $group_id
                                    );
                                }
                                DB::table('tra_user_group')->where('user_id', $id)->delete();
                                DB::table('tra_user_group')->insert($assigned_groups);
                            }
                            $res = array(
                                'success' => true,
                                'message' => 'Data updated Successfully!!'
                            );
                        } else {
                            $res = $success;
                        }
                    }
                } else {
                    //check if this email has been used before
                    $encryptedEmail = aes_encrypt($email);
                    $email_exists = DB::table('users')
                        ->where('email', $encryptedEmail)
                        ->first();
                    if (!is_null($email_exists)) {
                        $res = array(
                            'success' => false,
                            'message' => 'This Email Address (' . $email . ') is already registered. Please use a different Email Address!!'
                        );
                        return response()->json($res);
                    }
					
                    $password = str_random(8);
                    $uuid = generateUniqID();//unique user ID
                    $pwd = hashPwd($encryptedEmail, $uuid, $password);
                    //add extra params
                    $table_data['email'] = $encryptedEmail;
                    $table_data['password'] = $pwd;
                    $table_data['uuid'] = $uuid;

                    //first lets send this user an email with random password to avoid having a user in the db who hasn't receive pwd
                    if (is_connected()) {
                        //send the mail here
                        $link = url('/');
                        Mail::to($email)->send(new AccountActivation($email, $password, $link));
                        if (count(Mail::failures()) > 0) {
                            $res = array(
                                'success' => false,
                                'message' => 'Problem was encountered while sending email with account instructions. Please try again later!!'
                            );
                        } else {
                            $table_data['created_at'] = Carbon::now();
                            $table_data['created_by'] = $user_id;
                            $results = insertRecord($table_name, $table_data, $user_id);
                            if ($results['success'] == true) {
                                $insertId = $results['record_id'];
                                if (count($groups) > 0) {
                                    foreach ($groups as $group_id) {
                                        $assigned_groups[] = array(
                                            'user_id' => $insertId,
                                            'group_id' => $group_id
                                        );
                                    }
                                    DB::table('tra_user_group')->insert($assigned_groups);
                                }
                                if ($profile_url != '') {
                                    DB::table('par_user_images')
                                        ->where(array('saved_name' => $profile_url))
                                        ->update(array('user_id' => $insertId));
                                }
                                $res = array(
                                    'success' => true,
                                    'message' => 'User added successfully. Further account login credentials have been send to ' . $email . '. The user should check his/her email for login details!'
                                );
                            } else {
                                $res = array(
                                    'success' => false,
                                    'message' => $results['message']
                                );
                            }
                        }
                    } else {
                        $res = array(
                            'success' => false,
                            'message' => 'Whoops!! There is no internet connection. Check your connection then try again!!'
                        );
                    }
                }
            } catch (\Exception $e) {
                $res = array(
                    'success' => false,
                    'message' => $e->getMessage()
                );
            }
        }, 5);
        return response()->json($res);
    }

    public function getOpenUserGroups(Request $request)
    {
        $user_id = $request->input('user_id');
        $department_id = $request->input('department_id');
        $zone_id = $request->input('zone_id');
        $where = array(
            'department_id' => $department_id,
            'zone_id' => $zone_id
        );
        try {
            $qry = DB::table('par_groups as t1')
                ->where($where);
            if (isset($user_id) && $user_id != '') {
                $qry->whereNotIn('t1.id', function ($query) use ($user_id) {
                    $query->select(DB::raw('t2.group_id'))
                        ->from('tra_user_group as t2')
                        ->whereRaw('t2.user_id=' . $user_id);
                });
            }
            $data = $qry->get();
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
        return response()->json($res);
    }

    public function getAssignedUserGroups(Request $request)
    {
        $user_id = $request->input('user_id');
        try {
            $qry = DB::table('tra_user_group as t1')
                ->join('par_groups as t2', 't1.group_id', '=', 't2.id')
                ->select('t2.*')
                ->where('t1.user_id', $user_id);
            $data = $qry->get();
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
        return response()->json($res);
    }

    public function getOpenUserRoles(Request $request)
    {
        $user_id = $request->input('user_id');
        //$access_point_id = $request->input('accessPointId');
        try {
            $qry = DB::table('par_user_roles as t1');
            //->where('group_owner_level', $access_point_id);
            if (isset($user_id) && $user_id != '') {
                $qry->whereNotIn('t1.id', function ($query) use ($user_id) {
                    $query->select(DB::raw('t2.role_id'))
                        ->from('tra_user_roles_assignment as t2')
                        ->whereRaw('t2.user_id=' . $user_id);
                });
            }
            $data = $qry->get();
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
        return response()->json($res);
    }

    public function getAssignedUserRoles(Request $request)
    {
        $user_id = $request->input('user_id');
        try {
            $qry = DB::table('tra_user_roles_assignment as t1')
                ->join('par_user_roles as t2', 't1.role_id', '=', 't2.id')
                ->select('t2.*')
                ->where('t1.user_id', $user_id);
            $data = $qry->get();
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
        return response()->json($res);
    }

    public function saveUserImage(Request $req)
    {
        $user_id = $req->input('id');
        $res = array();
        try {
            if ($req->hasFile('profile_photo')) {
                $ben_image = $req->file('profile_photo');
                $origImageName = $ben_image->getClientOriginalName();
                $extension = $ben_image->getClientOriginalExtension();
                $destination = getcwd() . '\resources\images\user-profile';
                $savedName = str_random(5) . time() . '.' . $extension;
                $ben_image->move($destination, $savedName);
                $where = array(
                    'user_id' => $user_id
                );
                if ($user_id != '') {
                    $recordExists = recordExists('par_user_images', $where);
                    if ($recordExists) {
                        $update_params = array(
                            'initial_name' => $origImageName,
                            'saved_name' => $savedName,
                            'updated_by' => \Auth::user()->id
                        );
                        DB::table('par_user_images')
                            ->where($where)
                            ->update($update_params);
                    } else {
                        $insert_params = array(
                            'user_id' => $user_id,
                            'initial_name' => $origImageName,
                            'saved_name' => $savedName,
                            'created_by' => \Auth::user()->id
                        );
                        DB::table('par_user_images')
                            ->insert($insert_params);
                    }
                } else {
                    $insert_params = array(
                        'user_id' => $user_id,
                        'initial_name' => $origImageName,
                        'saved_name' => $savedName,
                        'created_by' => \Auth::user()->id
                    );
                    DB::table('par_user_images')
                        ->insert($insert_params);
                }
                $res = array(
                    'success' => true,
                    'image_name' => $savedName,
                    'message' => 'Image uploaded successfully!!'
                );
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

    public
    function resetUserPassword(Request $req)
    {
        //i need his/her encrypted username and UUID
        $user_id = $req->input('id');
        $res = array();
        try {
            DB::transaction(function () use ($user_id, &$res) {
                $user = new User();
                $userData = $user->find($user_id);
                if ($userData) {
                    $encryptedEmail = $userData->email;
                    $dms_id = $userData->dms_id;
                    $decryptedEmail = aes_decrypt($encryptedEmail);
                    $uuid = $userData->uuid;
                    $prevPwd = $userData->password;
                    $newPassword = hashPwd($encryptedEmail, $uuid, $decryptedEmail);
                    $new_dms_pwd = md5($decryptedEmail);
                    $data = array(
                        'password' => $newPassword
                    );
                    $logData = array(
                        'account_id' => $user_id,
                        'prev_password' => $prevPwd,
                        'new_password' => $newPassword,
                        'action_by' => \Auth::user()->id,
                        'time' => Carbon::now()
                    );
                    $pwd_updated = User::find($user_id)->update($data);
                    if ($pwd_updated) {
                        DB::table('tra_password_reset_logs')->insert($logData);
                        DB::table('tra_failed_login_attempts')->where('account_id', $user_id)->delete();
                        //update dms password too
                        /*$dms_db = DB::connection('dms_db');
                        $dms_db->table('tblusers')
                            ->where('id', $dms_id)
                            ->update(array('pwd' => $new_dms_pwd));*/
                        $res = array(
                            'success' => true,
                            'message' => 'Password was reset successfully!!'
                        );
                    } else {
                        $res = array(
                            'success' => false,
                            'message' => 'Problem encountered while resetting the password. Please try again!!'
                        );
                    }
                } else {
                    $res = array(
                        'success' => false,
                        'message' => 'Problem encountered while resetting the password-->User not found!!'
                    );
                }
            }, 5);
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

    function updateUserPassword(Request $req)
    {
        $res = array();
        try {
            DB::transaction(function () use ($req, &$res) {
                $user_id = $req->input('id');
                $newPassword = $req->input('new_pwd');
                $userData = User::find($user_id);
                if ($userData) {
                    $encryptedEmail = $userData->email;
                    $dms_id = $userData->dms_id;
                    $uuid = $userData->uuid;
                    $prevPwd = $userData->password;
                    $newPassword = hashPwd($encryptedEmail, $uuid, $newPassword);
                    $new_dms_pwd = md5($newPassword);
                    $data = array(
                        'password' => $newPassword
                    );
                    $logData = array(
                        'account_id' => $user_id,
                        'prev_password' => $prevPwd,
                        'new_password' => $newPassword,
                        'action_by' => \Auth::user()->id,
                        'time' => Carbon::now()
                    );
                    $pwd_updated = User::find($user_id)->update($data);
                    if ($pwd_updated) {
                        DB::table('tra_password_reset_logs')->insert($logData);
                        DB::table('tra_failed_login_attempts')->where('account_id', $user_id)->delete();
                        //update dms password too
                        /*  $dms_db = DB::connection('dms_db');
                          $dms_db->table('tblusers')
                              ->where('id', $dms_id)
                              ->update(array('pwd' => $new_dms_pwd));*/
                        $res = array(
                            'success' => true,
                            'message' => 'Password was reset successfully!!'
                        );
                    } else {
                        $res = array(
                            'success' => false,
                            'message' => 'Problem encountered while resetting the password. Please try again!!'
                        );
                    }
                } else {
                    $res = array(
                        'success' => false,
                        'message' => 'Problem encountered while resetting the password-->User not found!!'
                    );
                }
            }, 5);
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

    public function blockSystemUser(Request $req)
    {
        $user_id = $req->input('id');
        $email = $req->input('email');
        $reason = $req->input('reason');
        try {
            $params = array(
                'account_id' => $user_id,
                'email' => $email,
                'reason' => $reason,
                'action_by' => \Auth::user()->id,
                'date' => Carbon::now()
            );
            DB::table('tra_blocked_accounts')
                ->insert($params);
            $res = array(
                'success' => true,
                'message' => 'User blocked/deactivated successfully!!'
            );
        } catch (\Exception $e) {
            $res = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
        return response()->json($res);
    }

    public function unblockSystemUser(Request $req)
    {
        $user_id = $req->input('user_id');
        $reason = $req->input('reason');
        $res = array();
        try {
            DB::transaction(function () use ($user_id, $reason, &$res) {
                $blocking_details = DB::table('tra_blocked_accounts')
                    ->where('account_id', $user_id)
                    ->first();
                if (!is_null($blocking_details)) {
                    $unblock_details = array(
                        'account_id' => $blocking_details->account_id,
                        'email' => $blocking_details->email,
                        'date' => $blocking_details->date,
                        'action_by' => $blocking_details->action_by,
                        'reason' => $blocking_details->reason,
                        'unblock_reason' => $reason,
                        'unblock_by' => \Auth::user()->id,
                        'unblock_date' => Carbon::now()
                    );
                    DB::table('tra_unblocked_accounts')
                        ->insert($unblock_details);
                    DB::table('tra_blocked_accounts')
                        ->where('account_id', $user_id)
                        ->delete();
                    DB::table('tra_failed_login_attempts')
                        ->where('account_id', $user_id)
                        ->delete();
                    $res = array(
                        'success' => true,
                        'message' => 'User activated successfully!!'
                    );
                } else {
                    $res = array(
                        'success' => false,
                        'message' => 'Anomaly encountered. Blocked user details not found!!'
                    );
                }
            }, 5);
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
