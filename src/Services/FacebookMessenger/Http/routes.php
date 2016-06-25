<?php

Route::group(['prefix' => config('env.ROUTE_GLOBAL_PREFIX')], function() 
{
    Route::group(['namespace' => 'PragmaRX\Sdk\Services\FacebookMessenger\Http\Controllers'], function()
    {
        Route::group(['prefix' => 'facebook'], function()
        {
            Route::any('{robot}/{token}/webhook/handle', ['as' => 'facebookMessenger.webhook.handle', 'uses' => 'FacebookMessenger@handleWebhook']);
        });
    });
});