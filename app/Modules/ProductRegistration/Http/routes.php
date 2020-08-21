<?php

Route::group(['middleware' => 'auth:api','prefix' => 'productregistration', 'namespace' => 'App\\Modules\ProductRegistration\Http\Controllers'], function()
{
    
    Route::post('/saveNewProductReceivingBaseDetails', 'ProductRegistrationController@saveNewProductReceivingBaseDetails');
    Route::post('/saveRenAltProductReceivingBaseDetails', 'ProductRegistrationController@saveRenAltProductReceivingBaseDetails');
    Route::post('/onSaveProductOtherDetails', 'ProductRegistrationController@onSaveProductOtherDetails');
    Route::post('/onSaveProductinformation', 'ProductRegistrationController@onSaveProductinformation');
    
    Route::post('saveApplicationInvoicingDetails', 'ProductRegistrationController@saveApplicationInvoicingDetails');
   
    Route::get('/applications', 'ProductRegistrationController@getProductApplications');
    Route::get('/getElementCosts', 'ProductRegistrationController@getElementCosts');
    
    Route::get('/getManagerEvaluationApplications', 'ProductRegistrationController@getManagerEvaluationApplications');
    Route::get('/getManagerAuditingApplications', 'ProductRegistrationController@getManagerAuditingApplications');
    Route::get('/getGrantingDecisionApplications', 'ProductRegistrationController@getGrantingDecisionApplications');
    Route::get('/getApplicationUploadedDocs', 'ProductRegistrationController@getApplicationUploadedDocs');
    Route::get('/getApplicationUploadedDocs', 'ProductRegistrationController@getApplicationUploadedDocs');
    
    Route::get('/prepareNewProductReceivingStage', 'ProductRegistrationController@prepareNewProductReceivingStage');
    Route::get('/prepareOnlineProductReceivingStage', 'ProductRegistrationController@prepareOnlineProductReceivingStage');

    Route::get('/prepareProductsInvoicingStage', 'ProductRegistrationController@prepareProductsInvoicingStage');
    Route::get('/prepareNewProductPaymentStage', 'ProductRegistrationController@prepareNewProductPaymentStage');
    Route::get('/prepareProductsRegMeetingStage', 'ProductRegistrationController@prepareProductsRegMeetingStage');

    
    Route::post('/saveTCMeetingDetails', 'ProductRegistrationController@saveTCMeetingDetails');
    Route::post('/saveUpload', 'ProductRegistrationController@saveUpload');
    Route::post('/saveSample', 'ProductRegistrationController@saveSample');
    Route::post('/uploadApplicationFile', 'ProductRegistrationController@uploadApplicationFile');

    Route::post('/onDeleteProductOtherDetails', 'ProductRegistrationController@onDeleteProductOtherDetails');
    
    Route::post('/deleteUploadedProductImages', 'ProductRegistrationController@deleteUploadedProductImages');
    
    Route::post('/saveManufacturerDetails', 'ProductRegistrationController@saveManufacturerDetails');
    Route::post('/saveProductGmpApplicationDetails', 'ProductRegistrationController@saveProductGmpApplicationDetails');
    Route::post('/saveProductRegistrationComments', 'ProductRegistrationController@saveProductRegistrationComments');

    
    //products other details 
    
    
    Route::get('/onLoadproductNutrients', 'ProductRegistrationController@onLoadproductNutrients');
    Route::get('/onLoadOnlineproductNutrients', 'ProductRegistrationController@onLoadOnlineproductNutrients');
    Route::get('/onLoadproductIngredients', 'ProductRegistrationController@onLoadproductIngredients');
    Route::get('/onLoadproductPackagingDetails', 'ProductRegistrationController@onLoadproductPackagingDetails');
    Route::get('/onLoadManufacturingSitesDetails', 'ProductRegistrationController@onLoadManufacturingSitesDetails');
    Route::get('/onLoadproductManufacturer', 'ProductRegistrationController@onLoadproductManufacturer');
    Route::get('/onLoadproductApiManufacturer', 'ProductRegistrationController@onLoadproductApiManufacturer');
    Route::get('/onLoadproductGmpInspectionDetailsStr', 'ProductRegistrationController@onLoadproductGmpInspectionDetailsStr');
   
    Route::get('/getProductActiveIngredients', 'ProductRegistrationController@getProductActiveIngredients');
    Route::get('/onLoadgmpInspectionApplicationsDetails', 'ProductRegistrationController@onLoadgmpInspectionApplicationsDetails');
    Route::get('onLoadProductsSampledetails', 'ProductRegistrationController@onLoadProductsSampledetails');
    Route::get('getTcMeetingParticipants', 'ProductRegistrationController@getTcMeetingParticipants');
    Route::get('getProductRegistrationMeetingApplications', 'ProductRegistrationController@getProductRegistrationMeetingApplications');
    Route::get('getProductTcReviewMeetingApplications', 'ProductRegistrationController@getProductTcReviewMeetingApplications');
   
    Route::get('getProductApprovalApplications', 'ProductRegistrationController@getProductApprovalApplications');
    Route::get('getProductApprovalApplicationsNonTc', 'ProductRegistrationController@getProductApprovalApplicationsNonTc');
    
    Route::get('getproductregistrationAppsApproval', 'ProductRegistrationController@getproductregistrationAppsApproval');
   
    Route::get('getProductApplicationMoreDetails', 'ProductRegistrationController@getProductApplicationMoreDetails');
   
    Route::get('getEValuationComments', 'ProductRegistrationController@getEValuationComments');
    
    Route::get('getAuditingComments', 'ProductRegistrationController@getAuditingComments');
    
    
    Route::get('getOnlineApplications', 'ProductRegistrationController@getOnlineApplications');

    Route::get('onLoadOnlineproductIngredients', 'ProductRegistrationController@onLoadOnlineproductIngredients');
    Route::get('onLoadOnlineproductPackagingDetails', 'ProductRegistrationController@onLoadOnlineproductPackagingDetails');
    Route::get('onLoadOnlineproductManufacturer', 'ProductRegistrationController@onLoadOnlineproductManufacturer');
   
    Route::get('onLoadOnlineproductApiManufacturer', 'ProductRegistrationController@onLoadOnlineproductApiManufacturer');
    Route::get('onLoadOnlinegmpInspectionApplicationsDetails', 'ProductRegistrationController@onLoadOnlinegmpInspectionApplicationsDetails');
    Route::get('getRegisteredProductsAppsDetails', 'ProductRegistrationController@getRegisteredProductsAppsDetails');

    
   //connection('portal_db')->
});
