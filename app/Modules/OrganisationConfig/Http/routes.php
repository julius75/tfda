<?php

Route::group(['middleware' => 'web', 'prefix' => 'organisationconfig', 'namespace' => 'App\\Modules\OrganisationConfig\Http\Controllers'], function()
{
    Route::get('/', 'OrganisationConfigController@index');
    Route::get('getOrgConfigParamFromModel', 'OrganisationConfigController@getOrgConfigParamFromModel');
    Route::get('getDepartments', 'OrganisationConfigController@getDepartments');
});
