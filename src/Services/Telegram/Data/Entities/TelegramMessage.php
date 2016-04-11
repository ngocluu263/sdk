<?php

namespace PragmaRX\Sdk\Services\Telegram\Data\Entities;

use PragmaRX\Sdk\Core\Model;

class TelegramMessage extends Model
{
	protected $table = 'telegram_messages';

    protected $fillable = [
        'telegram_message_id',
        'chat_id',
        'from_id',
        'date',
        'forward_from_id',
        'forward_date',
        'reply_to_message_id',
        'text',
        'audio_id',
        'document_id',
        'photo',
        'sticker_id',
        'video_id',
        'voice_id',
        'caption',
        'contact_id',
        'location_id',
        'new_chat_participant_id',
        'left_chat_participant_id',
        'new_chat_title',
        'new_chat_photo',
        'delete_chat_photo',
        'group_chat_created',
        'supergroup_chat_created',
        'channel_chat_created',
        'migrate_to_chat_id',
        'migrate_from_chat_id',
    ];
}
