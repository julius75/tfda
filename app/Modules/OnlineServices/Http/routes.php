<?php

Route::group(['middleware' => 'web', 'prefix' => 'onlineservices', 'namespace' => 'App\\Modules\OnlineServices\Http\Controllers'], function()
{
    Route::get('/', 'OnlineServicesController@index');
    Route::post('doDeleteConfigWidgetParam', 'OnlineServicesConfigController@doDeleteConfigWidgetParam');
    Route::post('saveApplicationstatusactions', 'OnlineServicesConfigController@saveApplicationstatusactions');
    Route::post('saveOnlineservices', 'OnlineServicesConfigController@saveOnlineservices');

    Route::post('saveOnlinePortalData', 'OnlineServicesConfigController@saveOnlinePortalData');
    Route::post('saveUniformOnlinePortalData', 'OnlineServicesConfigController@saveUniformOnlinePortalData');
    
    
    Route::post('saveApplicationstatusactions', 'OnlineServicesConfigController@saveApplicationstatusactions');
    
	
	Route::get('getapplicationstatusactions', 'OnlineServicesConfigController@getapplicationstatusactions');
	Route::get('getOnlineMenuLevel0', 'OnlineServicesConfigController@getOnlineMenuLevel0');
    Route::get('getSystemNavigationMenuItems', 'OnlineServicesConfigController@getSystemNavigationMenuItems');
    Route::get('getOnlinePortalServicesDetails', 'OnlineServicesConfigController@getOnlinePortalServicesDetails');
    Route::get('getApplicationdocumentdefination', 'OnlineServicesConfigController@getApplicationdocumentdefination');
    Route::get('getOnlineProcessTransitionsdetails', 'OnlineServicesConfigController@getOnlineProcessTransitionsdetails');
   
    
});
