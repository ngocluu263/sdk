<?php

namespace PragmaRX\SDK\Registration;


class RegisterUserCommand {

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

} 
