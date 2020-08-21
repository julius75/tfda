<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use Illuminate\Support\Facades\DB;
Route::get('/', 'Init@index');
Route::group(['middleware' => ['web']], function () {
    //Authentication
    Route::post('login', 'Auth@handleLogin');
    Route::post('logout', 'Auth@logout');
    Route::post('forgotPassword', 'Auth@forgotPasswordHandler');
    Route::get('resetPassword', 'Auth@passwordResetLoader');
    Route::post('saveNewPassword', 'Auth@passwordResetHandler');
    Route::post('updatePassword', 'Auth@updateUserPassword');
    Route::get('authenticateUserSession', 'Auth@authenticateUserSession');
    Route::post('reValidateUser', 'Auth@reValidateUser');
    Route::get('createAdminPwd/{username}/{uuid}/{pwd}', 'Auth@createAdminPwd');
    //Common controller
    Route::get('getCommonParamFromModel', 'CommonController@getCommonParamFromModel');
    Route::post('saveApplicationApprovalDetails', 'CommonController@saveApplicationApprovalDetails');
    Route::post('saveApplicationPaymentDetails', 'CommonController@saveApplicationPaymentDetails');
    Route::post('submitQueriedOnlineApplication', 'CommonController@submitQueriedOnlineApplication');
    Route::post('submitRejectedOnlineApplication', 'CommonController@submitRejectedOnlineApplication');
    Route::get('getApplicationApprovalDetails', 'CommonController@getApplicationApprovalDetails');
    Route::post('saveApplicationInvoicingDetails', 'CommonController@saveApplicationInvoicingDetails');
    Route::post('removeInvoiceCostElement', 'CommonController@removeInvoiceCostElement');
    Route::get('getApplicationApplicantDetails', 'CommonController@getApplicationApplicantDetails');
    Route::get('getApplicationComments', 'CommonController@getApplicationComments');
    Route::get('checkInvoicePaymentsLimit', 'CommonController@checkInvoicePaymentsLimit');

    Route::get('serial', function () {
        generatePremiseRefNumber();
    });

    Route::get('random', function () {
        $results=DB::table('tra_clinical_trial_applications')
            //->where('id','>',212)
            ->get();
        foreach ($results as $result){
            $view_id='tfda' . str_random(10) . date('s');
            $id=$result->id;
            DB::table('tra_clinical_trial_applications')
                ->where('id',$id)
                ->update(array('view_id'=>$view_id));
        }
    });

    Route::get('testmail', function () {
        // onlineApplicationNotificationMail(3,'',array());
        //return new App\Mail\ApplicationReceivedOnMis('TFSGGS KIP',$msg);
        // Mail::to('ronokip55@gmail.com')->send(new App\Mail\ApplicationReceivedOnMis('',$msg));
    });

});
Route::post('authenticateMisMobileUser', 'Auth@authenticateMisMobileUser');
Route::get('logoutMisMobileUser', 'Auth@logoutMisMobileUser');