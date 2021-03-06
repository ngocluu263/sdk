<?php

use PragmaRX\Support\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddTelegramChat extends Migration
{
	public function migrateUp()
	{
		Schema::table('chats', function(Blueprint $table)
		{
			$table->string('telegram_chat_id', 64)->nullable()->index();
		});

        Schema::table('chat_messages', function(Blueprint $table)
        {
            $table->string('telegram_message_id', 64)->nullable()->index();
        });
	}

	public function migrateDown()
	{
        Schema::table('chats', function(Blueprint $table)
        {
            $table->dropColumn('telegram_chat_id');
        });

        Schema::table('chat_messages', function(Blueprint $table)
        {
            $table->dropColumn('telegram_message_id');
        });
	}
}
