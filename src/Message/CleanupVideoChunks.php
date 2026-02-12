<?php

namespace App\Message;

use Symfony\Component\Uid\Uuid;

class CleanupVideoChunks
{
    public function __construct(
        private readonly Uuid $uploadSessionId,
    ) {
    }

    public function getUploadSessionId(): Uuid
    {
        return $this->uploadSessionId;
    }
}
