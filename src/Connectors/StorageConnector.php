<?php

declare(strict_types=1);

namespace MarceloEatWorld\FalAI\Connectors;

final class StorageConnector extends FalConnector
{
    public function __construct(
        string $apiKey,
        private readonly string $baseUrl = 'https://rest.alpha.fal.ai',
    ) {
        parent::__construct($apiKey);
    }

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
