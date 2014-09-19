<?php

Route::group(['before' => 'auth', 'namespace' => 'PragmaRX\Sdk\Services\Profiles\Http\Controllers'], function()
{
	Route::group(['prefix' => 'profile'], function()
	{
		Route::get('edit/{username}', ['as' => 'profile.edit', 'uses' => 'Profiles@edit']);

		Route::patch('edit/{username}', ['as' => 'profile.edit', 'uses' => 'Profiles@update']);

		Route::get('{username}', ['as' => 'profile', 'uses' => 'Profiles@show']);
	});
});