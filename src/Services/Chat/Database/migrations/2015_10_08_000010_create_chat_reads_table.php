<?php

use PragmaRX\Support\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateChatReadsTable extends Migration
{
	public function migrateUp()
	{
		Schema::create('chat_reads', function(Blueprint $table)
		{
			$table->string('id', 64)->unique()->primary()->index();

			$table->string('chat_business_client_talker_id', 64)->index();
			$table->string('chat_id', 64)->index();
			$table->bigInteger('last_read_message_serial');

			$table->timestamps();
		});

		Schema::table('chat_reads', function(Blueprint $table)
		{
			$table->foreign('chat_id')
				->references('id')
				->on('chats')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

		Schema::table('chat_reads', function(Blueprint $table)
		{
			$table->foreign('chat_business_client_talker_id')
				->references('id')
				->on('chat_business_client_talkers')
				->onUpdate('cascade')
				->onDelete('cascade');
		});
	}

	public function migrateDown()
	{
		Schema::drop('chat_reads');
	}
}
