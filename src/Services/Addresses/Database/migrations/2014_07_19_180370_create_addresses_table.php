<?php

use PragmaRX\Support\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAddressesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function migrateUp()
	{
		Schema::create('addresses', function(Blueprint $table)
		{
			$table->string('id', 64)->primary();

			$table->string('street')->nullable();
			$table->string('neighborhood')->nullable();
			$table->string('city_id', 64);
			$table->string('zip_code')->nullable();
			$table->float('latitude')->nullable();
			$table->float('longitude')->nullable();

			$table->timestamps();
		});

		Schema::table('addresses', function(Blueprint $table)
		{
			$table->foreign('city_id')
					->references('id')
					->on('cities')
					->onUpdate('cascade')
					->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function migrateDown()
	{
		Schema::drop('addresses');
	}

}
