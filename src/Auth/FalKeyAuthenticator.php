<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Auth;

use Saloon\Contracts\Authenticator;
use Saloon\Http\PendingRequest;

final class FalKeyAuthenticator implements Authenticator
{
    public function __construct(
        private readonly string $apiKey,
    ) {}

    public function set(PendingRequest $pendingRequest): void
    {
        $pendingRequest->headers()->add('Authorization', 'Key ' . $this->apiKey);
    }
}
