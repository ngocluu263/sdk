<?php

Route::group(['namespace' => 'PragmaRX\Sdk\Services\Avatars\Http\Controllers'], function()
{
	Route::group(['prefix' => 'files'], function()
	{
		Route::post('/', ['as' => 'files.upload', 'uses' => 'Avatars@upload']);
	});
});