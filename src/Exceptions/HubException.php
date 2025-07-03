<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Exceptions;

use Exception;

class HubException extends Exception implements TransformersException
{
    public static function make(string $message): self
    {
        return new self($message);
    }
}
