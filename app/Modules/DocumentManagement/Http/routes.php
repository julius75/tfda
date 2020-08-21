<?php

Route::group(['middleware' => 'web', 'prefix' => 'documentmanagement', 'namespace' => 'App\\Modules\DocumentManagement\Http\Controllers'], function()
{
    Route::get('/', 'DocumentManagementController@index');
    //post actions 
    
    Route::post('saveDocumentRepositoryStructure', 'DmsConfigurations@saveDocumentRepositoryStructure');
    Route::post('saveDocumentRepositoryRootFolder', 'DmsConfigurations@saveDocumentRepositoryRootFolder');
    Route::post('saveDMSSiteDefinationDetails', 'DmsConfigurations@saveDMSSiteDefinationDetails');
    Route::post('saveDMSSectionDefinationDetails', 'DmsConfigurations@saveDMSSectionDefinationDetails');
    Route::post('saveDMSSecModulesDefinationDetails', 'DmsConfigurations@saveDMSSecModulesDefinationDetails');
    Route::post('saveDMSSecSubModulesDefinationDetails', 'DmsConfigurations@saveDMSSecSubModulesDefinationDetails');
    Route::post('saveDMSModulesDocTypeDefinationDetails', 'DmsConfigurations@saveDMSModulesDocTypeDefinationDetails');
    Route::post('saveDMSNoStructuredDocDefinationDetails', 'DmsConfigurations@saveDMSNoStructuredDocDefinationDetails');
    Route::post('uploadApplicationDocumentFile', 'DocumentManagementController@uploadApplicationDocumentFile');
    Route::post('uploadProductImages', 'DocumentManagementController@uploadProductImages');
    
    Route::post('onApplicationDocumentDelete', 'DocumentManagementController@onApplicationDocumentDelete');
    
    Route::post('onDeleteNonStructureApplicationDocument', 'DocumentManagementController@onDeleteNonStructureApplicationDocument');
    Route::post('uploadunstructureddocumentuploads', 'DocumentManagementController@uploadunstructureddocumentuploads');
    
    
    //the configurations 
    Route::get('getDocumentsTypes', 'DmsConfigurations@getDocumentsTypes');
    Route::get('getParameterstableSchema', 'DmsConfigurations@getParameterstableSchema');
    Route::get('getdocdefinationrequirementDetails', 'DmsConfigurations@getdocdefinationrequirementDetails');
    Route::get('docdefinationrequirementfilterdetails', 'DmsConfigurations@docdefinationrequirementfilterdetails');
    Route::get('getdocumentreposirotystructureDetails', 'DmsConfigurations@getdocumentreposirotystructureDetails');
    Route::get('getdocumentsectionsrepstructure', 'DmsConfigurations@getdocumentsectionsrepstructure');
    Route::get('getRepositoryrootfolderDetails', 'DmsConfigurations@getRepositoryrootfolderDetails');
    Route::get('dmsAuthentication', 'DmsConfigurations@dmsAuthentication');
   
    Route::get('getDMSSiteDefinationDetails', 'DmsConfigurations@getDMSSiteDefinationDetails');
    Route::get('getDMSSectionsDefinationDetails', 'DmsConfigurations@getDMSSectionsDefinationDetails');
    Route::get('getDMSSectionsModulesDefinationDetails', 'DmsConfigurations@getDMSSectionsModulesDefinationDetails');
    Route::get('getDMSSectionsSubModulesDefinationDetails', 'DmsConfigurations@getDMSSectionsSubModulesDefinationDetails');
    Route::get('getDMSModulesDocumentTypesDefinationDetails', 'DmsConfigurations@getDMSModulesDocumentTypesDefinationDetails');
   
    Route::get('getSOPMasterListDetails', 'DmsConfigurations@getSOPMasterListDetails');
   
    Route::get('getnonStructuredDocumentsDefination', 'DmsConfigurations@getnonStructuredDocumentsDefination');
    
    //dms Configurations
    Route::get('getDmsParamFromModel', 'DmsConfigurations@getDmsParamFromModel');
    
    //application documents 
    Route::get('onLoadApplicationDocumentsUploads', 'DocumentManagementController@onLoadApplicationDocumentsUploads');
    Route::get('onLoadProductImagesUploads', 'DocumentManagementController@onLoadProductImagesUploads');
    Route::get('onLoadApplicationDocumentsRequirements', 'DocumentManagementController@onLoadApplicationDocumentsRequirements');

    Route::get('getApplicationDocumentDownloadurl', 'DocumentManagementController@getApplicationDocumentDownloadurl');
    Route::get('getApplicationDocumentPreviousVersions', 'DocumentManagementController@getApplicationDocumentPreviousVersions');
    //KIP
    Route::get('getProcessApplicableDocTypes', 'DocumentManagementController@getProcessApplicableDocTypes');
    Route::get('getProcessApplicableDocRequirements', 'DocumentManagementController@getProcessApplicableDocRequirements');
    Route::get('onLoadApplicationDocumentsUploadsPortal', 'DocumentManagementController@onLoadApplicationDocumentsUploadsPortal');
    Route::get('onLoadOnlineProductImagesUploads', 'DocumentManagementController@onLoadOnlineProductImagesUploads');
    Route::get('onLoadUnstructureApplicationDocumentsUploads', 'DocumentManagementController@onLoadUnstructureApplicationDocumentsUploads');

    

    
});


