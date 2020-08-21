<?php

Route::group(['middleware' => 'web', 'prefix' => 'surveillance', 'namespace' => 'App\\Modules\Surveillance\Http\Controllers'], function()
{
    Route::get('/', 'SurveillanceController@index');
});

//API Routes
Route::group(['middleware' => 'auth:api', 'prefix' => 'surveillance', 'namespace' => 'App\\Modules\Surveillance\Http\Controllers'], function () {
    Route::post('saveSurveillanceCommonData', 'SurveillanceController@saveSurveillanceCommonData');
    Route::post('deleteSurveillanceRecord', 'SurveillanceController@deleteSurveillanceRecord');
    Route::post('savePmsProgramRegions', 'SurveillanceController@savePmsProgramRegions');
    Route::post('savePmsProgramProducts', 'SurveillanceController@savePmsProgramProducts');
    Route::get('getPmsProgramRegions', 'SurveillanceController@getPmsProgramRegions');
    Route::get('getPmsProgramProducts', 'SurveillanceController@getPmsProgramProducts');
    Route::get('getPmsPrograms', 'SurveillanceController@getPmsPrograms');
    Route::get('getPmsProgramPlans', 'SurveillanceController@getPmsProgramPlans');
    Route::get('getSurveillanceApplications', 'SurveillanceController@getSurveillanceApplications');
    Route::post('saveNewReceivingBaseDetails', 'SurveillanceController@saveNewReceivingBaseDetails');
    //start prepare
    Route::get('prepareStructuredPmsReceivingStage', 'SurveillanceController@prepareStructuredPmsReceivingStage');
    //end prepare
    Route::post('saveSurveillanceSampleDetails', 'SurveillanceController@saveSurveillanceSampleDetails');
    Route::get('getPmsApplicationSamplesReceiving', 'SurveillanceController@getPmsApplicationSamplesReceiving');
    Route::get('getPmsApplicationSamplesLabStages', 'SurveillanceController@getPmsApplicationSamplesLabStages');
    Route::get('getManagerApplicationsGeneric', 'SurveillanceController@getManagerApplicationsGeneric');
    Route::get('getPmsApplicationMoreDetails', 'SurveillanceController@getPmsApplicationMoreDetails');
    Route::post('savePmsPIRRecommendation', 'SurveillanceController@savePmsPIRRecommendation');
    Route::get('getPmsSampleIngredients', 'SurveillanceController@getPmsSampleIngredients');
    Route::get('getSampleLabAnalysisResults', 'SurveillanceController@getSampleLabAnalysisResults');
});

