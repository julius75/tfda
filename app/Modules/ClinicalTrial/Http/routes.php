<?php

Route::group(['middleware' => 'web', 'prefix' => 'clinicaltrial', 'namespace' => 'App\\Modules\ClinicalTrial\Http\Controllers'], function () {
    Route::get('/', 'ClinicalTrialController@index');
});

Route::group(['middleware' => 'auth:api', 'prefix' => 'clinicaltrial', 'namespace' => 'App\\Modules\ClinicalTrial\Http\Controllers'], function () {
    Route::get('getClinicalTrialApplications', 'ClinicalTrialController@getClinicalTrialApplications');
    Route::post('saveClinicalTrialCommonData', 'ClinicalTrialController@saveClinicalTrialCommonData');
    Route::get('getClinicalTrialParamFromModel', 'ClinicalTrialController@getClinicalTrialParamFromModel');
    Route::post('deleteClinicalTrialRecord', 'ClinicalTrialController@deleteClinicalTrialRecord');
    Route::post('softDeleteClinicalTrialRecord', 'ClinicalTrialController@softDeleteClinicalTrialRecord');
    Route::get('getClinicalTrialApplications', 'ClinicalTrialController@getClinicalTrialApplications');
    Route::get('getStudySitesList', 'ClinicalTrialController@getStudySitesList');
    Route::post('saveNewReceivingBaseDetails', 'ClinicalTrialController@saveNewReceivingBaseDetails');
    Route::post('saveNewApplicationClinicalTrialDetails', 'ClinicalTrialController@saveNewApplicationClinicalTrialDetails');
    Route::post('saveNewApplicationClinicalTrialOtherDetails', 'ClinicalTrialController@saveNewApplicationClinicalTrialOtherDetails');
    Route::get('prepareNewClinicalTrialReceivingStage', 'ClinicalTrialController@prepareNewClinicalTrialReceivingStage');
    Route::get('prepareNewClinicalTrialInvoicingStage', 'ClinicalTrialController@prepareNewClinicalTrialInvoicingStage');
    Route::get('prepareNewClinicalTrialPaymentStage', 'ClinicalTrialController@prepareNewClinicalTrialPaymentStage');
    Route::get('prepareNewClinicalTrialAssessmentStage', 'ClinicalTrialController@prepareNewClinicalTrialAssessmentStage');
    Route::get('prepareNewClinicalTrialManagerMeetingStage', 'ClinicalTrialController@prepareNewClinicalTrialManagerMeetingStage');
    Route::post('addClinicalStudySite', 'ClinicalTrialController@addClinicalStudySite');
    Route::get('getClinicalStudySites', 'ClinicalTrialController@getClinicalStudySites');
    Route::get('getClinicalTrialPersonnelList', 'ClinicalTrialController@getClinicalTrialPersonnelList');
    Route::post('addApplicationOtherInvestigators', 'ClinicalTrialController@addApplicationOtherInvestigators');
    Route::get('getClinicalTrialOtherInvestigators', 'ClinicalTrialController@getClinicalTrialOtherInvestigators');
    Route::get('getImpProducts', 'ClinicalTrialController@getImpProducts');
    Route::get('getImpProductIngredients', 'ClinicalTrialController@getImpProductIngredients');
    Route::get('getClinicalTrialManagerApplicationsGeneric', 'ClinicalTrialController@getClinicalTrialManagerApplicationsGeneric');
    Route::get('getClinicalTrialManagerMeetingApplications', 'ClinicalTrialController@getClinicalTrialManagerMeetingApplications');
    Route::get('getClinicalTrialRecommReviewApplications', 'ClinicalTrialController@getClinicalTrialRecommReviewApplications');
    Route::get('getClinicalTrialApplicationsAtApproval', 'ClinicalTrialController@getClinicalTrialApplicationsAtApproval');
    Route::post('saveTCMeetingDetails', 'ClinicalTrialController@saveTCMeetingDetails');
    Route::post('syncTcMeetingParticipants', 'ClinicalTrialController@syncTcMeetingParticipants');
    Route::get('getTcMeetingParticipants', 'ClinicalTrialController@getTcMeetingParticipants');
    Route::get('getExternalAssessorDetails', 'ClinicalTrialController@getExternalAssessorDetails');
    Route::get('getTcMeetingDetails', 'ClinicalTrialController@getTcMeetingDetails');
    Route::get('getClinicalTrialApplicationMoreDetails', 'ClinicalTrialController@getClinicalTrialApplicationMoreDetails');
    Route::get('getClinicalTrialsList', 'ClinicalTrialController@getClinicalTrialsList');
    Route::post('saveAmendmentReceivingBaseDetails', 'ClinicalTrialController@saveAmendmentReceivingBaseDetails');
    Route::get('getOnlineApplications', 'ClinicalTrialController@getOnlineApplications');
    Route::get('prepareOnlineClinicalTrialPreview', 'ClinicalTrialController@prepareOnlineClinicalTrialPreview');
    Route::get('getOnlineClinicalStudySites', 'ClinicalTrialController@getOnlineClinicalStudySites');
    Route::get('getOnlineClinicalTrialOtherInvestigators', 'ClinicalTrialController@getOnlineClinicalTrialOtherInvestigators');
    Route::get('getOnlineImpProducts', 'ClinicalTrialController@getOnlineImpProducts');
    Route::get('getOnlineImpProductIngredients', 'ClinicalTrialController@getOnlineImpProductIngredients');
});
