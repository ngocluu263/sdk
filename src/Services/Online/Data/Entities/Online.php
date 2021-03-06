<?php

namespace PragmaRX\Sdk\Services\Online\Data\Entities;

use PragmaRX\Sdk\Core\Database\Eloquent\Model;

class Online extends Model
{
    protected $table = 'online_users';

	protected $fillable = [
        'user_id',
        'last_seen_at',
        'last_seen_on_chat',
        'online_on_chat'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'last_seen_at'
    ];
}
