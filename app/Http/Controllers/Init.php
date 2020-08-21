<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Modules\UserManagement\Entities\Title;

class Init extends Controller
{

    public function index()
    {
        $host = $_SERVER['HTTP_HOST'];
        if ($host == '41.212.6.105:90') {
            die("<h4 style='text-align: center; color: red'>TFDA MIS VERSION 2 IS UNDER DEVELOPMENT AND IS NOT ACCESSIBLE AT THE MOMENT. KINDLY CONTACT SOFTCLANS!!</h4>
                 <p style='text-align: center; color: pink'></p>");
        }
        try {
            DB::connection()->getPdo();
            if (DB::connection()->getDatabaseName()) {
                // echo "Yes! Successfully connected to the DB: " . DB::connection()->getDatabaseName();
            }
        } catch (\Exception $e) {
            die("<h4 style='text-align: center; color: red'>Could not connect to the database.  Please check your configuration!!</h4>
                 <p style='text-align: center; color: pink'>" . $e->getMessage() . "</p>");
        } catch (\Throwable $throwable) {
            die("<h4 style='text-align: center; color: red'>Could not connect to the database.  Please check your configuration!!</h4>
                 <p style='text-align: center; color: pink'>" . $throwable->getMessage() . "</p>");
        }
        $base_url = url('/');
        if (\Auth::check() || \Auth::viaRemember()) {
            $loggedInUser = \Auth::user();
            $apiTokenResult = $loggedInUser->createToken('TFDA MIS');
            /*$apiToken = $apiTokenResult->token;
            $apiToken->save();*/
            $access_token = $apiTokenResult->accessToken;

            $is_logged_in = true;
            $title = Title::findOrFail(\Auth::user()->title_id)->name;
            $title = aes_decrypt($title);
            $user_id = \Auth::user()->id;
            $title_id = \Auth::user()->title_id;
            $gender_id = \Auth::user()->gender_id;
            $first_name = aes_decrypt(\Auth::user()->first_name);
            $last_name = aes_decrypt(\Auth::user()->last_name);
            $email = aes_decrypt(\Auth::user()->email);
            $phone = aes_decrypt(\Auth::user()->phone);
            $mobile = aes_decrypt(\Auth::user()->mobile);
            $profile_pic_url = 'resources/images/placeholder.png';
            $saved_name = DB::table('par_user_images')->where('user_id', \Auth::user()->id)->value('saved_name');
            if ($saved_name != '') {
                $profile_pic_url = $base_url . '/resources/images/user-profile/' . $saved_name;
            }
            $access_point = DB::table('par_access_points')->where('id', \Auth::user()->access_point_id)->value('name');
            $role = DB::table('par_user_roles')->where('id', \Auth::user()->user_role_id)->value('name');
        } else {
            $is_logged_in = false;
            $user_id = '';
            $title_id = '';
            $gender_id = '';
            $title = '';
            $first_name = '';
            $last_name = '';
            $email = '';
            $phone = '';
            $mobile = '';
            $profile_pic_url = 'resources/images/placeholder.png';
            $access_point = '';
            $role = '';
            $access_token = '';
        }
        $year = date('Y');
        $data['is_reset_pwd'] = false;
        $data['guid'] = '';
        $data['user_id'] = $user_id;
        $data['title_id'] = $title_id;
        $data['gender_id'] = $gender_id;
        $data['is_logged_in'] = $is_logged_in;
        $data['title'] = $title;
        $data['first_name'] = $first_name;
        $data['last_name'] = $last_name;
        $data['base_url'] = $base_url;
        $data['email'] = $email;
        $data['phone'] = $phone;
        $data['mobile'] = $mobile;
        $data['access_point'] = $access_point;
        $data['role'] = $role;
        $data['profile_pic_url'] = $profile_pic_url;
        $data['access_token'] = $access_token;

        $data['year'] = $year;
        $data['system_name'] = env('SYSTEM_NAME');
        $data['organisation_name'] = env('ORGANISATION_NAME');
        $data['org_name'] = env('ORG_NAME');
        $data['iso_cert'] = env('ISO_CERT');
        $data['ministy_name'] = env('MINISTRY_NAME');
        $data['system_version'] = env('SYSTEM_VERSION');
        $data['system_version'] = env('SYSTEM_VERSION');

        $data['nonMenusArray'] = getAssignedProcesses($user_id);

        return view('init', $data);
    }

}
