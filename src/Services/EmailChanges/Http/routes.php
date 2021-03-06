<?php

Route::group(['middleware' => 'web'], function()
{
    Route::group(['middleware' => 'web'], function()
    {
        Route::group(['prefix' => config('env.ROUTE_GLOBAL_PREFIX')], function() {
            Route::group(['middleware' => 'auth', 'namespace' => 'PragmaRX\Sdk\Services\EmailChanges\Http\Controllers'], function()
            {
                Route::group(['prefix' => 'email/change'], function()
                {
                    Route::get('{token}', ['as' => 'email.change', 'uses' => 'EmailChanges@change']);

                    Route::get('report/{token}', ['as' => 'email.change.report', 'uses' => 'EmailChanges@report']);
                });
            });
        });
    });
});
