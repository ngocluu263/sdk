<?php

namespace PragmaRX\Sdk\Services\Chat\Data\Repositories;

use Auth;
use Carbon\Carbon;
use PragmaRX\Sdk\Core\Data\Repository;
use PragmaRX\Sdk\Services\Businesses\Data\Entities\BusinessClient;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatScript;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatScriptType;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatService;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatMessage;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatCustomer;
use PragmaRX\Sdk\Services\Users\Data\Contracts\UserRepository;
use PragmaRX\Sdk\Services\Chat\Data\Entities\Chat as ChatModel;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatBusinessClientTalker;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatBusinessClientService;
use PragmaRX\Sdk\Services\Businesses\Data\Repositories\Businesses as BusinessesRepository;

class Chat extends Repository
{
	private $userRepository;

	protected $model = ChatModel::class;

	/**
	 * @var BusinessesRepository
	 */
	private $businessesRepository;

	public function __construct(UserRepository $userRepository, BusinessesRepository $businessesRepository)
	{
		$this->userRepository = $userRepository;
		$this->businessesRepository = $businessesRepository;
	}

	public function create($name, $email)
	{
		$user = $this->userRepository->findByEmailOrCreate($email, ['first_name' => $name], true); // allow empty password

		$business = $this->businessesRepository->createBusiness(['name' => 'Alerj']);

		$client = $this->businessesRepository->createClientForBusiness($business, 'Alô Alerj');

		$talker = ChatBusinessClientTalker::firstOrCreate([
			'business_client_id' => $client->id,
			'user_id' => $user->id,
		]);

		$service = ChatService::firstOrCreate(['name' => 'Chat']);

		$clientService = ChatBusinessClientService::firstOrCreate([
			'chat_service_id' => $service->id,
			'business_client_id' => $client->id,
            'description' => 'Chat do Call Center',
		]);

		return ChatModel::firstOrCreate([
			'chat_business_client_service_id' => $clientService->id,
			'owner_id' => $talker->id
		]);
	}

	public function all()
	{
		$chats = ChatModel::all();

		$result = [];

		foreach($chats as $chat)
		{
			$result[$chat->id] = [
				'id' => $chat->id,
				'talker' => [
					'fullName' => $chat->owner->user->present()->fullName,
					'avatar' => $chat->owner->user->present()->avatar
				],
				'responder' => $chat->responder_id ? ['fullName' => $chat->responder->user->present()->fullName] : null,
				'responder_id' => $chat->responder_id,
				'email' => $chat->owner->user->email,
				'isClosed' => is_null($chat->closed_at),
				'service' => strtolower($chat->service->type->name),
			    'messages' => $this->makeMessages($chat->messages()->with('talker.user')->get()),
			];
		}

		return $result;
	}

	public function createMessage($chatId, $userId, $message)
	{
		$chat = ChatModel::find($chatId);

		$talker = $this->findTalker($chat, $userId);

		return ChatMessage::create([
            'chat_id' => $chatId,
			'chat_business_client_talker_id' => $talker->id,
			'message' => $message,
		]);
	}

	private function findTalker($chat, $userId)
	{
		return ChatBusinessClientTalker::where('user_id', $userId)
					->where('business_client_id', $chat->service->business_client_id)
					->first();
	}

	private function makeMessages($get)
	{
		$messages = [];

		foreach($get as $message)
		{
			$messages[] = [
				'message' => $message->message,
			    'talker' => [
				    'id' => $message->talker->id,
				    'fullName' => $message->talker->user->present()->fullName,
			        'avatar' => $message->talker->user->present()->avatar,
			    ]
			];
		}

		return $messages;
	}

	public function allServices()
	{
		return ChatService::all();
	}

	public function createScript($attributes)
	{
		$script = ChatScript::firstOrCreate(
			[
				'name' => $attributes['name'],
				'business_client_id' => $attributes['business_client_id'],
				'chat_service_id' => $attributes['chat_service_id'],
				'script' => $attributes['script'],
			    'chat_script_type_id' => $attributes['chat_script_type_id'],
			]
		);

		return $script;
	}

	public function allScripts()
	{
		$result = [];

		foreach(ChatScript::all() as $script)
		{
			$result[] = [
				'id' => $script->id,
				'name' => $script->name,
				'script' => $script->script,
				'businessClient' => $script->client->name,
				'service' => $script->service->name,
			];
		}

		return $result;
	}

	public function allScriptTypes()
	{
		return ChatScriptType::all();
	}

	public function respond($chatId)
	{
		$chat = ChatModel::find($chatId);

		if ( ! $chat)
		{
			return $this->makeResponse(false, 'Chat não localizado');
		}

		if ($chat->responder)
		{
			return $this->makeResponse(false, 'Chat já sendo respondido');
		}

		return $this->makeResponse(
			true,
			'Chat iniciado',
			$this->setChatResponder($chat, Auth::user())
		);
	}

	private function setChatResponder($chat, $user)
	{
		$talker = $this->findOrCreateTalker($chat->service->client, $user);

		$chat->responder_id = $talker->id;

		$chat->opened_at = Carbon::now();

		$chat->save();

		return $chat;
	}

	/**
	 * @return array
	 */
	private function makeResponse($success, $message, $chat = null)
	{
		$response = [
			'success' => $success,
			'message' => $message,
		];

		if ($chat)
		{
			$response['chat'] = $chat;
		}

		return $response;
	}

	private function findOrCreateTalker($client, $user)
	{
		return ChatBusinessClientTalker::firstOrCreate([
			'business_client_id' => $client->id,
			'user_id' => $user->id,
		]);
	}

	public function getCurrentTalker()
	{
		$client = BusinessClient::first();

		return $this->findOrCreateTalker($client, Auth::user());
	}
}
