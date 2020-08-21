<?php

Route::group(['middleware' => 'web', 'prefix' => 'reports', 'namespace' => 'App\\Modules\Reports\Http\Controllers'], function()
{
    Route::get('/', 'ReportsController@index');
    Route::get('generateReport','ReportsController@generateReport');
    Route::get('generateApplicationInvoice','ReportsController@generateApplicationInvoice');
    Route::get('generateApplicationReceipt','ReportsController@generateApplicationReceipt');
    Route::get('generatePremiseCertificate','ReportsController@generatePremiseCertificate');
    Route::get('generatePremisePermit','ReportsController@generatePremisePermit');
});
