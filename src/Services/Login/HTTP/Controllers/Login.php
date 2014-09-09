<?php

namespace PragmaRX\Sdk\Services\Login\Http\Controllers;

use Illuminate\Support\Facades\Redirect;
use PragmaRX\Sdk\Core\Controller as BaseController;
use PragmaRX\Sdk\Services\Accounts\Commands\SignInCommand;
use PragmaRX\Sdk\Services\Login\Forms\SignIn as SignInForm;

use View;
use Input;
use Auth;
use Sentinel;
use Flash;

class Login extends BaseController {

	/**
	 * @var SignInForm
	 */
	private $signInForm;

	/**
	 * @param SignInForm $signInForm
	 */
	public function __construct(SignInForm $signInForm)
	{
		$this->beforeFilter('guest', ['except' => 'destroy']);

		$this->signInForm = $signInForm;
	}

	/**
	 * @return mixed
	 */
	public function create()
	{
		return View::make('login.create');
	}

	/**
	 * @return mixed
	 */
	public function store($email = null, $password = null)
	{
		$input = [
			'email' => $email ?: Input::get('email'),
		    'password' => $password ?: Input::get('password'),
		];

		$this->signInForm->validate($input);

		$this->execute(SignInCommand::class, $input);

		Flash::message(t('paragraphs.welcome-back'));

		return Redirect::intended('/');
	}

	public function destroy()
	{
		Flash::message(t('paragraphs.you-are-logged-out'));

		Auth::logout();

		return Redirect::home();
	}
}
