<?php

Route::group(['middleware' => 'web', 'prefix' => 'parameters', 'namespace' => 'Modules\Parameters\Http\Controllers'], function()
{
    Route::post('/{entity}', 'CommonParameter@saveParameter');
    Route::put('/{entity}', 'CommonParameter@saveParameter');
    Route::put('{entity}/merge', 'CommonParameter@merge');
    Route::get('/{entity}', 'CommonParameter@getParameters');
    Route::delete('/{entity}/{id}/{action}', 'CommonParameter@deleteParameter')
        -> where(
            [
                'id' => '[0-9]+',
                'action' => 'actual|soft|enable'
            ]
        );
});

Route::group(['middleware' => 'web', 'prefix' => 'premiseregistration/parameters', 'namespace' => 'Modules\Parameters\Http\Controllers'], function()
{
    Route::post('/{entity}', 'PremiseRegistration@saveParameter');
    Route::put('/{entity}', 'PremiseRegistration@saveParameter');
    Route::put('{entity}/merge', 'PremiseRegistration@merge');
    Route::get('/{entity}', 'PremiseRegistration@getParameters');
    Route::delete('/{entity}/{id}/{action}', 'PremiseRegistration@deleteParameter')
        -> where(
            [
                'id' => '[0-9]+',
                'action' => 'actual|soft|enable'
            ]
        );
});


Route::group(['middleware' => 'web', 'prefix' => 'organization/parameters', 'namespace' => 'Modules\Parameters\Http\Controllers'], function()
{
    Route::post('/{entity}', 'OrganizationParameter@saveParameter');
    Route::put('/{entity}', 'OrganizationParameter@saveParameter');
    Route::put('{entity}/merge', 'OrganizationParameter@merge');
    Route::get('/{entity}', 'OrganizationParameter@getParameters');
    Route::delete('/{entity}/{id}/{action}', 'OrganizationParameter@deleteParameter')
        -> where(
            [
                'id' => '[0-9]+',
                'action' => 'actual|soft|enable'
            ]
        );
});
//Added by KIP
Route::group(['middleware' => 'web', 'prefix' => 'commonparam', 'namespace' => 'Modules\Parameters\Http\Controllers'], function()
{
    //model_name:model_name, as a parameter
    Route::get('getCommonParamFromModel', 'CommonParameter@getCommonParamFromModel');
    Route::get('getCommonParamFromTable', 'CommonParameter@getCommonParamFromTable');
});