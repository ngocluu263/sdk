<?php

namespace PragmaRX\Sdk\Services\Tags\Commands;

use PragmaRX\Sdk\Services\Bus\Commands\SelfHandlingCommand;
use PragmaRX\Sdk\Services\Tags\Data\Repositories\ClientRepository;

class AddClientCommand extends SelfHandlingCommand {

	public $user;

	public $first_name;

	public $last_name;

	public $email;

	public $birthdate;

	function __construct($email, $first_name, $last_name, $user, $birthdate)
	{
		$this->email = $email;

		$this->first_name = $first_name;

		$this->last_name = $last_name;

		$this->user = $user;

		$this->birthdate = $birthdate;
	}

	public function handle(ClientRepository $clientRepository)
	{
		$client = $clientRepository->create(
			$this->user,
			$this->first_name,
			$this->last_name,
			$this->email,
			$this->birthdate
		);

		$this->dispatchEventsFor($client);
	}

}
