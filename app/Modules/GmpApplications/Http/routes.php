<?php

Route::group(['middleware' => 'web', 'prefix' => 'gmpapplications', 'namespace' => 'App\\Modules\GmpApplications\Http\Controllers'], function()
{
    Route::get('/', 'GmpApplicationsController@index');
});

Route::group(['middleware' => 'auth:api', 'prefix' => 'gmpapplications', 'namespace' => 'App\\Modules\GmpApplications\Http\Controllers'], function()
{
    Route::get('getGmpApplicationParamFromModel', 'GmpApplicationsController@getGmpApplicationParamFromModel');
    Route::post('saveGmpApplicationCommonData', 'GmpApplicationsController@saveGmpApplicationCommonData');
    Route::post('deleteGmpApplicationRecord', 'GmpApplicationsController@deleteGmpApplicationRecord');
    Route::get('getGmpApplications', 'GmpApplicationsController@getGmpApplications');
    Route::get('getManagerApplicationsGeneric', 'GmpApplicationsController@getManagerApplicationsGeneric');
    Route::get('getGmpApplicationsAtApproval', 'GmpApplicationsController@getGmpApplicationsAtApproval');
    Route::post('saveNewGmpReceivingBaseDetails', 'GmpApplicationsController@saveNewGmpReceivingBaseDetails');
    Route::post('saveRenewalGmpReceivingBaseDetails', 'GmpApplicationsController@saveRenewalGmpReceivingBaseDetails');
    //start prepare
    Route::get('prepareNewGmpReceivingStage', 'GmpApplicationsController@prepareNewGmpReceivingStage');
    Route::get('prepareNewGmpInvoicingStage', 'GmpApplicationsController@prepareNewGmpInvoicingStage');
    Route::get('prepareNewGmpPaymentStage', 'GmpApplicationsController@prepareNewGmpPaymentStage');
    Route::get('prepareNewGmpChecklistsStage', 'GmpApplicationsController@prepareNewGmpChecklistsStage');
    //end prepare
    Route::get('getSitePersonnelDetails', 'GmpApplicationsController@getSitePersonnelDetails');
    Route::get('getSiteOtherDetails', 'GmpApplicationsController@getSiteOtherDetails');
    Route::post('saveSiteOtherDetails', 'GmpApplicationsController@saveSiteOtherDetails');
    Route::get('getGmpCommonParams', 'GmpApplicationsController@getGmpCommonParams');
    Route::post('saveGmpInspectionLineDetails', 'GmpApplicationsController@saveGmpInspectionLineDetails');
    Route::get('getGmpInspectionLineDetails', 'GmpApplicationsController@getGmpInspectionLineDetails');
    Route::post('saveApplicationApprovalDetails', 'GmpApplicationsController@saveApplicationApprovalDetails');
    Route::get('getGmpApplicationMoreDetails', 'GmpApplicationsController@getGmpApplicationMoreDetails');
    Route::get('getManufacturingSitesList', 'GmpApplicationsController@getManufacturingSitesList');
    Route::get('getOnlineApplications', 'GmpApplicationsController@getOnlineApplications');
    Route::get('prepareNewGmpOnlineReceivingStage', 'GmpApplicationsController@prepareNewGmpOnlineReceivingStage');
    Route::get('getOnlineAppGmpPersonnelDetails', 'GmpApplicationsController@getOnlineAppGmpPersonnelDetails');
    Route::get('getOnlineAppGmpOtherDetails', 'GmpApplicationsController@getOnlineAppGmpOtherDetails');
    Route::get('getOnlineProductLineDetails', 'GmpApplicationsController@getOnlineProductLineDetails');
    Route::get('getGmpScheduleTeamDetails', 'GmpApplicationsController@getGmpScheduleTeamDetails');
    Route::post('saveGmpScheduleInspectionTypes', 'GmpApplicationsController@saveGmpScheduleInspectionTypes');
    Route::get('getGmpScheduleInspectionTypes', 'GmpApplicationsController@getGmpScheduleInspectionTypes');
    Route::post('saveGmpScheduleInspectors', 'GmpApplicationsController@saveGmpScheduleInspectors');
    Route::get('getGmpScheduleInspectors', 'GmpApplicationsController@getGmpScheduleInspectors');
    Route::get('getAssignedGmpInspections', 'GmpApplicationsController@getAssignedGmpInspections');
    Route::get('getGmpApplicationsForInspection', 'GmpApplicationsController@getGmpApplicationsForInspection');
    Route::post('addGmpApplicationsIntoInspectionSchedule', 'GmpApplicationsController@addGmpApplicationsIntoInspectionSchedule');
    Route::post('addGmpApplicationIntoInspectionSchedule', 'GmpApplicationsController@addGmpApplicationIntoInspectionSchedule');
});