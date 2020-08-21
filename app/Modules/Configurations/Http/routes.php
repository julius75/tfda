<?php

Route::group(['middleware' => 'web', 'prefix' => 'configurations', 'namespace' => 'App\\Modules\Configurations\Http\Controllers'], function()
{
    Route::get('/', 'ConfigurationsController@index');
    Route::post('saveConfigCommonData', 'ConfigurationsController@saveConfigCommonData');
    Route::post('saveSystemModuleData', 'ConfigurationsController@saveSystemModuleData');

    
    Route::get('getConfigParamFromModel', 'ConfigurationsController@getConfigParamFromModel');
    Route::post('deleteConfigRecord', 'ConfigurationsController@deleteConfigRecord');
    Route::post('softDeleteConfigRecord', 'ConfigurationsController@softDeleteConfigRecord');
    Route::post('undoConfigSoftDeletes', 'ConfigurationsController@undoConfigSoftDeletes');
    Route::get('getChecklistTypes', 'ConfigurationsController@getChecklistTypes');
    Route::get('getChecklistItems', 'ConfigurationsController@getChecklistItems');
    Route::get('getAllApplicationStatuses', 'ConfigurationsController@getAllApplicationStatuses');
    Route::get('getAlterationParameters', 'ConfigurationsController@getAlterationParameters');


    Route::get('getproductApplicationParameters', 'ConfigurationsController@getproductApplicationParameters');
    Route::get('getproductSubCategoryParameters', 'ConfigurationsController@getproductSubCategoryParameters');
    Route::get('getproductGeneraicNameParameters', 'ConfigurationsController@getproductGeneraicNameParameters');
    Route::get('getsystemSubModules', 'ConfigurationsController@getsystemSubModules');
    Route::get('getsystemModules', 'ConfigurationsController@getsystemModules');
    Route::get('getRefnumbersformats', 'ConfigurationsController@getRefnumbersformats');
    Route::get('getregistrationexpirytime_span', 'ConfigurationsController@getregistrationexpirytime_span');
    Route::get('getVariationCategoriesParameters', 'ConfigurationsController@getVariationCategoriesParameters');
    
    
});
