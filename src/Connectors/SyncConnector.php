<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Connectors;

final class SyncConnector extends FalConnector
{
    protected int $requestTimeout = 600;

    public function __construct(
        string $apiKey,
        private readonly string $baseUrl = 'https://fal.run',
    ) {
        parent::__construct($apiKey);
    }

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
