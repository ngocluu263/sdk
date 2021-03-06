<?php

Route::group(['middleware' => 'web'], function()
{
    Route::group(['prefix' => config('env.ROUTE_GLOBAL_PREFIX')], function() {
        Route::group(['prefix' => 'chat/client', 'namespace' => 'PragmaRX\Sdk\Services\Chat\Http\Client\Controllers'], function ()
        {
            Route::get('{clientId}/create/{layout?}', ['as' => 'chat.client.create', 'uses' => 'Chat@create']);

            Route::post('/', ['as' => 'chat.client.store', 'uses' => 'Chat@store']);

            Route::get('terminated/{chatId}', ['as' => 'chat.client.terminated', 'uses' => 'Chat@terminated']);

            Route::get('{id}', ['as' => 'chat.client', 'uses' => 'Chat@chat']);
        });

        Route::group(['prefix' => 'chats', 'middleware' => 'auth', 'namespace' => 'PragmaRX\Sdk\Services\Chat\Http\Server\Controllers'], function ()
        {
            Route::get('index', ['as' => 'chats.index', 'uses' => 'Chats@index']);
            Route::get('show/{id}', ['as' => 'chats.show', 'uses' => 'Chats@show']);
        });

        Route::group(['prefix' => 'chat/server', 'middleware' => 'auth', 'namespace' => 'PragmaRX\Sdk\Services\Chat\Http\Server\Controllers'], function ()
        {
            Route::get('/index', ['as' => 'chat.server.index', 'uses' => 'Chat@index']);

            Route::group(['prefix' => 'scripts'], function ()
            {
                Route::get('/', ['as' => 'chat.server.scripts.index', 'uses' => 'Scripts@index']);

                Route::get('create', ['as' => 'chat.server.scripts.create', 'uses' => 'Scripts@create']);

                Route::post('store', ['as' => 'chat.server.scripts.store', 'uses' => 'Scripts@store']);

                Route::get('edit/{scriptId}', ['as' => 'chat.server.scripts.edit', 'uses' => 'Scripts@edit']);

                Route::post('update', ['as' => 'chat.server.scripts.update', 'uses' => 'Scripts@update']);

                Route::get('delete/{scriptId}', ['as' => 'chat.server.scripts.delete', 'uses' => 'Scripts@delete']);
            });
        });

        // Authorization required
        Route::group(['prefix' => 'api/v1', 'middleware' => ['auth','cors'], 'namespace' => 'PragmaRX\Sdk\Services\Chat\Http\Server\Controllers'], function ()
        {
            Route::group(['prefix' => 'chat/server'], function ()
            {
                Route::get('scripts', ['as' => 'api.v1.chat.scripts', 'uses' => 'Api@scripts']);

                Route::get('all', ['as' => 'chat.all', 'uses' => 'Api@allChats']);

                Route::get('all/for/client/{clientId?}', ['as' => 'chat.all.for.client', 'uses' => 'Api@allChatsForClient']);

                Route::get('respond/{id}', ['as' => 'chat.respond', 'uses' => 'Api@respond']);

                Route::get('ping', ['as' => 'chat.ping', 'uses' => 'Api@ping']);

                Route::post('send', ['as' => 'chat.server.send.message', 'uses' => 'Api@serverSendMessage']);

                Route::post('read', ['as' => 'chat.server.read.message', 'uses' => 'Api@serverReadMessage']);

                Route::post('terminate', ['as' => 'chat.server.terminate', 'uses' => 'Api@terminateChat']);
            });
        });
    });
});

Route::group(['middleware' => 'api'], function()
{
    // No authorization required
    Route::group(['prefix' => 'api/v1', 'middleware' => ['cors'], 'namespace' => 'PragmaRX\Sdk\Services\Chat\Http\Server\Controllers'], function ()
    {
        Route::group(['prefix' => 'chat'], function ()
        {
            Route::group(['prefix' => 'client'], function ()
            {
                Route::post('send', ['as' => 'chat.client.send.message', 'uses' => 'Api@clientSendMessage']);

                Route::get('get/{chatId}', ['as' => 'chat.client.all', 'uses' => 'Api@getChat']);

                Route::get('operators/online/for/client/{clientId?}', ['as' => 'chat.operators.online.for.client', 'uses' => 'Api@operatorsOnlineForClient']);

                Route::get('checkin/{clientId}/{userId}', ['as' => 'chat.checkin', 'uses' => 'Api@checkIn']);

                Route::get('checkout/{clientId}/{userId}', ['as' => 'chat.checkout', 'uses' => 'Api@checkOut']);
            });

            Route::group(['prefix' => 'server'], function ()
            {
                Route::get('checkin/{clientId}/{userId}', ['as' => 'chat.checkin', 'uses' => 'Api@checkIn']);

                Route::get('checkout/{clientId}/{userId}', ['as' => 'chat.checkout', 'uses' => 'Api@checkOut']);
            });
        });
    });
});
