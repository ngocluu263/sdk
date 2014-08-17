<?php

namespace PragmaRX\SDK\Users;

use PragmaRX\SDK\Mailer\Mailer;
use PragmaRX\SDK\Profiles\Events\ProfileVisited;
use PragmaRX\SDK\ProfilesVisits\ProfileVisit;

use Activation;
use Flash;
use Auth;

class UserRepository {

	/**
	 * Save a user.
	 *
	 * @param User $user
	 * @return bool
	 */
	public function save($user)
	{
		return $user->save();
	}

	/**
	 * Get a paginated list of all users.
	 *
	 * @param int $howMany
	 * @return \Illuminate\Pagination\Paginator
	 */
	public function getPaginated($howMany = 25)
	{
		return User::orderBy('first_name')->simplePaginate($howMany);
	}

	/**
	 * Fetch a user by their username.
	 *
	 * @param $username
	 * @return \Illuminate\Database\Eloquent\Model|null|static
	 */
	public function findByUsername($username)
	{
		return User::where('username', $username)->first();

//		return User::with(['statuses' => function($query)
//		{
//			$query->latest();
//		}])->where('username', $username)->first();
	}

	/**
	 * Find a user by id.
	 *
	 * @param $id
	 * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|static
	 */
	public function findById($id)
	{
		return User::findOrFail($id);
	}

	public function findByEmail($email)
	{
		return User::where('email', $email)->first();
	}

	public function activate($email, $token)
	{
		return User::activate($email, $token);
	}

	public function sendUserActivationEmail($user)
	{
		Mailer::send(
			'emails.register.user-registered',
			$user,
			t('captions.activate-your-account')
		);

		Flash::message(t('paragraphs.activation-email-sent'));
	}

	public function checkAndCreateActivation($user)
	{
		if ( ! Activation::exists($user))
		{
			Activation::create($user);

			$this->sendUserActivationEmail($user);
		}
	}

	public function checkActivationByEmail($email)
	{
		$this->checkAndCreateActivation(
			$this->findByEmail($email)
		);
	}

	/**
	 * Follow a user.
	 *
	 * @param $user_to_follow
	 * @param $user_id
	 * @return mixed
	 */
	public function follow($user_to_follow, $user_id)
	{
		$user = $this->findById($user_id);

		$user_to_follow = $this->findByUsername($user_to_follow);

		if ( ! $user_to_follow->isFollowedBy($user))
		{
			$user = $user->following()->attach($user_to_follow->id);
		}

		return $user;
	}

	/**
	 * Unfollow a user.
	 *
	 * @param $user_to_unfollow
	 * @param $user_id
	 * @return mixed
	 */
	public function unfollow($user_to_unfollow, $user_id)
	{
		$user = $this->findById($user_id);

		$user_to_unfollow = $this->findByUsername($user_to_unfollow);

		return $user->following()->detach($user_to_unfollow->id);
	}

	/**
	 * Connect to a user.
	 *
	 * @param $user_to_connect
	 * @param $user_id
	 * @return mixed
	 */
	public function connect($user_to_connect, $user_id)
	{
		$user = $this->findById($user_id);

		$user_to_connect = $this->findByUsername($user_to_connect);

		if ( ! $user->isConnectedOrIsPendingTo($user_to_connect))
		{
			$user = $user->connections()->attach($user_to_connect->id);
		}

		return $user;
	}

	/**
	 * Disconnect from a user
	 *
	 * @param $user_to_disconnect
	 * @param $user_id
	 * @return mixed
	 */
	public function disconnect($user_to_disconnect, $user_id)
	{
		$user = $this->findById($user_id);

		$user_to_disconnect = $this->findByUsername($user_to_disconnect);

		return $user->connections()->detach($user_to_disconnect->id);
	}

	/**
	 * Block a user.
	 *
	 * @param $user_to_block
	 * @param $user_id
	 * @return mixed
	 */
	public function block($user_to_block, $user_id)
	{
		$user = $this->findById($user_id);

		$user_to_block = $this->findByUsername($user_to_block);

		if ( ! $user_to_block->isBlockedBy($user))
		{
			$user = $user->blockages()->attach($user_to_block->id);
		}

		return $user;
	}

	/**
	 * Unblock a user
	 *
	 * @param $user_to_unblock
	 * @param $user_id
	 * @return mixed
	 */
	public function unblock($user_to_unblock, $user_id)
	{
		$user = $this->findById($user_id);

		$user_to_unblock = $this->findByUsername($user_to_unblock);

		return $user->blockages()->detach($user_to_unblock->id);
	}

	public function getProfile($username)
	{
		$user = $this->findByUsername($username);

		$user->raise(new ProfileVisited($user));

		return $user;
	}

	public function registerVisitation($user)
	{
		if (Auth::id() == $user->id)
		{
			return;
		}

		ProfileVisit::visit([
			'visitor_id' => Auth::user()->id,
			'visited_id' => $user->id,
         ]);
	}

}
