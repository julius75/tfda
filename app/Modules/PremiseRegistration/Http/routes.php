<?php
//WEB Routes
Route::group(['middleware' => 'web', 'prefix' => 'premiseregistration', 'namespace' => 'App\\Modules\PremiseRegistration\Http\Controllers'], function () {
    Route::get('/', 'PremiseRegistrationController@index');
    Route::post('uploadApplicationFile', 'PremiseRegistrationController@uploadApplicationFile');
    //REPORTS
    Route::get('previewDoc', 'ReportsController@previewDoc');
    Route::get('printPremiseRegistrationCertificate', 'ReportsController@printPremiseRegistrationCertificate');
    Route::get('printPremiseBusinessPermit', 'ReportsController@printPremiseBusinessPermit');
    Route::get('getManagersReports', 'ReportsController@getManagersReports');
});
//API Routes
Route::group(['middleware' => 'auth:api', 'prefix' => 'premiseregistration', 'namespace' => 'App\\Modules\PremiseRegistration\Http\Controllers'], function () {
    //COMMON
    Route::get('getPremiseRegParamFromModel', 'PremiseRegistrationController@getPremiseRegParamFromModel');
    Route::get('getApplicantsList', 'PremiseRegistrationController@getApplicantsList');
    Route::get('getPremisesList', 'PremiseRegistrationController@getPremisesList');
    Route::get('getPremiseApplications', 'PremiseRegistrationController@getPremiseApplications');
    Route::get('getPremiseApplicationsAtApproval', 'PremiseRegistrationController@getPremiseApplicationsAtApproval');
    Route::get('getPremiseOtherDetails', 'PremiseRegistrationController@getPremiseOtherDetails');
    Route::get('getPremisePersonnelDetails', 'PremiseRegistrationController@getPremisePersonnelDetails');
    Route::post('savePremiseRegCommonData', 'PremiseRegistrationController@savePremiseRegCommonData');
    Route::post('deletePremiseRegRecord', 'PremiseRegistrationController@deletePremiseRegRecord');
    Route::post('softDeletePremiseRegRecord', 'PremiseRegistrationController@softDeletePremiseRegRecord');
    Route::post('undoPremiseRegSoftDeletes', 'PremiseRegistrationController@undoPremiseRegSoftDeletes');
    Route::post('savePremiseOtherDetails', 'PremiseRegistrationController@savePremiseOtherDetails');
    Route::get('getQueryPrevResponses', 'PremiseRegistrationController@getQueryPrevResponses');
    Route::post('closeApplicationQuery', 'PremiseRegistrationController@closeApplicationQuery');
    Route::post('saveApplicationReQueryDetails', 'PremiseRegistrationController@saveApplicationReQueryDetails');
    Route::post('saveApplicationInvoicingDetails', 'PremiseRegistrationController@saveApplicationInvoicingDetails');
    Route::post('removeInvoiceCostElement', 'PremiseRegistrationController@removeInvoiceCostElement');
    Route::get('getApplicationApplicantDetails', 'PremiseRegistrationController@getApplicationApplicantDetails');
    Route::post('saveApplicationPaymentDetails', 'PremiseRegistrationController@saveApplicationPaymentDetails');
    Route::post('removeApplicationPaymentDetails', 'PremiseRegistrationController@removeApplicationPaymentDetails');
    Route::get('getManagerApplicationsGeneric', 'PremiseRegistrationController@getManagerApplicationsGeneric');
    Route::get('getPremApplicationMoreDetails', 'PremiseRegistrationController@getPremApplicationMoreDetails');
    Route::get('getApplicationComments', 'PremiseRegistrationController@getApplicationComments');
    Route::get('getApplicationEvaluationTemplate', 'PremiseRegistrationController@getApplicationEvaluationTemplate');
    Route::post('saveApplicationApprovalDetails', 'PremiseRegistrationController@saveApplicationApprovalDetails');
    Route::post('deleteApplicationInvoice', 'PremiseRegistrationController@deleteApplicationInvoice');
    Route::get('getAllApplicationChecklistQueries', 'PremiseRegistrationController@getAllApplicationChecklistQueries');
    Route::post('savePremisePersonnelDetails', 'PremiseRegistrationController@savePremisePersonnelDetails');
    Route::post('savePremisePersonnelQualifications', 'PremiseRegistrationController@savePremisePersonnelQualifications');
    Route::get('getPremisePersonnelQualifications', 'PremiseRegistrationController@getPremisePersonnelQualifications');
    Route::post('deletePersonnelQualification', 'PremiseRegistrationController@deletePersonnelQualification');
    Route::post('uploadPersonnelDocument', 'PremiseRegistrationController@uploadPersonnelDocument');
    Route::get('getPersonnelDocuments', 'PremiseRegistrationController@getPersonnelDocuments');
    Route::get('getTraderPersonnel', 'PremiseRegistrationController@getTraderPersonnel');
    Route::post('savePremisePersonnelLinkageDetails', 'PremiseRegistrationController@savePremisePersonnelLinkageDetails');
    Route::get('getInspectionDetails', 'PremiseRegistrationController@getInspectionDetails');
    Route::get('getInspectionInspectors', 'PremiseRegistrationController@getInspectionInspectors');
    Route::get('getInspectorsList', 'PremiseRegistrationController@getInspectorsList');
    Route::post('saveInspectionInspectors', 'PremiseRegistrationController@saveInspectionInspectors');
    Route::post('removeInspectionInspectors', 'PremiseRegistrationController@removeInspectionInspectors');
    Route::post('saveNewReceivingBaseDetails', 'PremiseRegistrationController@saveNewReceivingBaseDetails');

    Route::post('saveRenewalReceivingBaseDetails', 'PremiseRegistrationController@saveRenewalReceivingBaseDetails');
    Route::post('saveAlterationReceivingBaseDetails', 'PremiseRegistrationController@saveAlterationReceivingBaseDetails');
    Route::post('saveRenewalAlterationReceivingBaseDetails', 'PremiseRegistrationController@saveRenewalAlterationReceivingBaseDetails');

    Route::get('prepareNewPremiseReceivingStage', 'PremiseRegistrationController@prepareNewPremiseReceivingStage');
    Route::get('prepareRenewalPremiseReceivingStage', 'PremiseRegistrationController@prepareRenewalPremiseReceivingStage');
    Route::get('prepareNewPremiseInvoicingStage', 'PremiseRegistrationController@prepareNewPremiseInvoicingStage');
    Route::get('prepareRenewalPremiseInvoicingStage', 'PremiseRegistrationController@prepareRenewalPremiseInvoicingStage');
    Route::get('prepareNewPremisePaymentStage', 'PremiseRegistrationController@prepareNewPremisePaymentStage');
    Route::get('prepareRenewalPremisePaymentStage', 'PremiseRegistrationController@prepareRenewalPremisePaymentStage');
    Route::get('getManagerApplicationsRenewalGeneric', 'PremiseRegistrationController@getManagerApplicationsRenewalGeneric');
    Route::get('prepareNewPremiseEvaluationStage', 'PremiseRegistrationController@prepareNewPremiseEvaluationStage');
    Route::get('prepareRenewalPremiseEvaluationStage', 'PremiseRegistrationController@prepareRenewalPremiseEvaluationStage');
    Route::get('getOnlineApplicationQueries', 'PremiseRegistrationController@getOnlineApplicationQueries');
    Route::post('saveOnlineQueries', 'PremiseRegistrationController@saveOnlineQueries');
    Route::post('saveApplicationChecklistDetails', 'PremiseRegistrationController@saveApplicationChecklistDetails');
    Route::post('syncAlterationAmendmentFormParts', 'PremiseRegistrationController@syncAlterationAmendmentFormParts');
    Route::post('syncAlterationAmendmentOtherParts', 'PremiseRegistrationController@syncAlterationAmendmentOtherParts');
    Route::post('getPremiseComparisonDetails', 'PremiseRegistrationController@getPremiseComparisonDetails');
    Route::get('getApplicationUploadedDocs', 'PremiseRegistrationController@getApplicationUploadedDocs');
    Route::get('getApplicationChecklistQueries', 'PremiseRegistrationController@getApplicationChecklistQueries');
    Route::get('prepareNewFoodOnlineReceivingStage', 'PremiseRegistrationController@prepareNewFoodOnlineReceivingStage');
    Route::post('saveNewAuditingChecklistDetails', 'PremiseRegistrationController@saveNewAuditingChecklistDetails');
    //Online Applications
    Route::get('getOnlineApplications', 'PremiseRegistrationController@getOnlineApplications');
    Route::get('getOnlineAppPremiseOtherDetails', 'PremiseRegistrationController@getOnlineAppPremiseOtherDetails');
    Route::get('getOnlineAppPremisePersonnelDetails', 'PremiseRegistrationController@getOnlineAppPremisePersonnelDetails');
    Route::get('getOnlineApplicationUploads', 'PremiseRegistrationController@getOnlineApplicationUploads');
    Route::post('saveOnlineApplicationDetails', 'PremiseRegistrationController@saveOnlineApplicationDetails');
    Route::post('updateOnlineApplicationQueryResponse', 'PremiseRegistrationController@updateOnlineApplicationQueryResponse');
    Route::post('rejectOnlineApplicationDetails', 'PremiseRegistrationController@rejectOnlineApplicationDetails');
});
