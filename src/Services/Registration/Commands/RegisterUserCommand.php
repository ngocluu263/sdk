<?php

namespace PragmaRX\Sdk\Services\Registration\Commands;

use PragmaRX\Sdk\Services\Bus\Commands\SelfHandlingCommand;
use PragmaRX\Sdk\Services\Users\Data\Repositories\UserRepository;

class RegisterUserCommand extends SelfHandlingCommand
{
	public $username;

	public $email;
	
	public $password;

	public $first_name;

	public $last_name;

	function __construct($username, $email, $password, $first_name, $last_name)
	{
		$this->username = $username;

		$this->email = $email;

		$this->password = $password;

		$this->first_name = $first_name;

		$this->last_name = $last_name;
	}

	public function handle(UserRepository $repository)
	{
		$user = $repository->register(
			$this->username,
			$this->email,
			$this->password,
			$this->first_name,
			$this->last_name
		);

		$repository->save($user);

		return $user;
	}
}
