<?php

namespace PragmaRX\Sdk\Services\Chat\Commands;

use PragmaRX\Sdk\Services\Bus\Commands\SelfHandlingCommand;
use PragmaRX\Sdk\Services\Chat\Data\Repositories\Chat as ChatRepository;

class CreateChat extends SelfHandlingCommand
{
    public $name;

    public $email;

    public $clientId;

    public $layout;

    public function handle(ChatRepository $repository) {
        $chat = $repository->create(
            $this->name,
            $this->email,
            $this->clientId,
            null,
            $this->layout
        );

        return $chat;
    }
}
