<?php

namespace PragmaRX\Sdk\Services\Messages\Data\Entities;

use PragmaRX\Sdk\Core\Database\Eloquent\Model;

class Message extends Model {

	protected $table = 'messages_messages';

	protected $touches = array('thread');

	protected $fillable = [
		'thread_id',
	    'sender_id',
	    'body',
	    'answering_message_id'
	];

	public function attachments()
	{
		return $this->hasMany('PragmaRX\Sdk\Services\Messages\Data\Entities\Attachment');
	}

	public function thread()
    {
        return $this->belongsTo('PragmaRX\Sdk\Services\Messages\Data\Entities\Thread');
    }

	public function sender()
	{
		return $this->belongsTo('PragmaRX\Sdk\Services\Messages\Data\Entities\Participant');
	}

}
