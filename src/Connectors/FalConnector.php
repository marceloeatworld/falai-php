<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Connectors;

use MarceloEatWorld\FalAI\Auth\FalKeyAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Saloon\Traits\Plugins\HasTimeout;

abstract class FalConnector extends Connector
{
    use AlwaysThrowOnErrors;
    use HasTimeout;

    protected int $connectTimeout = 30;
    protected int $requestTimeout = 300;

    public function __construct(
        protected readonly string $apiKey,
    ) {}

    protected function defaultAuth(): FalKeyAuthenticator
    {
        return new FalKeyAuthenticator($this->apiKey);
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
