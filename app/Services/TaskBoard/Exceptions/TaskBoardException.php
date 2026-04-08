<?php

namespace App\Services\TaskBoard\Exceptions;

use RuntimeException;

class TaskBoardException extends RuntimeException
{
    public static function fromHttp(string $message, ?string $body = null): self
    {
        $msg = $body !== null && $body !== '' ? $message.': '.$body : $message;

        return new self($msg);
    }
}
