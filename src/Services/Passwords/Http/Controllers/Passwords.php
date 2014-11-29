<?php

namespace PragmaRX\Sdk\Services\Passwords\Http\Controllers;

use PragmaRX\Sdk\Core\Controller as BaseController;
use Password as PasswordReminder;
use Redirect;
use PragmaRX\Sdk\Services\Passwords\Commands\ResetPasswordCommand;
use PragmaRX\Sdk\Services\Passwords\Commands\UpdatePasswordCommand;
use PragmaRX\Sdk\Services\Passwords\Http\Requests\RemindPassword as RemindPasswordRequest;
use PragmaRX\Sdk\Services\Passwords\Http\Requests\ResetPassword as ResetPasswordRequest;
use PragmaRX\Sdk\Services\Passwords\Http\Requests\UpdatePassword as UpdatePasswordRequest;
use PragmaRX\Sdk\Services\Users\Data\Repositories\UserRepository;

use Input;
use View;
use Hash;

use Flash;

class Passwords extends BaseController {

	/**
	 * @var UserRepository
	 */
	private $userRepository;

	public function __construct(UserRepository $userRepository)
	{
		$this->userRepository = $userRepository;
	}

	/**
	 * Display the password reminder view.
	 *
	 * @return Response
	 */
	public function create()
	{
		return View::make('passwords.create');
	}

	/**
	 * Handle a POST request to remind a user of their password.
	 *
	 * @return Response
	 */
	public function store(RemindPasswordRequest $request)
	{
		$this->execute(ResetPasswordCommand::class);

		return Redirect::route_no_ajax('notification')
			->with('title', t('titles.reset-your-password'))
			->with('message', t('paragraphs.reset-password-sent'))
			->withInput();
	}

	/**
	 * Display the password reset view for the given token.
	 *
	 * @param  string  $token
	 * @return Response
	 */
	public function reset($token, ResetPasswordRequest $request)
	{
		$request->merge(['token' => $token]);

		return View::make('passwords.reset')->with('token', $token);
	}

	/**
	 * Handle a POST request to reset a user's password.
	 *
	 * @return Response
	 */
	public function update(UpdatePasswordRequest $request)
	{
		$credentials = Input::only(
			'email',
			'password',
			'password_confirmation',
			'token'
		);

		$this->execute(UpdatePasswordCommand::class, $credentials);

		return Redirect::route('login');
	}

}
