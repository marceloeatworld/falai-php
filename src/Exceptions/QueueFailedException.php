<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Exceptions;

final class QueueFailedException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $requestId,
    ) {
        parent::__construct($message);
    }
}
