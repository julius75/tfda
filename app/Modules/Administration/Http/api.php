<?php
/**
 * Created by PhpStorm.
 * User: Kip
 * Date: 11/24/2018
 * Time: 4:55 PM
 */
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

Route::group(['middleware' => 'auth:api', 'prefix' => 'administration', 'namespace' => 'App\\Modules\Administration\Http\Controllers'], function () {
    Route::get('getAdminParamFromModel', 'AdministrationController@getAdminParamFromModel');
});
