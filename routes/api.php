<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'Auth@handleLogin');
});*/
Route::group(['middleware' => 'auth:api'], function () {
    Route::post('saveApplicationChecklistDetails', 'CommonController@saveApplicationChecklistDetails');
    Route::post('saveCommonData', 'CommonController@saveCommonData');
    Route::post('deleteCommonRecord', 'CommonController@deleteCommonRecord');
    Route::get('getApplicationInvoiceDetails', 'CommonController@getApplicationInvoiceDetails');
    Route::get('getElementCosts', 'CommonController@getElementCosts');
    Route::get('getApplicationPaymentDetails', 'CommonController@getApplicationPaymentDetails');
});
