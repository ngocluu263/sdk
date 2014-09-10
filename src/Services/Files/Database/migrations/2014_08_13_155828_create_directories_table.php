<?php

use PragmaRX\Support\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDirectoriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function migrateUp()
	{
		Schema::create('directories', function(Blueprint $table)
		{
			$table->string('id', 64)->unique();
			$table->string('host')->default('localhost');
			$table->string('path')->index();
			$table->string('relative_path')->index();
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
		Schema::drop('directories');
	}

}
