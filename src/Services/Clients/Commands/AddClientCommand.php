<?php

namespace PragmaRX\Sdk\Services\Clients\Commands;


class AddClientCommand {

	public $user;

	public $first_name;

	public $last_name;

	public $email;

	function __construct($email, $first_name, $last_name, $user)
	{
		$this->email = $email;

		$this->first_name = $first_name;

		$this->last_name = $last_name;

		$this->user = $user;
	}

}
