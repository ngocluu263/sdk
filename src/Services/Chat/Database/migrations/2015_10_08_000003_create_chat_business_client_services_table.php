<?php

use PragmaRX\Support\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateChatBusinessClientServicesTable extends Migration
{
	public function migrateUp()
	{
		Schema::create('chat_business_client_services', function(Blueprint $table)
		{
			$table->string('id', 64)->unique()->primary()->index();

			$table->string('business_client_id', 64);
			$table->string('chat_service_id', 64);
			$table->string('description');

			$table->timestamps();
		});

		Schema::table('chat_business_client_services', function(Blueprint $table)
		{
			$table->foreign('business_client_id')
				->references('id')
				->on('business_clients')
				->onUpdate('cascade')
				->onDelete('cascade');
		});

		Schema::table('chat_business_client_services', function(Blueprint $table)
		{
			$table->foreign('chat_service_id')
				->references('id')
				->on('chat_services')
				->onUpdate('cascade')
				->onDelete('cascade');
		});
	}

	public function migrateDown()
	{
		Schema::drop('chat_business_client_services');
	}
}
