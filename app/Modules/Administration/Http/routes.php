<?php

Route::group(['middleware' => 'web', 'prefix' => 'administration', 'namespace' => 'App\\Modules\Administration\Http\Controllers'], function()
{
    Route::get('/', 'AdministrationController@index');
    Route::get('getSystemNavigationMenuItems', 'AdministrationController@getSystemNavigationMenuItems');
    Route::get('getSystemMenus', 'AdministrationController@getSystemMenus');
    Route::get('getParentMenus', 'AdministrationController@getParentMenus');
    Route::get('getChildMenus', 'AdministrationController@getChildMenus');
    Route::post('saveMenuItem', 'AdministrationController@saveMenuItem');
    Route::post('deleteAdminRecord', 'AdministrationController@deleteAdminRecord');
    Route::post('softDeleteAdminRecord', 'AdministrationController@softDeleteAdminRecord');
    Route::post('undoAdminSoftDeletes', 'AdministrationController@undoAdminSoftDeletes');
    //Route::get('getAdminParamFromModel', 'AdministrationController@getAdminParamFromModel');
    Route::post('saveAdminCommonData', 'AdministrationController@saveAdminCommonData');
    Route::get('getSystemRoles', 'AdministrationController@getSystemRoles');
    Route::post('updateSystemNavigationAccessRoles', 'AdministrationController@updateSystemNavigationAccessRoles');
    Route::post('updateSystemPermissionAccessRoles', 'AdministrationController@updateSystemPermissionAccessRoles');
    Route::get('getNonMenuItems','AdministrationController@getNonMenuItems');
    Route::get('getNonMenuItemsSystemRoles','AdministrationController@getNonMenuItemsSystemRoles');
    Route::get('getMenuProcessesRoles','AdministrationController@getMenuProcessesRoles');
    Route::post('removeSelectedUsersFromGroup','AdministrationController@removeSelectedUsersFromGroup');
    Route::get('getSystemUserGroups','AdministrationController@getSystemUserGroups');
    Route::get('getFormFields','AdministrationController@getFormFields');

    Route::get('testApi1', function () {
        return redirect()->route('oauth/token');
    })->middleware('auth:api');
    Route::post('testApi','AdministrationController@test');
});
Route::group(['middleware' => 'auth:api', 'prefix' => 'administration', 'namespace' => 'App\\Modules\Administration\Http\Controllers'], function () {
    Route::get('getAdminParamFromModel', 'AdministrationController@getAdminParamFromModel');
});

