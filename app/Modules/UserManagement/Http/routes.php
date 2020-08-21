<?php

Route::group(['middleware' => 'web', 'prefix' => 'usermanagement', 'namespace' => 'App\\Modules\UserManagement\Http\Controllers'], function () {
    Route::get('/', 'UserManagementController@index');
    Route::get('getUserParamFromModel', 'UserManagementController@getUserParamFromModel');
    Route::post('saveUserCommonData', 'UserManagementController@saveUserCommonData');
    Route::post('deleteUserRecord', 'UserManagementController@deleteUserRecord');
    Route::post('softDeleteUserRecord', 'UserManagementController@softDeleteUserRecord');
    Route::post('undoUserSoftDeletes', 'UserManagementController@undoUserSoftDeletes');
    Route::get('getActiveSystemUsers', 'UserManagementController@getActiveSystemUsers');
    Route::get('getOpenUserRoles', 'UserManagementController@getOpenUserRoles');
    Route::get('getAssignedUserRoles', 'UserManagementController@getAssignedUserRoles');
    Route::get('getOpenUserGroups', 'UserManagementController@getOpenUserGroups');
    Route::get('getAssignedUserGroups', 'UserManagementController@getAssignedUserGroups');
    Route::post('saveUserImage', 'UserManagementController@saveUserImage');
    Route::post('saveUserInformation', 'UserManagementController@saveUserInformation');
    Route::post('resetUserPassword', 'UserManagementController@resetUserPassword');
    Route::post('updateUserPassword', 'UserManagementController@updateUserPassword');
    Route::post('blockSystemUser', 'UserManagementController@blockSystemUser');
    Route::get('getBlockedSystemUsers', 'UserManagementController@getBlockedSystemUsers');
    Route::post('unblockSystemUser', 'UserManagementController@unblockSystemUser');
    Route::get('getUnBlockedSystemUsers', 'UserManagementController@getUnBlockedSystemUsers');
});
