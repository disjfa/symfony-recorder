<?php

namespace App\Message;

use Symfony\Component\Uid\Uuid;

class DeleteTaskWithVideo
{
    public function __construct(
        private readonly Uuid $taskId,
    ) {
    }

    public function getTaskId(): Uuid
    {
        return $this->taskId;
    }
}
