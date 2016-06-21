<?php

namespace PragmaRX\Sdk\Services\Chat\Data\Repositories;

use Gate;
use Auth;
use DateInterval;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use PragmaRX\Sdk\Services\Caching\Service\Caching;
use PragmaRX\Sdk\Core\Data\Repositories\Repository;
use PragmaRX\Sdk\Services\Users\Data\Entities\User;
use PragmaRX\Sdk\Services\Chat\Events\ChatWasCreated;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatRead;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatScript;
use PragmaRX\Sdk\Services\Chat\Events\ChatUserCheckedIn;
use PragmaRX\Sdk\Services\Chat\Events\ChatUserCheckedOut;
use PragmaRX\Sdk\Services\Chat\Events\ChatMessageWasSent;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatService;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatMessage;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatCustomer;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatScriptType;
use PragmaRX\Sdk\Services\Telegram\Data\Repositories\Telegram;
use PragmaRX\Sdk\Services\Users\Data\Contracts\UserRepository;
use PragmaRX\Sdk\Services\Chat\Data\Entities\Chat as ChatModel;
use PragmaRX\Sdk\Services\Telegram\Data\Entities\TelegramMessage;
use PragmaRX\Sdk\Services\Businesses\Data\Entities\BusinessClient;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatBusinessClientTalker;
use PragmaRX\Sdk\Services\Businesses\Data\Entities\BusinessClientUser;
use PragmaRX\Sdk\Services\Chat\Data\Entities\ChatBusinessClientService;
use PragmaRX\Sdk\Services\Businesses\Data\Entities\BusinessClientUserRole;
use PragmaRX\Sdk\Services\Businesses\Data\Repositories\Businesses as BusinessesRepository;

class Chat extends Repository
{
	private $userRepository;

	protected $model = ChatModel::class;

	/**
	 * @var BusinessesRepository
	 */
	private $businessesRepository;

	/**
	 * @var Request
	 */
	private $request;

	public function __construct(UserRepository $userRepository, BusinessesRepository $businessesRepository, Request $request, Caching $caching)
	{
		$this->userRepository = $userRepository;
		$this->businessesRepository = $businessesRepository;
		$this->request = $request;

        parent::__construct($caching);
	}

    private function addDateColumnToDataArray($data, $model, $column)
    {
        $data[$column] = $model->present()->{camel($column)};
        $data[$column.'_order'] = (string) $model[$column];

        return $data;
    }

    public function checkIn($clientId, $userId)
    {
        $user = User::find($userId);

        $user->online->online_on_chat = true;

        $user->online->last_seen_on_chat = Carbon::now();

        $user->online->save();

        event(new ChatUserCheckedIn($user));
    }

    public function checkOut($clientId, $userId)
    {
        $user = User::find($userId);

        $user->online->online_on_chat = false;

        $user->online->save();

        event(new ChatUserCheckedOut($user));
    }

    private function clearAndOpenChat($chat)
    {
        if ($chat->closed_at)
        {
            $chat->closed_at = null;
            $chat->responder_id = null;
            $chat->save();
        }
    }

    public function create($names, $email, $clientId, $clientService = null, $ipAddress = null, $layout = 'master')
	{
        if (! is_array($names))
        {
            $names = ['first_name' => $names];
        }

		$user = $this->userRepository->findByEmailOrCreate($email, $names, true); // allow empty password

		$talker = ChatBusinessClientTalker::firstOrCreate([
			'business_client_id' => $clientId,
			'user_id' => $user->id,
		]);

        if (! $ipAddress)
        {
            $ipAddress = $this->request->ip();
        }

        if (! $clientService)
        {
            $service = ChatService::firstOrCreate(['name' => 'Chat']);

            $clientService = ChatBusinessClientService::firstOrCreate([
                'chat_service_id' => $service->id,
                'business_client_id' => $clientId,
                'description' => 'Chat do Call Center',
            ]);
        }

		$chat = ChatModel::firstOrCreate([
			'chat_business_client_service_id' => $clientService->id,
			'owner_id' => $talker->id,
			'owner_ip_address' => $ipAddress,
            'layout' => $layout,
		    'closed_at' => null,
		]);
        
        event(new ChatWasCreated($chat));

        return $chat;
	}

	public function allChats($open = true)
	{
        return $this->allChatsForClient(null, $open);
	}

	public function createMessage($chatId, $talkerId, $message = '')
	{
		$chat = $this->findById($chatId);

		$message = ChatMessage::create([
            'chat_id' => $chatId,
            'chat_business_client_talker_id' => $talkerId,
            'talker_ip_address' => $this->request->ip(),
			'message' => $message,
		]);

		$chat->last_message_at = Carbon::now();

        $chat = $this->findById($chatId);

        $chat->save();

        $this->fireChatMessageWasSentEvent($chatId, $talkerId, $message, $chat);

        return $message;
	}

    private function createTelegramChat($telegramMessage)
    {
        $chatBusinessClientService = $this->findChatBusinessClientServiceByTelegramRobot($telegramMessage->chat->bot);

        if (! $chatBusinessClientService)
        {
            return false;
        }

        $chat = $this->create(
            [
                'first_name' => $telegramMessage->from->first_name,
                'last_name' => $telegramMessage->from->last_name,
            ],
            $telegramMessage->from->email,
            $chatBusinessClientService->business_client_id,
            $chatBusinessClientService
        );

        $chat->owner->user->telegram_user_id = $telegramMessage->from->id;
        $chat->owner->user->save();

        $chat->telegram_chat_id = $telegramMessage->chat->id;
        $chat->save();

        return $chat;
    }

    private function findChatBusinessClientServiceByTelegramRobot($bot)
    {
        $service = ChatBusinessClientService::where('bot_name', $bot->name)
                    ->where('bot_token', $bot->token)
                    ->first();

        if (! $service)
        {
            \Log::error(sprintf('TELEGRAM BOT NOT FOUND. Name: %s - Token: %s', $bot->name, $bot->token));
        }

        return $service;
    }

    private function findOrCreateChatByTelegramChatId($telegramMessage)
    {
        $chat = ChatModel::where('telegram_chat_id', $telegramMessage->chat->id)
                    ->first();

        if (! $chat)
        {
            $chat = $this->createTelegramChat($telegramMessage);
        }

        return $chat;
    }

    public function findTalker($chat, $userId)
	{
		return $this->findOrCreateTalker($chat->service->client, $userId);
	}

    private function findUserFromTelegramUser($telegramMessage)
    {
        return $this->userRepository->findByEmailOrCreate(
            $telegramMessage->from->email,
            [
                'first_name' => $telegramMessage->from->first_name,
                'last_name' => $telegramMessage->from->last_name,
            ],
            true
        ); // allow empty password
    }

    /**
     * @param $chatId
     * @param $talkerId
     * @param $message
     * @param $chat
     * @return array
     */
    private function fireChatMessageWasSentEvent($chatId, $talkerId, $message, $chat)
    {
        $data = [];

        if (!is_null($message) && !empty($message))
        {
            $data = [
                'chat_id'   => $chatId,
                'message'   => $message->message,
                'fullName'  => $chat->owner->user->present()->fullName,
                'avatar'    => $chat->owner->user->present()->avatar,
                'owner_id'  => $chat->owner->id,
                'talker_id' => $talkerId,
                'message_model' => $message,
            ];

            event(new ChatMessageWasSent($data));
        }

        return $data;
    }

    /**
     * @param $clientId
     * @return mixed
     */
    private function getChatAndTalkersForClient($user, $clientId, $open) {
        $chats = $this->getChatsAndTalkers();

        if ($open)
        {
            $chats->whereNull('closed_at');
        }

        if (Gate::denies('viewUsers', $user))
        {
            $chats->where(function ($query) use ($user) {
                $query->whereNull('chats.responder_id')
                      ->orWhere('chat_business_client_talkers.user_id', $user->id);
            });
        }

        if ($clientId) {
            $chats->where('chat_business_client_services.business_client_id', $clientId);
        }

        $chats->orderBy('created_at', 'desc');

        return $chats;
    }

    /**
     * @param $clientId
     * @param $period
     * @return mixed
     */
    private function getChatAndTalkersForClientInPeriod($user, $clientId, $open, $period)
    {
        $chats = $this->getChatAndTalkersForClient($user, $clientId, $open);

        if ($period)
        {
            $chats->whereBetween('chats.created_at', $period);
        }

        return $chats;
    }

    /**
     * @return mixed
     */
    private function getChatsAndTalkers() {
        $chats = ChatModel::select('chats.*', 'chat_business_client_talkers.user_id')
                          ->join('chat_business_client_services', 'chats.chat_business_client_service_id', '=', 'chat_business_client_services.id')
                          ->leftJoin('chat_business_client_talkers', function ($join) {
                              $join->on('chat_business_client_services.business_client_id', '=', 'chat_business_client_talkers.business_client_id');
                              $join->on('chats.responder_id', '=', 'chat_business_client_talkers.id');
                          })
        ;

        return $chats;
    }

    public function getDashboard($businessClientId)
    {
        $tags = ['chats', 'chat_messages', 'users', 'chats', 'chat_messages', 'users', 'business_client_users', 'business_client_user_roles', 'chat_business_client_talkers'];

        list($result, $key) = $this->caching->cached($tags, ['routine' => 'getDashboard', '$businessClientId' => $businessClientId]);

        if ($result)
        {
            return $result;
        }

        list($chatInCourseCount, $chatInCourseOpenCount) = $this->getChatCount($businessClientId);

        list($operatorCount, $operatorAvailable, $operatorOnlikePeak) = $this->getOperatorCount($businessClientId);

        list($totalChatCount, $totalChatCountLastWeek) = $this->getTotalChatCount($businessClientId);

        list($totalMessageCount, $totalMessageCountToday) = $this->getTotalMessageCount($businessClientId);

        $result = [
            'inCourse' => [
                'count' => $chatInCourseOpenCount . ' / ' . $chatInCourseCount,
                'description' => '.',
            ],

            'operators' => [
                'count' => $operatorAvailable . ' de ' . $operatorCount,
                'description' => 'Pico de operadores online: ' . $operatorOnlikePeak,
            ],

            'chats' => [
                'count' => $totalChatCount,
                'description' => 'Na última semana: ' . $totalChatCountLastWeek,
            ],

            'messages' => [
                'count' => $totalMessageCount,
                'description' => 'Hoje: ' . $totalMessageCountToday,
            ],
        ];

        $this->caching->cache($tags, $key, $result);

        return $result;
    }

    private function isTelegramCommand($telegramMessage)
    {
        $repository = app(Telegram::class);

        return $repository->isCommand($telegramMessage->text);
    }

    private function makeMessages($all, $chat = null)
	{
		$messages = [];

		foreach($all as $message)
		{
			$messages[$message->id] = [
				'id' => $message->id,
				'message' => $message->present()->message,
			    'talker' => [
				    'id' => $message->talker->id,
				    'fullName' => $message->talker->user->present()->fullName,
			        'avatar' => $this->makeAvatar($message->talker, $chat),
			    ],
				'serial' => str_pad($message->serial, 10, "0", STR_PAD_LEFT),
			    'created_at' => (string) $message->present()->createdAt(),
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

        Caching::tags(ChatScript::class)->flush();

		return $script;
	}

	public function allScripts()
	{
        $key = Caching::makeKey(['allScripts', 'client', 'service']);

        if ($result = Caching::get($key))
        {
            return $result;
        }

		$result = [];

        foreach(ChatScript::with(['client', 'service'])->get() as $script)
		{
			$result[] = [
				'id' => $script->id,
				'name' => $script->name,
				'script' => $script->script,
				'businessClient' => $script->client->name,
				'service' => $script->service->name,
			];
		}

        Caching::tags([ChatScript::class])->forever($key, $result);

		return $result;
	}

	public function allScriptTypes()
	{
		return ChatScriptType::all();
	}

    /**
     * @param $chats
     * @return Collection
     */
    private function makeChatResult($chats, $id_column = 'id')
    {
        $result = [];

        foreach ($chats->get() as $chat)
        {
            $result[ $chat[$id_column] ] = $this->makeChatData($chat);
        }

        return app()->make(Collection::class, [$result]);
    }

    public function pingUser()
    {
        $user = Auth::user();

        $user->last_seen_at = Carbon::now();
    }

    public function receiveMessage($message)
    {
        if ($message instanceof TelegramMessage)
        {
            $this->receiveTelegramMessage($message);
        }
    }

    private function receiveTelegramMessage($telegramMessage)
    {
        $chat = $this->findOrCreateChatByTelegramChatId($telegramMessage);

        if (! $chat)
        {
            return false;
        }

        $this->clearAndOpenChat($chat);

        if (! $this->isTelegramCommand($telegramMessage))
        {
            $message = $this->createMessage($chat->id, $chat->owner->id);

            $message->telegram_message_id = $telegramMessage->id;

            $message->save();

            return $telegramMessage;
        }
    }

    public function respond($chatId)
	{
		$chat = $this->findById($chatId);

		if ( ! $chat)
		{
			return $this->makeResponse(false, 'Chat não localizado', $chat);
		}

		if ($chat->responder)
		{
			return $this->makeResponse(false, 'Chat já sendo respondido', $chat);
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

	private function findOrCreateTalker($client, $userId)
	{
		if ($userId instanceof User)
		{
			$userId = $userId->id;
		}

		return ChatBusinessClientTalker::firstOrCreate([
			'business_client_id' => $client->id,
			'user_id' => $userId,
		]);
	}

	public function getCurrentTalker()
	{
		$client = BusinessClient::first();

		return $this->findOrCreateTalker($client, Auth::user());
	}

	public function readMessage($chatId, $serial)
	{
		$talker = $this->findTalker($chat = $this->findById($chatId), Auth::user()->id);

		$read = $this->findChatLastReadMessage($talker, $chat);

		if ( ! $read)
		{
			$read = new ChatRead();
			$read->chat_business_client_talker_id = $talker->id;
			$read->chat_id = $chat->id;
		}

		if ($read->last_read_message_serial < $serial)
		{
			$read->last_read_message_serial = $serial;

			$read->save();
		}

		return $read;
	}

	private function getChatLastReadSerial($chat, $userId)
	{
		$read = $this->findChatLastReadMessage(
			$this->findTalker($chat, $userId),
			$chat
		);

		return $read ? $read->last_read_message_serial : 0;
	}

	private function findChatLastReadMessage($talker, $chat)
	{
		return ChatRead::where('chat_business_client_talker_id', $talker->id)
						->where('chat_id', $chat->id)
						->first();
	}

	private function getLastMessageSerial($messages)
	{
		$serial = 0;

		foreach ($messages as $message)
		{
		    $_serial = intval((string) $message['serial']);

			$serial = max($_serial, $serial);
		}

		return $serial;
	}

	public function terminate($chatId)
	{
		$chat = $this->findById($chatId);

		$chat->closed_at = Carbon::now();

		$chat->save();

		return $chat;
	}

	public function getChat($chatId)
	{
		$chat = $this->findById($chatId);

		return $this->makeChatData($chat);
	}

	private function makeChatData($chat)
	{
		$user = Auth::user() ?: $chat->owner->user;

		$messages = $this->makeMessages(
			$chat->messages()
				->with('talker.user')
				->orderBy('serial', 'desc')
				->get(),
			$chat
		);

		$data = [];

		$lastMessageSerial = $this->getLastMessageSerial($messages);

		$data['id'] = $chat->id;

		$data['talker'] = [
			'fullName' => $chat->owner->user->present()->fullName,
			'avatar' => $chat->owner->user->present()->avatar,
            'user_id' => $chat->owner->user->id,
            'id' => $chat->owner->id,
		];

		$data['responder'] = $chat->responder_id
								?   [
										'fullName' => $chat->responder->user->present()->fullName,
								        'user_id' => $chat->responder->user->id,
                                        'avatar' => $chat->responder->user->present()->avatar,
                                        'id' => $chat->responder->id,
									]
								: null;
		$data['responder_id'] = $chat->responder_id;
		$data['email'] = $chat->owner->user->email;
		$data['isClosed'] = is_null($chat->closed_at);
		$data['service'] = strtolower($chat->service->type->name);
		$data['messages'] = $this->makeMessages($chat->messages()->with('talker.user')->get(), $chat);

        $data = $this->addDateColumnToDataArray($data, $chat, 'opened_at');
        $data = $this->addDateColumnToDataArray($data, $chat, 'last_message_at');
        $data = $this->addDateColumnToDataArray($data, $chat, 'closed_at');
		$data = $this->addDateColumnToDataArray($data, $chat, 'created_at');
		$data = $this->addDateColumnToDataArray($data, $chat, 'updated_at');

		$data['last_read_message_serial'] = $this->getChatLastReadSerial($chat, $user->id);
		$data['last_message_serial'] = $lastMessageSerial;

		return $data;
	}

	private function makeAvatar($talker, $chat)
	{
		$role = $this->findRoleByTalker($talker);

		if ($role && $talker->client->avatar)
		{
			$avatar = $talker->client->avatar->file->getUrl();
		}
		else
		{
			$avatar = $talker->user->present()->avatar;
		}

		return $avatar;
	}

	/**
	 * @param $id
	 * @return \PragmaRX\Sdk\Services\Chat\Data\Entities\ChatScript|null
	 */
	public function findScriptById($id)
	{
		return ChatScript::remember(60)->find($id);
	}

	public function updateScript($attributes)
	{
		$script = $this->findScriptById($attributes['id']);

		$attributes = array_only($attributes, $this->getModelFillableAttributes($script));

		$script->fill($attributes);

		$script->save();
	}

	public function deleteScript($scriptId)
	{
		$script = $this->findScriptById($scriptId);

		$script->delete();

		return $script;
	}

	/**
	 * @param $talker
	 * @return mixed
	 */
	private function findRoleByTalker($talker)
	{
		if ($clientUser = $this->findClientUserByTalker($talker))
		{
			return BusinessClientUserRole::where('business_client_user_id', $clientUser->id)
                    ->first();
		}

		return null;
	}

	private function findClientUserByTalker($talker)
	{
		return BusinessClientUser::where('user_id', $talker->user->id)
                ->first();
	}

    public function allMessagesForClient($clientId, $open = true, $period = null)
    {
        $tags = ['chats', 'chat_messages'];

        list($result, $key) = $this->caching->cached($tags, ['routine' => 'allMessagesForClient', 'clientId' => $clientId, 'period' => $period, 'open' => $open]);

        if ($result)
        {
            return $result;
        }

        $result = $this->makeChatResult(
            $this->getChatAndTalkersForClientInPeriod(Auth::user(), $clientId, $open, $period)
                 ->addSelect('chat_messages.id as chat_message_id')
                 ->join('chat_messages', 'chats.id', '=', 'chat_messages.chat_id')
            , 'chat_message_id'
        );

        $this->caching->cache($tags, $key, $result);

        return $result;
    }

	public function allChatsForClient($clientId = null, $open = true, $period = null)
	{
        $tags = ['chats', 'chat_messages'];

        list($result, $key) = $this->caching->cached($tags, ['routine' => 'allChatsForClient', 'clientId' => $clientId, 'period' => $period, 'open' => $open]);

        if ($result)
        {
            return $result;
        }

        $result = $this->makeChatResult(
            $this->getChatAndTalkersForClientInPeriod(Auth::user(), $clientId, $open, $period)
        );

        $this->caching->cache($tags, $key, $result);

        return $result;
	}

    /**
     * @param $clientId
     */
    public function getOperatorsForClient($clientId)
    {
        return BusinessClientUser::
                select([
                    'business_client_id',
                    'business_client_users.user_id',
                    'email',
                    'first_name',
                    'last_name',
                    'avatar_id',
                    'online_users.last_seen_at',
                    'online_users.last_seen_at',
                ])
                ->join('users', 'business_client_users.user_id', '=', 'users.id')
                ->leftJoin('online_users', 'online_users.user_id', '=', 'users.id')
                ->where('business_client_id', $clientId)
        ;
    }

    public function operatorsForClient($clientId = null)
    {
        $tags = ['chats', 'chat_messages', 'users', 'business_client_users', 'business_client_user_roles', 'chat_business_client_talkers'];

        list($result, $key) = $this->caching->cached($tags, ['routine' => 'operatorsForClient', 'clientId' => $clientId]);

        if ($result)
        {
            return $result;
        }

        $result = $this->getOperatorsForClient($clientId)
                       ->get();

        $this->caching->cache($tags, $key, $result);

        return $result;
    }

    public function operatorsOnlineForClient($clientId = null)
    {
        return $this->getOperatorsForClient($clientId)
                ->where('online_users.online_on_chat', true)
                ->get()
        ;
    }

    public function operatorsOnlineOnChatForClient($clientId = null)
    {
        $now = Carbon::now()->subMinute(1);

        return $this->getOperatorsForClient($clientId)
                    ->where('online_users.online_on_chat', true)
                    ->get()
            ;
    }

    /**
     * @param $businessClientId
     * @return array
     */
    private function getChatCount($businessClientId)
    {
        $chats = $this->allChatsForClient($businessClientId);

        $chatInCourseCount = $chats->count();

        $chatInCourseOpenCount = $chats->where('responder_id', null)->count();

        return [$chatInCourseCount, $chatInCourseOpenCount];
    }

    private function getOperatorCount($businessClientId)
    {
        $operatorCount = $this->operatorsForClient($businessClientId)->count();
        $operatorAvailable = $this->operatorsOnlineOnChatForClient($businessClientId)->count();
        $operatorOnlikePeak = 2;

        return [$operatorCount, $operatorAvailable, $operatorOnlikePeak];
    }

    private function getTotalChatCount($businessClientId)
    {
        $allChats = $this
            ->allChatsForClient($businessClientId, false)
            ->count();

        $now = Carbon::now();
        $lastWeek = Carbon::now()->subDays(2);

        $lastWeekCount = $this
            ->allChatsForClient($businessClientId, false, [$lastWeek, $now])
            ->count();

        return [$allChats, $lastWeekCount];
    }

    private function getTotalMessageCount($businessClientId)
    {
        $interval = DateInterval::createFromDateString('-7 days');

        $to = Carbon::now();
        $from = Carbon::now()->add($interval);

        $interval = [$from, $to];

        $totalMessageCount = $this
            ->allMessagesForClient($businessClientId)
            ->count();

        $totalMessageCountToday = $this
            ->allMessagesForClient($businessClientId, true, $interval)
            ->count();

        return [$totalMessageCount, $totalMessageCountToday];
    }
}
