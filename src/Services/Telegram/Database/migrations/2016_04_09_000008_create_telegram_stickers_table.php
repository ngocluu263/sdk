<?php

use PragmaRX\Support\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTelegramStickersTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function migrateUp()
	{
		// Roles
		Schema::create('telegram_stickers', function(Blueprint $table)
		{
			$table->string('id', 64)->unique()->primary()->index();

            $table->string('telegram_file_id')->unique()->index();
            $table->string('file_name_id', 64)->nullable()->index();
            $table->integer('width');
            $table->integer('height');
            $table->string('thumb_id', 64)->nullable();
            $table->integer('file_size')->nullable()->unsigned();

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function migrateDown()
	{
		Schema::drop('telegram_stickers');
	}
}
