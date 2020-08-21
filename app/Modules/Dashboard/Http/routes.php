<?php

Route::group(['middleware' => 'web', 'prefix' => 'dashboard', 'namespace' => 'App\\Modules\Dashboard\Http\Controllers'], function () {
    Route::get('/', 'DashboardController@index');
    Route::get('getInTrayItems', 'DashboardController@getInTrayItems');
    Route::get('getOutTrayItems', 'DashboardController@getOutTrayItems');
    Route::get('getSystemGuidelines', 'DashboardController@getSystemGuidelines');
    Route::post('saveDashCommonData', 'DashboardController@saveDashCommonData');
});
