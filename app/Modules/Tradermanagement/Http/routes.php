<?php

Route::group(['middleware' => 'web', 'prefix' => 'tradermanagement', 'namespace' => 'App\\Modules\Tradermanagement\Http\Controllers'], function()
{
    Route::get('/', 'TradermanagementController@index');
    Route::post('saveTraderInformation', 'TradermanagementController@saveTraderInformation');
    Route::post('updateAccountApprovalStatus', 'TradermanagementController@updateAccountApprovalStatus');

    
    Route::post('getDownloadTinCertificateUrl', 'TradermanagementController@getDownloadTinCertificateUrl');
   

    Route::get('gettraderAccountsManagementDetails', 'TradermanagementController@gettraderAccountsManagementDetails');
    Route::get('getTraderStatusesCounter', 'TradermanagementController@getTraderStatusesCounter');
    Route::get('printtraderAccountsManagementDetails', 'TradermanagementReports@printtraderAccountsManagementDetails');
    Route::get('gettraderUsersAccountsManagementDetails', 'TradermanagementController@gettraderUsersAccountsManagementDetails');

});
