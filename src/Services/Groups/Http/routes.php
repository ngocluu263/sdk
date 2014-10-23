<?php

Route::group(['namespace' => 'PragmaRX\Sdk\Services\Groups\Http\Controllers'], function()
{
	Route::group(['before' => 'auth', 'prefix' => 'groups'], function()
	{
		Route::get('/', ['as' => 'groups', 'uses' => 'Groups@index']);

		Route::post('/', ['as' => 'groups', 'uses' => 'Groups@store']);

		Route::patch('{id}', ['as' => 'groups.edit', 'uses' => 'Groups@update']);

		Route::delete('{id}', ['as' => 'groups.delete', 'uses' => 'Groups@delete']);

		Route::post('/validate', ['as' => 'groups.validate', 'uses' => 'Groups@validate']);

		Route::post('/{id}/members', ['as' => 'groups.members.add', 'uses' => 'Groups@addMembers']);

		Route::post('/{id}/members/validate', ['as' => 'groups.members.add.validate', 'uses' => 'Groups@addMembersValidate']);

		Route::delete('/{id}/members/delete', ['as' => 'groups.members.delete', 'uses' => 'Groups@deleteMembers']);
	});
});
